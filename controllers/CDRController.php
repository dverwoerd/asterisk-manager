<?php
require_once APP_ROOT . '/controllers/BaseController.php';

class CDRController extends BaseController
{
    public function index(): void
    {
        $filters = [
            'date_from'   => $this->get('date_from', date('Y-m-01')),
            'date_to'     => $this->get('date_to', date('Y-m-d')),
            'src'         => $this->get('src', ''),
            'dst'         => $this->get('dst', ''),
            'disposition' => $this->get('disposition', ''),
        ];

        $where  = ["calldate BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)"];
        $params = [$filters['date_from'], $filters['date_to']];

        if ($filters['src'])         { $where[] = "src LIKE ?";       $params[] = "%{$filters['src']}%"; }
        if ($filters['dst'])         { $where[] = "dst LIKE ?";       $params[] = "%{$filters['dst']}%"; }
        if ($filters['disposition']) { $where[] = "disposition = ?";  $params[] = $filters['disposition']; }

        $whereStr = implode(' AND ', $where);

        $page    = max(1, (int)$this->get('p', 1));
        $perPage = 50;
        $total   = Database::count('cdr_records', $whereStr, $params);
        $pages   = max(1, (int)ceil($total / $perPage));
        $offset  = ($page - 1) * $perPage;

        $records = Database::fetchAll(
            "SELECT * FROM cdr_records WHERE $whereStr ORDER BY calldate DESC LIMIT $perPage OFFSET $offset",
            $params
        );

        $summary = Database::fetchOne(
            "SELECT COUNT(*) as total_calls,
                    COALESCE(SUM(billsec), 0) as total_seconds,
                    SUM(disposition='ANSWERED') as answered
             FROM cdr_records WHERE $whereStr",
            $params
        );

        $this->view('cdr.index', [
            'title'   => t('cdr'),
            'records' => $records,
            'filters' => $filters,
            'summary' => $summary,
            'page'    => $page,
            'pages'   => $pages,
            'total'   => $total,
        ]);
    }

    public function sync(): void
    {
        $this->requireOperator();
        try {
            $count = $this->syncFromAsterisk();
            if ($count === 0) {
                $this->flash('info', 'Geen nieuwe CDR records gevonden. Zijn er gesprekken geweest?');
            } else {
                $this->flash('success', "$count nieuwe CDR records geïmporteerd en verwerkt.");
            }
        } catch (Exception $e) {
            $this->flash('danger', 'CDR sync mislukt: ' . $e->getMessage());
            logError('CDR sync: ' . $e->getMessage());
        }
        redirect('?page=cdr');
    }

    // ---- CSV-upload (handmatig importeren van CDR-records) ----

    public function post_upload(): void
    {
        $this->requireOperator();
        $f = $_FILES['cdr_csv'] ?? null;
        if (!$f || $f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
            $this->flash('error', 'Geen (geldig) bestand ontvangen.');
            redirect('?page=cdr');
        }
        try {
            [$nieuw, $dubbel, $fout] = self::importCsv($f['tmp_name']);
            $this->flash('success', "CSV geimporteerd: $nieuw nieuw, $dubbel al aanwezig/overgeslagen" . ($fout ? ", $fout ongeldige regels" : '') . '.');
        } catch (Exception $e) {
            $this->flash('error', 'Import mislukt: ' . $e->getMessage());
        }
        redirect('?page=cdr');
    }

    /**
     * Importeert een CDR-CSV. Ondersteunt:
     *  - bestand met kopregel (kolomnamen als calldate/datum, src/van, dst/naar/bestemming,
     *    billsec/duur, duration, disposition/status, clid, uniqueid), scheidingsteken , ; of tab
     *  - Asterisk Master.csv zonder kopregel (vaste kolomvolgorde)
     * Dubbele records (zelfde uniqueid, of zelfde datum+src+dst+billsec) worden overgeslagen.
     * @return array{0:int,1:int,2:int} nieuw, dubbel/overgeslagen, ongeldig
     */
    public static function importCsv(string $path): array
    {
        $h = fopen($path, 'r');
        if (!$h) throw new Exception('Kan bestand niet openen.');
        $first = fgets($h);
        if ($first === false) throw new Exception('Leeg bestand.');
        $first = preg_replace('/^\xEF\xBB\xBF/', '', $first);
        $delim = ',';
        foreach ([';', "\t", ','] as $d) {
            if (substr_count($first, $d) > substr_count($first, $delim)) $delim = $d;
        }
        rewind($h);

        $alias = [
            'calldate' => ['calldate','date','datum','start','starttime','datum/tijd','date/time','datetime'],
            'src'      => ['src','source','from','van','bron','caller','toestel'],
            'dst'      => ['dst','destination','to','naar','bestemming','called','nummer'],
            'billsec'  => ['billsec','billable','billable sec','gespreksduur','seconds','sec','duur'],
            'duration' => ['duration','totale duur','total'],
            'disposition' => ['disposition','status','result','resultaat'],
            'clid'     => ['clid','callerid'],
            'uniqueid' => ['uniqueid','id','callid'],
            'channel'  => ['channel'], 'dstchannel' => ['dstchannel'], 'dcontext' => ['dcontext'],
            'lastapp'  => ['lastapp'], 'lastdata' => ['lastdata'], 'accountcode' => ['accountcode'], 'userfield' => ['userfield'],
        ];

        $map = null; $nieuw = 0; $dubbel = 0; $fout = 0; $regel = 0;
        while (($row = fgetcsv($h, 8192, $delim)) !== false) {
            $regel++;
            if ($row === [null] || count($row) < 3) { if ($regel > 1) $fout++; continue; }
            $row = array_map(fn($v) => trim((string)$v, " \t\"\xEF\xBB\xBF"), $row);

            if ($regel === 1) {
                $low = array_map('strtolower', $row);
                $hasHeader = false;
                foreach ($low as $c) foreach ($alias['dst'] as $a) if ($c === $a) $hasHeader = true;
                if ($hasHeader) {
                    $map = [];
                    foreach ($alias as $field => $names) {
                        foreach ($low as $i => $c) if (in_array($c, $names, true) && !in_array($i, $map, true)) { $map[$field] = $i; break; }
                    }
                    if (!isset($map['calldate'], $map['dst'])) throw new Exception('Kolommen calldate/datum en dst/bestemming ontbreken in de kopregel.');
                    continue;
                }
                // Master.csv: accountcode,src,dst,dcontext,clid,channel,dstchannel,lastapp,lastdata,start,answer,end,duration,billsec,disposition,amaflags,uniqueid
                // of de eenvoudige volgorde calldate,clid,src,dst,dcontext,channel,dstchannel,lastapp,lastdata,duration,billsec,disposition
                $map = (count($row) >= 17)
                    ? ['accountcode'=>0,'src'=>1,'dst'=>2,'dcontext'=>3,'clid'=>4,'channel'=>5,'dstchannel'=>6,'lastapp'=>7,'lastdata'=>8,'calldate'=>9,'duration'=>12,'billsec'=>13,'disposition'=>14,'uniqueid'=>16]
                    : ['calldate'=>0,'clid'=>1,'src'=>2,'dst'=>3,'dcontext'=>4,'channel'=>5,'dstchannel'=>6,'lastapp'=>7,'lastdata'=>8,'duration'=>9,'billsec'=>10,'disposition'=>11,'accountcode'=>13,'uniqueid'=>14];
            }

            $g = fn($k) => isset($map[$k], $row[$map[$k]]) ? $row[$map[$k]] : '';
            $datum = self::parseDatum($g('calldate'));
            $dst   = $g('dst');
            if ($datum === null || $dst === '') { $fout++; continue; }

            $billsec  = self::parseSeconden($g('billsec'));
            $duration = isset($map['duration']) ? self::parseSeconden($g('duration')) : $billsec;
            $src      = $g('src');
            $disp     = strtoupper($g('disposition'));
            if (!in_array($disp, ['ANSWERED','NO ANSWER','FAILED','BUSY','CONGESTION'], true)) {
                $disp = $billsec > 0 ? 'ANSWERED' : 'NO ANSWER';
            }
            $uid = $g('uniqueid');
            if ($uid === '') $uid = 'csv-' . md5($datum . '|' . $src . '|' . $dst . '|' . $billsec);

            $stmt = Database::query(
                "INSERT IGNORE INTO cdr_records
                 (calldate, clid, src, dst, dcontext, channel, dstchannel, lastapp, lastdata,
                  duration, billsec, disposition, amaflags, accountcode, uniqueid, userfield)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,0,?,?,?)",
                [$datum, $g('clid'), $src, $dst, $g('dcontext'), $g('channel'), $g('dstchannel'),
                 $g('lastapp'), $g('lastdata'), $duration, $billsec, $disp, $g('accountcode'), $uid, $g('userfield')]);
            if ($stmt->rowCount() > 0) $nieuw++; else $dubbel++;
        }
        fclose($h);
        return [$nieuw, $dubbel, $fout];
    }

    private static function parseDatum(string $s): ?string
    {
        if ($s === '') return null;
        foreach (['Y-m-d H:i:s','Y-m-d H:i','d-m-Y H:i:s','d-m-Y H:i','d/m/Y H:i:s','d/m/Y H:i','Y/m/d H:i:s'] as $fmt) {
            $d = DateTime::createFromFormat($fmt, $s);
            if ($d && $d->format($fmt) === $s) return $d->format('Y-m-d H:i:s');
        }
        $t = strtotime($s);
        return $t ? date('Y-m-d H:i:s', $t) : null;
    }

    private static function parseSeconden(string $s): int
    {
        if ($s === '') return 0;
        if (preg_match('/^(\d+):(\d{2}):(\d{2})$/', $s, $m)) return $m[1]*3600 + $m[2]*60 + $m[3];
        if (preg_match('/^(\d+):(\d{2})$/', $s, $m)) return $m[1]*60 + $m[2];
        return (int)round((float)str_replace(',', '.', $s));
    }

    public function export(): void
    {
        $dateFrom = $this->get('date_from', date('Y-m-01'));
        $dateTo   = $this->get('date_to', date('Y-m-d'));

        $records = Database::fetchAll(
            "SELECT calldate, src, dst, duration, billsec, disposition
             FROM cdr_records
             WHERE calldate BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
             ORDER BY calldate DESC",
            [$dateFrom, $dateTo]
        );

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=cdr_' . $dateFrom . '_' . $dateTo . '.csv');

        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF"); // BOM voor Excel
        fputcsv($out, ['Date/Time', 'Source', 'Destination', 'Duration', 'Billable Sec', 'Disposition'], ';');
        foreach ($records as $r) {
            fputcsv($out, [
                $r['calldate'],
                $r['src'],
                $r['dst'],
                formatDuration($r['duration']),
                $r['billsec'],
                $r['disposition'],
            ], ';');
        }
        fclose($out);
        exit;
    }

    // ---- Sync vanuit Asterisk CDR database ----

    private function syncFromAsterisk(): int
    {
        // Methode 1: Probeer MySQL (cdr_mysql module)
        try {
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=asteriskcdrdb;charset=utf8mb4',
                DB_HOST, DB_PORT);
            $asteriskDb = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            $check = $asteriskDb->query("SHOW TABLES LIKE 'cdr'")->fetch();
            if ($check) {
                return $this->syncFromMySQL($asteriskDb);
            }
        } catch (Exception $e) {
            logError('CDR MySQL niet beschikbaar, probeer CSV: ' . $e->getMessage());
        }

        // Methode 2: CSV bestand (cdr_csv module — standaard in Asterisk)
        $csvFile = '/var/log/asterisk/cdr-csv/Master.csv';
        if (file_exists($csvFile)) {
            return $this->syncFromCSV($csvFile);
        }

        throw new Exception(
            'Geen CDR bron gevonden. ' .
            'Zorg dat /var/log/asterisk/cdr-csv/Master.csv bestaat ' .
            'of configureer cdr_mysql in Asterisk.'
        );
    }

    private function syncFromMySQL(PDO $db): int
    {
        $lastSync = Database::fetchOne("SELECT MAX(calldate) as last FROM cdr_records");
        $since    = $lastSync['last'] ?? '2000-01-01 00:00:00';

        $stmt = $db->prepare("SELECT * FROM cdr WHERE calldate > ? ORDER BY calldate LIMIT 5000");
        $stmt->execute([$since]);
        $rows  = $stmt->fetchAll();
        $count = 0;

        foreach ($rows as $row) {
            try {
                Database::query(
                    "INSERT IGNORE INTO cdr_records
                     (calldate, clid, src, dst, dcontext, channel, dstchannel,
                      lastapp, lastdata, duration, billsec, disposition,
                      amaflags, accountcode, uniqueid, userfield)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                    [
                        $row['calldate'],       $row['clid']        ?? '',
                        $row['src']         ?? '', $row['dst']         ?? '',
                        $row['dcontext']    ?? '', $row['channel']     ?? '',
                        $row['dstchannel']  ?? '', $row['lastapp']     ?? '',
                        $row['lastdata']    ?? '', (int)($row['duration']  ?? 0),
                        (int)($row['billsec'] ?? 0), $row['disposition'] ?? 'ANSWERED',
                        (int)($row['amaflags'] ?? 0), $row['accountcode'] ?? '',
                        $row['uniqueid']    ?? uniqid(), $row['userfield'] ?? '',
                    ]
                );
                $count++;
            } catch (Exception $e) {
                logError('CDR MySQL row: ' . $e->getMessage());
            }
        }

        return $count;
    }

    private function syncFromCSV(string $csvFile): int
    {
        $lastSync = Database::fetchOne("SELECT MAX(calldate) as last FROM cdr_records");
        $since    = $lastSync['last'] ?? '2000-01-01 00:00:00';

        $handle = fopen($csvFile, 'r');
        if (!$handle) {
            throw new Exception("Kan CSV bestand niet openen: $csvFile");
        }

        $count = 0;
        while (($row = fgetcsv($handle, 4096, ',')) !== false) {
            if (count($row) < 11) continue;

            $calldate    = trim($row[0],  '"');
            $clid        = trim($row[1],  '"');
            $src         = trim($row[2],  '"');
            $dst         = trim($row[3],  '"');
            $dcontext    = trim($row[4],  '"');
            $channel     = trim($row[5],  '"');
            $dstchannel  = trim($row[6],  '"');
            $lastapp     = trim($row[7],  '"');
            $lastdata    = trim($row[8],  '"');
            $duration    = (int)trim($row[9],  '"');
            $billsec     = (int)trim($row[10], '"');
            $disposition = trim($row[11] ?? 'ANSWERED', '"');
            $amaflags    = (int)trim($row[12] ?? '0', '"');
            $accountcode = trim($row[13] ?? '', '"');
            $uniqueid    = trim($row[14] ?? uniqid(), '"');
            $userfield   = trim($row[15] ?? '', '"');

            // Sla oude en ongeldige records over
            if (empty($calldate) || $calldate === 'calldate' || $calldate <= $since) continue;

            try {
                Database::query(
                    "INSERT IGNORE INTO cdr_records
                     (calldate, clid, src, dst, dcontext, channel, dstchannel,
                      lastapp, lastdata, duration, billsec, disposition,
                      amaflags, accountcode, uniqueid, userfield)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                    [
                        $calldate, $clid, $src, $dst, $dcontext,
                        $channel, $dstchannel, $lastapp, $lastdata,
                        $duration, $billsec, $disposition, $amaflags,
                        $accountcode, $uniqueid, $userfield,
                    ]
                );
                $count++;
            } catch (Exception $e) {
                logError('CDR CSV row: ' . $e->getMessage());
            }
        }

        fclose($handle);
        return $count;
    }
}
