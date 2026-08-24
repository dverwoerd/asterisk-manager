<?php $isEdit = ($action === 'edit'); ?>
<div class="page-header">
    <h1 class="page-title"><?= $title ?></h1>
    <a href="?page=queues" class="btn btn-ghost">← <?= t('back') ?></a>
</div>

<form method="POST" action="?page=queues&action=post_<?= $action ?><?= $isEdit ? '&id='.$queue['id'] : '' ?>">
    <?= csrf() ?>
    <div class="form-layout">
        <div class="form-col">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Queue Settings</h3></div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label><?= t('number') ?> *</label>
                            <input type="text" name="number" class="form-control mono" value="<?= sanitize($queue['number']) ?>" pattern="\d+" required placeholder="e.g. 200">
                        </div>
                        <div class="form-group">
                            <label><?= t('name') ?> *</label>
                            <input type="text" name="name" class="form-control mono" value="<?= sanitize($queue['name']) ?>" required placeholder="support">
                            <small class="form-hint">Lowercase, no spaces (used in config)</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?= t('strategy') ?></label>
                        <select name="strategy" class="form-control">
                            <?php foreach (['ringall'=>'Ring All','leastrecent'=>'Least Recent','fewestcalls'=>'Fewest Calls','random'=>'Random','rrmemory'=>'Round Robin','linear'=>'Linear'] as $v => $l): ?>
                            <option value="<?= $v ?>" <?= $queue['strategy']===$v?'selected':'' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label><?= t('timeout') ?> (sec)</label>
                            <input type="number" name="timeout" class="form-control" value="<?= (int)$queue['timeout'] ?>" min="5" max="120">
                        </div>
                        <div class="form-group">
                            <label><?= t('wrapup_time') ?> (sec)</label>
                            <input type="number" name="wrapup_time" class="form-control" value="<?= (int)$queue['wrapup_time'] ?>" min="0">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label><?= t('max_callers') ?></label>
                            <input type="number" name="max_callers" class="form-control" value="<?= (int)$queue['max_callers'] ?>" min="0" placeholder="0 = unlimited">
                        </div>
                        <div class="form-group">
                            <label><?= t('music_on_hold') ?></label>
                            <input type="text" name="music_on_hold" class="form-control mono" value="<?= sanitize($queue['music_on_hold']) ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Announce Hold Time</label>
                            <select name="announce_hold_time" class="form-control">
                                <?php foreach (['yes','no','once'] as $v): ?>
                                <option value="<?= $v ?>" <?= $queue['announce_hold_time']===$v?'selected':'' ?>><?= ucfirst($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Announce Frequency (sec)</label>
                            <input type="number" name="announce_frequency" class="form-control" value="<?= (int)$queue['announce_frequency'] ?>" min="0">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Join Announcement (sound file)</label>
                        <div style="display:flex;gap:8px">
                            <input type="text" name="join_announcement" id="join_announcement"
                                   class="form-control mono"
                                   value="<?= sanitize($queue['join_announcement']) ?>"
                                   placeholder="bijv. queue-thankyou of custom/welkom">
                            <button type="button" class="btn btn-ghost" onclick="openSoundPicker('join_announcement')">🔊 Kies</button>
                        </div>
                        <small class="form-hint">Laat leeg voor geen aankondiging. Gebruik <code>custom/bestandsnaam</code> voor eigen bestanden.</small>
                    </div>
                    <div class="form-group">
                        <label>Music on Hold</label>
                        <div style="display:flex;gap:8px">
                            <input type="text" name="music_on_hold" id="music_on_hold_field"
                                   class="form-control mono"
                                   value="<?= sanitize($queue['music_on_hold'] ?? 'default') ?>">
                            <button type="button" class="btn btn-ghost" onclick="openSoundPicker('music_on_hold_field')">🔊 Kies</button>
                        </div>
                    </div>

                    <!-- Sound Picker Modal -->
                    <div id="soundPickerModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:1000;align-items:center;justify-content:center">
                        <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:24px;width:600px;max-height:80vh;display:flex;flex-direction:column">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
                                <h3 style="margin:0">🔊 Kies een Soundfile</h3>
                                <button type="button" onclick="closeSoundPicker()" class="btn btn-ghost btn-sm">✕</button>
                            </div>
                            <input type="text" id="soundSearch" class="form-control mono" placeholder="Zoek..." oninput="filterSounds()" style="margin-bottom:12px">
                            <div style="display:flex;gap:8px;margin-bottom:12px">
                                <button type="button" class="btn btn-sm btn-ghost" onclick="loadSounds('en')">EN</button>
                                <button type="button" class="btn btn-sm btn-ghost" onclick="loadSounds('nl')">NL</button>
                                <button type="button" class="btn btn-sm btn-accent" onclick="loadSounds('custom')">Custom</button>
                            </div>
                            <div id="soundList" style="overflow-y:auto;flex:1;display:grid;grid-template-columns:1fr 1fr;gap:4px"></div>
                        </div>
                    </div>

                    <script>
                    let currentField = null;
                    let currentPrefix = '';

                    function openSoundPicker(fieldId) {
                        currentField = fieldId;
                        document.getElementById('soundPickerModal').style.display = 'flex';
                        loadSounds('en');
                    }

                    function closeSoundPicker() {
                        document.getElementById('soundPickerModal').style.display = 'none';
                    }

                    function loadSounds(lang) {
                        currentPrefix = lang === 'custom' ? 'custom/' : '';
                        fetch('?page=sounds&action=list&lang=' + lang)
                            .then(r => r.json())
                            .then(data => {
                                const sounds = lang === 'custom' ? data.custom : data.sounds;
                                renderSounds(sounds, currentPrefix);
                            });
                    }

                    function renderSounds(sounds, prefix) {
                        const list = document.getElementById('soundList');
                        list.innerHTML = sounds.map(s =>
                            `<div class="sound-item mono" style="padding:6px 10px;border-radius:4px;cursor:pointer;background:var(--bg-secondary);font-size:12px"
                                  onclick="selectSound('${prefix}${s}')">${prefix}${s}</div>`
                        ).join('');
                    }

                    function selectSound(name) {
                        document.getElementById(currentField).value = name;
                        closeSoundPicker();
                    }

                    function filterSounds() {
                        const q = document.getElementById('soundSearch').value.toLowerCase();
                        document.querySelectorAll('.sound-item').forEach(el => {
                            el.style.display = el.textContent.toLowerCase().includes(q) ? '' : 'none';
                        });
                    }

                    // Sluit modal bij klik buiten
                    document.getElementById('soundPickerModal').addEventListener('click', function(e) {
                        if (e.target === this) closeSoundPicker();
                    });
                    </script>
                    <div class="form-group">
                        <label>Caller ID Prefix</label>
                        <input type="text" name="caller_id_prefix" class="form-control mono" value="<?= sanitize($queue['caller_id_prefix']) ?>" placeholder="e.g. [Support] ">
                    </div>
                    <div class="form-check">
                        <label class="toggle-label">
                            <input type="checkbox" name="enabled" value="1" <?= $queue['enabled'] ? 'checked' : '' ?>>
                            <span class="toggle"></span><?= t('enabled') ?>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Timeout Destination -->
            <div class="card mt-4">
                <div class="card-header"><h3 class="card-title">Timeout Destination</h3></div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Destination Type</label>
                            <select name="timeout_destination_type" class="form-control">
                                <?php foreach (['hangup','extension','queue','voicemail','external'] as $v): ?>
                                <option value="<?= $v ?>" <?= $queue['timeout_destination_type']===$v?'selected':'' ?>><?= ucfirst($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Destination</label>
                            <input type="text" name="timeout_destination" class="form-control mono" value="<?= sanitize($queue['timeout_destination']) ?>" placeholder="Extension, queue name...">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Members -->
        <div class="form-col">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Queue Members <span id="memberCountBadge" class="badge badge-info"><?= count($members) ?> members</span></h3>
                    <button type="button" id="addMember" class="btn btn-sm btn-ghost">+ Add Member</button>
                </div>
                <div class="card-body">
                    <div id="membersContainer">
                        <?php if (empty($members)): ?>
                        <div class="member-row" style="display:flex;gap:8px;margin-bottom:8px;align-items:center">
                            <select name="members[]" class="form-control">
                                <option value="">Select Extension...</option>
                                <?php foreach ($extensions as $e): ?>
                                <option value="<?= $e['extension'] ?>"><?= sanitize($e['extension']) ?> — <?= sanitize($e['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="number" name="penalties[]" class="form-control" style="width:80px" placeholder="Penalty" min="0" max="100" value="0">
                            <button type="button" class="btn btn-sm btn-danger-ghost remove-member">✕</button>
                        </div>
                        <?php else: ?>
                        <?php foreach ($members as $m): ?>
                        <div class="member-row" style="display:flex;gap:8px;margin-bottom:8px;align-items:center">
                            <select name="members[]" class="form-control">
                                <option value="">Select Extension...</option>
                                <?php foreach ($extensions as $e): ?>
                                <option value="<?= $e['extension'] ?>" <?= $m['extension']===$e['extension']?'selected':'' ?>><?= sanitize($e['extension']) ?> — <?= sanitize($e['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="number" name="penalties[]" class="form-control" style="width:80px" placeholder="Penalty" min="0" max="100" value="<?= (int)$m['penalty'] ?>">
                            <button type="button" class="btn btn-sm btn-danger-ghost remove-member">✕</button>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <small class="form-hint">Penalty: lower = higher priority (0 = highest). Agents with equal penalty ring simultaneously.</small>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header"><h3 class="card-title"><?= t('notes') ?></h3></div>
                <div class="card-body">
                    <textarea name="notes" class="form-control" rows="4"><?= sanitize($queue['notes']) ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="form-actions">
        <a href="?page=queues" class="btn btn-ghost"><?= t('cancel') ?></a>
        <button type="submit" class="btn btn-accent">💾 <?= t('save') ?> & <?= t('reload') ?></button>
    </div>
</form>
