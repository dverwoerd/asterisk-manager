<!-- views/customers/index.php -->
<div class="page-header">
    <h1 class="page-title"><?= t('nav_customers') ?></h1>
    <a href="?page=customers&action=add" class="btn btn-accent">+ <?= t('add_new') ?></a>
</div>
<div class="card">
    <div class="card-body p-0">
        <?php if (empty($customers)): ?>
        <div class="empty-state"><div class="empty-icon">⊙</div><p>Nog geen klanten. Voeg een klant toe en koppel de toestellen, dan verschijnt hij bij de gespreksspecificaties.</p><a href="?page=customers&action=add" class="btn btn-accent">Klant toevoegen</a></div>
        <?php else: ?>
        <table class="data-table">
            <thead><tr><th>Bedrijf</th><th>Contact</th><th>E-mail</th><th>Toestellen</th><th><?= t('actions') ?></th></tr></thead>
            <tbody>
            <?php foreach ($customers as $c): ?>
            <tr>
                <td class="font-bold"><?= sanitize($c['company_name'] ?: '—') ?></td>
                <td><?= sanitize($c['contact_name']) ?></td>
                <td class="mono text-sm"><?= sanitize($c['email'] ?? '') ?></td>
                <td class="mono text-sm"><?= $c['extensions_csv'] ? sanitize(str_replace(',', ', ', $c['extensions_csv'])) : '<span class="text-muted">—</span>' ?></td>
                <td class="action-cell">
                    <a href="?page=gesprekspecificaties&action=detail&id=<?= $c['id'] ?>" class="btn btn-sm btn-ghost">Specificatie</a>
                    <a href="?page=customers&action=edit&id=<?= $c['id'] ?>" class="btn btn-sm btn-ghost"><?= t('edit') ?></a>
                    <a href="?page=customers&action=delete&id=<?= $c['id'] ?>" class="btn btn-sm btn-danger-ghost" onclick="return confirm('<?= t('confirm_delete') ?>')">✕</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
