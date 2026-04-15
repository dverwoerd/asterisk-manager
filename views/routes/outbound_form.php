<?php $isEdit = ($action === 'edit'); ?>
<div class="page-header">
    <h1 class="page-title"><?= $title ?></h1>
    <a href="?page=outbound_routes" class="btn btn-ghost">← <?= t('back') ?></a>
</div>

<form method="POST" action="?page=outbound_routes&action=post_<?= $action ?><?= $isEdit ? '&id='.$route['id'] : '' ?>">
    <?= csrf() ?>
    <div class="form-layout">
        <div class="form-col">
            <!-- Route info -->
            <div class="card">
                <div class="card-header"><h3 class="card-title">Route Settings</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label><?= t('name') ?> *</label>
                        <input type="text" name="name" class="form-control" value="<?= sanitize($route['name']) ?>" required placeholder="e.g. National Calls">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Priority</label>
                            <input type="number" name="priority" class="form-control" value="<?= (int)$route['priority'] ?>" min="0">
                            <small class="form-hint">Higher = matched first</small>
                        </div>
                        <div class="form-group" style="display:flex;flex-direction:column;justify-content:flex-end">
                            <label class="toggle-label" style="margin-top:8px">
                                <input type="checkbox" name="emergency" value="1" <?= $route['emergency'] ? 'checked' : '' ?>>
                                <span class="toggle"></span>Emergency Route
                            </label>
                        </div>
                    </div>
                    <div class="form-check">
                        <label class="toggle-label">
                            <input type="checkbox" name="enabled" value="1" <?= $route['enabled'] ? 'checked' : '' ?>>
                            <span class="toggle"></span><?= t('enabled') ?>
                        </label>
                    </div>
                    <div class="form-group mt-4">
                        <label>Notes</label>
                        <textarea name="notes" class="form-control" rows="2"><?= sanitize($route['notes']) ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Trunk selection -->
            <div class="card mt-4">
                <div class="card-header"><h3 class="card-title">Trunks (ordered by preference)</h3></div>
                <div class="card-body">
                    <?php foreach ($trunks as $i => $trunk): ?>
                    <label class="toggle-label" style="margin-bottom:10px;border:1px solid var(--border);padding:8px;border-radius:4px">
                        <input type="checkbox" name="trunk_ids[]" value="<?= $trunk['id'] ?>"
                               <?= in_array($trunk['id'], $routeTrunks ?? []) ? 'checked' : '' ?>>
                        <span class="toggle"></span>
                        <span>
                            <span class="mono font-bold"><?= sanitize($trunk['name']) ?></span>
                            <span class="text-muted text-sm"> — <?= sanitize($trunk['host']) ?></span>
                        </span>
                    </label>
                    <?php endforeach; ?>
                    <?php if (empty($trunks)): ?>
                    <p class="text-muted text-sm">No trunks configured. <a href="?page=trunks&action=add">Add a trunk first.</a></p>
                    <?php endif; ?>
                    <small class="form-hint">Trunks are tried in order. If the first fails, the next is tried.</small>
                </div>
            </div>
        </div>

        <!-- Dial Patterns -->
        <div class="form-col">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Dial Patterns</h3>
                    <button type="button" id="addPattern" class="btn btn-sm btn-ghost">+ Add Pattern</button>
                </div>
                <div class="card-body">
                    <div class="dial-pattern-header" style="display:grid;grid-template-columns:1fr 90px 90px 36px;gap:6px;margin-bottom:6px">
                        <span class="form-hint">Match Pattern</span>
                        <span class="form-hint">Prepend</span>
                        <span class="form-hint">Strip Prefix</span>
                        <span></span>
                    </div>
                    <div id="patternsContainer">
                        <?php $patternsToShow = empty($patterns) ? [['match_pattern'=>'','prepend'=>'','prefix'=>'']] : $patterns; ?>
                        <?php foreach ($patternsToShow as $p): ?>
                        <div class="pattern-row" style="display:grid;grid-template-columns:1fr 90px 90px 36px;gap:6px;margin-bottom:6px">
                            <input type="text" name="patterns[]" class="form-control mono form-control-sm" value="<?= sanitize($p['match_pattern']) ?>" placeholder="e.g. 0[1-9]X. or _31XXXXXXXXX">
                            <input type="text" name="prepends[]" class="form-control mono form-control-sm" value="<?= sanitize($p['prepend']) ?>" placeholder="e.g. +">
                            <input type="text" name="prefixes[]" class="form-control mono form-control-sm" value="<?= sanitize($p['prefix']) ?>" placeholder="e.g. 0">
                            <button type="button" class="btn btn-sm btn-danger-ghost remove-pattern">✕</button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-4" style="background:var(--bg);padding:12px;border-radius:4px;border:1px solid var(--border-light)">
                        <p class="text-sm text-muted font-bold" style="margin-bottom:6px">Pattern syntax:</p>
                        <table style="font-size:11px;font-family:var(--font-mono);width:100%">
                            <tr><td class="text-accent">X</td><td class="text-muted">Any digit 0-9</td></tr>
                            <tr><td class="text-accent">Z</td><td class="text-muted">Any digit 1-9</td></tr>
                            <tr><td class="text-accent">N</td><td class="text-muted">Any digit 2-9</td></tr>
                            <tr><td class="text-accent">.</td><td class="text-muted">One or more characters</td></tr>
                            <tr><td class="text-accent">[1-9]</td><td class="text-muted">Digit range</td></tr>
                            <tr><td class="text-accent">_</td><td class="text-muted">Pattern prefix (Asterisk)</td></tr>
                        </table>
                        <p class="text-sm text-muted" style="margin-top:8px">Examples: <span class="mono">0[1-9]XXXXXXX</span> (national), <span class="mono">00.</span> (international), <span class="mono">06XXXXXXXX</span> (mobile NL)</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="form-actions">
        <a href="?page=outbound_routes" class="btn btn-ghost"><?= t('cancel') ?></a>
        <button type="submit" class="btn btn-accent">💾 <?= t('save') ?> & <?= t('reload') ?></button>
    </div>
</form>
