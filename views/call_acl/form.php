<?php $isEdit = ($action === 'edit'); ?>
<div class="page-header">
    <h1 class="page-title"><?= $title ?></h1>
    <a href="?page=call_acl" class="btn btn-ghost">← <?= t('back') ?></a>
</div>

<form method="POST" action="?page=call_acl&action=post_<?= $action ?><?= $isEdit ? '&id='.$group['id'] : '' ?>">
    <?= csrf() ?>
    <div class="form-layout">
        <div class="form-col">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Groep</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label><?= t('name') ?> *</label>
                        <input type="text" name="name" class="form-control" value="<?= sanitize($group['name']) ?>" required placeholder="Campro">
                    </div>
                    <div class="form-group">
                        <label>Toestellen in deze groep</label>
                        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:6px">
                            <?php foreach ($extensions as $e): ?>
                            <label class="toggle-label" style="display:flex;gap:8px;align-items:center">
                                <input type="checkbox" name="members[]" value="<?= sanitize($e['extension']) ?>" <?= in_array($e['extension'], $members) ? 'checked' : '' ?>>
                                <span class="mono"><?= sanitize($e['extension']) ?></span> <span class="text-sm"><?= sanitize($e['full_name'] ?? '') ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <small class="form-hint">Leden mogen alleen onderling bellen.</small>
                    </div>
                    <div class="form-group">
                        <label>Ook toegestaan (extensies, komma-gescheiden)</label>
                        <input type="text" name="allowed_extras" class="form-control mono" value="<?= sanitize($group['allowed_extras']) ?>" placeholder="bv. 100,101">
                        <small class="form-hint">Extensies buiten de groep waarmee de leden wel mogen bellen en door gebeld mogen worden (bv. receptie).</small>
                    </div>
                    <div class="form-group">
                        <label>Notities</label>
                        <textarea name="notes" class="form-control" rows="2"><?= sanitize($group['notes'] ?? '') ?></textarea>
                    </div>
                    <div class="form-check">
                        <label class="toggle-label">
                            <input type="checkbox" name="enabled" value="1" <?= $group['enabled'] ? 'checked' : '' ?>>
                            <span class="toggle"></span><?= t('enabled') ?>
                        </label>
                    </div>
                </div>
            </div>
            <div style="margin-top:16px"><button type="submit" class="btn btn-accent"><?= t('save') ?></button></div>
        </div>
    </div>
</form>
