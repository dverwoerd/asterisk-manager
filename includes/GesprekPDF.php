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

require_once APP_ROOT . '/controllers/GesprekspecificatiesController.php';

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
            'mode'              => 'c',          // standaard PDF-lettertypen (Helvetica), niet ingebed
            'format'            => 'A4',
            'margin_top'        => 0,
            'margin_bottom'     => 22,
            'margin_footer'     => 8,
            'margin_left'       => 0,
            'margin_right'      => 0,
            'default_font'      => 'helvetica',
            'default_font_size' => 10,
        ]);

        $klantnaam = $customer['company_name'] ?: $customer['contact_name'];
        $mpdf->SetTitle("Gespreksspecificaties $klantnaam - $maandLabel");
        $mpdf->SetAuthor($company['name']);
        $mpdf->SetCreator('Asterisk Manager');

        $mpdf->SetHTMLFooter(
            '<div style="text-align:center;font-family:helvetica;font-size:8pt;color:#9aa3b2">'
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

        $rand = self::RAND;
        $css = "
        body { font-family: helvetica; font-size: 10pt; color: #222222; }
        table { border-collapse: collapse; }
        .kop    { background: $blauw; color: #ffffff; text-align: center; font-size: 17pt; font-weight: bold;
                  height: 45.3pt; padding-top: 22.4pt; }
        .balk   { background: $oranje; color: #ffffff; text-align: center; font-size: 13pt; font-weight: bold;
                  height: 27.5pt; padding-top: 9.8pt; }
        .inhoud { padding: 0 39.7pt; }
        .info td { padding: 4.85pt 0; font-size: 10pt; }
        .info .l { width: 56.7pt; }
        .tbl    { width: 100%; border: 0.75pt solid $rand; }
        .tbl th { background: $blauw; color: #ffffff; font-weight: bold; text-align: center; }
        .tbl tr.alt td { background: " . self::LICHTBLAUW . "; }
        .tbl tr.totaal td { background: $oranje; color: #ffffff; font-weight: bold; }
        .c { text-align: center; } .r { text-align: right; } .lnk { text-align: left; }
        .klein { font-size: 7.5pt; color: #8a93a3; font-weight: normal; }

        .groot th { font-size: 10pt; height: 40.3pt; padding: 0 14pt; }
        .groot td { font-size: 10pt; padding: 14.2pt 13.6pt 14.2pt 14pt; }

        .spec th { font-size: 9pt; padding: 8pt 9pt; }
        .spec td { font-size: 8.5pt; padding: 3.5pt 9pt; }
        .spec tr.cat td { background: #D3DBEA; color: $blauw; font-weight: bold; padding: 7pt 9pt; border-top: 1pt solid $blauw; }
        .spec tr.nr td { background: #F1F4F9; color: $blauw; font-weight: bold; padding: 5pt 9pt; border-top: 0.5pt solid $rand; }
        .spec tr.totaal td { padding: 7pt 9pt; }
        .pagina2 { color: $blauw; font-size: 11pt; font-weight: bold; margin: 0 0 3pt; }
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
                <th style="width:176.6pt">Categorie</th><th style="width:143.6pt">Aantal gesprekken</th>
                <th style="width:95.2pt">Minuten</th><th style="width:100.5pt">Totaal (&euro;)</th>
            </tr></thead>
            <tbody>' . $rijen . '
                <tr class="totaal"><td class="lnk">Totaal</td><td></td><td></td>
                    <td class="r">' . self::euro($t['kosten']) . '</td></tr>
            </tbody>
        </table>';

        // ---- Pagina 2: per categorie, per nummer, de afzonderlijke gesprekken ----
        $dur = fn(int $sec) => GesprekspecificatiesController::leesbareDuur($sec);
        $rijen = '';
        foreach ($spec['categorieen'] as $cat => $g) {
            if ($g['aantal'] === 0) continue;
            $rijen .= '<tr class="cat"><td colspan="2" class="lnk">' . htmlspecialchars($cat) . '</td>'
                . '<td class="c">' . $dur((int)$g['sec']) . '</td><td class="c">' . (int)$g['minuten'] . '</td>'
                . '<td class="r">' . self::euro($g['kosten']) . '</td></tr>';

            foreach ($g['nummers'] as $n) {
                $omschr = !empty($n['land']) ? ' &middot; ' . htmlspecialchars($n['land']) : '';
                if (isset($n['tarief_bekend']) && !$n['tarief_bekend']) $omschr .= ' &middot; geen tarief';
                $rijen .= '<tr class="nr"><td colspan="2" class="lnk">' . htmlspecialchars($n['nummer']) . $omschr . '</td>'
                    . '<td class="c">' . $dur((int)$n['sec']) . '</td><td class="c">' . (int)$n['minuten'] . '</td>'
                    . '<td class="r">' . self::euro4($n['kosten']) . '</td></tr>';
                $j = 0;
                foreach ($n['calls'] as $c) {
                    $rijen .= '<tr' . ($j++ % 2 ? ' class="alt"' : '') . '>'
                        . '<td class="lnk">' . date('d-m-Y H:i', strtotime($c['calldate'])) . '</td>'
                        . '<td class="lnk">Toestel ' . htmlspecialchars($c['src']) . (!empty($c['doorgeschakeld']) ? ' (doorgeschakeld)' : '') . '</td>'
                        . '<td class="c">' . $dur((int)$c['billsec']) . '</td>'
                        . '<td class="c">' . (int)$c['minuten'] . '</td>'
                        . '<td class="r">' . self::euro4($c['kosten']) . '</td>'
                        . '</tr>';
                }
            }
        }
        if ($rijen === '') {
            $rijen = '<tr><td colspan="5" class="c klein">Geen gesprekken in deze periode.</td></tr>';
        }
        $perNummer = '
        <table class="tbl spec">
            <thead><tr>
                <th style="width:120pt" class="lnk">Datum en tijd</th><th style="width:130pt" class="lnk">Toestel / nummer</th>
                <th style="width:95pt">Duur</th><th style="width:60pt">Minuten</th>
                <th style="width:110pt">Kosten (&euro;)</th>
            </tr></thead>
            <tbody>' . $rijen . '
                <tr class="totaal"><td class="lnk" colspan="2">Totaal</td>
                    <td class="c">' . $dur((int)$t['sec']) . '</td><td class="c">' . (int)$t['minuten'] . '</td>
                    <td class="r">' . self::euro($t['kosten']) . '</td></tr>
            </tbody>
        </table>';

        return '<html><head><style>' . $css . '</style></head><body>
<div class="kop">' . htmlspecialchars($company['name']) . '</div>
<div class="balk">Gespreksspecificaties</div>
<div class="inhoud">
    <table class="info" style="margin-top:20.6pt">
        <tr><td class="l">Klant:</td><td>' . $klant . '</td></tr>
        <tr><td class="l">Periode:</td><td>' . htmlspecialchars($maandLabel) . '</td></tr>
    </table>
    <div style="height:15.8pt"></div>
    ' . $samenvatting . '
</div>

<pagebreak margin-top="16mm" />

<div class="inhoud">
    <div class="pagina2">Specificatie per nummer</div>
    <div class="klein" style="margin-bottom:10px">' . $klant . ' &middot; ' . htmlspecialchars($maandLabel) . ' &middot; per categorie en nummer</div>
    ' . $perNummer . '
</div>
</body></html>';
    }

    private static function euro4(float $v): string
    {
        return '&euro; ' . number_format($v, 4, ',', '.');
    }

    private static function euro(float $v): string
    {
        return '&euro; ' . number_format($v, 2, ',', '.');
    }
}
