<div class="page-header">
    <h1 class="page-title">Add Dialplan Entry</h1>
    <a href="?page=dialplan&action=context&id=<?= $contextId ?>" class="btn btn-ghost">← Back</a>
</div>
<form method="POST" action="?page=dialplan&action=post_add_entry">
    <?= csrf() ?>
    <input type="hidden" name="context_id" value="<?= $contextId ?>">
    <div class="card" style="max-width:680px">
        <div class="card-body">
            <div class="form-row">
                <div class="form-group">
                    <label>Extension *</label>
                    <input type="text" name="extension" class="form-control mono"
                           value="<?= sanitize($entry['extension'] ?? '') ?>"
                           required placeholder="e.g. 1234 or _X. or s">
                </div>
                <div class="form-group">
                    <label>Priority</label>
                    <input type="number" name="priority" class="form-control"
                           value="<?= (int)($entry['priority'] ?? 1) ?>" min="1">
                </div>
            </div>
            <div class="form-group">
                <label>Application *</label>
                <input type="text" name="application" class="form-control mono"
                       value="<?= sanitize($entry['application'] ?? '') ?>"
                       required placeholder="e.g. Dial, Playback, Goto, Hangup"
                       list="apps-list">
                <datalist id="apps-list">
                    <option value="Answer">
                    <option value="Background">
                    <option value="Dial">
                    <option value="Goto">
                    <option value="GotoIf">
                    <option value="Hangup">
                    <option value="NoOp">
                    <option value="Playback">
                    <option value="Queue">
                    <option value="Record">
                    <option value="Set">
                    <option value="VoiceMail">
                    <option value="Wait">
                    <option value="AGI">
                </datalist>
            </div>
            <div class="form-group">
                <label>Application Data</label>
                <input type="text" name="app_data" class="form-control mono"
                       value="<?= sanitize($entry['app_data'] ?? '') ?>"
                       placeholder="e.g. PJSIP/1001,30,tT">
                <small class="form-hint">Arguments passed to the application.</small>
            </div>
            <div class="form-group">
                <label>Notes</label>
                <input type="text" name="notes" class="form-control"
                       value="<?= sanitize($entry['notes'] ?? '') ?>">
            </div>
        </div>
    </div>
    <div class="form-actions">
        <a href="?page=dialplan&action=context&id=<?= $contextId ?>" class="btn btn-ghost">Cancel</a>
        <button type="submit" class="btn btn-accent">💾 Save Entry</button>
    </div>
</form>
