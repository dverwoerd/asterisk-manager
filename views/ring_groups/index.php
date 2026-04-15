<!-- views/ring_groups/index.php -->
<div class="page-header">
    <h1 class="page-title"><?= t('ring_groups') ?></h1>
    <a href="?page=ring_groups&action=add" class="btn btn-accent">+ <?= t('add_new') ?></a>
</div>
<div class="card">
    <div class="card-body p-0">
        <?php if (empty($groups)): ?>
        <div class="empty-state"><div class="empty-icon">◎</div><p><?= t('no_records') ?></p><a href="?page=ring_groups&action=add" class="btn btn-accent">Add First Ring Group</a></div>
        <?php else: ?>
        <table class="data-table">
            <thead><tr><th><?= t('number') ?></th><th><?= t('name') ?></th><th><?= t('strategy') ?></th><th>Members</th><th><?= t('ring_time') ?></th><th><?= t('status') ?></th><th><?= t('actions') ?></th></tr></thead>
            <tbody>
            <?php foreach ($groups as $g): ?>
            <tr>
                <td class="mono font-bold"><?= sanitize($g['number']) ?></td>
                <td><?= sanitize($g['name']) ?></td>
                <td class="mono text-sm"><?= $g['strategy'] ?></td>
                <td><span class="badge badge-info"><?= $g['member_count'] ?></span></td>
                <td class="mono"><?= $g['ring_time'] ?>s</td>
                <td><span class="badge <?= $g['enabled'] ? 'badge-success' : 'badge-default' ?>"><?= $g['enabled'] ? t('enabled') : t('disabled') ?></span></td>
                <td class="action-cell">
                    <a href="?page=ring_groups&action=edit&id=<?= $g['id'] ?>" class="btn btn-sm btn-ghost"><?= t('edit') ?></a>
                    <a href="?page=ring_groups&action=delete&id=<?= $g['id'] ?>" class="btn btn-sm btn-danger-ghost" onclick="return confirm('<?= t('confirm_delete') ?>')">✕</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
