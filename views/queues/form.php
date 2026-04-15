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
                        <input type="text" name="join_announcement" class="form-control mono" value="<?= sanitize($queue['join_announcement']) ?>" placeholder="e.g. queue-thankyou">
                    </div>
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
