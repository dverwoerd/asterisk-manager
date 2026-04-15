<!-- views/customers/form.php -->
<?php $isEdit = ($action === 'edit'); ?>
<div class="page-header"><h1 class="page-title"><?= $title ?></h1><a href="?page=customers" class="btn btn-ghost">← Back</a></div>
<form method="POST" action="?page=customers&action=post_<?= $action ?><?= $isEdit ? '&id='.$customer['id'] : '' ?>">
    <?= csrf() ?>
    <div class="form-layout">
        <div class="form-col">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Customer Details</h3></div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group"><label>Company Name</label><input type="text" name="company_name" class="form-control" value="<?= sanitize($customer['company_name']) ?>"></div>
                        <div class="form-group"><label>Contact Name *</label><input type="text" name="contact_name" class="form-control" value="<?= sanitize($customer['contact_name']) ?>" required></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" value="<?= sanitize($customer['email']) ?>"></div>
                        <div class="form-group"><label>Phone</label><input type="tel" name="phone" class="form-control mono" value="<?= sanitize($customer['phone']) ?>"></div>
                    </div>
                    <div class="form-group"><label>Address</label><textarea name="address" class="form-control" rows="2"><?= sanitize($customer['address']) ?></textarea></div>
                    <div class="form-row">
                        <div class="form-group"><label>City</label><input type="text" name="city" class="form-control" value="<?= sanitize($customer['city']) ?>"></div>
                        <div class="form-group"><label>Postal Code</label><input type="text" name="postal_code" class="form-control mono" value="<?= sanitize($customer['postal_code']) ?>"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Country</label><input type="text" name="country" class="form-control" value="<?= sanitize($customer['country']) ?>"></div>
                        <div class="form-group"><label>VAT Number</label><input type="text" name="vat_number" class="form-control mono" value="<?= sanitize($customer['vat_number']) ?>"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="form-col">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Billing Settings</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Rate Plan</label>
                        <select name="rate_plan_id" class="form-control">
                            <?php foreach ($ratePlans as $rp): ?>
                            <option value="<?= $rp['id'] ?>" <?= $customer['rate_plan_id']==$rp['id']?'selected':'' ?>><?= sanitize($rp['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Assigned Extensions</label>
                        <input type="text" name="extensions_csv" class="form-control mono" value="<?= sanitize($customer['extensions_csv']) ?>" placeholder="e.g. 1001,1002,1003">
                        <small class="form-hint">Comma-separated extension numbers. CDR records for these extensions are included in invoices.</small>
                    </div>
                    <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control" rows="4"><?= sanitize($customer['notes']) ?></textarea></div>
                </div>
            </div>
        </div>
    </div>
    <div class="form-actions"><a href="?page=customers" class="btn btn-ghost">Cancel</a><button type="submit" class="btn btn-accent">💾 Save</button></div>
</form>
