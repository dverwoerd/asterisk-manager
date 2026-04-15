<!-- ============================================================
     views/queues/index.php
     ============================================================ -->
<?php /* Queue index */ ?>
<div class="page-header">
    <h1 class="page-title"><?= t('queues') ?></h1>
    <a href="?page=queues&action=add" class="btn btn-accent">+ <?= t('add_new') ?></a>
</div>
<div class="card">
    <div class="card-body p-0">
        <?php if (empty($queues)): ?>
        <div class="empty-state"><div class="empty-icon">⋮⋮</div><p><?= t('no_records') ?></p><a href="?page=queues&action=add" class="btn btn-accent">Add First Queue</a></div>
        <?php else: ?>
        <table class="data-table">
            <thead><tr><th><?= t('number') ?></th><th><?= t('name') ?></th><th><?= t('strategy') ?></th><th>Members</th><th><?= t('timeout') ?></th><th>Max</th><th><?= t('status') ?></th><th><?= t('actions') ?></th></tr></thead>
            <tbody>
            <?php foreach ($queues as $q): ?>
            <tr>
                <td class="mono font-bold"><?= sanitize($q['number']) ?></td>
                <td><?= sanitize($q['name']) ?></td>
                <td class="mono text-sm"><?= $q['strategy'] ?></td>
                <td><span class="badge badge-info"><?= $q['member_count'] ?></span></td>
                <td class="mono"><?= $q['timeout'] ?>s</td>
                <td class="mono"><?= $q['max_callers'] ?: '∞' ?></td>
                <td><span class="badge <?= $q['enabled'] ? 'badge-success' : 'badge-default' ?>"><?= $q['enabled'] ? t('enabled') : t('disabled') ?></span></td>
                <td class="action-cell">
                    <a href="?page=queues&action=edit&id=<?= $q['id'] ?>" class="btn btn-sm btn-ghost"><?= t('edit') ?></a>
                    <a href="?page=queues&action=delete&id=<?= $q['id'] ?>" class="btn btn-sm btn-danger-ghost" onclick="return confirm('<?= t('confirm_delete') ?>')">✕</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
