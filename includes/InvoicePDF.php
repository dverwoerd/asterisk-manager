<?php
// ============================================================
// InvoicePDF - Genereert PDF facturen via mPDF
// Installeer mPDF via: composer require mpdf/mpdf
// ============================================================

class InvoicePDF
{
    public static function generate(array $invoice, array $items, array $company): string
    {
        // Controleer of mPDF beschikbaar is
        $autoload = APP_ROOT . '/vendor/autoload.php';
        if (!file_exists($autoload)) {
            throw new Exception(
                'mPDF niet geïnstalleerd. Voer uit: composer require mpdf/mpdf'
            );
        }
        require_once $autoload;

        $mpdf = new \Mpdf\Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4',
            'margin_top'    => 15,
            'margin_bottom' => 15,
            'margin_left'   => 15,
            'margin_right'  => 15,
            'default_font'  => 'dejavusans',
        ]);

        $mpdf->SetTitle('Factuur ' . $invoice['invoice_number']);
        $mpdf->SetAuthor($company['name'] ?? 'Asterisk Manager');

        $html = self::buildHTML($invoice, $items, $company);
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', 'S'); // Return als string
    }

    public static function download(array $invoice, array $items, array $company): void
    {
        $autoload = APP_ROOT . '/vendor/autoload.php';
        if (!file_exists($autoload)) {
            throw new Exception('mPDF niet geïnstalleerd. Voer uit: composer require mpdf/mpdf');
        }
        require_once $autoload;

        $mpdf = new \Mpdf\Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4',
            'margin_top'    => 20,
            'margin_bottom' => 20,
            'margin_left'   => 20,
            'margin_right'  => 20,
            'default_font'  => 'dejavusans',
        ]);

        $mpdf->SetTitle('Factuur ' . $invoice['invoice_number']);
        $mpdf->SetAuthor($company['name'] ?? '');

        $html = self::buildHTML($invoice, $items, $company);
        $mpdf->WriteHTML($html);
        $mpdf->Output('factuur-' . $invoice['invoice_number'] . '.pdf', 'D');
        exit;
    }

    private static function buildHTML(array $invoice, array $items, array $company): string
    {
        $statusLabels = [
            'draft'     => 'Concept',
            'sent'      => 'Verzonden',
            'paid'      => 'Betaald',
            'overdue'   => 'Achterstallig',
            'cancelled' => 'Geannuleerd',
        ];
        $statusColors = [
            'draft'     => '#64748b',
            'sent'      => '#3b82f6',
            'paid'      => '#22c55e',
            'overdue'   => '#ef4444',
            'cancelled' => '#94a3b8',
        ];

        $status      = $invoice['status'] ?? 'draft';
        $statusLabel = $statusLabels[$status] ?? $status;
        $statusColor = $statusColors[$status] ?? '#64748b';

        // Bouw items tabel
        $itemRows = '';
        foreach ($items as $item) {
            $itemRows .= sprintf(
                '<tr>
                    <td class="desc">%s</td>
                    <td class="num">%s</td>
                    <td class="num">€ %s</td>
                    <td class="num total">€ %s</td>
                </tr>',
                htmlspecialchars($item['description']),
                number_format((float)$item['quantity'], 2, ',', '.'),
                number_format((float)$item['unit_price'], 4, ',', '.'),
                number_format((float)$item['total'], 2, ',', '.')
            );
        }

        $logoHtml = '';
        $logoPath = getSetting('logo_path', '');
        if ($logoPath && file_exists($logoPath)) {
            $logoData = base64_encode(file_get_contents($logoPath));
            $logoMime = mime_content_type($logoPath);
            $logoHtml = '<img src="data:' . $logoMime . ';base64,' . $logoData . '" style="max-height:60px;max-width:200px;">';
        }

        $html = '
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: DejaVuSans, sans-serif;
        font-size: 10pt;
        color: #1a1a2e;
        line-height: 1.5;
    }

    /* Header */
    .invoice-header {
        display: table;
        width: 100%;
        margin-bottom: 30px;
    }
    .header-left {
        display: table-cell;
        width: 55%;
        vertical-align: top;
    }
    .header-right {
        display: table-cell;
        width: 45%;
        vertical-align: top;
        text-align: right;
    }
    .company-name {
        font-size: 18pt;
        font-weight: bold;
        color: #0d1117;
        margin-bottom: 4px;
    }
    .company-info {
        font-size: 9pt;
        color: #64748b;
        line-height: 1.6;
    }
    .invoice-title {
        font-size: 22pt;
        font-weight: bold;
        color: #00a085;
        margin-bottom: 8px;
    }
    .invoice-number {
        font-size: 11pt;
        color: #1a1a2e;
        margin-bottom: 12px;
    }
    .status-badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 4px;
        font-size: 8pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: white;
        background: ' . $statusColor . ';
    }

    /* Meta tabel */
    .meta-table {
        width: 100%;
        margin-top: 8px;
        border-collapse: collapse;
    }
    .meta-table td {
        padding: 2px 4px;
        font-size: 9pt;
    }
    .meta-table td:first-child {
        color: #64748b;
        width: 100px;
    }
    .meta-table td:last-child {
        text-align: right;
        font-weight: bold;
    }

    /* Divider */
    .divider {
        border: none;
        border-top: 2px solid #e2e8f0;
        margin: 20px 0;
    }
    .divider-accent {
        border-top-color: #00a085;
    }

    /* Bill To */
    .bill-to-label {
        font-size: 7pt;
        text-transform: uppercase;
        letter-spacing: 0.15em;
        color: #94a3b8;
        margin-bottom: 4px;
    }
    .bill-to-name {
        font-size: 12pt;
        font-weight: bold;
        color: #1a1a2e;
        margin-bottom: 2px;
    }
    .bill-to-info {
        font-size: 9pt;
        color: #475569;
        line-height: 1.6;
    }

    /* Items tabel */
    .items-table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
    }
    .items-table th {
        background: #1a1a2e;
        color: white;
        padding: 8px 10px;
        font-size: 8pt;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }
    .items-table th.num { text-align: right; }
    .items-table td {
        padding: 9px 10px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 9.5pt;
        vertical-align: top;
    }
    .items-table td.num { text-align: right; }
    .items-table td.total { font-weight: bold; }
    .items-table td.desc { color: #334155; }
    .items-table tr:last-child td { border-bottom: none; }
    .items-table tr:nth-child(even) td { background: #f8fafc; }

    /* Totalen */
    .totals-wrap {
        display: table;
        width: 100%;
        margin-top: 10px;
    }
    .totals-spacer {
        display: table-cell;
        width: 55%;
    }
    .totals-table-wrap {
        display: table-cell;
        width: 45%;
        vertical-align: top;
    }
    .totals-table {
        width: 100%;
        border-collapse: collapse;
    }
    .totals-table td {
        padding: 5px 10px;
        font-size: 9.5pt;
    }
    .totals-table td:first-child { color: #64748b; }
    .totals-table td:last-child {
        text-align: right;
        font-weight: bold;
    }
    .totals-table tr.total-final td {
        font-size: 13pt;
        font-weight: bold;
        padding: 10px 10px;
        border-top: 3px solid #00a085;
        color: #00a085;
    }
    .totals-table tr.total-final td:first-child { color: #1a1a2e; }

    /* Footer */
    .invoice-footer {
        margin-top: 30px;
        padding-top: 15px;
        border-top: 1px solid #e2e8f0;
        font-size: 8.5pt;
        color: #94a3b8;
        text-align: center;
    }
    .notes-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 4px;
        padding: 10px 14px;
        font-size: 9pt;
        color: #475569;
        margin-top: 15px;
    }
    .notes-label {
        font-weight: bold;
        color: #334155;
        margin-bottom: 3px;
    }
</style>
</head>
<body>

<!-- Header -->
<div class="invoice-header">
    <div class="header-left">
        ' . $logoHtml . '
        <div class="company-name">' . htmlspecialchars($company['name'] ?? '') . '</div>
        <div class="company-info">
            ' . nl2br(htmlspecialchars($company['address'] ?? '')) . '<br>
            ' . (!empty($company['vat']) ? 'BTW: ' . htmlspecialchars($company['vat']) . '<br>' : '') . '
            ' . (!empty($company['email']) ? htmlspecialchars($company['email']) : '') . '
            ' . (!empty($company['phone']) ? ' &nbsp;|&nbsp; ' . htmlspecialchars($company['phone']) : '') . '
        </div>
    </div>
    <div class="header-right">
        <div class="invoice-title">FACTUUR</div>
        <div class="invoice-number">' . htmlspecialchars($invoice['invoice_number']) . '</div>
        <span class="status-badge">' . $statusLabel . '</span>
        <table class="meta-table">
            <tr>
                <td>Factuurdatum</td>
                <td>' . date('d-m-Y', strtotime($invoice['issue_date'])) . '</td>
            </tr>
            <tr>
                <td>Vervaldatum</td>
                <td>' . date('d-m-Y', strtotime($invoice['due_date'])) . '</td>
            </tr>
            <tr>
                <td>Periode</td>
                <td>' . date('d-m-Y', strtotime($invoice['period_start'])) . ' t/m ' . date('d-m-Y', strtotime($invoice['period_end'])) . '</td>
            </tr>
        </table>
    </div>
</div>

<hr class="divider divider-accent">

<!-- Bill To -->
<div class="bill-to-label">Aan</div>
<div class="bill-to-name">' . htmlspecialchars($invoice['company_name'] ?: ($invoice['contact_name'] ?? '')) . '</div>
<div class="bill-to-info">
    ' . htmlspecialchars($invoice['contact_name'] ?? '') . '<br>
    ' . nl2br(htmlspecialchars($invoice['address'] ?? '')) . '
    ' . (!empty($invoice['vat_number']) ? '<br>BTW: ' . htmlspecialchars($invoice['vat_number']) : '') . '
</div>

<!-- Items -->
<table class="items-table">
    <thead>
        <tr>
            <th style="text-align:left;width:55%">Omschrijving</th>
            <th class="num" style="width:15%">Minuten</th>
            <th class="num" style="width:15%">Tarief/min</th>
            <th class="num" style="width:15%">Bedrag</th>
        </tr>
    </thead>
    <tbody>
        ' . $itemRows . '
    </tbody>
</table>

<!-- Totalen -->
<div class="totals-wrap">
    <div class="totals-spacer"></div>
    <div class="totals-table-wrap">
        <table class="totals-table">
            <tr>
                <td>Subtotaal</td>
                <td>€ ' . number_format((float)$invoice['subtotal'], 2, ',', '.') . '</td>
            </tr>
            <tr>
                <td>BTW ' . $invoice['tax_rate'] . '%</td>
                <td>€ ' . number_format((float)$invoice['tax_amount'], 2, ',', '.') . '</td>
            </tr>
            <tr class="total-final">
                <td>TOTAAL ' . htmlspecialchars($invoice['currency'] ?? 'EUR') . '</td>
                <td>€ ' . number_format((float)$invoice['total'], 2, ',', '.') . '</td>
            </tr>
        </table>
    </div>
</div>

' . (!empty($invoice['notes']) ? '
<div class="notes-box">
    <div class="notes-label">Notities</div>
    ' . nl2br(htmlspecialchars($invoice['notes'])) . '
</div>' : '') . '

<!-- Footer -->
<div class="invoice-footer">
    Gelieve het bedrag binnen 30 dagen over te maken onder vermelding van factuurnummer
    <strong>' . htmlspecialchars($invoice['invoice_number']) . '</strong>
    ' . (!empty($company['name']) ? '&nbsp;&bull;&nbsp; ' . htmlspecialchars($company['name']) : '') . '
    ' . (!empty($company['vat']) ? '&nbsp;&bull;&nbsp; BTW: ' . htmlspecialchars($company['vat']) : '') . '
</div>

</body>
</html>';

        return $html;
    }
}
