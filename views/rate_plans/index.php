<!-- views/rate_plans/index.php -->
<div class="page-header">
    <h1 class="page-title"><?= t('rate_plans') ?></h1>
    <a href="?page=rate_plans&action=add" class="btn btn-accent">+ <?= t('add_new') ?></a>
</div>
<div class="card">
    <div class="card-body p-0">
        <?php if (empty($plans)): ?>
        <div class="empty-state"><div class="empty-icon">€</div><p>No rate plans configured.</p></div>
        <?php else: ?>
        <table class="data-table">
            <thead><tr><th>Name</th><th>Currency</th><th>Billing Increment</th><th>Connection Fee</th><th>Rates</th><th>Status</th><th><?= t('actions') ?></th></tr></thead>
            <tbody>
            <?php foreach ($plans as $p): ?>
            <tr>
                <td class="font-bold"><?= sanitize($p['name']) ?></td>
                <td class="mono"><?= $p['currency'] ?></td>
                <td class="mono"><?= $p['billing_increment'] ?>s</td>
                <td class="mono"><?= number_format($p['connection_fee'],4,',','.') ?></td>
                <td><a href="?page=rate_plans&action=view_plan&id=<?= $p['id'] ?>" class="badge badge-info"><?= $p['rate_count'] ?> rates</a></td>
                <td><span class="badge <?= $p['active'] ? 'badge-success' : 'badge-default' ?>"><?= $p['active'] ? 'Active' : 'Inactive' ?></span></td>
                <td class="action-cell">
                    <a href="?page=rate_plans&action=view_plan&id=<?= $p['id'] ?>" class="btn btn-sm btn-ghost">Rates</a>
                    <a href="?page=rate_plans&action=edit&id=<?= $p['id'] ?>" class="btn btn-sm btn-ghost"><?= t('edit') ?></a>
                    <a href="?page=rate_plans&action=delete&id=<?= $p['id'] ?>" class="btn btn-sm btn-danger-ghost" onclick="return confirm('<?= t('confirm_delete') ?>')">✕</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
