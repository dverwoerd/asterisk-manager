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
        .spec td { font-size: 9pt; padding: 6pt 9pt; }
        .spec tr.cat td { background: #D3DBEA; color: $blauw; font-weight: bold; padding: 7pt 9pt; }
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

        // ---- Specificatie per nummer, gegroepeerd op categorie (pagina 2) ----
        $rijen = '';
        $huidig = null;
        $i = 0;
        foreach ($spec['nummers'] as $n) {
            if ($n['categorie'] !== $huidig) {
                $huidig = $n['categorie'];
                $g = $spec['categorieen'][$huidig];
                $rijen .= '<tr class="cat"><td colspan="2" class="lnk">' . htmlspecialchars($huidig) . '</td>'
                    . '<td class="c">' . (int)$g['aantal'] . '</td><td class="c">' . (int)$g['minuten'] . '</td>'
                    . '<td class="r">' . self::euro($g['kosten']) . '</td></tr>';
                $i = 0;
            }
            $ts = $n['toestellen'] ?? [];
            $toestel = $ts ? (count($ts) > 1 ? 'Toestellen ' : 'Toestel ') . htmlspecialchars(implode(', ', $ts)) : '';

            $rijen .= '<tr' . ($i++ % 2 ? ' class="alt"' : '') . '>'
                . '<td class="lnk">' . htmlspecialchars($n['nummer']) . '</td>'
                . '<td class="lnk">' . (!empty($n['land'])
                        ? htmlspecialchars($n['land']) . ($toestel ? '<br><span class="klein">' . $toestel . '</span>' : '')
                        : '<span class="klein">' . $toestel . '</span>') . '</td>'
                . '<td class="c">' . (int)$n['aantal'] . '</td>'
                . '<td class="c">' . (int)$n['minuten'] . '</td>'
                . '<td class="r">' . self::euro($n['kosten']) . '</td>'
                . '</tr>';
        }
        if ($rijen === '') {
            $rijen = '<tr><td colspan="5" class="c klein">Geen gesprekken in deze periode.</td></tr>';
        }
        $perNummer = '
        <table class="tbl spec">
            <thead><tr>
                <th style="width:101.7pt">Nummer</th><th style="width:222pt">Bestemming</th>
                <th style="width:55pt">Aantal</th><th style="width:58.4pt">Minuten</th>
                <th style="width:79pt">Totaal (&euro;)</th>
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
    <div class="klein" style="margin-bottom:10px">' . $klant . ' &middot; ' . htmlspecialchars($maandLabel) . ' &middot; gesorteerd op categorie</div>
    ' . $perNummer . '
</div>
</body></html>';
    }

    private static function euro(float $v): string
    {
        return '&euro; ' . number_format($v, 2, ',', '.');
    }
}
