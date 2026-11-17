<?php
// ============================================================
// GesprekPDF: Gespreksspecificatie-PDF via mPDF (1 pagina per klant en maand)
//
// Opzet:
//   - blauwe kop met bedrijfsnaam, oranje balk "Gespreksspecificaties"
//   - Klant / Periode
//   - samenvatting: alle zes categorieen (ook met 0 gesprekken), totaalrij
//   - "Specificatie per nummer": nummer, bestemming (categorie + toestel), aantal, minuten, totaal
//   - voettekst met bedrijfsgegevens
// Pantronic huisstijl: blauw #1B3A6B, oranje #F37021
// ============================================================

class GesprekPDF
{
    const BLAUW      = '#1B3A6B';
    const ORANJE     = '#F37021';
    const LICHTBLAUW = '#E8EDF5';
    const RAND       = '#D0D7E2';

    public static function download(array $customer, array $company, string $maandLabel, array $spec): void
    {
        $mpdf = self::maak($customer, $company, $maandLabel, $spec);

        // J.J.A. Smidts + November 2026  ->  J_J_A__Smidts_November_2026.pdf
        $klantnaam    = $customer['company_name'] ?: $customer['contact_name'];
        $bestandsnaam = preg_replace('/[^A-Za-z0-9]/', '_', $klantnaam) . '_'
                      . preg_replace('/[^A-Za-z0-9]/', '_', $maandLabel) . '.pdf';

        $mpdf->Output($bestandsnaam, 'D');
        exit;
    }

    public static function maak(array $customer, array $company, string $maandLabel, array $spec): \Mpdf\Mpdf
    {
        $autoload = APP_ROOT . '/vendor/autoload.php';
        if (!file_exists($autoload)) {
            throw new Exception('mPDF niet geinstalleerd. Voer uit: composer require mpdf/mpdf');
        }
        require_once $autoload;

        $mpdf = new \Mpdf\Mpdf([
            'mode'              => 'utf-8',
            'format'            => 'A4',
            'margin_top'        => 0,
            'margin_bottom'     => 22,
            'margin_footer'     => 8,
            'margin_left'       => 0,
            'margin_right'      => 0,
            'default_font'      => 'dejavusans',
            'default_font_size' => 9.5,
        ]);

        $klantnaam = $customer['company_name'] ?: $customer['contact_name'];
        $mpdf->SetTitle("Gespreksspecificaties $klantnaam - $maandLabel");
        $mpdf->SetAuthor($company['name']);
        $mpdf->SetCreator('Asterisk Manager');

        $mpdf->SetHTMLFooter(
            '<div style="text-align:center;font-size:7.5pt;color:#9aa3b2">'
            . htmlspecialchars($company['name'])
            . ' &nbsp;&middot;&nbsp; ' . htmlspecialchars($company['address'])
            . ' &nbsp;&middot;&nbsp; Tel. ' . htmlspecialchars($company['phone'])
            . '</div>'
        );

        $mpdf->WriteHTML(self::buildHTML($customer, $company, $maandLabel, $spec));
        return $mpdf;
    }

    private static function buildHTML(array $customer, array $company, string $maandLabel, array $spec): string
    {
        $blauw  = self::BLAUW;
        $oranje = self::ORANJE;
        $klant  = htmlspecialchars($customer['company_name'] ?: $customer['contact_name']);
        $t      = $spec['totaal'];

        $css = "
        body { font-family: dejavusans; color: #222; }
        table { border-collapse: collapse; }
        .kop     { background: $blauw; color: #ffffff; text-align: center; font-size: 20pt; font-weight: bold; padding: 22px 0 20px; }
        .balk    { background: $oranje; color: #ffffff; text-align: center; font-size: 13pt; font-weight: bold; padding: 12px 0; }
        .inhoud  { padding: 0 20mm; }
        .info td { padding: 3px 0; font-size: 10pt; }
        .info .l { width: 22mm; color: #444; }
        h2       { color: $blauw; font-size: 11.5pt; margin: 22px 0 8px; }
        .tbl     { width: 100%; border: 1px solid " . self::RAND . "; }
        .tbl th  { background: $blauw; color: #ffffff; font-size: 8.5pt; font-weight: bold; padding: 9px 8px; }
        .tbl td  { padding: 9px 8px; font-size: 9pt; }
        .tbl tr.alt td { background: " . self::LICHTBLAUW . "; }
        .tbl tr.totaal td { background: $oranje; color: #ffffff; font-weight: bold; }
        .c { text-align: center; } .r { text-align: right; } .lnk { text-align: left; }
        .klein { font-size: 7pt; color: #8a93a3; }
        .groot td { padding: 13px 14px; font-size: 9.5pt; }
        ";

        // ---- Samenvatting: alle categorieen ----
        $rijen = '';
        $i = 0;
        foreach ($spec['categorieen'] as $cat => $g) {
            $rijen .= '<tr' . ($i++ % 2 ? ' class="alt"' : '') . '>'
                . '<td class="lnk">' . htmlspecialchars($cat) . '</td>'
                . '<td class="c">' . (int)$g['aantal'] . '</td>'
                . '<td class="c">' . (int)$g['minuten'] . '</td>'
                . '<td class="r">' . self::euro($g['kosten']) . '</td>'
                . '</tr>';
        }
        $samenvatting = '
        <table class="tbl groot">
            <thead><tr>
                <th class="c">Categorie</th><th class="c">Aantal gesprekken</th>
                <th class="c">Minuten</th><th class="r">Totaal (&euro;)</th>
            </tr></thead>
            <tbody>' . $rijen . '
                <tr class="totaal"><td class="lnk">Totaal</td><td></td><td></td>
                    <td class="r">' . self::euro($t['kosten']) . '</td></tr>
            </tbody>
        </table>';

        // ---- Specificatie per nummer ----
        $rijen = '';
        $i = 0;
        foreach ($spec['nummers'] as $n) {
            $best = htmlspecialchars($n['categorie']);
            if (!empty($n['land'])) $best .= ' &middot; ' . htmlspecialchars($n['land']);
            $ts = $n['toestellen'] ?? [];
            $toestel = $ts ? (count($ts) > 1 ? 'Toestellen ' : 'Toestel ') . htmlspecialchars(implode(', ', $ts)) : '';

            $rijen .= '<tr' . ($i++ % 2 ? ' class="alt"' : '') . '>'
                . '<td class="lnk">' . htmlspecialchars($n['nummer']) . '</td>'
                . '<td class="lnk">' . $best . ($toestel ? '<br><span class="klein">' . $toestel . '</span>' : '') . '</td>'
                . '<td class="c">' . (int)$n['aantal'] . '</td>'
                . '<td class="c">' . (int)$n['minuten'] . '</td>'
                . '<td class="r">' . self::euro($n['kosten']) . '</td>'
                . '</tr>';
        }
        if ($rijen === '') {
            $rijen = '<tr><td colspan="5" class="c klein">Geen gesprekken in deze periode.</td></tr>';
        }
        $perNummer = '
        <h2>Specificatie per nummer</h2>
        <table class="tbl">
            <thead><tr>
                <th class="c" style="width:30mm">Nummer</th><th class="c">Bestemming</th>
                <th class="c" style="width:18mm">Aantal</th><th class="c" style="width:18mm">Minuten</th>
                <th class="r" style="width:24mm">Totaal (&euro;)</th>
            </tr></thead>
            <tbody>' . $rijen . '
                <tr class="totaal"><td class="lnk">Totaal</td><td></td>
                    <td class="c">' . (int)$t['aantal'] . '</td><td class="c">' . (int)$t['minuten'] . '</td>
                    <td class="r">' . self::euro($t['kosten']) . '</td></tr>
            </tbody>
        </table>';

        return '<html><head><style>' . $css . '</style></head><body>
<div class="kop">' . htmlspecialchars($company['name']) . '</div>
<div class="balk">Gespreksspecificaties</div>
<div class="inhoud">
    <table class="info" style="margin-top:22px">
        <tr><td class="l">Klant:</td><td>' . $klant . '</td></tr>
        <tr><td class="l">Periode:</td><td>' . htmlspecialchars($maandLabel) . '</td></tr>
    </table>
    <div style="height:18px"></div>
    ' . $samenvatting . $perNummer . '
</div>
</body></html>';
    }

    private static function euro(float $v): string
    {
        return '&euro; ' . number_format($v, 2, ',', '.');
    }
}
