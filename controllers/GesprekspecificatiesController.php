<?php
// ============================================================
// GesprekspecificatiesController
// Genereert per-klant per-maand overzichten vanuit cdr_records
// ============================================================
require_once APP_ROOT . '/controllers/BaseController.php';

class GesprekspecificatiesController extends BaseController
{
    // Categorieën in vaste volgorde voor consistente weergave
    const CATEGORIEEN = [
        'Nationaal',
        'Mobiel',
        '088 Nummer',
        'Internationaal',
        '0800 Gratis',
        '0900 Premium',
    ];

    // ---- index: maandkiezer + klantenlijst met samenvatting ----

    public function index(): void
    {
        $maand = $this->get('maand', date('Y-m'));          // bijv. 2026-11
        [$jaar, $mnr] = explode('-', $maand . '-01');

        $dateFrom = "$jaar-$mnr-01";
        $dateTo   = date('Y-m-t', strtotime($dateFrom));    // laatste dag van de maand

        $customers = Database::fetchAll(
            "SELECT id, company_name, contact_name, extensions_csv
             FROM customers WHERE active=1 ORDER BY company_name, contact_name"
        );

        // Per klant: samenvatting van CDR voor de gekozen maand
        $overzicht = [];
        foreach ($customers as $c) {
            $exts = $this->extensiesVanKlant($c);
            if (empty($exts)) continue;

            $ph  = implode(',', array_fill(0, count($exts), '?'));
            $sum = Database::fetchOne(
                "SELECT COUNT(*) AS aantal,
                        COALESCE(SUM(billsec),0) AS totaal_sec,
                        COALESCE(SUM(cost),0)    AS totaal_kosten
                 FROM cdr_records
                 WHERE src IN ($ph)
                   AND DATE(calldate) BETWEEN ? AND ?
                   AND disposition = 'ANSWERED'
                   AND billsec > 0",
                [...$exts, $dateFrom, $dateTo]
            );

            if (!$sum || (int)$sum['aantal'] === 0) continue;

            $overzicht[] = [
                'id'            => $c['id'],
                'naam'          => $c['company_name'] ?: $c['contact_name'],
                'aantal'        => (int)$sum['aantal'],
                'totaal_sec'    => (int)$sum['totaal_sec'],
                'totaal_kosten' => (float)$sum['totaal_kosten'],
                'maand'         => $maand,
                'dateFrom'      => $dateFrom,
                'dateTo'        => $dateTo,
            ];
        }

        // Beschikbare maanden (vanuit CDR)
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

    // ---- detail: uitgebreid overzicht per klant + maand ----

    public function detail(): void
    {
        $customerId = $this->id();
        $maand      = $this->get('maand', date('Y-m'));
        [$jaar, $mnr] = explode('-', $maand . '-01');

        $dateFrom = "$jaar-$mnr-01";
        $dateTo   = date('Y-m-t', strtotime($dateFrom));

        $customer = Database::fetchOne("SELECT * FROM customers WHERE id=?", [$customerId]);
        if (!$customer) redirect('?page=gesprekspecificaties');

        $calls = $this->fetchCalls($customer, $dateFrom, $dateTo);
        $perCategorie = $this->groepeerOpCategorie($calls);

        $totaalAantal  = count($calls);
        $totaalSec     = array_sum(array_column($calls, 'billsec'));
        $totaalKosten  = self::totaalKosten($perCategorie);
        $weergave      = $this->weergave();

        $maandLabel = $this->maandNaam($mnr) . ' ' . $jaar;

        $this->view('gesprekspecificaties.detail', [
            'title'         => 'Gespreksspecificaties — ' . ($customer['company_name'] ?: $customer['contact_name']),
            'customer'      => $customer,
            'maand'         => $maand,
            'maandLabel'    => $maandLabel,
            'dateFrom'      => $dateFrom,
            'dateTo'        => $dateTo,
            'perCategorie'  => $perCategorie,
            'totaalAantal'  => $totaalAantal,
            'totaalSec'     => $totaalSec,
            'totaalKosten'  => $totaalKosten,
            'weergave'      => $weergave,
        ]);
    }

    // ---- pdf: download Pantronic Gespreksspecificatie PDF ----

    public function pdf(): void
    {
        $customerId = $this->id();
        $maand      = $this->get('maand', date('Y-m'));
        [$jaar, $mnr] = explode('-', $maand . '-01');

        $dateFrom = "$jaar-$mnr-01";
        $dateTo   = date('Y-m-t', strtotime($dateFrom));

        $customer = Database::fetchOne("SELECT * FROM customers WHERE id=?", [$customerId]);
        if (!$customer) redirect('?page=gesprekspecificaties');

        $calls        = $this->fetchCalls($customer, $dateFrom, $dateTo);
        $perCategorie = $this->groepeerOpCategorie($calls);
        $maandLabel   = $this->maandNaam($mnr) . ' ' . $jaar;

        $company = [
            'name'    => getSetting('company_name',    'Pantronic IT'),
            'address' => getSetting('company_address', 'Bobinestraat 7-17, 3903 KE Veenendaal'),
            'phone'   => getSetting('company_phone',   '0318-478226'),
            'email'   => getSetting('company_email',   'welkom@pantronic.nl'),
            'vat'     => getSetting('company_vat',     ''),
        ];

        require_once APP_ROOT . '/includes/GesprekPDF.php';
        GesprekPDF::download($customer, $company, $maandLabel, $perCategorie, $this->weergave());
    }

    /** 'nummer' (standaard, samengevoegd per bestemming) of 'gesprek' (elk gesprek los). */
    private function weergave(): string
    {
        return $this->get('weergave', 'nummer') === 'gesprek' ? 'gesprek' : 'nummer';
    }

    // ================================================================
    // Hulpfuncties
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

    private function fetchCalls(array $customer, string $dateFrom, string $dateTo): array
    {
        $exts = $this->extensiesVanKlant($customer);
        if (empty($exts)) return [];

        $ph   = implode(',', array_fill(0, count($exts), '?'));
        $rows = Database::fetchAll(
            "SELECT calldate, src, dst, billsec,
                    COALESCE(cost, 0) AS cost,
                    destination_name
             FROM cdr_records
             WHERE src IN ($ph)
               AND DATE(calldate) BETWEEN ? AND ?
               AND disposition = 'ANSWERED'
               AND billsec > 0
             ORDER BY calldate",
            [...$exts, $dateFrom, $dateTo]
        );

        // Voeg categorie toe aan elk gesprek
        foreach ($rows as &$r) {
            $r['categorie'] = self::categoriseer($r['dst']);
        }

        return $rows;
    }

    /**
     * Groepeert gesprekken per categorie en daarbinnen per bestemmingsnummer.
     *
     * Berekening:
     *  - Kosten worden per gesprek berekend door applyRatesToCDR() (tariefstap +
     *    starttarief per gesprek) en hier alleen opgeteld. Nooit opnieuw berekenen
     *    vanuit totale duur x tarief: dat geeft een ander (te laag) bedrag.
     *  - Optellen gebeurt in hele 1/10.000 euro (cost is DECIMAL(10,4)), zodat er
     *    geen float-afwijkingen ontstaan. Afronding naar centen pas bij weergave.
     *  - Nummers worden genormaliseerd (0612.., 0031612.., +31612.. = één regel).
     */
    private function groepeerOpCategorie(array $calls): array
    {
        $leeg = fn() => ['calls' => [], 'nummers' => [], 'aantal' => 0, 'sec' => 0, 'kosten_e4' => 0];

        $groepen = [];
        foreach (self::CATEGORIEEN as $cat) {
            $groepen[$cat] = $leeg();
        }

        foreach ($calls as $c) {
            $cat = $c['categorie'];
            if (!isset($groepen[$cat])) {
                $groepen[$cat] = $leeg();
            }
            $sec = (int)$c['billsec'];
            $e4  = (int)round((float)$c['cost'] * 10000);

            $groepen[$cat]['calls'][]    = $c;
            $groepen[$cat]['aantal']    += 1;
            $groepen[$cat]['sec']       += $sec;
            $groepen[$cat]['kosten_e4'] += $e4;

            // ---- per nummer ----
            $key = self::normaliseerNummer($c['dst']);
            $n   = &$groepen[$cat]['nummers'][$key];
            if ($n === null) {
                $n = [
                    'nummer'           => self::toonNummer($key),
                    'destination_name' => $c['destination_name'] ?? '',
                    'toestellen'       => [],
                    'aantal'           => 0,
                    'sec'              => 0,
                    'kosten_e4'        => 0,
                    'eerste'           => $c['calldate'],
                    'laatste'          => $c['calldate'],
                ];
            }
            $n['aantal']    += 1;
            $n['sec']       += $sec;
            $n['kosten_e4'] += $e4;
            $n['toestellen'][$c['src']] = true;
            if ($c['calldate'] < $n['eerste'])  $n['eerste']  = $c['calldate'];
            if ($c['calldate'] > $n['laatste']) $n['laatste'] = $c['calldate'];
            if ($n['destination_name'] === '' && !empty($c['destination_name'])) {
                $n['destination_name'] = $c['destination_name'];
            }
            unset($n);
        }

        // Verwijder lege categorieën
        $groepen = array_filter($groepen, fn($g) => $g['aantal'] > 0);

        // Afronden + sorteren (hoogste kosten eerst, dan meeste gesprekken, dan nummer)
        foreach ($groepen as &$g) {
            $g['kosten'] = $g['kosten_e4'] / 10000;
            foreach ($g['nummers'] as &$n) {
                $n['kosten']     = $n['kosten_e4'] / 10000;
                $n['gem_sec']    = $n['aantal'] > 0 ? (int)round($n['sec'] / $n['aantal']) : 0;
                $n['toestellen'] = array_keys($n['toestellen']);
                sort($n['toestellen'], SORT_NATURAL);
            }
            unset($n);
            uasort($g['nummers'], fn($a, $b) =>
                [$b['kosten_e4'], $b['aantal'], $a['nummer']] <=> [$a['kosten_e4'], $a['aantal'], $b['nummer']]
            );
            $g['aantal_nummers'] = count($g['nummers']);
        }
        unset($g);

        return $groepen;
    }

    /** Sleutel voor groepering: internationaal formaat zonder + of 00 (bijv. 31612345678). */
    public static function normaliseerNummer(string $dst): string
    {
        $n = preg_replace('/\D/', '', $dst);
        if (str_starts_with($n, '00')) return substr($n, 2);
        if (str_starts_with($n, '0') && strlen($n) > 1) return '31' . substr($n, 1);
        return $n;
    }

    /** Weergave: NL-nummers nationaal (0612345678), buitenland met + (+441234567890). */
    public static function toonNummer(string $norm): string
    {
        if (str_starts_with($norm, '31') && strlen($norm) > 4) return '0' . substr($norm, 2);
        return strlen($norm) > 6 ? '+' . $norm : $norm;
    }

    /** Totale kosten over alle categorieën, exact (zelfde basis als factuur). */
    public static function totaalKosten(array $perCategorie): float
    {
        return array_sum(array_column($perCategorie, 'kosten_e4')) / 10000;
    }

    public static function categoriseer(string $dst): string
    {
        $n = preg_replace('/\D/', '', $dst);

        // Normaliseer naar internationaal formaat
        if (str_starts_with($n, '00')) {
            $norm = substr($n, 2);
        } elseif (str_starts_with($n, '0') && strlen($n) > 1) {
            $norm = '31' . substr($n, 1);
        } else {
            $norm = $n;
        }

        if (str_starts_with($norm, '310800') || str_starts_with($n, '0800')) return '0800 Gratis';
        if (str_starts_with($norm, '310900') || str_starts_with($n, '0900')) return '0900 Premium';
        if (str_starts_with($norm, '31088')  || str_starts_with($n, '088'))  return '088 Nummer';
        if (str_starts_with($norm, '316'))                                    return 'Mobiel';
        if (str_starts_with($norm, '31') && strlen($norm) >= 5)              return 'Nationaal';

        return 'Internationaal';
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
