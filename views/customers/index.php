<!-- views/customers/index.php -->
<div class="page-header">
    <h1 class="page-title"><?= t('customers') ?></h1>
    <a href="?page=customers&action=add" class="btn btn-accent">+ <?= t('add_new') ?></a>
</div>
<div class="card">
    <div class="card-body p-0">
        <?php if (empty($customers)): ?>
        <div class="empty-state"><div class="empty-icon">⊙</div><p>No customers. Add a customer to generate invoices.</p><a href="?page=customers&action=add" class="btn btn-accent">Add Customer</a></div>
        <?php else: ?>
        <table class="data-table">
            <thead><tr><th>Company</th><th>Contact</th><th>Email</th><th>Extensions</th><th>Rate Plan</th><th><?= t('actions') ?></th></tr></thead>
            <tbody>
            <?php foreach ($customers as $c): ?>
            <tr>
                <td class="font-bold"><?= sanitize($c['company_name'] ?: '—') ?></td>
                <td><?= sanitize($c['contact_name']) ?></td>
                <td class="mono text-sm"><?= sanitize($c['email']) ?></td>
                <td class="mono text-sm"><?= sanitize($c['extensions_csv']) ?: '<span class="text-muted">—</span>' ?></td>
                <td><?= sanitize($c['rate_plan_name'] ?? '—') ?></td>
                <td class="action-cell">
                    <a href="?page=invoices&action=generate" class="btn btn-sm btn-ghost">Invoice</a>
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
