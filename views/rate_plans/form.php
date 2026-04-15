<!-- views/rate_plans/form.php -->
<?php $isEdit = ($action === 'edit'); ?>
<div class="page-header"><h1 class="page-title"><?= $title ?></h1><a href="?page=rate_plans" class="btn btn-ghost">← Back</a></div>
<form method="POST" action="?page=rate_plans&action=post_<?= $action ?><?= $isEdit ? '&id='.$plan['id'] : '' ?>">
    <?= csrf() ?>
    <div class="card" style="max-width:600px">
        <div class="card-body">
            <div class="form-group"><label>Plan Name *</label><input type="text" name="name" class="form-control" value="<?= sanitize($plan['name']) ?>" required placeholder="e.g. Standard, Premium, Reseller"></div>
            <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="2"><?= sanitize($plan['description']) ?></textarea></div>
            <div class="form-row">
                <div class="form-group"><label>Currency</label><select name="currency" class="form-control"><option value="EUR" <?= $plan['currency']==='EUR'?'selected':'' ?>>EUR €</option><option value="USD" <?= $plan['currency']==='USD'?'selected':'' ?>>USD $</option><option value="GBP" <?= $plan['currency']==='GBP'?'selected':'' ?>>GBP £</option></select></div>
                <div class="form-group"><label>Default Billing Increment (sec)</label><input type="number" name="billing_increment" class="form-control" value="<?= (int)$plan['billing_increment'] ?>" min="1" max="3600"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Minimum Duration (sec)</label><input type="number" name="minimum_duration" class="form-control" value="<?= (int)$plan['minimum_duration'] ?>" min="0"></div>
                <div class="form-group"><label>Default Connection Fee</label><input type="text" name="connection_fee" class="form-control mono" value="<?= number_format($plan['connection_fee'],4,',','.') ?>" placeholder="0,0000"></div>
            </div>
            <div class="form-check"><label class="toggle-label"><input type="checkbox" name="active" value="1" <?= $plan['active']?'checked':'' ?>><span class="toggle"></span>Active</label></div>
        </div>
    </div>
    <div class="form-actions"><a href="?page=rate_plans" class="btn btn-ghost">Cancel</a><button type="submit" class="btn btn-accent">💾 Save</button></div>
</form>
