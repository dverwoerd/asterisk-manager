<!-- views/invoices/view.php -->
<div class="page-header">
    <h1 class="page-title"><?= t('invoice') ?>: <?= sanitize($invoice['invoice_number']) ?></h1>
    <div class="page-actions">
        <a href="?page=invoices" class="btn btn-ghost">← Back</a>
        <a href="?page=invoices&action=print&id=<?= $invoice['id'] ?>" class="btn btn-ghost" target="_blank">🖨 Print</a>
        <a href="?page=invoices&action=pdf&id=<?= $invoice['id'] ?>" class="btn btn-accent">⬇ Download PDF</a>
        <a href="?page=invoices&action=delete&id=<?= $invoice['id'] ?>"
           class="btn btn-sm btn-danger-ghost"
           onclick="return confirm('Factuur <?= sanitize($invoice['invoice_number']) ?> verwijderen? De CDR records worden ook vrijgegeven.')">✕ Verwijderen</a>
        <?php if ($invoice['status'] !== 'paid'): ?>
        <form method="POST" action="?page=invoices&action=update_status&id=<?= $invoice['id'] ?>" style="display:inline">
            <?= csrf() ?><input type="hidden" name="status" value="sent">
            <button class="btn btn-ghost">Mark Sent</button>
        </form>
        <form method="POST" action="?page=invoices&action=update_status&id=<?= $invoice['id'] ?>" style="display:inline">
            <?= csrf() ?><input type="hidden" name="status" value="paid">
            <button class="btn btn-accent">✓ Mark Paid</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<div class="invoice-preview">
    <div class="invoice-header">
        <div class="invoice-from">
            <div class="invoice-company"><?= sanitize($company['name']) ?></div>
            <div class="invoice-address"><?= nl2br(sanitize($company['address'])) ?></div>
            <?php if ($company['vat']): ?><div>VAT: <?= sanitize($company['vat']) ?></div><?php endif; ?>
            <?php if ($company['email']): ?><div><?= sanitize($company['email']) ?></div><?php endif; ?>
        </div>
        <div class="invoice-meta">
            <div class="invoice-number-big"><?= sanitize($invoice['invoice_number']) ?></div>
            <table class="invoice-meta-table">
                <tr><td>Date</td><td><?= formatDate($invoice['issue_date']) ?></td></tr>
                <tr><td>Due</td><td><?= formatDate($invoice['due_date']) ?></td></tr>
                <tr><td>Period</td><td><?= formatDate($invoice['period_start']) ?> – <?= formatDate($invoice['period_end']) ?></td></tr>
                <tr><td>Status</td><td><span class="badge badge-<?= ['draft'=>'default','sent'=>'info','paid'=>'success','overdue'=>'danger'][$invoice['status']]??'default' ?>"><?= t($invoice['status']) ?></span></td></tr>
            </table>
        </div>
    </div>

    <div class="invoice-bill-to">
        <div class="invoice-section-label">BILL TO</div>
        <div class="invoice-customer-name"><?= sanitize($invoice['company_name'] ?: $invoice['contact_name']) ?></div>
        <div><?= sanitize($invoice['contact_name']) ?></div>
        <div><?= nl2br(sanitize($invoice['address'] ?? '')) ?></div>
        <?php if ($invoice['vat_number']): ?><div>VAT: <?= sanitize($invoice['vat_number']) ?></div><?php endif; ?>
        <?php if ($invoice['email']): ?><div><?= sanitize($invoice['email']) ?></div><?php endif; ?>
    </div>

    <table class="invoice-items-table">
        <thead>
            <tr>
                <th>Description</th>
                <th class="text-right">Qty (min)</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item): ?>
        <tr>
            <td><?= sanitize($item['description']) ?></td>
            <td class="mono text-right"><?= number_format($item['quantity'],2,',','.') ?></td>
            <td class="mono text-right">€ <?= number_format($item['unit_price'],4,',','.') ?></td>
            <td class="mono text-right font-bold">€ <?= number_format($item['total'],2,',','.') ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="invoice-subtotal">
                <td colspan="3" class="text-right">Subtotal</td>
                <td class="mono text-right">€ <?= number_format($invoice['subtotal'],2,',','.') ?></td>
            </tr>
            <tr>
                <td colspan="3" class="text-right">VAT <?= $invoice['tax_rate'] ?>%</td>
                <td class="mono text-right">€ <?= number_format($invoice['tax_amount'],2,',','.') ?></td>
            </tr>
            <tr class="invoice-total">
                <td colspan="3" class="text-right font-bold">TOTAL <?= $invoice['currency'] ?></td>
                <td class="mono text-right font-bold invoice-total-amount">€ <?= number_format($invoice['total'],2,',','.') ?></td>
            </tr>
        </tfoot>
    </table>

    <?php if ($invoice['notes']): ?>
    <div class="invoice-notes">
        <strong>Notes:</strong><br>
        <?= nl2br(sanitize($invoice['notes'])) ?>
    </div>
    <?php endif; ?>
</div>
