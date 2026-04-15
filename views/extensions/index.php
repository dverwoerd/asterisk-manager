<div class="page-header">
    <h1 class="page-title"><?= t('extensions') ?></h1>
    <div class="page-actions">
        <a href="?page=extensions&action=add" class="btn btn-accent">+ <?= t('add_new') ?></a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <form method="GET" action="" class="search-form">
            <input type="hidden" name="page" value="extensions">
            <input type="text" name="search" value="<?= sanitize($search) ?>" placeholder="<?= t('search') ?>..." class="form-control form-control-sm search-input">
            <button type="submit" class="btn btn-sm btn-ghost">⌕</button>
            <?php if ($search): ?><a href="?page=extensions" class="btn btn-sm btn-ghost">✕</a><?php endif; ?>
        </form>
        <span class="record-count"><?= count($extensions) ?> records</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($extensions)): ?>
        <div class="empty-state">
            <div class="empty-icon">☎</div>
            <p><?= t('no_records') ?></p>
            <a href="?page=extensions&action=add" class="btn btn-accent">Add First Extension</a>
        </div>
        <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th><?= t('extension') ?></th>
                    <th><?= t('full_name') ?></th>
                    <th><?= t('callerid') ?></th>
                    <th><?= t('context') ?></th>
                    <th><?= t('codecs') ?></th>
                    <th><?= t('voicemail') ?></th>
                    <th><?= t('status') ?></th>
                    <th>Toestel</th>
                    <th><?= t('actions') ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($extensions as $ext): ?>
            <tr>
                <td class="mono font-bold"><?= sanitize($ext['extension']) ?></td>
                <td><?= sanitize($ext['full_name']) ?></td>
                <td class="mono text-sm"><?= sanitize($ext['callerid_name']) ?> &lt;<?= sanitize($ext['callerid_number']) ?>&gt;</td>
                <td class="mono text-sm"><?= sanitize($ext['context']) ?></td>
                <td class="mono text-sm"><?= sanitize($ext['codecs']) ?></td>
                <td>
                    <?php if ($ext['voicemail_enabled']): ?>
                    <span class="badge badge-success">✓</span>
                    <?php else: ?>
                    <span class="badge badge-default">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge <?= $ext['enabled'] ? 'badge-success' : 'badge-default' ?>">
                        <?= $ext['enabled'] ? t('enabled') : t('disabled') ?>
                    </span>
                </td>
                <td>
                    <?php if ($ext['phone_id']): ?>
                    <span class="badge badge-info"><?= sanitize($ext['phone_model']) ?></span>
                    <br><span class="mono text-sm text-muted"><?= sanitize($ext['phone_mac']) ?></span>
                    <?php else: ?>
                    <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td class="action-cell">
                    <a href="?page=extensions&action=edit&id=<?= $ext['id'] ?>" class="btn btn-sm btn-ghost"><?= t('edit') ?></a>
                    <?php if ($ext['phone_id']): ?>
                    <a href="?page=provision&action=edit&id=<?= $ext['phone_id'] ?>" class="btn btn-sm btn-ghost" title="Toestel bewerken">📱</a>
                    <a href="?page=provision&action=reboot&id=<?= $ext['phone_id'] ?>"
                       class="btn btn-sm btn-ghost" title="Reboot"
                       onclick="return confirm('Toestel herstarten?')">↺</a>
                    <?php endif; ?>
                    <a href="?page=extensions&action=delete&id=<?= $ext['id'] ?>"
                       class="btn btn-sm btn-danger-ghost"
                       onclick="return confirm('<?= t('confirm_delete') ?>')">✕</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
