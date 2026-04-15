<div class="page-header">
    <h1 class="page-title">Context: [<?= sanitize($context['name']) ?>]</h1>
    <div class="page-actions">
        <a href="?page=dialplan" class="btn btn-ghost">← Back</a>
        <a href="?page=dialplan&action=add_entry&context_id=<?= $context['id'] ?>" class="btn btn-accent">+ Add Entry</a>
    </div>
</div>
<?php if ($context['description']): ?>
<p class="text-muted mb-4"><?= sanitize($context['description']) ?></p>
<?php endif; ?>
<div class="card">
    <div class="card-body p-0">
        <?php if (empty($entries)): ?>
        <div class="empty-state">
            <div class="empty-icon">⌥</div>
            <p>No entries in this context yet.</p>
            <a href="?page=dialplan&action=add_entry&context_id=<?= $context['id'] ?>" class="btn btn-accent">Add First Entry</a>
        </div>
        <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Extension</th>
                    <th>Priority</th>
                    <th>Application</th>
                    <th>Data</th>
                    <th>Notes</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($entries as $e): ?>
            <tr>
                <td class="mono font-bold"><?= sanitize($e['extension']) ?></td>
                <td class="mono"><?= $e['priority'] ?></td>
                <td class="mono" style="color:var(--accent)"><?= sanitize($e['application']) ?></td>
                <td class="mono text-sm"><?= sanitize($e['app_data']) ?></td>
                <td class="text-muted text-sm"><?= sanitize($e['notes']) ?></td>
                <td class="action-cell">
                    <a href="?page=dialplan&action=delete_entry&id=<?= $e['id'] ?>"
                       class="btn btn-sm btn-danger-ghost"
                       onclick="return confirm('Delete this entry?')">✕</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
