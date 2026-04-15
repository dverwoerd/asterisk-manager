<?php $isEdit = ($action === 'edit'); $blfKeys = $blfKeys ?? []; ?>
<div class="page-header">
    <h1 class="page-title"><?= $title ?></h1>
    <a href="?page=provision" class="btn btn-ghost">← Back</a>
</div>

<form method="POST" action="?page=provision&action=post_<?= $action ?><?= $isEdit ? '&id='.$phone['id'] : '' ?>">
    <?= csrf() ?>
    <div class="form-layout">

        <!-- Linker kolom: toestel instellingen -->
        <div class="form-col">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Toestel Instellingen</h3></div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Extensie *</label>
                            <select name="extension_id" class="form-control" required
                                    onchange="updateDisplayName(this)">
                                <option value="">Selecteer extensie...</option>
                                <?php foreach ($extensions as $e): ?>
                                <option value="<?= $e['id'] ?>"
                                    <?= ($phone['extension_id'] ?? 0) == $e['id'] ? 'selected' : '' ?>
                                    data-name="<?= sanitize($e['full_name']) ?>">
                                    <?= sanitize($e['extension']) ?> — <?= sanitize($e['full_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Model</label>
                            <select name="model" class="form-control" onchange="updateTrunkType(this)">
                                <?php foreach ($models as $code => $label): ?>
                                <option value="<?= $code ?>"
                                    <?= ($phone['model'] ?? 'T46U') === $code ? 'selected' : '' ?>>
                                    <?= $label ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>MAC Address *</label>
                        <input type="text" name="mac_address" class="form-control mono"
                               value="<?= sanitize($phone['mac_address']) ?>"
                               placeholder="AA:BB:CC:DD:EE:FF of AABBCCDDEEFF"
                               required <?= $isEdit ? 'readonly' : '' ?>>
                        <small class="form-hint">Staat op de achterkant van het toestel of op de verpakking.</small>
                    </div>

                    <div class="form-group">
                        <label>Display Name</label>
                        <input type="text" id="displayName" name="display_name" class="form-control"
                               value="<?= sanitize($phone['display_name']) ?>"
                               placeholder="Naam op het display">
                    </div>

                    <div id="gigasetExtra" style="display:none">
                        <div class="form-group">
                            <label>Extra Extensies (Gigaset handsets)</label>
                            <select name="extra_extensions[]" class="form-control" multiple style="height:100px">
                                <?php foreach ($extensions as $e): ?>
                                <?php
                                    $extraIds = array_map('trim', explode(',', $phone['extra_extensions'] ?? ''));
                                    $selected = in_array($e['id'], $extraIds) ? 'selected' : '';
                                ?>
                                <option value="<?= $e['id'] ?>" <?= $selected ?>>
                                    <?= sanitize($e['extension']) ?> — <?= sanitize($e['full_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-hint">
                                Gigaset DECT bases ondersteunen meerdere handsets.<br>
                                Selecteer extra extensies voor handset 2, 3, etc. (Ctrl+klik voor meerdere)
                            </small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Admin Wachtwoord</label>
                        <div class="input-group">
                            <input type="password" id="adminPass" name="admin_password"
                                   class="form-control mono"
                                   value="<?= sanitize($phone['admin_password'] ?? 'admin') ?>">
                            <button type="button" class="btn btn-ghost input-addon"
                                    onclick="togglePwd('adminPass')">👁</button>
                            <button type="button" class="btn btn-ghost input-addon"
                                    onclick="genPwd('adminPass')">⟳</button>
                        </div>
                        <small class="form-hint">Wachtwoord voor de web interface van het toestel.</small>
                    </div>
                </div>
            </div>

            <!-- Tijd & Datum -->
            <div class="card mt-4">
                <div class="card-header"><h3 class="card-title">Tijd & Datum</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Tijdzone</label>
                        <select name="timezone" class="form-control">
                            <?php foreach ($timezones as $tz): ?>
                            <option value="<?= $tz ?>"
                                <?= ($phone['timezone'] ?? 'Europe/Amsterdam') === $tz ? 'selected' : '' ?>>
                                <?= $tz ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>NTP Server</label>
                        <input type="text" name="ntp_server" class="form-control mono"
                               value="<?= sanitize($phone['ntp_server'] ?? 'pool.ntp.org') ?>"
                               placeholder="pool.ntp.org">
                    </div>
                    <div class="form-group">
                        <label>Taal Interface</label>
                        <select name="language" class="form-control">
                            <?php foreach ($languages as $code => $label): ?>
                            <option value="<?= $code ?>"
                                <?= ($phone['language'] ?? 'Dutch') === $code ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Display instellingen -->
            <div class="card mt-4">
                <div class="card-header"><h3 class="card-title">Display & Screensaver</h3></div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Screensaver</label>
                            <select name="screensaver_time" class="form-control">
                                <option value="0" <?= ($phone['screensaver_time'] ?? 0) == 0 ? 'selected' : '' ?>>Uit</option>
                                <option value="300" <?= ($phone['screensaver_time'] ?? 0) == 300 ? 'selected' : '' ?>>5 minuten</option>
                                <option value="600" <?= ($phone['screensaver_time'] ?? 0) == 600 ? 'selected' : '' ?>>10 minuten</option>
                                <option value="1800" <?= ($phone['screensaver_time'] ?? 0) == 1800 ? 'selected' : '' ?>>30 minuten</option>
                                <option value="3600" <?= ($phone['screensaver_time'] ?? 0) == 3600 ? 'selected' : '' ?>>1 uur</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Klok op screensaver</label>
                            <select name="screensaver_clock" class="form-control">
                                <option value="0" <?= empty($phone['screensaver_clock']) ? 'selected' : '' ?>>Geen klok</option>
                                <option value="1" <?= !empty($phone['screensaver_clock']) ? 'selected' : '' ?>>Klok weergeven</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Backlight tijd</label>
                        <select name="backlight_time" class="form-control">
                            <option value="0" <?= ($phone['backlight_time'] ?? 0) == 0 ? 'selected' : '' ?>>Altijd aan</option>
                            <option value="15" <?= ($phone['backlight_time'] ?? 0) == 15 ? 'selected' : '' ?>>15 seconden</option>
                            <option value="30" <?= ($phone['backlight_time'] ?? 0) == 30 ? 'selected' : '' ?>>30 seconden</option>
                            <option value="60" <?= ($phone['backlight_time'] ?? 0) == 60 ? 'selected' : '' ?>>1 minuut</option>
                            <option value="300" <?= ($phone['backlight_time'] ?? 0) == 300 ? 'selected' : '' ?>>5 minuten</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Adresboek -->
            <div class="card mt-4">
                <div class="card-header"><h3 class="card-title">Company Adresboek</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Adresboek Groep</label>
                        <select name="phonebook_group_id" class="form-control">
                            <option value="">Geen adresboek</option>
                            <?php
                            $pbGroups = Database::fetchAll("SELECT id, name FROM phonebook_groups ORDER BY name");
                            foreach ($pbGroups as $pbg):
                            ?>
                            <option value="<?= $pbg['id'] ?>"
                                <?= ($phone['phonebook_group_id'] ?? '') == $pbg['id'] ? 'selected' : '' ?>>
                                <?= sanitize($pbg['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-hint">Het toestel laadt dit adresboek automatisch bij provisioning.</small>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="card mt-4">
                <div class="card-header"><h3 class="card-title">Notities</h3></div>
                <div class="card-body">
                    <textarea name="notes" class="form-control" rows="3"><?= sanitize($phone['notes'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Rechter kolom: BLF Keys -->
        <div class="form-col">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">DSS / BLF Keys</h3>
                    <button type="button" class="btn btn-sm btn-ghost" onclick="addBLFKey()">
                        + Add Key
                    </button>
                </div>
                <div class="card-body">
                    <div style="display:grid;grid-template-columns:60px 100px 1fr 1fr 80px 30px;gap:6px;margin-bottom:6px;font-size:10px;font-family:var(--font-mono);color:var(--text-muted)">
                        <span>KEY #</span>
                        <span>TYPE</span>
                        <span>LABEL</span>
                        <span>EXTENSIE / WAARDE</span>
                        <span>PICKUP</span>
                        <span></span>
                    </div>

                    <div id="blfContainer">
                        <?php if (empty($blfKeys)): ?>
                        <!-- Lege rij als voorbeeld -->
                        <?php else: ?>
                        <?php foreach ($blfKeys as $i => $key): ?>
                        <div class="blf-row" style="display:grid;grid-template-columns:60px 100px 1fr 1fr 80px 30px;gap:6px;margin-bottom:6px;align-items:center">
                            <input type="number" name="key_numbers[]" class="form-control form-control-sm mono"
                                   value="<?= (int)$key['key_number'] ?>" min="1" max="40" placeholder="1">
                            <select name="key_types[]" class="form-control form-control-sm">
                                <?php foreach (['blf'=>'BLF','speed_dial'=>'Speed Dial','dtmf'=>'DTMF','conference'=>'Conference','forward'=>'Forward','transfer'=>'Transfer','hold'=>'Hold','dnd'=>'DND'] as $v => $l): ?>
                                <option value="<?= $v ?>" <?= $key['key_type']===$v?'selected':'' ?>><?= $l ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" name="key_labels[]" class="form-control form-control-sm"
                                   value="<?= sanitize($key['label']) ?>" placeholder="Label">
                            <select name="key_ext_ids[]" class="form-control form-control-sm"
                                    onchange="updateKeyValue(this)">
                                <option value="">Handmatig invoeren...</option>
                                <?php foreach ($extensions as $e): ?>
                                <option value="<?= $e['id'] ?>"
                                    data-ext="<?= sanitize($e['extension']) ?>"
                                    data-name="<?= sanitize($e['full_name']) ?>"
                                    <?= ($key['extension_id'] ?? 0) == $e['id'] ? 'selected' : '' ?>>
                                    <?= sanitize($e['extension']) ?> — <?= sanitize($e['full_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" name="key_pickups[]" class="form-control form-control-sm mono"
                                   value="<?= sanitize($key['pickup_code'] ?? '*8') ?>" placeholder="*8">
                            <button type="button" class="btn btn-sm btn-danger-ghost"
                                    onclick="this.closest('.blf-row').remove()">✕</button>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Verborgen waarden voor handmatige invoer -->
                    <div id="blfValues" style="display:none">
                        <?php foreach ($blfKeys as $key): ?>
                        <input name="key_values[]" value="<?= sanitize($key['value']) ?>">
                        <?php endforeach; ?>
                    </div>

                    <small class="form-hint" style="margin-top:8px;display:block">
                        BLF = Busy Lamp Field (bezettoestand zichtbaar + direct bellen + pickup).
                        Key nummers: 1-6 = vaste keys boven scherm, 7+ = uitbreidingsmodule.
                    </small>
                </div>
            </div>

            <!-- Snelkoppeling: voeg alle extensies toe als BLF -->
            <div class="card mt-4">
                <div class="card-header"><h3 class="card-title">Snelle BLF Setup</h3></div>
                <div class="card-body">
                    <p class="text-sm text-muted" style="margin-bottom:10px">
                        Voeg alle extensies automatisch toe als BLF keys:
                    </p>
                    <button type="button" class="btn btn-ghost btn-sm" onclick="addAllExtensions()">
                        ⚡ Voeg alle extensies toe als BLF
                    </button>
                    <small class="form-hint">Begint vanaf key 1. Bestaande keys worden overschreven.</small>
                </div>
            </div>
        </div>
    </div>

    <div class="form-actions">
        <a href="?page=provision" class="btn btn-ghost">Annuleren</a>
        <button type="submit" class="btn btn-accent">💾 Opslaan & Config Genereren</button>
    </div>
</form>

<!-- Extension data voor JavaScript -->
<script>
const allExtensions = <?= json_encode(array_map(fn($e) => [
    'id'        => $e['id'],
    'extension' => $e['extension'],
    'full_name' => $e['full_name'],
], $extensions)) ?>;

function togglePwd(id) {
    const el = document.getElementById(id);
    el.type = el.type === 'password' ? 'text' : 'password';
}

function genPwd(id) {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
    let pwd = '';
    for (let i = 0; i < 12; i++) pwd += chars[Math.floor(Math.random() * chars.length)];
    document.getElementById(id).value = pwd;
    document.getElementById(id).type = 'text';
}

function updateTrunkType(sel) {
    const isGigaset = sel.value.includes('N300') || sel.value.includes('N510') ||
                      sel.value.includes('N720') || sel.value.includes('N870');
    const gigasetDiv = document.getElementById('gigasetExtra');
    if (gigasetDiv) gigasetDiv.style.display = isGigaset ? 'block' : 'none';
}

// Controleer model bij laden
document.addEventListener('DOMContentLoaded', function() {
    const modelSel = document.querySelector('select[name="model"]');
    if (modelSel) updateTrunkType(modelSel);
});

function updateDisplayName(sel) {
    const opt = sel.options[sel.selectedIndex];
    const nameField = document.getElementById('displayName');
    if (!nameField.value && opt.dataset.name) {
        nameField.value = opt.dataset.name;
    }
}

function addBLFKey(extId = '', extNum = '', extName = '', keyNum = null) {
    const container = document.getElementById('blfContainer');
    const rows      = container.querySelectorAll('.blf-row');
    const nextKey   = keyNum || (rows.length + 1);

    const row = document.createElement('div');
    row.className = 'blf-row';
    row.style.cssText = 'display:grid;grid-template-columns:60px 100px 1fr 1fr 80px 30px;gap:6px;margin-bottom:6px;align-items:center';

    let extOptions = '<option value="">Handmatig invoeren...</option>';
    allExtensions.forEach(e => {
        const sel = e.id == extId ? ' selected' : '';
        extOptions += `<option value="${e.id}" data-ext="${e.extension}" data-name="${e.full_name}"${sel}>${e.extension} — ${e.full_name}</option>`;
    });

    const label = extName || '';
    const typeOptions = [
        ['blf','BLF'],['speed_dial','Speed Dial'],['dtmf','DTMF'],
        ['conference','Conference'],['forward','Forward'],['transfer','Transfer'],
        ['hold','Hold'],['dnd','DND']
    ].map(([v,l]) => `<option value="${v}">${l}</option>`).join('');

    row.innerHTML = `
        <input type="number" name="key_numbers[]" class="form-control form-control-sm mono" value="${nextKey}" min="1" max="40">
        <select name="key_types[]" class="form-control form-control-sm">${typeOptions}</select>
        <input type="text" name="key_labels[]" class="form-control form-control-sm" value="${label}" placeholder="Label">
        <select name="key_ext_ids[]" class="form-control form-control-sm" onchange="updateKeyValue(this)">${extOptions}</select>
        <input type="text" name="key_pickups[]" class="form-control form-control-sm mono" value="*8" placeholder="*8">
        <button type="button" class="btn btn-sm btn-danger-ghost" onclick="this.closest('.blf-row').remove()">✕</button>
    `;
    container.appendChild(row);

    // Voeg lege value toe
    const valContainer = document.getElementById('blfValues');
    const valInput = document.createElement('input');
    valInput.name = 'key_values[]';
    valInput.value = extNum || '';
    valContainer.appendChild(valInput);
}

function updateKeyValue(sel) {
    const opt  = sel.options[sel.selectedIndex];
    const row  = sel.closest('.blf-row');
    // Update label als het leeg is
    const labelField = row.querySelector('input[name="key_labels[]"]');
    if (!labelField.value && opt.dataset.name) {
        labelField.value = opt.dataset.name;
    }
}

function addAllExtensions() {
    if (!confirm('Alle extensies toevoegen als BLF keys? Bestaande keys blijven staan.')) return;
    const container = document.getElementById('blfContainer');
    const existing  = container.querySelectorAll('.blf-row').length;
    allExtensions.forEach((e, i) => {
        addBLFKey(e.id, e.extension, e.full_name, existing + i + 1);
    });
}
</script>
