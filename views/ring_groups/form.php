<?php $isEdit = ($action === 'edit'); ?>
<div class="page-header">
    <h1 class="page-title"><?= $title ?></h1>
    <a href="?page=ring_groups" class="btn btn-ghost">← <?= t('back') ?></a>
</div>

<form method="POST" action="?page=ring_groups&action=post_<?= $action ?><?= $isEdit ? '&id='.$group['id'] : '' ?>">
    <?= csrf() ?>
    <div class="form-layout">
        <div class="form-col">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Ring Group Settings</h3></div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label><?= t('number') ?> *</label>
                            <input type="text" name="number" class="form-control mono" value="<?= sanitize($group['number']) ?>" pattern="\d+" required placeholder="e.g. 300">
                        </div>
                        <div class="form-group">
                            <label><?= t('name') ?> *</label>
                            <input type="text" name="name" class="form-control" value="<?= sanitize($group['name']) ?>" required placeholder="Sales Team">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label><?= t('strategy') ?></label>
                            <select name="strategy" class="form-control">
                                <?php foreach ([
                                    'ringall'         => 'Ring All Simultaneously',
                                    'hunt'            => 'Hunt (sequential)',
                                    'memoryhunt'      => 'Memory Hunt',
                                    'firstnotonphone' => 'First Not On Phone',
                                    'random'          => 'Random',
                                ] as $v => $l): ?>
                                <option value="<?= $v ?>" <?= $group['strategy']===$v?'selected':'' ?>><?= $l ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><?= t('ring_time') ?></label>
                            <input type="number" name="ring_time" class="form-control" value="<?= (int)$group['ring_time'] ?>" min="5" max="120">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Caller ID Prefix</label>
                        <input type="text" name="caller_id_prefix" class="form-control mono" value="<?= sanitize($group['caller_id_prefix']) ?>" placeholder="e.g. [Sales] ">
                        <small class="form-hint">Prepended to caller ID name when ring group is called</small>
                    </div>
                    <div class="form-check">
                        <label class="toggle-label">
                            <input type="checkbox" name="enabled" value="1" <?= $group['enabled'] ? 'checked' : '' ?>>
                            <span class="toggle"></span><?= t('enabled') ?>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Destination after timeout -->
            <div class="card mt-4">
                <div class="card-header"><h3 class="card-title">No Answer Destination</h3></div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Type</label>
                            <select name="destination_type" class="form-control">
                                <?php foreach (['hangup','extension','queue','voicemail','external','announcement'] as $v): ?>
                                <option value="<?= $v ?>" <?= $group['destination_type']===$v?'selected':'' ?>><?= ucfirst($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Destination</label>
                            <input type="text" name="destination" class="form-control mono" value="<?= sanitize($group['destination']) ?>" placeholder="Extension, queue name, number...">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header"><h3 class="card-title"><?= t('notes') ?></h3></div>
                <div class="card-body">
                    <textarea name="notes" class="form-control" rows="3"><?= sanitize($group['notes']) ?></textarea>
                </div>
            </div>
        </div>

        <!-- Members -->
        <div class="form-col">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Members <span id="memberCountBadge" class="badge badge-info"><?= count($members) ?> members</span></h3>
                    <button type="button" id="addMember" class="btn btn-sm btn-ghost">+ Add Member</button>
                </div>
                <div class="card-body">
                    <div id="membersContainer">
                        <?php $membersToShow = empty($members) ? [['extension'=>'','ring_time'=>20]] : $members; ?>
                        <?php foreach ($membersToShow as $i => $m): ?>
                        <div class="member-row" style="display:flex;gap:8px;margin-bottom:8px;align-items:center">
                            <span class="mono text-muted" style="width:20px;text-align:right;font-size:11px"><?= $i+1 ?>.</span>
                            <select name="members[]" class="form-control">
                                <option value="">Select Extension...</option>
                                <?php foreach ($extensions as $e): ?>
                                <option value="<?= $e['extension'] ?>" <?= ($m['extension']??'')===$e['extension']?'selected':'' ?>><?= sanitize($e['extension']) ?> — <?= sanitize($e['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="number" name="ring_times[]" class="form-control" style="width:80px" placeholder="Ring(s)" min="5" max="120" value="<?= (int)($m['ring_time']??20) ?>">
                            <button type="button" class="btn btn-sm btn-danger-ghost remove-member">✕</button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <small class="form-hint">Order matters for hunt/memoryhunt strategies. Ring time per member for sequential strategies.</small>
                </div>
            </div>
        </div>
    </div>

    <div class="form-actions">
        <a href="?page=ring_groups" class="btn btn-ghost"><?= t('cancel') ?></a>
        <button type="submit" class="btn btn-accent">💾 <?= t('save') ?> & <?= t('reload') ?></button>
    </div>
</form>
