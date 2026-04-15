<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Invoice <?= sanitize($invoice['invoice_number']) ?></title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 13px; color: #1a1a1a; background: #fff; padding: 40px; }
.inv-header { display: flex; justify-content: space-between; margin-bottom: 40px; }
.company-name { font-size: 22px; font-weight: 700; color: #0d1117; margin-bottom: 4px; }
.company-info { color: #555; font-size: 12px; line-height: 1.7; }
.inv-number { font-size: 26px; font-weight: 700; color: #00a085; text-align: right; margin-bottom: 10px; font-family: 'Courier New', monospace; }
.meta-table { font-size: 12px; }
.meta-table td { padding: 2px 8px; }
.meta-table td:first-child { color: #888; }
.meta-table td:last-child { text-align: right; font-family: 'Courier New', monospace; }
.bill-to { margin-bottom: 30px; padding: 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; }
.section-label { font-size: 9px; letter-spacing: 0.15em; color: #999; text-transform: uppercase; margin-bottom: 6px; }
.customer-name { font-size: 15px; font-weight: 600; }
table.items { width: 100%; border-collapse: collapse; margin-bottom: 0; }
table.items th { background: #1a1a1a; color: #fff; padding: 8px 12px; font-size: 11px; text-align: left; }
table.items th:last-child { text-align: right; }
table.items td { padding: 9px 12px; border-bottom: 1px solid #f0f0f0; }
table.items td:last-child { text-align: right; font-family: 'Courier New', monospace; }
.totals-row td { padding: 6px 12px; }
.totals-row td:first-child { text-align: right; color: #666; }
.totals-row td:last-child { text-align: right; font-family: 'Courier New', monospace; min-width: 120px; }
.grand-total td { font-size: 16px; font-weight: 700; border-top: 3px solid #00a085; padding-top: 10px; }
.grand-total td:last-child { color: #00a085; }
.footer { margin-top: 40px; padding-top: 16px; border-top: 1px solid #e2e8f0; font-size: 11px; color: #888; }
.status-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
.status-paid { background: #d1fae5; color: #065f46; }
.status-draft { background: #f1f5f9; color: #475569; }
.status-sent  { background: #dbeafe; color: #1e40af; }
@media print {
    body { padding: 20px; }
    @page { margin: 15mm; }
}
</style>
</head>
<body>
<div class="inv-header">
    <div>
        <div class="company-name"><?= sanitize($company['name']) ?></div>
        <div class="company-info">
            <?= nl2br(sanitize($company['address'])) ?><br>
            <?php if ($company['vat']): ?>VAT: <?= sanitize($company['vat']) ?><br><?php endif; ?>
            <?php if ($company['email']): ?><?= sanitize($company['email']) ?><?php endif; ?>
            <?php if ($company['phone']): ?> · <?= sanitize($company['phone']) ?><?php endif; ?>
        </div>
    </div>
    <div>
        <div class="inv-number">INVOICE<br><?= sanitize($invoice['invoice_number']) ?></div>
        <table class="meta-table">
            <tr><td>Date</td><td><?= formatDate($invoice['issue_date']) ?></td></tr>
            <tr><td>Due Date</td><td><?= formatDate($invoice['due_date']) ?></td></tr>
            <tr><td>Period</td><td><?= formatDate($invoice['period_start']) ?> – <?= formatDate($invoice['period_end']) ?></td></tr>
            <tr><td>Status</td><td><span class="status-badge status-<?= $invoice['status'] ?>"><?= $invoice['status'] ?></span></td></tr>
        </table>
    </div>
</div>

<div class="bill-to">
    <div class="section-label">Bill To</div>
    <div class="customer-name"><?= sanitize($invoice['company_name'] ?: $invoice['contact_name']) ?></div>
    <div><?= sanitize($invoice['contact_name']) ?></div>
    <?php if ($invoice['address']): ?><div><?= nl2br(sanitize($invoice['address'])) ?></div><?php endif; ?>
    <?php if ($invoice['vat_number']): ?><div>VAT: <?= sanitize($invoice['vat_number']) ?></div><?php endif; ?>
</div>

<table class="items">
    <thead>
        <tr><th>Description</th><th>Quantity (min)</th><th>Unit Price</th><th>Total</th></tr>
    </thead>
    <tbody>
    <?php foreach ($items as $item): ?>
    <tr>
        <td><?= sanitize($item['description']) ?></td>
        <td><?= number_format($item['quantity'],2,',','.') ?></td>
        <td>€ <?= number_format($item['unit_price'],4,',','.') ?></td>
        <td>€ <?= number_format($item['total'],2,',','.') ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr class="totals-row"><td colspan="2"></td><td>Subtotal</td><td>€ <?= number_format($invoice['subtotal'],2,',','.') ?></td></tr>
        <tr class="totals-row"><td colspan="2"></td><td>VAT <?= $invoice['tax_rate'] ?>%</td><td>€ <?= number_format($invoice['tax_amount'],2,',','.') ?></td></tr>
        <tr class="totals-row grand-total"><td colspan="2"></td><td>TOTAL <?= $invoice['currency'] ?></td><td>€ <?= number_format($invoice['total'],2,',','.') ?></td></tr>
    </tfoot>
</table>

<div class="footer">
    <?php if ($invoice['notes']): ?><p><strong>Notes:</strong> <?= nl2br(sanitize($invoice['notes'])) ?></p><?php endif; ?>
    <p style="margin-top:8px">Please transfer the amount within 30 days to the bank account stated in your agreement. Reference invoice number <?= sanitize($invoice['invoice_number']) ?>.</p>
</div>

<script>window.onload = function() { window.print(); }</script>
</body>
</html>
