<!-- views/rate_plans/rate_form.php -->
<?php $isEdit = ($action === 'edit_rate'); ?>
<div class="page-header"><h1 class="page-title"><?= $title ?></h1><a href="?page=rate_plans&action=view_plan&id=<?= $planId ?>" class="btn btn-ghost">← Back</a></div>
<form method="POST" action="?page=rate_plans&action=post_<?= $action ?><?= $isEdit ? '&id='.$rate['id'] : '' ?>">
    <?= csrf() ?>
    <input type="hidden" name="plan_id" value="<?= $planId ?>">
    <div class="card" style="max-width:680px">
        <div class="card-body">
            <div class="form-row">
                <div class="form-group"><label>Destination Name *</label><input type="text" name="destination_name" class="form-control" value="<?= sanitize($rate['destination_name']) ?>" required placeholder="e.g. Netherlands - Mobile"></div>
                <div class="form-group"><label>Number Prefix</label><input type="text" name="prefix" class="form-control mono" value="<?= sanitize($rate['prefix']) ?>" placeholder="e.g. 316 (blank = catch-all)"><small class="form-hint">Digits only. Longer prefix = higher priority.</small></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Rate per Minute *</label><div class="input-group"><span style="padding:7px 10px;background:var(--bg);border:1px solid var(--border);border-right:none;border-radius:4px 0 0 4px;color:var(--text-muted);font-size:13px">€</span><input type="text" id="rate_per_minute" name="rate_per_minute" class="form-control mono" value="<?= number_format($rate['rate_per_minute'],4,',','.') ?>" required placeholder="0,0200"></div><small class="form-hint" id="ratePreview"></small></div>
                <div class="form-group"><label>Connection Fee</label><div class="input-group"><span style="padding:7px 10px;background:var(--bg);border:1px solid var(--border);border-right:none;border-radius:4px 0 0 4px;color:var(--text-muted);font-size:13px">€</span><input type="text" name="connection_fee" class="form-control mono" value="<?= number_format($rate['connection_fee'],4,',','.') ?>" placeholder="0,0000"></div></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Billing Increment (sec)</label><input type="number" name="billing_increment" class="form-control" value="<?= (int)$rate['billing_increment'] ?>" min="1" max="3600"></div>
                <div class="form-group"><label>Notes</label><input type="text" name="notes" class="form-control" value="<?= sanitize($rate['notes']) ?>"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Valid From (time)</label><input type="time" name="time_start" class="form-control" value="<?= $rate['time_start'] ?? '00:00:00' ?>"></div>
                <div class="form-group"><label>Valid Until (time)</label><input type="time" name="time_end" class="form-control" value="<?= $rate['time_end'] ?? '23:59:59' ?>"></div>
            </div>
        </div>
    </div>
    <div class="form-actions"><a href="?page=rate_plans&action=view_plan&id=<?= $planId ?>" class="btn btn-ghost">Cancel</a><button type="submit" class="btn btn-accent">💾 Save Rate</button></div>
</form>
