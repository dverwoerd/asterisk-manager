<!-- views/customers/form.php -->
<?php
$isEdit  = ($action === 'edit');
$mine    = array_filter(array_map('trim', explode(',', $customer['extensions_csv'] ?? '')));
$known   = array_column($extensions, 'extension');
$extra   = array_diff($mine, $known); // nummers die geen actief toestel zijn
?>
<div class="page-header"><h1 class="page-title"><?= $title ?></h1><a href="?page=customers" class="btn btn-ghost">← <?= t('back') ?></a></div>
<form method="POST" action="?page=customers&action=post_<?= $action ?><?= $isEdit ? '&id='.$customer['id'] : '' ?>">
    <?= csrf() ?>
    <div class="form-layout">
        <div class="form-col">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Klantgegevens</h3></div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group"><label>Bedrijfsnaam</label><input type="text" name="company_name" class="form-control" value="<?= sanitize($customer['company_name'] ?? '') ?>"></div>
                        <div class="form-group"><label>Contactpersoon *</label><input type="text" name="contact_name" class="form-control" value="<?= sanitize($customer['contact_name'] ?? '') ?>" required></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>E-mail</label><input type="email" name="email" class="form-control" value="<?= sanitize($customer['email'] ?? '') ?>"></div>
                        <div class="form-group"><label>Telefoon</label><input type="tel" name="phone" class="form-control mono" value="<?= sanitize($customer['phone'] ?? '') ?>"></div>
                    </div>
                    <div class="form-group"><label>Adres</label><textarea name="address" class="form-control" rows="2"><?= sanitize($customer['address'] ?? '') ?></textarea></div>
                    <div class="form-row">
                        <div class="form-group"><label>Postcode</label><input type="text" name="postal_code" class="form-control mono" value="<?= sanitize($customer['postal_code'] ?? '') ?>"></div>
                        <div class="form-group"><label>Plaats</label><input type="text" name="city" class="form-control" value="<?= sanitize($customer['city'] ?? '') ?>"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Land</label><input type="text" name="country" class="form-control" value="<?= sanitize($customer['country'] ?? 'Nederland') ?>"></div>
                        <div class="form-group"><label>BTW-nummer</label><input type="text" name="vat_number" class="form-control mono" value="<?= sanitize($customer['vat_number'] ?? '') ?>"></div>
                    </div>
                    <div class="form-group"><label>Notities</label><textarea name="notes" class="form-control" rows="2"><?= sanitize($customer['notes'] ?? '') ?></textarea></div>
                </div>
            </div>
        </div>
        <div class="form-col">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Toestellen van deze klant</h3></div>
                <div class="card-body">
                    <style>
                    .acl-ext{display:flex;gap:8px;align-items:center;padding:8px 10px;border:1px solid var(--border,#2a2f3a);border-radius:6px;cursor:pointer;transition:background .15s,border-color .15s}
                    .acl-ext:hover{border-color:var(--accent)}
                    .acl-ext input{accent-color:var(--accent)}
                    .acl-ext.on{background:rgba(255,255,255,.07);border-color:var(--accent);box-shadow:inset 3px 0 0 var(--accent)}
                    </style>
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:6px">
                        <?php foreach ($extensions as $e): $on = in_array($e['extension'], $mine); ?>
                        <label class="acl-ext<?= $on ? ' on' : '' ?>">
                            <input type="checkbox" name="ext[]" value="<?= sanitize($e['extension']) ?>" <?= $on ? 'checked' : '' ?>
                                   onchange="this.parentNode.classList.toggle('on', this.checked)">
                            <span class="mono font-bold"><?= sanitize($e['extension']) ?></span> <span class="text-sm"><?= sanitize($e['full_name'] ?? '') ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="form-group" style="margin-top:14px">
                        <label>Overige nummers (komma-gescheiden)</label>
                        <input type="text" name="extensions_extra" class="form-control mono" value="<?= sanitize(implode(',', $extra)) ?>" placeholder="bv. oud toestel of DID">
                        <small class="form-hint">Gesprekken van deze toestellen komen op de specificatie. Het caller-ID-nummer van elk toestel wordt automatisch meegenomen.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="form-actions"><a href="?page=customers" class="btn btn-ghost"><?= t('cancel') ?></a><button type="submit" class="btn btn-accent"><?= t('save') ?></button></div>
</form>
