<!-- views/trunks/form.php -->
<?php $isEdit = ($action === 'edit'); ?>
<div class="page-header">
    <h1 class="page-title"><?= $title ?></h1>
    <a href="?page=trunks" class="btn btn-ghost">← <?= t('back') ?></a>
</div>
<form method="POST" action="?page=trunks&action=post_<?= $action ?><?= $isEdit ? '&id='.$trunk['id'] : '' ?>">
    <?= csrf() ?>
    <div class="card" style="max-width:720px">
        <div class="card-header"><h3 class="card-title">SIP/PJSIP Trunk</h3></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group">
                    <label><?= t('name') ?> *</label>
                    <input type="text" name="name" class="form-control mono" value="<?= sanitize($trunk['name']) ?>" required placeholder="e.g. sipgate-trunk" pattern="[a-zA-Z0-9_-]+">
                    <small class="form-hint">Alphanumeric, underscores and hyphens only</small>
                </div>
                <div class="form-group">
                    <label>Verbinding Type *</label>
                    <select name="trunk_type" class="form-control">
                        <option value="provider" <?= ($trunk['trunk_type'] ?? 'provider') === 'provider' ? 'selected' : '' ?>>Provider (VoIP met registratie)</option>
                        <option value="pbx" <?= ($trunk['trunk_type'] ?? '') === 'pbx' ? 'selected' : '' ?>>PBX / 3CX (directe koppeling via IP)</option>
                    </select>
                    <small class="form-hint">Provider = VoIP aanbieder met registratie. PBX = directe SIP koppeling met 3CX of andere PBX.</small>
                </div>
                <div class="form-group">
                    <label>Protocol Type</label>
                    <select name="type" class="form-control">
                        <option value="pjsip" <?= $trunk['type']==='pjsip'?'selected':'' ?>>PJSIP (Asterisk 22 recommended)</option>
                        <option value="sip" <?= $trunk['type']==='sip'?'selected':'' ?>>chan_sip (legacy)</option>
                        <option value="dahdi" <?= $trunk['type']==='dahdi'?'selected':'' ?>>DAHDI (analog/ISDN)</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>SIP Server / Host *</label>
                    <input type="text" name="host" class="form-control mono" value="<?= sanitize($trunk['host']) ?>" required placeholder="sip.provider.com">
                </div>
                <div class="form-group">
                    <label>Port</label>
                    <input type="number" name="port" class="form-control mono" value="<?= (int)$trunk['port'] ?>" min="1" max="65535" placeholder="5060">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Username / Account</label>
                    <input type="text" name="username" class="form-control mono" value="<?= sanitize($trunk['username']) ?>" placeholder="SIP account username">
                </div>
                <div class="form-group">
                    <label>Password / Secret</label>
                    <div class="input-group">
                        <input type="password" id="trunk_pass" name="password" class="form-control mono" value="" placeholder="<?= $isEdit ? 'Leave blank to keep' : 'SIP password' ?>">
                        <button type="button" class="btn btn-ghost input-addon" onclick="togglePassword('trunk_pass')">👁</button>
                    </div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Inbound Context</label>
                    <input type="text" name="context" class="form-control mono" value="<?= sanitize($trunk['context']) ?>" placeholder="from-trunk">
                </div>
                <div class="form-group">
                    <label>Max Channels</label>
                    <input type="number" name="max_channels" class="form-control" value="<?= (int)$trunk['max_channels'] ?>" min="1" max="999">
                </div>
            </div>
            <div class="form-group">
                <label>Outbound Caller ID</label>
                <input type="text" name="outbound_cid" class="form-control mono" value="<?= sanitize($trunk['outbound_cid']) ?>" placeholder="e.g. +31201234567">
            </div>
            <div class="form-group">
                <label>Allowed Codecs</label>
                <?php $selCodecs = explode(',', $trunk['codecs'] ?? 'ulaw,alaw'); ?>
                <div class="codec-grid">
                    <?php foreach (['ulaw','alaw','g722','g729','g726','gsm','opus'] as $c): ?>
                    <label class="codec-item"><input type="checkbox" name="codecs[]" value="<?= $c ?>" <?= in_array($c,$selCodecs)?'checked':'' ?>><span class="mono"><?= $c ?></span></label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="form-group">
                <label>Notes</label>
                <textarea name="notes" class="form-control" rows="2"><?= sanitize($trunk['notes']) ?></textarea>
            </div>
            <div class="form-check">
                <label class="toggle-label">
                    <input type="checkbox" name="enabled" value="1" <?= $trunk['enabled']?'checked':'' ?>>
                    <span class="toggle"></span><?= t('enabled') ?>
                </label>
            </div>
        </div>
    </div>
    <div class="form-actions">
        <a href="?page=trunks" class="btn btn-ghost"><?= t('cancel') ?></a>
        <button type="submit" class="btn btn-accent">💾 <?= t('save') ?> & <?= t('reload') ?></button>
    </div>
<script>
function updateTrunkType(sel) {
    const isPBX = sel.value === 'pbx';
    const regNote = document.getElementById('reg-note');
    if (regNote) {
        regNote.style.display = isPBX ? 'block' : 'none';
    }
}
document.addEventListener('DOMContentLoaded', function() {
    const sel = document.querySelector('select[name="trunk_type"]');
    if (sel) updateTrunkType(sel);
});
</script>
</form>
<script>
function togglePassword(id) { const el=document.getElementById(id); el.type=el.type==='password'?'text':'password'; }
</script>
