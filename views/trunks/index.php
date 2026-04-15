<!-- views/trunks/index.php -->
<div class="page-header">
    <h1 class="page-title"><?= t('trunks') ?></h1>
    <a href="?page=trunks&action=add" class="btn btn-accent">+ <?= t('add_new') ?></a>
</div>
<div class="card">
    <div class="card-body p-0">
        <?php if (empty($trunks)): ?>
        <div class="empty-state"><div class="empty-icon">⟷</div><p>No trunks configured. Add a SIP trunk to make and receive calls.</p><a href="?page=trunks&action=add" class="btn btn-accent">Add Trunk</a></div>
        <?php else: ?>
        <table class="data-table">
            <thead><tr><th><?= t('name') ?></th><th>Type</th><th>Host</th><th>Username</th><th>Max Channels</th><th>Context</th><th><?= t('status') ?></th><th><?= t('actions') ?></th></tr></thead>
            <tbody>
            <?php foreach ($trunks as $t): ?>
            <tr>
                <td class="mono font-bold"><?= sanitize($t['name']) ?></td>
                <td><span class="badge badge-info"><?= strtoupper($t['type']) ?></span></td>
                <td class="mono text-sm"><?= sanitize($t['host']) ?>:<?= $t['port'] ?></td>
                <td class="mono"><?= sanitize($t['username']) ?></td>
                <td class="mono"><?= $t['max_channels'] ?></td>
                <td class="mono text-sm"><?= sanitize($t['context']) ?></td>
                <td><span class="badge <?= $t['enabled'] ? 'badge-success' : 'badge-default' ?>"><?= $t['enabled'] ? t('enabled') : t('disabled') ?></span></td>
                <td class="action-cell">
                    <a href="?page=trunks&action=edit&id=<?= $t['id'] ?>" class="btn btn-sm btn-ghost"><?= t('edit') ?></a>
                    <a href="?page=trunks&action=delete&id=<?= $t['id'] ?>" class="btn btn-sm btn-danger-ghost" onclick="return confirm('<?= t('confirm_delete') ?>')">✕</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
