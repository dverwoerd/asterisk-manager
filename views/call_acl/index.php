<!-- views/call_acl/index.php -->
<div class="page-header">
    <h1 class="page-title"><?= t('nav_call_acl') ?></h1>
    <a href="?page=call_acl&action=add" class="btn btn-accent">+ <?= t('add_new') ?></a>
</div>
<div class="card mb-4">
    <div class="card-body text-sm">
        Toestellen in een groep mogen <strong>alleen</strong> bellen met (en gebeld worden door) andere toestellen
        uit dezelfde groep, plus de extensies die bij "Ook toegestaan" staan. Toestellen die in geen enkele groep zitten
        blijven onbeperkt. Bellen naar buiten, naar belgroepen/wachtrijen en inkomende gesprekken worden niet geblokkeerd.
    </div>
</div>
<div class="card">
    <div class="card-body p-0">
        <?php if (empty($groups)): ?>
        <div class="empty-state"><div class="empty-icon">⛨</div><p><?= t('no_records') ?></p><a href="?page=call_acl&action=add" class="btn btn-accent">Eerste groep toevoegen</a></div>
        <?php else: ?>
        <table class="data-table">
            <thead><tr><th><?= t('name') ?></th><th>Toestellen</th><th>Ook toegestaan</th><th><?= t('status') ?></th><th><?= t('actions') ?></th></tr></thead>
            <tbody>
            <?php foreach ($groups as $g): ?>
            <tr>
                <td class="font-bold"><?= sanitize($g['name']) ?></td>
                <td class="mono text-sm"><?= sanitize($g['members'] ?? '') ?></td>
                <td class="mono text-sm"><?= sanitize(str_replace(',', ', ', $g['allowed_extras'])) ?></td>
                <td><span class="badge <?= $g['enabled'] ? 'badge-success' : 'badge-default' ?>"><?= $g['enabled'] ? t('enabled') : t('disabled') ?></span></td>
                <td class="action-cell">
                    <a href="?page=call_acl&action=edit&id=<?= $g['id'] ?>" class="btn btn-sm btn-ghost"><?= t('edit') ?></a>
                    <a href="?page=call_acl&action=delete&id=<?= $g['id'] ?>" class="btn btn-sm btn-danger-ghost" onclick="return confirm('<?= t('confirm_delete') ?>')">✕</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
