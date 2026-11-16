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
        array  $perCategorie
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
            'margin_bottom'  => 15,
            'margin_left'    => 0,
            'margin_right'   => 0,
            'default_font'   => 'dejavusans',
            'default_font_size' => 9,
        ]);

        $klantnaam = $customer['company_name'] ?: $customer['contact_name'];
        $mpdf->SetTitle("Gespreksspecificatie $klantnaam — $maandLabel");
        $mpdf->SetAuthor($company['name']);
        $mpdf->SetCreator('Pantronic Asterisk Manager');

        $html = self::buildHTML($customer, $company, $maandLabel, $perCategorie);
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
        array  $perCategorie
    ): string {
        $klantnaam = htmlspecialchars($customer['company_name'] ?: $customer['contact_name']);
        $blauw     = self::BLAUW;
        $oranje    = self::ORANJE;

        // Totalen berekenen
        $totaalAantal = 0;
        $totaalSec    = 0;
        $totaalKosten = 0.0;
        foreach ($perCategorie as $cat => $data) {
            $totaalAantal += $data['aantal'];
            $totaalSec    += $data['sec'];
            $totaalKosten += $data['kosten'];
        }

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
            padding: 14px 24px;
            display: table;
            width: 100%;
            border-bottom: 1px solid #E2E8F0;
        }
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
        .tbl-samenvatting tr:nth-child(even) td { background: " . self::LICHTBLAUW . "; }
        .tbl-samenvatting .cat-badge {
            display: inline-block;
            padding: 1px 7px;
            border-radius: 3px;
            font-size: 7.5pt;
            font-weight: bold;
            color: #fff;
        }
        .tbl-samenvatting .totaalrij td {
            background: $oranje !important;
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
        .tbl-detail td.r { text-align: right; font-family: monospace; }
        .tbl-detail tr:nth-child(even) td { background: " . self::GRIJS . "; }
        .tbl-detail .subtotaalrij td {
            background: $blauw !important;
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
        foreach ($perCategorie as $cat => $data) {
            $kleur = self::CAT_KLEUREN[$cat] ?? self::BLAUW;
            $samenvattingRijen .= sprintf(
                '<tr>
                    <td><span class="cat-badge" style="background:%s">%s</span></td>
                    <td class="r">%s</td>
                    <td class="r">%s</td>
                    <td class="r"><strong>€ %s</strong></td>
                    <td class="r">%s%%</td>
                </tr>',
                $kleur,
                htmlspecialchars($cat),
                number_format($data['aantal']),
                self::formatDur($data['sec']),
                number_format($data['kosten'], 2, ',', '.'),
                $totaalKosten > 0 ? number_format($data['kosten'] / $totaalKosten * 100, 1, ',', '.') : '0'
            );
        }

        // ---- Detail per categorie ----
        $detailBlokken = '';
        foreach ($perCategorie as $cat => $data) {
            $kleur = self::CAT_KLEUREN[$cat] ?? self::BLAUW;

            $rijen = '';
            foreach ($data['calls'] as $c) {
                $rijen .= sprintf(
                    '<tr>
                        <td class="mono">%s</td>
                        <td class="mono">%s</td>
                        <td class="mono">%s</td>
                        <td class="mono">%s%s</td>
                        <td class="r">%s</td>
                        <td class="r">€ %s</td>
                    </tr>',
                    date('d-m-Y', strtotime($c['calldate'])),
                    date('H:i:s', strtotime($c['calldate'])),
                    htmlspecialchars($c['src']),
                    htmlspecialchars($c['dst']),
                    !empty($c['destination_name'])
                        ? ' <span style="color:#94a3b8;font-size:7.5pt">— ' . htmlspecialchars($c['destination_name']) . '</span>'
                        : '',
                    self::formatDur((int)$c['billsec']),
                    number_format((float)$c['cost'], 4, ',', '.')
                );
            }

            $detailBlokken .= '
            <div class="section" style="page-break-inside:avoid">
                <div class="section-title" style="border-color:' . $kleur . ';color:' . $kleur . '">'
                    . htmlspecialchars($cat)
                    . ' <span style="font-size:7pt;font-weight:normal;color:#94a3b8">'
                    . $data['aantal'] . ' gesprekken — '
                    . self::formatDur($data['sec'])
                    . ' — € ' . number_format($data['kosten'], 2, ',', '.')
                    . '</span>
                </div>
                <table class="tbl-detail">
                    <thead>
                        <tr style="background:' . $kleur . '">
                            <th>Datum</th>
                            <th>Tijd</th>
                            <th>Toestel</th>
                            <th>Bestemming</th>
                            <th class="r">Duur</th>
                            <th class="r">Kosten</th>
                        </tr>
                    </thead>
                    <tbody>' . $rijen . '</tbody>
                    <tfoot>
                        <tr class="subtotaalrij">
                            <td colspan="4">Subtotaal ' . htmlspecialchars($cat) . '</td>
                            <td class="r">' . self::formatDur($data['sec']) . '</td>
                            <td class="r">€ ' . number_format($data['kosten'], 2, ',', '.') . '</td>
                        </tr>
                    </tfoot>
                </table>
            </div>';
        }

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
<div class="info-section">
    <div class="info-left">
        <div class="info-label">Klant</div>
        <div class="info-value">' . $klantnaam . '</div>
        <div class="info-sub">' . htmlspecialchars($customer['contact_name'] ?? '') . '</div>
    </div>
    <div class="info-right">
        <div class="info-label">Periode</div>
        <div class="info-value">' . htmlspecialchars($maandLabel) . '</div>
        <div class="info-sub">
            Totaal: ' . number_format($totaalAantal) . ' gesprekken &nbsp;&bull;&nbsp;
            ' . self::formatDur($totaalSec) . ' &nbsp;&bull;&nbsp;
            <strong style="color:' . $oranje . '">€ ' . number_format($totaalKosten, 2, ',', '.') . '</strong> excl. BTW
        </div>
    </div>
</div>

<!-- Samenvatting per categorie -->
<div class="section">
    <div class="section-title">Samenvatting per categorie</div>
    <table class="tbl-samenvatting">
        <thead>
            <tr>
                <th>Categorie</th>
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
                <td class="r">' . number_format($totaalAantal) . '</td>
                <td class="r">' . self::formatDur($totaalSec) . '</td>
                <td class="r">€ ' . number_format($totaalKosten, 2, ',', '.') . '</td>
                <td class="r">100%</td>
            </tr>
        </tfoot>
    </table>
</div>

<!-- Detail per categorie -->
' . $detailBlokken . '

<!-- Footer -->
<div class="pdf-footer">
    <strong>' . htmlspecialchars($company['name']) . '</strong>
    &nbsp;&bull;&nbsp; ' . htmlspecialchars($company['address']) . '
    &nbsp;&bull;&nbsp; Tel. ' . htmlspecialchars($company['phone']) . '
    &nbsp;&bull;&nbsp; ' . htmlspecialchars($company['email']) . '
</div>

</body>
</html>';
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
