<?php
// ============================================================
// GesprekPDF — Pantronic Gespreksspecificatie PDF via mPDF
//
// Pantronic huisstijl: blauw #1B3A6B, oranje #F37021
// Blokstructuur: header (blauw), oranje balk, klant/periode info,
// samenvatting per categorie, detail per categorie, voettekst met bedrijfsgegevens.
// Categorieën kleurgecodeerd (6 kleuren).
// Detail: gesprekken gegroepeerd per nummer; kolommen per gesprek:
// datum, tijd, toestel, bestemming, duur, kosten (4 decimalen).
// ============================================================

class GesprekPDF
{
    const BLAUW      = '#1B3A6B';
    const ORANJE     = '#F37021';
    const LICHTBLAUW = '#EEF2FA';
    const GRIJS      = '#F4F6FA';

    const CAT_KLEUREN = [
        'Nationaal'      => '#1B3A6B',
        'Mobiel'         => '#2A5298',
        '088 Nummer'     => '#3D7ABF',
        'Internationaal' => '#C9571A',
        '0800 Gratis'    => '#27AE60',
        '0900 Premium'   => '#E74C3C',
    ];

    public static function download(array $customer, array $company, string $maandLabel, array $spec): void
    {
        $autoload = APP_ROOT . '/vendor/autoload.php';
        if (!file_exists($autoload)) {
            throw new Exception('mPDF niet geïnstalleerd. Voer uit: composer require mpdf/mpdf');
        }
        require_once $autoload;

        $mpdf = self::maak($customer, $company, $maandLabel, $spec);

        $klantnaam = $customer['company_name'] ?: $customer['contact_name'];
        $bestandsnaam = 'gespreksspecificatie-'
            . preg_replace('/[^a-z0-9]/i', '-', $klantnaam) . '-'
            . str_replace(' ', '-', strtolower($maandLabel)) . '.pdf';

        $mpdf->Output($bestandsnaam, 'D');
        exit;
    }

    public static function maak(array $customer, array $company, string $maandLabel, array $spec): \Mpdf\Mpdf
    {
        $mpdf = new \Mpdf\Mpdf([
            'mode'              => 'utf-8',
            'format'            => 'A4',
            'margin_top'        => 0,
            'margin_bottom'     => 18,
            'margin_footer'     => 6,
            'margin_left'       => 0,
            'margin_right'      => 0,
            'default_font'      => 'dejavusans',
            'default_font_size' => 9,
        ]);

        $klantnaam = $customer['company_name'] ?: $customer['contact_name'];
        $mpdf->SetTitle("Gespreksspecificatie $klantnaam — $maandLabel");
        $mpdf->SetAuthor($company['name']);
        $mpdf->SetCreator('Pantronic Asterisk Manager');

        // Voettekst met bedrijfsgegevens op elke pagina
        $mpdf->SetHTMLFooter(
            '<table width="100%" style="border-top:2px solid ' . self::BLAUW . ';font-size:7.5pt;color:#64748b">'
            . '<tr><td style="padding:6px 24px 0;text-align:center">'
            . '<strong style="color:' . self::BLAUW . '">' . htmlspecialchars($company['name']) . '</strong>'
            . ' &nbsp;&bull;&nbsp; ' . htmlspecialchars($company['address'])
            . ' &nbsp;&bull;&nbsp; Tel. ' . htmlspecialchars($company['phone'])
            . ' &nbsp;&bull;&nbsp; ' . htmlspecialchars($company['email'])
            . ' &nbsp;&bull;&nbsp; Pagina {PAGENO} van {nbpg}'
            . '</td></tr></table>'
        );

        $mpdf->WriteHTML(self::buildHTML($customer, $company, $maandLabel, $spec));
        return $mpdf;
    }

    private static function buildHTML(array $customer, array $company, string $maandLabel, array $spec): string
    {
        $klantnaam = htmlspecialchars($customer['company_name'] ?: $customer['contact_name']);
        $blauw  = self::BLAUW;
        $oranje = self::ORANJE;
        $t      = $spec['totaal'];
        $totE4  = (int)round($t['kosten'] * 10000);

        $css = "
        body { font-family: dejavusans; font-size: 9pt; color: #1a1a1a; }
        table { border-collapse: collapse; }

        .header { background: $blauw; color: #fff; padding: 18px 24px 14px; }
        .header-title { font-size: 18pt; font-weight: bold; color: #fff; }
        .header-sub { font-size: 9.5pt; color: #C5D0E3; margin-top: 2px; }
        .orange-bar { background: $oranje; height: 4px; font-size: 1pt; line-height: 1pt; }

        .info { width: 100%; border-bottom: 1px solid #E2E8F0; }
        .info td { padding: 14px 24px; vertical-align: top; width: 50%; }
        .info-label { font-size: 7.5pt; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; }
        .info-value { font-size: 11pt; font-weight: bold; color: $blauw; }
        .info-sub { font-size: 8.5pt; color: #64748b; }

        .section { padding: 12px 24px 0; }
        .section-title { font-size: 8pt; font-weight: bold; text-transform: uppercase; letter-spacing: 1px;
                         color: $oranje; border-bottom: 2px solid $oranje; padding-bottom: 4px; margin-bottom: 8px; }

        .tbl { width: 100%; }
        .tbl th { color: #fff; padding: 6px 10px; font-size: 8pt; font-weight: bold; text-align: left; }
        .tbl td { padding: 5px 10px; border-bottom: 1px solid #E2E8F0; font-size: 8.5pt; }
        .tbl tr.alt td { background: " . self::LICHTBLAUW . "; }
        .tbl .r { text-align: right; }
        .tbl tr.totaal td { background: $oranje; color: #fff; font-weight: bold; padding: 6px 10px; border: none; }

        .det th { padding: 4px 8px; font-size: 7.5pt; }
        .det td { padding: 3px 8px; font-size: 8pt; }
        .det tr.alt td { background: " . self::GRIJS . "; }
        .det tr.nummer td { background: #E6ECF5; font-weight: bold; color: $blauw; padding: 5px 8px; border-bottom: 1px solid #CBD5E1; }
        .det tr.sub td { background: $blauw; color: #fff; font-weight: bold; padding: 4px 8px; border: none; }
        .mono { font-family: dejavusansmono; }
        .muted { color: #94a3b8; font-size: 7.5pt; font-weight: normal; }
        ";

        // ---- Samenvatting per categorie (alleen categorieën met gesprekken) ----
        $rijen = '';
        $i = 0;
        foreach ($spec['categorieen'] as $cat => $g) {
            if ($g['aantal'] === 0) continue;
            $kleur = self::CAT_KLEUREN[$cat] ?? $blauw;
            $rijen .= '<tr' . ($i++ % 2 ? ' class="alt"' : '') . '>'
                . '<td><span style="background:' . $kleur . ';color:#ffffff;font-weight:bold;font-size:7.5pt">&nbsp;'
                . htmlspecialchars($cat) . '&nbsp;</span></td>'
                . '<td class="r">' . number_format($g['aantal'], 0, ',', '.') . '</td>'
                . '<td class="r">' . self::formatDur($g['sec']) . '</td>'
                . '<td class="r"><strong>' . self::euro($g['kosten']) . '</strong></td>'
                . '<td class="r">' . ($totE4 > 0 ? number_format($g['e4'] / $totE4 * 100, 1, ',', '.') : '0') . '%</td>'
                . '</tr>';
        }
        $samenvatting = '
        <div class="section">
            <div class="section-title">Samenvatting per categorie</div>
            <table class="tbl">
                <thead><tr>
                    <th style="background:' . $blauw . '">Categorie</th>
                    <th class="r" style="background:' . $blauw . '">Gesprekken</th>
                    <th class="r" style="background:' . $blauw . '">Duur</th>
                    <th class="r" style="background:' . $blauw . '">Kosten</th>
                    <th class="r" style="background:' . $blauw . '">% van totaal</th>
                </tr></thead>
                <tbody>' . $rijen . '</tbody>
                <tfoot><tr class="totaal">
                    <td>Totaal</td>
                    <td class="r">' . number_format($t['aantal'], 0, ',', '.') . '</td>
                    <td class="r">' . self::formatDur($t['sec']) . '</td>
                    <td class="r">' . self::euro($t['kosten']) . '</td>
                    <td class="r">100%</td>
                </tr></tfoot>
            </table>
        </div>';

        // ---- Detail per categorie, gesprekken gegroepeerd per nummer ----
        $detail = '';
        foreach ($spec['categorieen'] as $cat => $g) {
            if ($g['aantal'] === 0) continue;
            $kleur = self::CAT_KLEUREN[$cat] ?? $blauw;
            $th = fn(string $txt, string $cls = '', string $w = '') =>
                '<th' . ($cls ? " class=\"$cls\"" : '') . ' style="background:' . $kleur . ($w ? ";width:$w" : '') . '">' . $txt . '</th>';

            $rijen = '';
            foreach ($g['nummers'] as $n) {
                $omschr = $n['land'] !== '' ? $n['land'] : $cat;
                $rijen .= '<tr class="nummer">'
                    . '<td colspan="4"><span class="mono">' . htmlspecialchars($n['nummer']) . '</span>'
                    . ' <span class="muted">— ' . htmlspecialchars($omschr) . ' — ' . $n['aantal'] . ' gesprek' . ($n['aantal'] === 1 ? '' : 'ken') . '</span></td>'
                    . '<td class="r">' . self::formatDur($n['sec']) . '</td>'
                    . '<td class="r">' . self::euro4($n['kosten']) . '</td>'
                    . '</tr>';
                $j = 0;
                foreach ($n['calls'] as $c) {
                    $rijen .= '<tr' . ($j++ % 2 ? ' class="alt"' : '') . '>'
                        . '<td class="mono">' . date('d-m-Y', strtotime($c['calldate'])) . '</td>'
                        . '<td class="mono">' . date('H:i:s', strtotime($c['calldate'])) . '</td>'
                        . '<td class="mono">' . htmlspecialchars($c['src']) . '</td>'
                        . '<td class="mono">' . htmlspecialchars($c['dst']) . '</td>'
                        . '<td class="r mono">' . self::formatDur((int)$c['billsec']) . '</td>'
                        . '<td class="r mono">' . self::euro4($c['kosten']) . '</td>'
                        . '</tr>';
                }
            }

            $detail .= '
            <div class="section">
                <div class="section-title" style="border-color:' . $kleur . ';color:' . $kleur . '">'
                    . htmlspecialchars($cat)
                    . ' <span class="muted">' . count($g['nummers']) . ' nummers — ' . $g['aantal'] . ' gesprekken — '
                    . self::formatDur($g['sec']) . ' — ' . self::euro($g['kosten']) . '</span>
                </div>
                <table class="tbl det">
                    <thead><tr>'
                    . $th('Datum', '', '15%') . $th('Tijd', '', '12%') . $th('Toestel', '', '12%')
                    . $th('Bestemming') . $th('Duur', 'r', '12%') . $th('Kosten', 'r', '14%') .
                    '</tr></thead>
                    <tbody>' . $rijen . '</tbody>
                    <tfoot><tr class="sub">
                        <td colspan="4">Subtotaal ' . htmlspecialchars($cat) . '</td>
                        <td class="r">' . self::formatDur($g['sec']) . '</td>
                        <td class="r">' . self::euro($g['kosten']) . '</td>
                    </tr></tfoot>
                </table>
            </div>';
        }

        return '<html><head><style>' . $css . '</style></head><body>

<div class="header">
    <div class="header-title">GESPREKSSPECIFICATIE</div>
    <div class="header-sub">' . htmlspecialchars($company['name']) . ' &mdash; ' . htmlspecialchars($maandLabel) . '</div>
</div>
<div class="orange-bar">&nbsp;</div>

<table class="info"><tr>
    <td>
        <div class="info-label">Klant</div>
        <div class="info-value">' . $klantnaam . '</div>
        <div class="info-sub">' . htmlspecialchars($customer['contact_name'] ?? '') . '</div>
    </td>
    <td>
        <div class="info-label">Periode</div>
        <div class="info-value">' . htmlspecialchars($maandLabel) . '</div>
        <div class="info-sub">Totaal: ' . number_format($t['aantal'], 0, ',', '.') . ' gesprekken &nbsp;&bull;&nbsp; '
            . self::formatDur($t['sec']) . ' &nbsp;&bull;&nbsp; <strong style="color:' . $oranje . '">'
            . self::euro($t['kosten']) . '</strong> excl. BTW</div>
    </td>
</tr></table>

' . $samenvatting . $detail . '

</body></html>';
    }

    private static function euro(float $v): string
    {
        return '€ ' . number_format($v, 2, ',', '.');
    }

    private static function euro4(float $v): string
    {
        return '€ ' . number_format($v, 4, ',', '.');
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
