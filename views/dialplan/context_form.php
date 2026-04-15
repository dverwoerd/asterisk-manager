<?php $isEdit = isset($context['id']); ?>
<div class="page-header">
    <h1 class="page-title">Add Dialplan Context</h1>
    <a href="?page=dialplan" class="btn btn-ghost">← Back</a>
</div>
<form method="POST" action="?page=dialplan&action=post_add_context">
    <?= csrf() ?>
    <div class="card" style="max-width:600px">
        <div class="card-body">
            <div class="form-group">
                <label>Context Name *</label>
                <input type="text" name="name" class="form-control mono"
                       value="<?= sanitize($context['name'] ?? '') ?>"
                       required placeholder="e.g. custom-ivr" pattern="[a-zA-Z0-9_-]+">
                <small class="form-hint">Alphanumeric, underscores and hyphens only.</small>
            </div>
            <div class="form-group">
                <label>Description</label>
                <input type="text" name="description" class="form-control"
                       value="<?= sanitize($context['description'] ?? '') ?>"
                       placeholder="What is this context used for?">
            </div>
        </div>
    </div>
    <div class="form-actions">
        <a href="?page=dialplan" class="btn btn-ghost">Cancel</a>
        <button type="submit" class="btn btn-accent">💾 Save Context</button>
    </div>
</form>
