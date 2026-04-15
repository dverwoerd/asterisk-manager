<!-- views/routes/outbound.php -->
<div class="page-header">
    <h1 class="page-title"><?= t('outbound_routes') ?></h1>
    <a href="?page=outbound_routes&action=add" class="btn btn-accent">+ <?= t('add_new') ?></a>
</div>
<div class="card">
    <div class="card-body p-0">
        <?php if (empty($routes)): ?>
        <div class="empty-state"><div class="empty-icon">↗</div><p><?= t('no_records') ?></p></div>
        <?php else: ?>
        <table class="data-table">
            <thead><tr><th><?= t('name') ?></th><th>Dial Patterns</th><th>Trunks</th><th>Priority</th><th>Emergency</th><th><?= t('status') ?></th><th><?= t('actions') ?></th></tr></thead>
            <tbody>
            <?php foreach ($routes as $r): ?>
            <tr>
                <td class="font-bold"><?= sanitize($r['name']) ?></td>
                <td class="mono text-sm">
                    <?php foreach (array_slice($r['patterns'],0,3) as $p): ?>
                    <span class="badge badge-default" style="margin:1px"><?= sanitize($p['match_pattern']) ?></span>
                    <?php endforeach; ?>
                    <?php if (count($r['patterns']) > 3): ?>
                    <span class="text-muted text-sm">+<?= count($r['patterns'])-3 ?> more</span>
                    <?php endif; ?>
                </td>
                <td class="mono text-sm">
                    <?php foreach ($r['trunks'] as $t): ?>
                    <span class="badge badge-info" style="margin:1px"><?= sanitize($t['name']) ?></span>
                    <?php endforeach; ?>
                </td>
                <td class="mono"><?= $r['priority'] ?></td>
                <td><?= $r['emergency'] ? '<span class="badge badge-danger">Yes</span>' : '<span class="badge badge-default">No</span>' ?></td>
                <td><span class="badge <?= $r['enabled'] ? 'badge-success' : 'badge-default' ?>"><?= $r['enabled'] ? t('enabled') : t('disabled') ?></span></td>
                <td class="action-cell">
                    <a href="?page=outbound_routes&action=edit&id=<?= $r['id'] ?>" class="btn btn-sm btn-ghost"><?= t('edit') ?></a>
                    <a href="?page=outbound_routes&action=delete&id=<?= $r['id'] ?>" class="btn btn-sm btn-danger-ghost" onclick="return confirm('<?= t('confirm_delete') ?>')">✕</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
