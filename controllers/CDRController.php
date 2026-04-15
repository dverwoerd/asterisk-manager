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
                    SUM(disposition='ANSWERED') as answered,
                    COALESCE(SUM(cost), 0) as total_cost
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

    public function calculate_costs(): void
    {
        $this->requireOperator();
        $count = self::applyRatesToCDR();
        $this->flash('success', "Kosten berekend voor $count CDR records.");
        redirect('?page=cdr');
    }

    public function export(): void
    {
        $dateFrom = $this->get('date_from', date('Y-m-01'));
        $dateTo   = $this->get('date_to', date('Y-m-d'));

        $records = Database::fetchAll(
            "SELECT calldate, src, dst, duration, billsec, disposition, cost, destination_name
             FROM cdr_records
             WHERE calldate BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
             ORDER BY calldate DESC",
            [$dateFrom, $dateTo]
        );

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=cdr_' . $dateFrom . '_' . $dateTo . '.csv');

        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF"); // BOM voor Excel
        fputcsv($out, ['Date/Time', 'Source', 'Destination', 'Duration', 'Billable Sec', 'Disposition', 'Cost', 'Destination Name'], ';');
        foreach ($records as $r) {
            fputcsv($out, [
                $r['calldate'],
                $r['src'],
                $r['dst'],
                formatDuration($r['duration']),
                $r['billsec'],
                $r['disposition'],
                number_format((float)($r['cost'] ?? 0), 4, ',', '.'),
                $r['destination_name'] ?? '',
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

        if ($count > 0) self::applyRatesToCDR();
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
        if ($count > 0) self::applyRatesToCDR();
        return $count;
    }

    public static function applyRatesToCDR(): int
    {
        // Alleen uitgaande gesprekken berekenen
        // Interne nummers zijn max 3 cijfers (100-999), externe nummers zijn langer
        $records = Database::fetchAll(
            "SELECT * FROM cdr_records
             WHERE cost IS NULL
             AND disposition='ANSWERED'
             AND billsec > 0
             AND LENGTH(REGEXP_REPLACE(dst, '[^0-9]', '')) > 3
             LIMIT 1000"
        );

        $ratePlans = Database::fetchAll("SELECT * FROM rate_plans WHERE active=1");
        $count     = 0;

        foreach ($records as $cdr) {
            $dst      = preg_replace('/\D/', '', $cdr['dst']);
            $bestRate = null;
            $bestLen  = -1;
            $planId   = null;
            $destName = null;

            foreach ($ratePlans as $plan) {
                $rates = Database::fetchAll(
                    "SELECT * FROM rate_plan_rates WHERE plan_id=? ORDER BY LENGTH(prefix) DESC",
                    [$plan['id']]
                );
                foreach ($rates as $rate) {
                    $prefix = $rate['prefix'];
                    if ($prefix === '' || str_starts_with($dst, $prefix)) {
                        $len = strlen($prefix);
                        if ($len > $bestLen) {
                            $bestLen  = $len;
                            $bestRate = $rate;
                            $planId   = $plan['id'];
                            $destName = $rate['destination_name'];
                        }
                    }
                }
            }

            if ($bestRate) {
                $increment = max(1, (int)($bestRate['billing_increment'] ?? 60));
                $billsec   = max((int)$cdr['billsec'], $increment);
                $minutes   = ceil($billsec / $increment) * ($increment / 60);
                $cost      = ($minutes * (float)$bestRate['rate_per_minute'])
                           + (float)($bestRate['connection_fee'] ?? 0);

                Database::update('cdr_records', [
                    'rate_plan_id'     => $planId,
                    'rate_applied'     => $bestRate['rate_per_minute'],
                    'cost'             => round($cost, 4),
                    'destination_name' => $destName,
                ], 'id=?', [$cdr['id']]);
                $count++;
            }
        }

        return $count;
    }
}
