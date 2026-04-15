<!-- views/dialplan/index.php -->
<div class="page-header">
    <h1 class="page-title"><?= t('dialplan') ?></h1>
    <div class="page-actions">
        <a href="?page=dialplan&action=add_context" class="btn btn-ghost">+ Add Context</a>
        <a href="?page=dialplan&action=reload_all" class="btn btn-accent" onclick="return confirm('Regenerate ALL config files and reload Asterisk?')">⟳ Full Reload</a>
    </div>
</div>

<div class="card mb-4" style="border-color:var(--warning)">
    <div class="card-body" style="display:flex;gap:10px;align-items:center;padding:10px 16px">
        <span style="color:var(--warning)">⚠</span>
        <span class="text-sm">The dialplan below is for <strong>custom contexts only</strong>. Extensions, queues, ring groups, and routes are managed automatically via their respective pages and written to <code class="mono">extensions.conf</code> on save.</span>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <?php if (empty($contexts)): ?>
        <div class="empty-state"><div class="empty-icon">⌥</div><p>No custom dialplan contexts. Add one to define custom call logic.</p></div>
        <?php else: ?>
        <table class="data-table">
            <thead><tr><th>Context Name</th><th>Description</th><th>Entries</th><th><?= t('actions') ?></th></tr></thead>
            <tbody>
            <?php foreach ($contexts as $ctx): ?>
            <tr>
                <td class="mono font-bold">[<?= sanitize($ctx['name']) ?>]</td>
                <td class="text-muted text-sm"><?= sanitize($ctx['description']) ?></td>
                <td><span class="badge badge-info"><?= $ctx['entry_count'] ?></span></td>
                <td class="action-cell">
                    <a href="?page=dialplan&action=context&id=<?= $ctx['id'] ?>" class="btn btn-sm btn-ghost">View / Edit</a>
                    <a href="?page=dialplan&action=add_entry&context_id=<?= $ctx['id'] ?>" class="btn btn-sm btn-ghost">+ Entry</a>
                    <a href="?page=dialplan&action=delete_context&id=<?= $ctx['id'] ?>" class="btn btn-sm btn-danger-ghost" onclick="return confirm('Delete context and all its entries?')">✕</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
