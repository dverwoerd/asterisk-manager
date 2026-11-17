<?php
// ============================================================
// GesprekPDF — Pantronic Gespreksspecificatie PDF via mPDF
// ============================================================

class GesprekPDF
{
    // Pantronic kleuren
    const BLAUW   = '#1B3A6B';
    const ORANJE  = '#F37021';
    const LICHTBLAUW = '#EEF2FA';
    const GRIJS   = '#F4F6FA';

    // Categorielabels en kleurcodes in PDF
    const CAT_KLEUREN = [
        'Nationaal'      => '#1B3A6B',
        'Mobiel'         => '#2A5298',
        '088 Nummer'     => '#3D7ABF',
        'Internationaal' => '#C9571A',
        '0800 Gratis'    => '#27AE60',
        '0900 Premium'   => '#E74C3C',
    ];

    public static function download(
        array  $customer,
        array  $company,
        string $maandLabel,
        array  $perCategorie,
        string $weergave = 'nummer'
    ): void {
        $autoload = APP_ROOT . '/vendor/autoload.php';
        if (!file_exists($autoload)) {
            throw new Exception('mPDF niet geïnstalleerd. Voer uit: composer require mpdf/mpdf');
        }
        require_once $autoload;

        $mpdf = new \Mpdf\Mpdf([
            'mode'           => 'utf-8',
            'format'         => 'A4',
            'margin_top'     => 0,
            'margin_bottom'  => 18,
            'margin_footer'  => 6,
            'margin_left'    => 0,
            'margin_right'   => 0,
            'default_font'   => 'dejavusans',
            'default_font_size' => 9,
        ]);

        $klantnaam = $customer['company_name'] ?: $customer['contact_name'];
        $mpdf->SetTitle("Gespreksspecificatie $klantnaam — $maandLabel");
        $mpdf->SetAuthor($company['name']);
        $mpdf->SetCreator('Pantronic Asterisk Manager');

        // Footer op elke pagina: bedrijfsgegevens + paginanummer
        $mpdf->SetHTMLFooter(
            '<table width="100%" style="border-top:1px solid ' . self::BLAUW . ';font-size:7pt;color:#64748b;padding-top:4px">'
            . '<tr><td style="padding:4px 24px 0">'
            . '<strong style="color:' . self::BLAUW . '">' . htmlspecialchars($company['name']) . '</strong>'
            . ' &bull; ' . htmlspecialchars($company['address'])
            . ' &bull; Tel. ' . htmlspecialchars($company['phone'])
            . ' &bull; ' . htmlspecialchars($company['email'])
            . '</td><td style="padding:4px 24px 0;text-align:right;white-space:nowrap">'
            . 'Pagina {PAGENO} van {nbpg}</td></tr></table>'
        );

        $html = self::buildHTML($customer, $company, $maandLabel, $perCategorie, $weergave);
        $mpdf->WriteHTML($html);

        $bestandsnaam = 'gespreksspecificatie-'
            . preg_replace('/[^a-z0-9]/i', '-', $klantnaam) . '-'
            . str_replace(' ', '-', strtolower($maandLabel)) . '.pdf';

        $mpdf->Output($bestandsnaam, 'D');
        exit;
    }

    private static function buildHTML(
        array  $customer,
        array  $company,
        string $maandLabel,
        array  $perCategorie,
        string $weergave
    ): string {
        $klantnaam = htmlspecialchars($customer['company_name'] ?: $customer['contact_name']);
        $blauw     = self::BLAUW;
        $oranje    = self::ORANJE;

        // Totalen berekenen
        $totaalAantal = 0;
        $totaalSec    = 0;
        $totaalE4     = 0;
        $totaalNrs    = 0;
        foreach ($perCategorie as $cat => $data) {
            $totaalAantal += $data['aantal'];
            $totaalSec    += $data['sec'];
            $totaalE4     += $data['kosten_e4'];
            $totaalNrs    += $data['aantal_nummers'];
        }
        $totaalKosten = $totaalE4 / 10000;

        // ---- CSS ----
        $css = "
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: DejaVuSans, sans-serif; font-size: 9pt; color: #1a1a1a; line-height: 1.4; }
        h2 { font-size: 11pt; font-weight: bold; color: $blauw; }

        /* Header blok */
        .header {
            background: $blauw;
            color: #fff;
            padding: 18px 24px 14px;
            margin-bottom: 0;
        }
        .header-title {
            font-size: 18pt;
            font-weight: bold;
            letter-spacing: -0.02em;
            margin-bottom: 2px;
        }
        .header-sub {
            font-size: 9.5pt;
            opacity: 0.75;
        }

        /* Oranje balk */
        .orange-bar {
            background: $oranje;
            height: 4px;
            margin-bottom: 0;
        }

        /* Info sectie */
        .info-section {
            width: 100%;
            border-bottom: 1px solid #E2E8F0;
        }
        .info-section td { padding: 14px 24px; }
        .info-left, .info-right {
            display: table-cell;
            vertical-align: top;
            width: 50%;
        }
        .info-label {
            font-size: 7.5pt;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #94a3b8;
            margin-bottom: 2px;
        }
        .info-value {
            font-size: 11pt;
            font-weight: bold;
            color: $blauw;
        }
        .info-sub {
            font-size: 8.5pt;
            color: #64748b;
            margin-top: 1px;
        }

        /* Samenvatting tabel */
        .section { padding: 12px 24px; }
        .section-title {
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: $oranje;
            border-bottom: 2px solid $oranje;
            padding-bottom: 4px;
            margin-bottom: 8px;
        }

        table { border-collapse: collapse; width: 100%; }
        .tbl-samenvatting th {
            background: $blauw;
            color: #fff;
            padding: 6px 10px;
            font-size: 8pt;
            text-align: left;
            font-weight: bold;
        }
        .tbl-samenvatting th.r { text-align: right; }
        .tbl-samenvatting td {
            padding: 5px 10px;
            border-bottom: 1px solid #E2E8F0;
            font-size: 8.5pt;
        }
        .tbl-samenvatting td.r { text-align: right; font-family: monospace; }
        .tbl-samenvatting tr.alt td { background: " . self::LICHTBLAUW . "; }
        .tbl-samenvatting .cat-badge {
            display: inline-block;
            padding: 1px 7px;
            border-radius: 3px;
            font-size: 7.5pt;
            font-weight: bold;
            color: #fff;
        }
        .tbl-samenvatting .totaalrij td {
            background: $oranje;
            color: #fff;
            font-weight: bold;
            padding: 6px 10px;
        }
        .tbl-samenvatting .totaalrij td.r { text-align: right; font-family: monospace; }

        /* Detail tabel */
        .tbl-detail { margin-top: 4px; }
        .tbl-detail th {
            background: #2A5298;
            color: #fff;
            padding: 4px 8px;
            font-size: 7.5pt;
            text-align: left;
        }
        .tbl-detail th.r { text-align: right; }
        .tbl-detail td {
            padding: 3px 8px;
            border-bottom: 1px solid #E2E8F0;
            font-size: 8pt;
        }
        .tbl-detail td.mono { font-family: monospace; }
        .tbl-detail td { vertical-align: top; }
        .muted { color:#94a3b8; font-size:7pt; }
        .voetnoot { font-size:7pt; color:#64748b; line-height:1.5; padding-top:4px; }
        .tbl-detail td.r { text-align: right; font-family: monospace; }
        .tbl-detail tr.alt td { background: " . self::GRIJS . "; }
        .tbl-detail .subtotaalrij td {
            background: $blauw;
            color: #fff;
            font-weight: bold;
            padding: 4px 8px;
            font-size: 8pt;
        }
        .tbl-detail .subtotaalrij td.r { text-align: right; font-family: monospace; }

        /* Footer */
        .pdf-footer {
            padding: 10px 24px;
            border-top: 2px solid $blauw;
            margin-top: 16px;
            font-size: 7.5pt;
            color: #64748b;
            text-align: center;
        }
        .pdf-footer strong { color: $blauw; }
        ";

        // ---- Samenvatting rijen ----
        $samenvattingRijen = '';
        $i = 0;
        foreach ($perCategorie as $cat => $data) {
            $kleur = self::CAT_KLEUREN[$cat] ?? self::BLAUW;
            $samenvattingRijen .= sprintf(
                '<tr%s>
                    <td><span style="color:%s;font-size:10pt">&#9632;</span>&nbsp; <strong>%s</strong></td>
                    <td class="r">%s</td>
                    <td class="r">%s</td>
                    <td class="r">%s</td>
                    <td class="r"><strong>€ %s</strong></td>
                    <td class="r">%s%%</td>
                </tr>',
                ($i++ % 2) ? ' class="alt"' : '',
                $kleur,
                htmlspecialchars($cat),
                number_format($data['aantal_nummers'], 0, ',', '.'),
                number_format($data['aantal'], 0, ',', '.'),
                self::formatDur($data['sec']),
                number_format($data['kosten'], 2, ',', '.'),
                $totaalE4 > 0 ? number_format($data['kosten_e4'] / $totaalE4 * 100, 1, ',', '.') : '0'
            );
        }

        // ---- Detail per categorie ----
        $detailBlokken = '';
        foreach ($perCategorie as $cat => $data) {
            $kleur  = self::CAT_KLEUREN[$cat] ?? self::BLAUW;
            $euro   = fn(int $e4) => '€ ' . number_format($e4 / 10000, 2, ',', '.');

            if ($weergave === 'gesprek') {
                // Elk gesprek los (oude weergave)
                $kop = '<th style="width:13%">Datum</th><th style="width:10%">Tijd</th>'
                     . '<th style="width:12%">Toestel</th><th>Bestemming</th>'
                     . '<th class="r" style="width:10%">Duur</th><th class="r" style="width:12%">Kosten</th>';
                $span = 4;
                $rijen = '';
                foreach ($data['calls'] as $j => $c) {
                    $rijen .= sprintf(
                        '<tr%s><td class="mono">%s</td><td class="mono">%s</td><td class="mono">%s</td>'
                        . '<td class="mono">%s%s</td><td class="r">%s</td><td class="r">€ %s</td></tr>',
                        $j % 2 ? ' class="alt"' : '',
                        date('d-m-Y', strtotime($c['calldate'])),
                        date('H:i:s', strtotime($c['calldate'])),
                        htmlspecialchars($c['src']),
                        htmlspecialchars($c['dst']),
                        !empty($c['destination_name'])
                            ? ' <span class="muted">— ' . htmlspecialchars($c['destination_name']) . '</span>' : '',
                        self::formatDur((int)$c['billsec']),
                        number_format((float)$c['cost'], 4, ',', '.')
                    );
                }
                $subtotaalCellen = '<td class="r">' . self::formatDur($data['sec']) . '</td>'
                                 . '<td class="r">' . $euro($data['kosten_e4']) . '</td>';
            } else {
                // Samengevoegd per bestemmingsnummer
                $kop = '<th style="width:18%">Nummer</th><th>Omschrijving</th>'
                     . '<th style="width:18%">Toestel(len)</th>'
                     . '<th class="r" style="width:9%;white-space:nowrap">Gespr.</th>'
                     . '<th class="r" style="width:10%">Duur</th>'
                     . '<th class="r" style="width:8%">Gem.</th>'
                     . '<th class="r" style="width:11%">Kosten</th>';
                $span = 3;
                $rijen = '';
                $j = 0;
                foreach ($data['nummers'] as $n) {
                    $periode = substr($n['eerste'], 0, 10) === substr($n['laatste'], 0, 10)
                        ? date('d-m', strtotime($n['eerste']))
                        : date('d-m', strtotime($n['eerste'])) . ' t/m ' . date('d-m', strtotime($n['laatste']));
                    $rijen .= sprintf(
                        '<tr%s><td class="mono"><strong>%s</strong></td>'
                        . '<td>%s<br><span class="muted">%s</span></td>'
                        . '<td class="mono" style="white-space:nowrap">%s</td>'
                        . '<td class="r">%s</td><td class="r">%s</td><td class="r">%s</td>'
                        . '<td class="r"><strong>%s</strong></td></tr>',
                        ($j++ % 2) ? ' class="alt"' : '',
                        htmlspecialchars($n['nummer']),
                        htmlspecialchars($n['destination_name'] ?: '-'),
                        $periode,
                        htmlspecialchars(self::kortLijst($n['toestellen'])),
                        number_format($n['aantal'], 0, ',', '.'),
                        self::formatDur($n['sec']),
                        self::formatDur($n['gem_sec']),
                        $euro($n['kosten_e4'])
                    );
                }
                $subtotaalCellen = '<td class="r">' . number_format($data['aantal'], 0, ',', '.') . '</td>'
                                 . '<td class="r">' . self::formatDur($data['sec']) . '</td>'
                                 . '<td class="r"></td>'
                                 . '<td class="r">' . $euro($data['kosten_e4']) . '</td>';
            }

            $kolommen = $span + substr_count($subtotaalCellen, '<td');
            $detailBlokken .= '
            <div class="section">
                <table class="tbl-detail">
                    <thead>
                    <tr><td colspan="' . $kolommen . '" style="padding:10px 0 4px;background:#fff;border-bottom:2px solid ' . $kleur . '">'
                    . '<span style="font-size:8pt;font-weight:bold;letter-spacing:0.1em;color:' . $kleur . '">' . htmlspecialchars(mb_strtoupper($cat)) . '</span>'
                    . ' <span style="font-size:7pt;color:#94a3b8">'
                    . ($weergave === 'nummer' ? $data['aantal_nummers'] . ' nummers — ' : '')
                    . $data['aantal'] . ' gesprekken — '
                    . self::formatDur($data['sec'])
                    . ' — ' . $euro($data['kosten_e4'])
                    . '</span></td></tr>
                    <tr>' . preg_replace('/<th( class="r")?(?: style="([^"]*)")?>/', '<th$1 style="background:' . $kleur . ';$2">', $kop) . '</tr>
                    </thead>
                    <tbody>' . $rijen . '</tbody>
                    <tfoot>
                        <tr class="subtotaalrij">
                            <td colspan="' . $span . '">Subtotaal ' . htmlspecialchars($cat) . '</td>'
                            . $subtotaalCellen . '
                        </tr>
                    </tfoot>
                </table>
            </div>';
        }

        $voetnoot = $weergave === 'nummer'
            ? '<div class="section voetnoot">Gesprekken naar hetzelfde nummer zijn samengevoegd. Kosten worden per gesprek berekend
               (tariefstap en eventueel starttarief per gesprek) en daarna opgeteld. Bedragen per regel zijn afgerond op centen;
               subtotalen en totaal zijn berekend over de exacte gesprekskosten, waardoor een afrondingsverschil van enkele centen
               ten opzichte van de som van de regels mogelijk is. Gem. = gemiddelde gespreksduur. Alle bedragen excl. BTW.</div>'
            : '<div class="section voetnoot">Alle bedragen excl. BTW.</div>';

        // ---- Volledig HTML document ----
        return '<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><style>' . $css . '</style></head>
<body>

<!-- Header -->
<div class="header">
    <div class="header-title">GESPREKSSPECIFICATIE</div>
    <div class="header-sub">' . htmlspecialchars($company['name']) . ' &mdash; ' . htmlspecialchars($maandLabel) . '</div>
</div>
<div class="orange-bar"></div>

<!-- Klant + periode info -->
<table class="info-section">
<tr>
    <td style="width:50%;vertical-align:top">
        <div class="info-label">Klant</div>
        <div class="info-value">' . $klantnaam . '</div>
        <div class="info-sub">' . htmlspecialchars($customer['contact_name'] ?? '') . '</div>
    </td>
    <td style="width:50%;vertical-align:top">
        <div class="info-label">Periode</div>
        <div class="info-value">' . htmlspecialchars($maandLabel) . '</div>
        <div class="info-sub">
            ' . number_format($totaalAantal, 0, ',', '.') . ' gesprekken naar ' . number_format($totaalNrs, 0, ',', '.') . ' nummers
            &nbsp;&bull;&nbsp; ' . self::formatDur($totaalSec) . '
        </div>
        <div class="info-value" style="color:' . $oranje . ';margin-top:4px">€ '
            . number_format($totaalKosten, 2, ',', '.') . ' <span style="font-size:8pt;color:#64748b;font-weight:normal">excl. BTW</span></div>
    </td>
</tr>
</table>

<!-- Samenvatting per categorie -->
<div class="section">
    <div class="section-title">Samenvatting per categorie</div>
    <table class="tbl-samenvatting">
        <thead>
            <tr>
                <th>Categorie</th>
                <th class="r">Nummers</th>
                <th class="r">Gesprekken</th>
                <th class="r">Duur</th>
                <th class="r">Kosten</th>
                <th class="r">% van totaal</th>
            </tr>
        </thead>
        <tbody>' . $samenvattingRijen . '</tbody>
        <tfoot>
            <tr class="totaalrij">
                <td>Totaal</td>
                <td class="r">' . number_format($totaalNrs, 0, ',', '.') . '</td>
                <td class="r">' . number_format($totaalAantal, 0, ',', '.') . '</td>
                <td class="r">' . self::formatDur($totaalSec) . '</td>
                <td class="r">€ ' . number_format($totaalKosten, 2, ',', '.') . '</td>
                <td class="r">100%</td>
            </tr>
        </tfoot>
    </table>
</div>

<!-- Detail per categorie -->
' . $detailBlokken . '

' . $voetnoot . '

</body>
</html>';
    }

    /** "101, 102, 103 +2" — houdt de kolom smal bij veel toestellen. */
    private static function kortLijst(array $items, int $max = 3): string
    {
        if (count($items) <= $max) return implode(', ', $items);
        return implode(', ', array_slice($items, 0, $max)) . ' +' . (count($items) - $max);
    }

    private static function formatDur(int $sec): string
    {
        $h = intdiv($sec, 3600);
        $m = intdiv($sec % 3600, 60);
        $s = $sec % 60;
        if ($h > 0) return sprintf('%d:%02d:%02d', $h, $m, $s);
        return sprintf('%d:%02d', $m, $s);
    }
}
