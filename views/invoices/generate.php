<!-- views/invoices/generate.php -->
<div class="page-header">
    <h1 class="page-title"><?= t('generate_invoice') ?></h1>
    <a href="?page=invoices" class="btn btn-ghost">← Back</a>
</div>
<div class="card" style="max-width:640px">
    <div class="card-header"><h3 class="card-title">Generate Invoice from CDR</h3></div>
    <div class="card-body">
        <form method="POST" action="?page=invoices&action=post_generate">
            <?= csrf() ?>
            <div class="form-group">
                <label>Customer *</label>
                <select name="customer_id" class="form-control" required>
                    <option value="">Select customer...</option>
                    <?php foreach ($customers as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= sanitize($c['company_name'] ?: $c['contact_name']) ?> (<?= sanitize($c['extensions_csv'] ?: 'no extensions') ?>)</option>
                    <?php endforeach; ?>
                </select>
                <small class="form-hint">Make sure the customer has extensions assigned.</small>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Period Start *</label>
                    <input type="date" name="date_from" class="form-control" value="<?= $dateFrom ?>" required>
                </div>
                <div class="form-group">
                    <label>Period End *</label>
                    <input type="date" name="date_to" class="form-control" value="<?= $dateTo ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label>VAT Rate (%)</label>
                <input type="number" name="tax_rate" class="form-control" value="<?= getSetting('default_tax_rate', 21) ?>" min="0" max="100" step="0.5">
            </div>
            <div class="card mt-4" style="border-color:var(--info)">
                <div class="card-body" style="padding:12px 16px">
                    <p class="text-sm" style="color:var(--info)">ℹ️ The system will:</p>
                    <ul class="text-sm text-muted" style="margin:6px 0 0 16px;list-style:disc;line-height:2">
                        <li>Find all answered CDR records for the customer's extensions in the selected period</li>
                        <li>Apply the customer's rate plan to calculate costs</li>
                        <li>Group calls by destination type</li>
                        <li>Create a draft invoice with line items</li>
                        <li>Mark the CDR records as invoiced</li>
                    </ul>
                </div>
            </div>
            <div class="form-actions">
                <a href="?page=invoices" class="btn btn-ghost">Cancel</a>
                <button type="submit" class="btn btn-accent">⚡ Generate Invoice</button>
            </div>
        </form>
    </div>
</div>
