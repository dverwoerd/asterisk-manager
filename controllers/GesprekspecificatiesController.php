<?php
// ============================================================
// GesprekspecificatiesController
// Per-klant per-maand gespreksspecificaties vanuit cdr_records.
//
// Berekening identiek aan cdr.pantronic.nl:
//   - alleen beantwoorde uitgaande gesprekken (billsec > 0)
//   - minuten per gesprek naar boven afgerond (ceil(billsec / 60))
//   - kosten per gesprek = starttarief + minuten x minuuttarief
//       Nationaal / 088 : billing_tarief_nationaal (0,025)
//       Mobiel          : billing_tarief_mobiel    (0,065)
//       Internationaal  : bel_tarieven, prefix 4 -> 3 -> 2 cijfers
//       0900 Premium    : bel_tarieven (prefix 3190)
//       0800 Gratis     : 0 (ook geen starttarief)
//   - starttarief: billing_starttarief (0,065)
//   - kosten per gesprek afgerond op 4 decimalen, totalen daarna op centen
//   - nummer zonder tarief in bel_tarieven: kosten 0 + melding
// ============================================================
require_once APP_ROOT . '/controllers/BaseController.php';

class GesprekspecificatiesController extends BaseController
{
    const CATEGORIEEN = [
        'Nationaal',
        'Mobiel',
        '088 Nummer',
        'Internationaal',
        '0800 Gratis',
        '0900 Premium',
    ];

    private static ?array $tarieven = null;

    // ---- index: maandkiezer + klantenlijst met samenvatting ----

    public function index(): void
    {
        $maand = $this->get('maand', date('Y-m'));
        [$dateFrom, $dateTo, $jaar, $mnr] = $this->periode($maand);

        $customers = Database::fetchAll(
            "SELECT id, company_name, contact_name, extensions_csv
             FROM customers WHERE active=1 ORDER BY company_name, contact_name"
        );

        $overzicht = [];
        foreach ($customers as $c) {
            $calls = $this->fetchCalls($c, $dateFrom, $dateTo);
            if (empty($calls)) continue;

            $overzicht[] = [
                'id'               => $c['id'],
                'naam'             => $c['company_name'] ?: $c['contact_name'],
                'aantal'           => count($calls),
                'totaal_sec'       => array_sum(array_column($calls, 'billsec')),
                'totaal_min'       => array_sum(array_column($calls, 'minuten')),
                'totaal_kosten'    => self::somKosten($calls),
                'zonder_tarief'    => count(array_filter($calls, fn($x) => !$x['tarief_bekend'])),
                'maand'            => $maand,
                'dateFrom'         => $dateFrom,
                'dateTo'           => $dateTo,
            ];
        }

        $maanden = Database::fetchAll(
            "SELECT DISTINCT DATE_FORMAT(calldate, '%Y-%m') AS maand
             FROM cdr_records
             WHERE disposition='ANSWERED' AND billsec>0
             ORDER BY maand DESC
             LIMIT 24"
        );

        $this->view('gesprekspecificaties.index', [
            'title'     => 'Gespreksspecificaties',
            'overzicht' => $overzicht,
            'maand'     => $maand,
            'maanden'   => $maanden,
            'dateFrom'  => $dateFrom,
            'dateTo'    => $dateTo,
        ]);
    }

    // ---- detail: per klant + maand ----

    public function detail(): void
    {
        $customerId = $this->id();
        $maand      = $this->get('maand', date('Y-m'));
        [$dateFrom, $dateTo, $jaar, $mnr] = $this->periode($maand);

        $customer = Database::fetchOne("SELECT * FROM customers WHERE id=?", [$customerId]);
        if (!$customer) redirect('?page=gesprekspecificaties');

        $calls = $this->fetchCalls($customer, $dateFrom, $dateTo);
        $spec  = self::bouwSpecificatie($calls);

        $this->view('gesprekspecificaties.detail', [
            'title'      => 'Gespreksspecificaties — ' . ($customer['company_name'] ?: $customer['contact_name']),
            'customer'   => $customer,
            'maand'      => $maand,
            'maandLabel' => $this->maandNaam($mnr) . ' ' . $jaar,
            'spec'       => $spec,
            'dubbelen'   => $this->dubbelen,
            'tarieven'   => $this->tariefInstellingen(),
        ]);
    }

    // ---- pdf: download in cdr.pantronic.nl-opmaak ----

    public function pdf(): void
    {
        $customerId = $this->id();
        $maand      = $this->get('maand', date('Y-m'));
        [$dateFrom, $dateTo, $jaar, $mnr] = $this->periode($maand);

        $customer = Database::fetchOne("SELECT * FROM customers WHERE id=?", [$customerId]);
        if (!$customer) redirect('?page=gesprekspecificaties');

        $calls = $this->fetchCalls($customer, $dateFrom, $dateTo);
        $spec  = self::bouwSpecificatie($calls);

        $company = [
            'name'    => getSetting('company_name',    'Pantronic IT'),
            'address' => getSetting('company_address', 'Bobinestraat 7-17, 3903 KE Veenendaal'),
            'phone'   => getSetting('company_phone',   '0318-478226'),
            'email'   => getSetting('company_email',   'welkom@pantronic.nl'),
        ];

        require_once APP_ROOT . '/includes/GesprekPDF.php';
        GesprekPDF::download($customer, $company, $this->maandNaam($mnr) . ' ' . $jaar, $spec);
    }

    // ================================================================
    // Berekening
    // ================================================================

    /**
     * Bouwt de complete specificatie:
     *  - categorieen : alle 6 categorieën (ook lege), met aantal / minuten / kosten
     *  - nummers     : per bestemmingsnummer samengevoegd, gesorteerd op categorie en kosten
     *  - totaal      : aantal / minuten / sec / kosten
     *  - zonder_tarief : nummers waarvoor geen tarief gevonden is
     */
    public static function bouwSpecificatie(array $calls): array
    {
        $cats = [];
        foreach (self::CATEGORIEEN as $cat) {
            $cats[$cat] = ['aantal' => 0, 'minuten' => 0, 'sec' => 0, 'e4' => 0, 'nummers' => []];
        }

        $nummers = [];
        $zonder  = [];

        foreach ($calls as $c) {
            $cat = $c['categorie'];
            $cats[$cat]['aantal']  += 1;
            $cats[$cat]['minuten'] += $c['minuten'];
            $cats[$cat]['sec']     += (int)$c['billsec'];
            $cats[$cat]['e4']      += $c['kosten_e4'];

            // Per categorie: gesprekken gegroepeerd per nummer (voor detail-tabellen)
            $cn = &$cats[$cat]['nummers'][$c['nummer']];
            if ($cn === null) {
                $cn = ['nummer' => self::toonNummer($c['nummer']), 'land' => $c['land'],
                       'tarief_bekend' => $c['tarief_bekend'],
                       'aantal' => 0, 'minuten' => 0, 'sec' => 0, 'e4' => 0, 'calls' => []];
            }
            $cn['aantal']  += 1;
            $cn['minuten'] += $c['minuten'];
            $cn['sec']     += (int)$c['billsec'];
            $cn['e4']      += $c['kosten_e4'];
            $cn['calls'][]  = $c;
            unset($cn);

            $key = $c['nummer'];
            if (!isset($nummers[$key])) {
                $nummers[$key] = [
                    'nummer'        => self::toonNummer($key),
                    'categorie'     => $cat,
                    'land'          => $c['land'],
                    'tarief'        => $c['tarief'],
                    'tarief_bekend' => $c['tarief_bekend'],
                    'toestellen'    => [],
                    'aantal'        => 0,
                    'minuten'       => 0,
                    'sec'           => 0,
                    'e4'            => 0,
                ];
            }
            $n = &$nummers[$key];
            $n['aantal']  += 1;
            $n['minuten'] += $c['minuten'];
            $n['sec']     += (int)$c['billsec'];
            $n['e4']      += $c['kosten_e4'];
            $n['toestellen'][$c['src']] = true;
            unset($n);

            if (!$c['tarief_bekend']) {
                $zonder[$key] = ($zonder[$key] ?? 0) + 1;
            }
        }

        foreach ($cats as &$g) {
            $g['kosten'] = $g['e4'] / 10000;
            foreach ($g['nummers'] as &$cn) {
                $cn['kosten'] = $cn['e4'] / 10000;
            }
            unset($cn);
            // hoogste kosten eerst, dan meeste gesprekken, dan nummer
            uasort($g['nummers'], fn($a, $b) =>
                [$b['e4'], $b['aantal'], $a['nummer']] <=> [$a['e4'], $a['aantal'], $b['nummer']]);
            $g['nummers'] = array_values($g['nummers']);
        }
        unset($g);

        $volgorde = array_flip(self::CATEGORIEEN);
        foreach ($nummers as &$n) {
            $n['kosten']     = $n['e4'] / 10000;
            $n['toestellen'] = array_keys($n['toestellen']);
            sort($n['toestellen'], SORT_NATURAL);
        }
        unset($n);
        uasort($nummers, fn($a, $b) =>
            [$volgorde[$a['categorie']], $b['e4'], $b['aantal'], $a['nummer']]
            <=> [$volgorde[$b['categorie']], $a['e4'], $a['aantal'], $b['nummer']]
        );

        $totE4 = array_sum(array_column($cats, 'e4'));

        return [
            'categorieen'   => $cats,
            'nummers'       => array_values($nummers),
            'zonder_tarief' => $zonder,
            'totaal'        => [
                'aantal'  => array_sum(array_column($cats, 'aantal')),
                'minuten' => array_sum(array_column($cats, 'minuten')),
                'sec'     => array_sum(array_column($cats, 'sec')),
                'kosten'  => $totE4 / 10000,
            ],
        ];
    }

    /** Kosten van één gesprek volgens cdr.pantronic.nl. */
    public static function berekenGesprek(string $dst, int $billsec, array $instellingen, array $tarieven): array
    {
        $norm    = self::normaliseerNummer($dst);
        $cat     = self::categoriseer($dst);
        $minuten = (int)ceil($billsec / 60);
        $start   = $instellingen['start'];
        $land    = '';
        $bekend  = true;

        switch ($cat) {
            case '0800 Gratis':
                $tarief = 0.0;
                $start  = 0.0;
                break;
            case 'Nationaal':
            case '088 Nummer':
                $tarief = $instellingen['nationaal'];
                break;
            case 'Mobiel':
                $tarief = $instellingen['mobiel'];
                break;
            default: // Internationaal, 0900 Premium
                [$tarief, $land] = self::zoekTarief($norm, $tarieven);
                if ($tarief === null) {
                    $bekend = false;
                    $tarief = 0.0;
                    $start  = 0.0;
                }
        }

        $kosten = round($start + $minuten * $tarief, 4);

        return [
            'nummer'        => $norm,
            'categorie'     => $cat,
            'minuten'       => $minuten,
            'tarief'        => $tarief,
            'land'          => $land,
            'tarief_bekend' => $bekend,
            'kosten'        => $kosten,
            'kosten_e4'     => (int)round($kosten * 10000),
        ];
    }

    /** Prefix-lookup 4 -> 3 -> 2 cijfers (zoals cdr.pantronic.nl). */
    public static function zoekTarief(string $norm, array $tarieven): array
    {
        foreach ([4, 3, 2] as $len) {
            $pfx = substr($norm, 0, $len);
            if (isset($tarieven[$pfx])) {
                return [(float)$tarieven[$pfx]['tarief'], trim($tarieven[$pfx]['land'] . ' ' . $tarieven[$pfx]['regio'])];
            }
        }
        return [null, ''];
    }

    private static function laadTarieven(): array
    {
        if (self::$tarieven !== null) return self::$tarieven;
        self::$tarieven = [];
        try {
            foreach (Database::fetchAll("SELECT prefix, tarief, land, regio FROM bel_tarieven") as $r) {
                self::$tarieven[$r['prefix']] = $r;
            }
        } catch (Exception $e) {
            // tabel bestaat nog niet: migrate_bel_tarieven.sql uitvoeren
        }
        return self::$tarieven;
    }

    private function tariefInstellingen(): array
    {
        $f = fn($k, $d) => (float)str_replace(',', '.', (string)getSetting($k, $d));
        return [
            'start'     => $f('billing_starttarief',      '0.065'),
            'nationaal' => $f('billing_tarief_nationaal', '0.025'),
            'mobiel'    => $f('billing_tarief_mobiel',    '0.065'),
        ];
    }

    private static function somKosten(array $calls): float
    {
        return array_sum(array_column($calls, 'kosten_e4')) / 10000;
    }

    // ================================================================
    // Data
    // ================================================================

    private function extensiesVanKlant(array $customer): array
    {
        $exts = array_filter(
            array_map('trim', explode(',', $customer['extensions_csv'] ?? ''))
        );

        // Voeg ook DID/callerid nummers toe (zoals InvoicesController doet)
        $extra = [];
        foreach ($exts as $ext) {
            $row = Database::fetchOne(
                "SELECT callerid_number FROM extensions WHERE extension=? AND enabled=1",
                [$ext]
            );
            if (!empty($row['callerid_number'])) {
                $cid = preg_replace('/\D/', '', $row['callerid_number']);
                $extra[] = $cid;
                if (str_starts_with($cid, '31')) {
                    $extra[] = '0' . substr($cid, 2);
                }
            }
        }

        return array_unique(array_values(array_filter(array_merge($exts, $extra))));
    }

    /** Aantal gesprekken dat als dubbel is weggelaten bij de laatste fetchCalls (voor de melding op het scherm). */
    private int $dubbelen = 0;

    /**
     * Zelfde toestel, zelfde (genormaliseerde) bestemming, starttijd binnen 5 s en duur binnen 5 s:
     * dan is het hetzelfde gesprek uit twee bronnen. De langste duur blijft staan.
     */
    private function ontdubbel(array $rows): array
    {
        usort($rows, fn($a, $b) => strcmp($a['calldate'], $b['calldate']));
        $kept = [];
        $laatste = [];                     // sleutel => index in $kept
        $this->dubbelen = 0;
        foreach ($rows as $r) {
            $key = $r['src'] . '|' . self::normaliseerNummer($r['dst']);
            $ts  = strtotime($r['calldate']);
            if (isset($laatste[$key])) {
                $i = $laatste[$key];
                if (abs($ts - strtotime($kept[$i]['calldate'])) <= 5
                    && abs((int)$r['billsec'] - (int)$kept[$i]['billsec']) <= 5) {
                    if ((int)$r['billsec'] > (int)$kept[$i]['billsec']) $kept[$i] = $r;
                    $this->dubbelen++;
                    continue;
                }
            }
            $kept[] = $r;
            $laatste[$key] = count($kept) - 1;
        }
        return $kept;
    }

    private function fetchCalls(array $customer, string $dateFrom, string $dateTo): array
    {
        $exts = $this->extensiesVanKlant($customer);
        if (empty($exts)) return [];

        $ph   = implode(',', array_fill(0, count($exts), '?'));
        $rows = Database::fetchAll(
            "SELECT calldate, src, dst, billsec
             FROM cdr_records
             WHERE src IN ($ph)
               AND DATE(calldate) BETWEEN ? AND ?
               AND disposition = 'ANSWERED'
               AND billsec > 0
               AND LENGTH(REGEXP_REPLACE(dst, '[^0-9]', '')) > 3
             ORDER BY calldate",
            [...$exts, $dateFrom, $dateTo]
        );

        // Doorgeschakelde gesprekken: Asterisk boekt het doorgeschakelde been (Local/...) op de oorspronkelijke
        // beller als src. Zo'n been hoort bij de klant van het toestel dat het gesprek doorschakelde: het
        // toestel kreeg kort daarvoor (max 60 s) een inkomend gesprek van dezelfde beller zonder op te nemen.
        $fwd = Database::fetchAll(
            "SELECT f.calldate, o.dst AS src, f.dst, f.billsec
             FROM cdr_records f
             JOIN cdr_records o ON o.src = f.src
                AND o.dst IN ($ph)
                AND o.calldate BETWEEN f.calldate - INTERVAL 60 SECOND AND f.calldate
                AND o.disposition <> 'ANSWERED'
             WHERE f.channel LIKE 'Local/%'
               AND f.src NOT IN ($ph)
               AND DATE(f.calldate) BETWEEN ? AND ?
               AND f.disposition = 'ANSWERED'
               AND f.billsec > 0
               AND LENGTH(REGEXP_REPLACE(f.dst, '[^0-9]', '')) > 3
             GROUP BY f.uniqueid
             ORDER BY f.calldate",
            [...$exts, ...$exts, $dateFrom, $dateTo]
        );
        foreach ($fwd as &$f) { $f['doorgeschakeld'] = true; }
        unset($f);
        $rows = array_merge($rows, $fwd);

        // Doorgeschakeld gesprek waarvan de duur op het inkomende been staat: de beller (of ons eigen DID als
        // CLI) -> toestel, met 'Local/'-been naar het externe nummer dat zelf 0 s heeft. De duur van het inkomende
        // been is dan het echte gesprek; de bestemming haal ik uit het Local-been.
        $fwd2 = Database::fetchAll(
            "SELECT i.calldate, i.dst AS src, f.dst, i.billsec
             FROM cdr_records i
             JOIN cdr_records f ON f.src = i.src
                AND f.channel LIKE 'Local/%'
                AND f.disposition = 'ANSWERED'
                AND f.billsec <= 1
                AND f.calldate BETWEEN i.calldate - INTERVAL 60 SECOND AND i.calldate + INTERVAL 60 SECOND
                AND LENGTH(REGEXP_REPLACE(f.dst, '[^0-9]', '')) > 3
             WHERE i.channel LIKE 'PJSIP/%'
               AND i.dst IN ($ph)
               AND LENGTH(REGEXP_REPLACE(i.dst, '[^0-9]', '')) <= 3
               AND LENGTH(REGEXP_REPLACE(i.src, '[^0-9]', '')) >= 9
               AND DATE(i.calldate) BETWEEN ? AND ?
               AND i.disposition = 'ANSWERED'
               AND i.billsec > 0
             GROUP BY i.uniqueid
             ORDER BY i.calldate",
            [...$exts, $dateFrom, $dateTo]
        );
        foreach ($fwd2 as &$f) { $f['doorgeschakeld'] = true; }
        unset($f);
        $rows = array_merge($rows, $fwd2);

        // Asterisk-CDR en geimporteerde CSV-records staan in dezelfde tabel. Overlappen ze (zelfde toestel,
        // nummer en starttijd), dan telt dat gesprek maar een keer.
        $rows = $this->ontdubbel($rows);

        $inst     = $this->tariefInstellingen();
        $tarieven = self::laadTarieven();
        foreach ($rows as &$r) {
            $r += self::berekenGesprek($r['dst'], (int)$r['billsec'], $inst, $tarieven);
        }
        unset($r);

        return $rows;
    }

    // ================================================================
    // Nummers
    // ================================================================

    /** Leesbare gespreksduur ("9 min 27 s"), zodat die niet op een kloktijd lijkt. Gedeeld door scherm en PDF. */
    public static function leesbareDuur(int $sec): string
    {
        $h = intdiv($sec, 3600); $m = intdiv($sec % 3600, 60); $x = $sec % 60;
        if ($h > 0) return sprintf('%d u %02d min', $h, $m);
        if ($m > 0) return sprintf('%d min %02d s', $m, $x);
        return $x . ' s';
    }

    /** Internationaal formaat zonder + of 00 (0612.., +31612.., 0031612.. -> 31612..). */
    public static function normaliseerNummer(string $dst): string
    {
        $n = preg_replace('/\D/', '', $dst);
        if (str_starts_with($n, '00')) return substr($n, 2);
        if (str_starts_with($n, '0') && strlen($n) > 1) return '31' . substr($n, 1);
        return $n;
    }

    /** Weergave: NL nationaal (0612345678), buitenland met + (+442071234567). */
    public static function toonNummer(string $norm): string
    {
        if (str_starts_with($norm, '31') && strlen($norm) > 4) return '0' . substr($norm, 2);
        return strlen($norm) > 6 ? '+' . $norm : $norm;
    }

    public static function categoriseer(string $dst): string
    {
        $norm = self::normaliseerNummer($dst);

        if (str_starts_with($norm, '31800')) return '0800 Gratis';
        if (str_starts_with($norm, '31900')) return '0900 Premium';
        if (str_starts_with($norm, '3188'))  return '088 Nummer';
        if (str_starts_with($norm, '316'))   return 'Mobiel';
        if (str_starts_with($norm, '31') && strlen($norm) >= 5) return 'Nationaal';

        return 'Internationaal';
    }

    // ================================================================
    // Overig
    // ================================================================

    private function periode(string $maand): array
    {
        if (!preg_match('/^(\d{4})-(\d{2})$/', $maand, $m)) {
            $m = [null, date('Y'), date('m')];
        }
        $dateFrom = "{$m[1]}-{$m[2]}-01";
        return [$dateFrom, date('Y-m-t', strtotime($dateFrom)), $m[1], $m[2]];
    }

    private function maandNaam(string $mnr): string
    {
        $namen = [
            '01'=>'Januari','02'=>'Februari','03'=>'Maart','04'=>'April',
            '05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Augustus',
            '09'=>'September','10'=>'Oktober','11'=>'November','12'=>'December',
        ];
        return $namen[$mnr] ?? $mnr;
    }
}
