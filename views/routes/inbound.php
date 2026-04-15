<!-- views/routes/inbound.php -->
<div class="page-header">
    <h1 class="page-title"><?= t('inbound_routes') ?></h1>
    <a href="?page=inbound_routes&action=add" class="btn btn-accent">+ <?= t('add_new') ?></a>
</div>
<div class="card">
    <div class="card-body p-0">
        <?php if (empty($routes)): ?>
        <div class="empty-state"><div class="empty-icon">↘</div><p><?= t('no_records') ?></p></div>
        <?php else: ?>
        <table class="data-table">
            <thead><tr><th><?= t('did') ?></th><th>CID</th><th><?= t('description') ?></th><th>Destination Type</th><th>Destination</th><th>Priority</th><th><?= t('status') ?></th><th><?= t('actions') ?></th></tr></thead>
            <tbody>
            <?php foreach ($routes as $r): ?>
            <tr>
                <td class="mono font-bold"><?= sanitize($r['did']) ?: '<span class="text-muted">Any</span>' ?></td>
                <td class="mono text-sm"><?= sanitize($r['cid_number']) ?: '—' ?></td>
                <td><?= sanitize($r['description']) ?></td>
                <td><span class="badge badge-info"><?= $r['destination_type'] ?></span></td>
                <td class="mono"><?= sanitize($r['destination']) ?></td>
                <td class="mono"><?= $r['priority'] ?></td>
                <td><span class="badge <?= $r['enabled'] ? 'badge-success' : 'badge-default' ?>"><?= $r['enabled'] ? t('enabled') : t('disabled') ?></span></td>
                <td class="action-cell">
                    <a href="?page=inbound_routes&action=edit&id=<?= $r['id'] ?>" class="btn btn-sm btn-ghost"><?= t('edit') ?></a>
                    <a href="?page=inbound_routes&action=delete&id=<?= $r['id'] ?>" class="btn btn-sm btn-danger-ghost" onclick="return confirm('<?= t('confirm_delete') ?>')">✕</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
