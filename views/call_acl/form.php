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
                        <style>
                        .acl-ext{display:flex;gap:8px;align-items:center;padding:8px 10px;border:1px solid var(--border,#2a2f3a);border-radius:6px;cursor:pointer;transition:background .15s,border-color .15s}
                        .acl-ext:hover{border-color:var(--accent)}
                        .acl-ext input{accent-color:var(--accent)}
                        .acl-ext.on{background:rgba(255,255,255,.07);border-color:var(--accent);box-shadow:inset 3px 0 0 var(--accent)}
                        </style>
                        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:6px">
                            <?php foreach ($extensions as $e): $on = in_array($e['extension'], $members); ?>
                            <label class="acl-ext<?= $on ? ' on' : '' ?>">
                                <input type="checkbox" name="members[]" value="<?= sanitize($e['extension']) ?>" <?= $on ? 'checked' : '' ?>
                                       onchange="this.parentNode.classList.toggle('on', this.checked)">
                                <span class="mono font-bold"><?= sanitize($e['extension']) ?></span> <span class="text-sm"><?= sanitize($e['full_name'] ?? '') ?></span>
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
