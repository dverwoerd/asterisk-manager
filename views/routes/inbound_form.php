<?php $isEdit = ($action === 'edit'); ?>
<div class="page-header">
    <h1 class="page-title"><?= $title ?></h1>
    <a href="?page=inbound_routes" class="btn btn-ghost">← <?= t('back') ?></a>
</div>

<form method="POST" action="?page=inbound_routes&action=post_<?= $action ?><?= $isEdit ? '&id='.$route['id'] : '' ?>">
    <?= csrf() ?>
    <div class="card" style="max-width:680px">
        <div class="card-header"><h3 class="card-title">Inbound Route</h3></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group">
                    <label><?= t('did') ?></label>
                    <input type="text" name="did" class="form-control mono" value="<?= sanitize($route['did']) ?>" placeholder="e.g. 0201234567 (blank = any)">
                    <small class="form-hint">DID number this route matches. Leave blank to match any inbound call.</small>
                </div>
                <div class="form-group">
                    <label>Caller ID (optional)</label>
                    <input type="text" name="cid_number" class="form-control mono" value="<?= sanitize($route['cid_number']) ?>" placeholder="Specific CID to match">
                </div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <input type="text" name="description" class="form-control" value="<?= sanitize($route['description']) ?>" placeholder="e.g. Main company number">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Destination Type *</label>
                    <select name="destination_type" class="form-control" onchange="updateDestHint(this.value)">
                        <?php foreach ([
                            'extension'   => 'Extension',
                            'queue'       => 'Queue',
                            'ring_group'  => 'Ring Group',
                            'voicemail'   => 'Voicemail',
                            'announcement'=> 'Announcement',
                            'external'    => 'External Number',
                            'hangup'      => 'Hangup',
                        ] as $v => $l): ?>
                        <option value="<?= $v ?>" <?= $route['destination_type']===$v?'selected':'' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Destination *</label>
                    <input type="text" name="destination" class="form-control mono" value="<?= sanitize($route['destination']) ?>" id="destInput" placeholder="Extension number, queue name...">
                    <small class="form-hint" id="destHint">Enter the extension number, queue name, etc.</small>
                </div>
            </div>
            <div class="form-group">
                <label>Priority</label>
                <input type="number" name="priority" class="form-control" value="<?= (int)$route['priority'] ?>" min="0" max="99">
                <small class="form-hint">Higher priority routes are matched first (0 = lowest)</small>
            </div>
            <div class="form-group">
                <label>Notes</label>
                <textarea name="notes" class="form-control" rows="2"><?= sanitize($route['notes']) ?></textarea>
            </div>
            <div class="form-check">
                <label class="toggle-label">
                    <input type="checkbox" name="enabled" value="1" <?= $route['enabled'] ? 'checked' : '' ?>>
                    <span class="toggle"></span><?= t('enabled') ?>
                </label>
            </div>
        </div>
    </div>
    <div class="form-actions">
        <a href="?page=inbound_routes" class="btn btn-ghost"><?= t('cancel') ?></a>
        <button type="submit" class="btn btn-accent">💾 <?= t('save') ?> & <?= t('reload') ?></button>
    </div>
</form>
<script>
const hints = {
    extension: 'Enter the extension number (e.g. 1001)',
    queue: 'Enter the queue name (e.g. support)',
    ring_group: 'Enter the ring group number (e.g. 300)',
    voicemail: 'Enter the mailbox number (e.g. 1001)',
    announcement: 'Enter the sound file name (e.g. welcome-message)',
    external: 'Enter the full phone number',
    hangup: 'No destination needed',
};
function updateDestHint(val) {
    document.getElementById('destHint').textContent = hints[val] || '';
}
</script>
