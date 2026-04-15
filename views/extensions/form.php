<?php $isEdit = ($action === 'edit'); ?>
<div class="page-header">
    <h1 class="page-title"><?= $title ?></h1>
    <a href="?page=extensions" class="btn btn-ghost">← <?= t('back') ?></a>
</div>

<form method="POST" action="?page=extensions&action=post_<?= $action ?><?= $isEdit ? '&id='.$ext['id'] : '' ?>">
    <?= csrf() ?>
    <div class="form-layout">
        <!-- Left column -->
        <div class="form-col">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Basic Settings</h3></div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="extension"><?= t('extension') ?> *</label>
                            <input type="text" id="extension" name="extension"
                                   class="form-control mono"
                                   value="<?= sanitize($ext['extension']) ?>"
                                   pattern="\d{3,6}" placeholder="e.g. 1001" required
                                   <?= $isEdit ? 'readonly' : '' ?>>
                            <small class="form-hint">3–6 digits</small>
                        </div>
                        <div class="form-group">
                            <label for="full_name"><?= t('full_name') ?> *</label>
                            <input type="text" id="full_name" name="full_name"
                                   class="form-control"
                                   value="<?= sanitize($ext['full_name']) ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="email"><?= t('email') ?></label>
                        <input type="email" id="email" name="email"
                               class="form-control"
                               value="<?= sanitize($ext['email']) ?>">
                    </div>

                    <!-- SIP Password met generator -->
                    <div class="form-group">
                        <label for="secret"><?= t('secret') ?><?= !$isEdit ? ' *' : '' ?></label>

                        <div class="password-generator-box">
                            <!-- Input + toggle + copy -->
                            <div class="input-group mb-2">
                                <input type="password" id="secret" name="secret"
                                       class="form-control mono"
                                       autocomplete="new-password"
                                       <?= !$isEdit ? 'required' : '' ?>
                                       placeholder="<?= $isEdit ? 'Leave blank to keep current' : 'Click generate or type password' ?>">
                                <button type="button" class="btn btn-ghost input-addon"
                                        onclick="togglePwdVisibility()"
                                        title="Show/hide password" id="toggleBtn">👁</button>
                                <button type="button" class="btn btn-ghost input-addon"
                                        onclick="copyPassword()"
                                        title="Copy to clipboard">⎘</button>
                            </div>

                            <!-- Strength bar -->
                            <div class="strength-bar-wrap">
                                <div class="strength-bar" id="strengthBar"></div>
                            </div>
                            <div class="strength-label" id="strengthLabel"></div>

                            <!-- Generator opties -->
                            <div class="generator-panel" id="generatorPanel">
                                <div class="generator-header">
                                    <span class="generator-title">Password Generator</span>
                                    <button type="button"
                                            class="btn btn-sm btn-accent"
                                            onclick="generateSIPPassword()">⟳ Generate</button>
                                </div>
                                <div class="generator-options">
                                    <div class="gen-option">
                                        <label>Length: <span id="lengthVal">16</span></label>
                                        <input type="range" id="pwdLength" min="8" max="32"
                                               value="16" class="range-input"
                                               oninput="document.getElementById('lengthVal').textContent=this.value">
                                    </div>
                                    <div class="gen-checkboxes">
                                        <label class="gen-check">
                                            <input type="checkbox" id="useUpper" checked> A-Z
                                        </label>
                                        <label class="gen-check">
                                            <input type="checkbox" id="useLower" checked> a-z
                                        </label>
                                        <label class="gen-check">
                                            <input type="checkbox" id="useNumbers" checked> 0-9
                                        </label>
                                        <label class="gen-check">
                                            <input type="checkbox" id="useSymbols"> !@#$
                                        </label>
                                    </div>
                                </div>
                                <!-- Generated password preview -->
                                <div class="gen-preview" id="genPreview" style="display:none">
                                    <span class="mono" id="genPreviewText"></span>
                                    <button type="button" class="btn btn-sm btn-ghost"
                                            onclick="useGeneratedPassword()">Use this</button>
                                </div>
                            </div>
                        </div>

                        <?php if ($isEdit): ?>
                        <small class="form-hint">Leave blank to keep the current password.</small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="context"><?= t('context') ?></label>
                        <input type="text" id="context" name="context"
                               class="form-control mono"
                               value="<?= sanitize($ext['context']) ?>"
                               list="contexts-list">
                        <datalist id="contexts-list">
                            <option value="from-internal">
                            <option value="from-external">
                        </datalist>
                    </div>
                    <div class="form-check">
                        <label class="toggle-label">
                            <input type="checkbox" name="enabled" value="1"
                                   <?= $ext['enabled'] ? 'checked' : '' ?>>
                            <span class="toggle"></span>
                            <?= t('enabled') ?>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Caller ID -->
            <div class="card mt-4">
                <div class="card-header"><h3 class="card-title">Caller ID</h3></div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label><?= t('callerid_name') ?></label>
                            <input type="text" name="callerid_name" class="form-control"
                                   value="<?= sanitize($ext['callerid_name']) ?>">
                        </div>
                        <div class="form-group">
                            <label><?= t('callerid_number') ?></label>
                            <input type="text" name="callerid_number"
                                   class="form-control mono"
                                   value="<?= sanitize($ext['callerid_number']) ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right column -->
        <div class="form-col">
            <!-- Codecs -->
            <div class="card">
                <div class="card-header"><h3 class="card-title">Audio & Codecs</h3></div>
                <div class="card-body">
                    <?php
                    $selectedCodecs = explode(',', $ext['codecs'] ?? 'ulaw,alaw,g722');
                    $allCodecs = ['ulaw','alaw','g722','g729','g726','g723','gsm','opus','speex'];
                    ?>
                    <div class="form-group">
                        <label>Allowed Codecs</label>
                        <div class="codec-grid">
                        <?php foreach ($allCodecs as $codec): ?>
                            <label class="codec-item">
                                <input type="checkbox" name="codecs[]" value="<?= $codec ?>"
                                       <?= in_array($codec, $selectedCodecs) ? 'checked' : '' ?>>
                                <span class="mono"><?= $codec ?></span>
                            </label>
                        <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label><?= t('dtmf_mode') ?></label>
                            <select name="dtmf_mode" class="form-control">
                                <?php foreach (['rfc4733','inband','info','auto'] as $m): ?>
                                <option value="<?= $m ?>"
                                    <?= $ext['dtmf_mode'] === $m ? 'selected' : '' ?>>
                                    <?= $m ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><?= t('max_contacts') ?></label>
                            <input type="number" name="max_contacts" class="form-control"
                                   value="<?= (int)$ext['max_contacts'] ?>" min="1" max="10">
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?= t('call_recording') ?></label>
                        <select name="call_recording" class="form-control">
                            <option value="never"
                                <?= $ext['call_recording']==='never' ? 'selected':'' ?>>Never</option>
                            <option value="always"
                                <?= $ext['call_recording']==='always' ? 'selected':'' ?>>Always</option>
                            <option value="on_demand"
                                <?= $ext['call_recording']==='on_demand' ? 'selected':'' ?>>On Demand</option>
                        </select>
                    </div>
                    <div class="form-check">
                        <label class="toggle-label">
                            <input type="checkbox" name="call_waiting" value="1"
                                   <?= $ext['call_waiting'] ? 'checked' : '' ?>>
                            <span class="toggle"></span>
                            Call Waiting
                        </label>
                    </div>
                </div>
            </div>

            <!-- Voicemail -->
            <div class="card mt-4">
                <div class="card-header"><h3 class="card-title"><?= t('voicemail') ?></h3></div>
                <div class="card-body">
                    <div class="form-check">
                        <label class="toggle-label">
                            <input type="checkbox" name="voicemail_enabled" value="1"
                                   id="vmEnabled"
                                   <?= $ext['voicemail_enabled'] ? 'checked' : '' ?>
                                   onchange="document.getElementById('vmFields').style.display=this.checked?'':'none'">
                            <span class="toggle"></span>
                            Enable Voicemail
                        </label>
                    </div>
                    <div id="vmFields"
                         <?= !$ext['voicemail_enabled'] ? 'style="display:none"' : '' ?>>
                        <div class="form-group mt-3">
                            <label><?= t('voicemail_pin') ?></label>
                            <input type="text" name="voicemail_pin"
                                   class="form-control mono"
                                   value="<?= sanitize($ext['voicemail_pin']) ?>"
                                   pattern="\d{4,8}" placeholder="4–8 digits">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="card mt-4">
                <div class="card-header"><h3 class="card-title"><?= t('notes') ?></h3></div>
                <div class="card-body">
                    <textarea name="notes" class="form-control"
                              rows="3"><?= sanitize($ext['notes']) ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><h3 class="card-title">📞 Doorschakelen (Call Forward)</h3></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group">
                    <label>Altijd doorschakelen naar</label>
                    <input type="text" name="cf_always" class="form-control mono"
                           value="<?= sanitize($ext['cf_always'] ?? '') ?>"
                           placeholder="0612345678 of leeg laten">
                    <small class="form-hint">Altijd doorschakelen — toestel belt niet meer.</small>
                </div>
                <div class="form-group">
                    <label>Beltijd (seconden)</label>
                    <input type="number" name="ring_time" class="form-control mono"
                           value="<?= (int)($ext['ring_time'] ?? 20) ?>" min="5" max="60">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Bij geen antwoord doorschakelen naar</label>
                    <input type="text" name="cf_noanswer" class="form-control mono"
                           value="<?= sanitize($ext['cf_noanswer'] ?? '') ?>"
                           placeholder="0612345678 of leeg laten">
                </div>
                <div class="form-group">
                    <label>Bij bezet doorschakelen naar</label>
                    <input type="text" name="cf_busy" class="form-control mono"
                           value="<?= sanitize($ext['cf_busy'] ?? '') ?>"
                           placeholder="0612345678 of leeg laten">
                </div>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="cf_voicemail" value="1"
                           <?= !empty($ext['cf_voicemail']) ? 'checked' : '' ?>>
                    Bij geen antwoord naar voicemail (als geen extern nummer ingesteld)
                </label>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><h3 class="card-title">🔒 Security</h3></div>
        <div class="card-body">
            <div class="form-group">
                <label>Extra toegestane IPs <small class="text-muted">(voor externe toestellen)</small></label>
                <input type="text" name="allowed_ips" class="form-control mono"
                       value="<?= sanitize($ext['allowed_ips'] ?? '') ?>"
                       placeholder="1.2.3.4, 5.6.7.8/24">
                <small class="form-hint">Intern netwerk (172.16.0.0/12, 10.0.0.0/8, 192.168.0.0/16) is altijd toegestaan. Voeg externe IPs komma-gescheiden toe.</small>
            </div>
        </div>
    </div>
    <div class="form-actions">
        <a href="?page=extensions" class="btn btn-ghost"><?= t('cancel') ?></a>
        <button type="submit" class="btn btn-accent">💾 <?= t('save') ?></button>
    </div>
</form>

<?php if ($isEdit): ?>
<!-- Provisioning sectie onder het extensie formulier -->
<div class="card mt-4">
    <div class="card-header">
        <h3 class="card-title">📱 Toestel Provisioning</h3>
        <?php if (!empty($phone)): ?>
        <span class="badge badge-success">✓ Geconfigureerd — <?= sanitize($phone['model']) ?></span>
        <?php else: ?>
        <span class="badge badge-warning">Niet geconfigureerd</span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <form method="POST" action="?page=extensions&action=save_provision&id=<?= $ext['id'] ?>">
            <?= csrf() ?>
            <div class="form-row">
                <div class="form-group">
                    <label>MAC Adres</label>
                    <input type="text" name="mac_address" class="form-control mono"
                           value="<?= sanitize($phone['mac_address'] ?? '') ?>"
                           placeholder="AA:BB:CC:DD:EE:FF">
                </div>
                <div class="form-group">
                    <label>Model</label>
                    <select name="model" class="form-control">
                        <optgroup label="Yealink">
                        <?php foreach ($yealinkModels as $code => $label): ?>
                        <option value="<?= $code ?>" <?= ($phone['model'] ?? '') === $code ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                        </optgroup>
                        <optgroup label="Gigaset">
                        <?php foreach ($gigasetModels as $code => $label): ?>
                        <option value="<?= $code ?>" <?= ($phone['model'] ?? '') === $code ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                        </optgroup>
                    </select>
                </div>
                <div class="form-group">
                    <label>Admin Wachtwoord</label>
                    <input type="text" name="admin_password" class="form-control mono"
                           value="<?= sanitize($phone['admin_password'] ?? 'admin') ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Tijdzone</label>
                    <select name="timezone" class="form-control">
                        <?php foreach ($timezones as $tz => $label): ?>
                        <option value="<?= $tz ?>" <?= ($phone['timezone'] ?? 'Europe/Amsterdam') === $tz ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Taal</label>
                    <select name="language" class="form-control">
                        <?php foreach ($languages as $lang): ?>
                        <option value="<?= $lang ?>" <?= ($phone['language'] ?? 'Dutch') === $lang ? 'selected' : '' ?>><?= $lang ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Adresboek</label>
                    <select name="phonebook_group_id" class="form-control">
                        <option value="">Geen</option>
                        <?php foreach ($phonebookGroups as $pg): ?>
                        <option value="<?= $pg['id'] ?>" <?= ($phone['phonebook_group_id'] ?? '') == $pg['id'] ? 'selected' : '' ?>><?= sanitize($pg['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
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
                    <label>Backlight</label>
                    <select name="backlight_time" class="form-control">
                        <option value="0" <?= ($phone['backlight_time'] ?? 0) == 0 ? 'selected' : '' ?>>Altijd aan</option>
                        <option value="30" <?= ($phone['backlight_time'] ?? 0) == 30 ? 'selected' : '' ?>>30 seconden</option>
                        <option value="60" <?= ($phone['backlight_time'] ?? 0) == 60 ? 'selected' : '' ?>>1 minuut</option>
                        <option value="300" <?= ($phone['backlight_time'] ?? 0) == 300 ? 'selected' : '' ?>>5 minuten</option>
                    </select>
                </div>
            </div>

            <?php if (!empty($phone)): ?>
            <div class="form-actions" style="margin-top:12px;padding-top:0;border:none">
                <a href="?page=provision&action=generate&id=<?= $phone['id'] ?>" class="btn btn-ghost btn-sm">⟳ Genereer</a>
                <a href="?page=provision&action=reprovision&id=<?= $phone['id'] ?>" class="btn btn-ghost btn-sm">⟳ Reprovision</a>
                <a href="?page=provision&action=reboot&id=<?= $phone['id'] ?>"
                   class="btn btn-ghost btn-sm"
                   onclick="return confirm('Toestel herstarten?')">↺ Reboot</a>
                <button type="submit" class="btn btn-accent btn-sm">💾 Provisioning Opslaan</button>
            </div>
            <?php else: ?>
            <div class="form-actions" style="margin-top:12px;padding-top:0;border:none">
                <button type="submit" class="btn btn-accent btn-sm">💾 Provisioning Aanmaken</button>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Extra CSS voor password generator -->
<style>
.password-generator-box {
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
}
.password-generator-box .input-group {
    border-bottom: 1px solid var(--border);
    margin-bottom: 0;
}
.password-generator-box .input-group .form-control {
    border: none;
    border-radius: 0;
}
.password-generator-box .input-group .input-addon {
    border: none;
    border-left: 1px solid var(--border);
    border-radius: 0;
}
.strength-bar-wrap {
    height: 3px;
    background: var(--border);
}
.strength-bar {
    height: 3px;
    width: 0%;
    transition: all 0.3s ease;
    border-radius: 2px;
}
.strength-label {
    font-family: var(--font-mono);
    font-size: 10px;
    padding: 3px 10px;
    letter-spacing: 0.08em;
    min-height: 20px;
}
.generator-panel {
    padding: 12px;
    background: var(--bg);
    border-top: 1px solid var(--border);
}
.generator-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
}
.generator-title {
    font-family: var(--font-mono);
    font-size: 11px;
    letter-spacing: 0.1em;
    color: var(--text-muted);
    text-transform: uppercase;
}
.generator-options {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.gen-option label {
    font-size: 11px;
    color: var(--text-muted);
    font-family: var(--font-mono);
    display: flex;
    justify-content: space-between;
    margin-bottom: 3px;
    text-transform: none;
    letter-spacing: 0;
}
.range-input {
    width: 100%;
    accent-color: var(--accent);
    cursor: pointer;
}
.gen-checkboxes {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
.gen-check {
    display: flex;
    align-items: center;
    gap: 5px;
    font-family: var(--font-mono);
    font-size: 11px;
    color: var(--text-muted);
    cursor: pointer;
    padding: 4px 8px;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    transition: all 0.15s;
}
.gen-check:has(input:checked) {
    border-color: var(--accent);
    color: var(--accent);
    background: var(--accent-dim);
}
.gen-check input { display: none; }
.gen-preview {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 10px;
    padding: 8px 10px;
    background: var(--bg-card);
    border: 1px solid var(--accent);
    border-radius: var(--radius);
    gap: 10px;
}
.gen-preview .mono {
    font-size: 13px;
    color: var(--accent);
    word-break: break-all;
    flex: 1;
}
.mb-2 { margin-bottom: 8px; }
</style>

<script>
let generatedPassword = '';

// ---- Toggle visibility ----
function togglePwdVisibility() {
    const input = document.getElementById('secret');
    const btn   = document.getElementById('toggleBtn');
    if (input.type === 'password') {
        input.type = 'text';
        btn.textContent = '🙈';
    } else {
        input.type = 'password';
        btn.textContent = '👁';
    }
}

// ---- Copy to clipboard ----
function copyPassword() {
    const val = document.getElementById('secret').value;
    if (!val) { alert('No password to copy.'); return; }
    navigator.clipboard.writeText(val).then(() => {
        const btn = event.target;
        btn.textContent = '✓';
        setTimeout(() => btn.textContent = '⎘', 1500);
    });
}

// ---- Generate password ----
function generateSIPPassword() {
    const length  = parseInt(document.getElementById('pwdLength').value);
    const upper   = document.getElementById('useUpper').checked;
    const lower   = document.getElementById('useLower').checked;
    const numbers = document.getElementById('useNumbers').checked;
    const symbols = document.getElementById('useSymbols').checked;

    let chars = '';
    if (upper)   chars += 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    if (lower)   chars += 'abcdefghjkmnpqrstuvwxyz';
    if (numbers) chars += '23456789';
    if (symbols) chars += '!@#$%&*';

    if (!chars) { chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789'; }

    let pwd = '';
    const arr = new Uint32Array(length);
    window.crypto.getRandomValues(arr);
    for (let i = 0; i < length; i++) {
        pwd += chars[arr[i] % chars.length];
    }

    generatedPassword = pwd;

    // Show preview
    const preview = document.getElementById('genPreview');
    preview.style.display = 'flex';
    document.getElementById('genPreviewText').textContent = pwd;
}

// ---- Use generated password ----
function useGeneratedPassword() {
    if (!generatedPassword) return;
    const input = document.getElementById('secret');
    input.value = generatedPassword;
    input.type  = 'text';
    document.getElementById('toggleBtn').textContent = '🙈';
    document.getElementById('genPreview').style.display = 'none';
    updateStrength(generatedPassword);
}

// ---- Password strength ----
function updateStrength(val) {
    const bar   = document.getElementById('strengthBar');
    const label = document.getElementById('strengthLabel');
    if (!val) { bar.style.width = '0%'; label.textContent = ''; return; }

    let score = 0;
    if (val.length >= 8)  score++;
    if (val.length >= 12) score++;
    if (val.length >= 16) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[a-z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    const levels = [
        { min: 0, pct: '15%', color: '#ef4444', text: 'Very Weak' },
        { min: 2, pct: '30%', color: '#ef4444', text: 'Weak' },
        { min: 3, pct: '50%', color: '#f59e0b', text: 'Fair' },
        { min: 4, pct: '70%', color: '#f59e0b', text: 'Good' },
        { min: 5, pct: '85%', color: '#22c55e', text: 'Strong' },
        { min: 6, pct: '100%', color: '#00d4aa', text: '💪 Very Strong' },
    ];

    let level = levels[0];
    for (const l of levels) { if (score >= l.min) level = l; }

    bar.style.width      = level.pct;
    bar.style.background = level.color;
    label.textContent    = level.text;
    label.style.color    = level.color;
}

// Listen to manual input
document.getElementById('secret').addEventListener('input', function() {
    updateStrength(this.value);
});
</script>
