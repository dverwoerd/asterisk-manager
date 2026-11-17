<?php
// ============================================================
// GesprekPDF — Gespreksspecificatie in de opmaak van cdr.pantronic.nl
//
//   [donkerblauw 24 mm]  Pantronic IT
//   [geel 13 mm]         Gespreksspecificaties
//   Klant:   ...
//   Periode: ...
//   Categorietabel (alle 6 categorieën): Categorie | Aantal gesprekken | Minuten | Totaal (€)
//   Specificatie per nummer (samengevoegd): Nummer | Bestemming | Aantal | Minuten | Totaal (€)
//   Voettekst grijs
// ============================================================

class GesprekPDF
{
    const BLAUW_DONKER = '#1F4E79';   // rgb(31,78,121)
    const BLAUW_MID    = '#2E75B6';   // rgb(46,117,182)
    const BLAUW_LICHT  = '#DEEAF1';   // rgb(222,234,241)
    const GEEL         = '#FFD966';   // rgb(255,217,102)
    const RAND         = '#C8D7E6';   // rgb(200,215,230)

    public static function download(string $klant, string $periode, array $spec, array $company): void
    {
        $autoload = APP_ROOT . '/vendor/autoload.php';
        if (!file_exists($autoload)) {
            throw new Exception('mPDF niet geïnstalleerd. Voer uit: composer require mpdf/mpdf');
        }
        require_once $autoload;

        $mpdf = self::maak($klant, $periode, $spec, $company);

        $bestand = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $klant) . '_'
                 . preg_replace('/[^a-zA-Z0-9\-_]/', '_', $periode) . '.pdf';

        $mpdf->Output($bestand, 'D');
        exit;
    }

    /** Losgetrokken van download() zodat het ook naar bestand kan (tests, mail). */
    public static function maak(string $klant, string $periode, array $spec, array $company): \Mpdf\Mpdf
    {
        $mpdf = new \Mpdf\Mpdf([
            'mode'              => 'c',          // core fonts: Helvetica, net als jsPDF
            'format'            => 'A4',
            'margin_top'        => 0,
            'margin_bottom'     => 16,
            'margin_left'       => 0,
            'margin_right'      => 0,
            'margin_footer'     => 8,
            'default_font'      => 'helvetica',
            'default_font_size' => 10,
        ]);
        $mpdf->SetTitle("Gespreksspecificaties $klant - $periode");
        $mpdf->SetAuthor($company['name']);

        $mpdf->SetHTMLFooter(
            '<div style="text-align:center;font-size:8pt;color:#A0A0A0">'
            . htmlspecialchars($company['name']) . ' &nbsp;&#183;&nbsp; '
            . htmlspecialchars($company['address']) . ' &nbsp;&#183;&nbsp; Tel. '
            . htmlspecialchars($company['phone'])
            . '</div>'
        );

        $mpdf->WriteHTML(self::html($klant, $periode, $spec, $company));
        return $mpdf;
    }

    private static function html(string $klant, string $periode, array $spec, array $company): string
    {
        $bd = self::BLAUW_DONKER; $bm = self::BLAUW_MID; $bl = self::BLAUW_LICHT;
        $ge = self::GEEL;         $ra = self::RAND;

        $css = "
        body  { font-family: helvetica; font-size: 10pt; color: #1E1E1E; }
        table { border-collapse: collapse; }

        .kop  { width: 100%; background: $bd; }
        .kop td { height: 24mm; text-align: center; vertical-align: middle;
                  color: #FFFFFF; font-size: 17pt; font-weight: bold; }
        .balk { width: 100%; background: $ge; }
        .balk td { height: 13mm; text-align: center; vertical-align: middle;
                   color: $bd; font-size: 13pt; font-weight: bold; }

        .info { margin: 9mm 14mm 0 14mm; font-size: 11pt; }
        .info td { padding: 0 0 3mm 0; }

        .tbl { width: 182mm; margin: 4mm 14mm 0 14mm; border: 0.2mm solid $ra; }
        .tbl th { background: $bm; color: #FFFFFF; font-weight: bold; font-size: 10pt;
                  text-align: center; padding: 5mm; }
        .tbl td { font-size: 10pt; padding: 5mm; }
        .tbl tr.alt td { background: $bl; }
        .tbl tr.voet td { background: $ge; color: $bd; font-weight: bold; }
        .l { text-align: left; } .c { text-align: center; } .r { text-align: right; }

        .titel { margin: 9mm 14mm 0 14mm; font-size: 11pt; font-weight: bold; color: $bd; }
        .tbl.nrs th { padding: 2.5mm 3mm; font-size: 9pt; }
        .tbl.nrs td { padding: 2mm 3mm; font-size: 9pt; }
        .klein { font-size: 7.5pt; color: #637080; }

        ";

        // ---- Categorietabel (alle 6, zoals cdr.pantronic.nl) ----
        $rijen = '';
        $i = 0;
        foreach ($spec['categorieen'] as $cat => $g) {
            $rijen .= '<tr' . ($i++ % 2 ? ' class="alt"' : '') . '>'
                . '<td class="l" style="width:62mm">' . htmlspecialchars($cat) . '</td>'
                . '<td class="c">' . $g['aantal'] . '</td>'
                . '<td class="c">' . $g['minuten'] . '</td>'
                . '<td class="r">' . self::euro($g['kosten']) . '</td>'
                . '</tr>';
        }
        $catTabel = '
        <table class="tbl">
            <thead><tr>
                <th>Categorie</th><th>Aantal gesprekken</th><th>Minuten</th><th>Totaal (&euro;)</th>
            </tr></thead>
            <tbody>' . $rijen . '</tbody>
            <tfoot><tr class="voet">
                <td class="l" colspan="3">Totaal</td>
                <td class="r">' . self::euro($spec['totaal']['kosten']) . '</td>
            </tr></tfoot>
        </table>';

        // ---- Specificatie per nummer ----
        $nrTabel = '';
        if (!empty($spec['nummers'])) {
            $rijen = '';
            $i = 0;
            foreach ($spec['nummers'] as $n) {
                $omschr = $n['land'] !== '' ? $n['land'] : $n['categorie'];
                $rijen .= '<tr' . ($i++ % 2 ? ' class="alt"' : '') . '>'
                    . '<td class="l">' . htmlspecialchars($n['nummer']) . '</td>'
                    . '<td class="l">' . htmlspecialchars($omschr)
                    . '<br><span class="klein">Toestel ' . htmlspecialchars(implode(', ', $n['toestellen'])) . '</span></td>'
                    . '<td class="c">' . $n['aantal'] . '</td>'
                    . '<td class="c">' . $n['minuten'] . '</td>'
                    . '<td class="r">' . self::euro($n['kosten']) . '</td>'
                    . '</tr>';
            }
            $nrTabel = '
            <div class="titel">Specificatie per nummer</div>
            <table class="tbl nrs" style="margin-top:3mm">
                <thead><tr>
                    <th class="l" style="width:36mm">Nummer</th>
                    <th class="l">Bestemming</th>
                    <th style="width:20mm">Aantal</th>
                    <th style="width:20mm">Minuten</th>
                    <th class="r" style="width:28mm">Totaal (&euro;)</th>
                </tr></thead>
                <tbody>' . $rijen . '</tbody>
                <tfoot><tr class="voet">
                    <td class="l" colspan="2">Totaal</td>
                    <td class="c">' . $spec['totaal']['aantal'] . '</td>
                    <td class="c">' . $spec['totaal']['minuten'] . '</td>
                    <td class="r">' . self::euro($spec['totaal']['kosten']) . '</td>
                </tr></tfoot>
            </table>';
        }

        return '<html><head><style>' . $css . '</style></head><body>
            <table class="kop"><tr><td>' . htmlspecialchars($company['name']) . '</td></tr></table>
            <table class="balk"><tr><td>Gespreksspecificaties</td></tr></table>
            <table class="info">
                <tr><td style="width:20mm">Klant:</td><td>' . htmlspecialchars($klant) . '</td></tr>
                <tr><td>Periode:</td><td>' . htmlspecialchars($periode) . '</td></tr>
            </table>
            ' . $catTabel . $nrTabel . '
        </body></html>';
    }

    private static function euro(float $v): string
    {
        return '&euro; ' . number_format($v, 2, ',', '.');
    }
}
