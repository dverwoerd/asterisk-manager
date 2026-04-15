<div class="page-header">
    <h1 class="page-title"><?= $title ?></h1>
    <div class="page-actions">
        <a href="?page=phonebook" class="btn btn-ghost">← Groepen</a>
        <a href="?page=phonebook&action=import&group_id=<?= $group['id'] ?>" class="btn btn-ghost">⬆ Import CSV</a>
        <a href="?page=phonebook&action=xml&group_id=<?= $group['id'] ?>" class="btn btn-ghost" target="_blank">📄 XML</a>
        <a href="?page=phonebook&action=add_contact&group_id=<?= $group['id'] ?>" class="btn btn-accent">+ Contact</a>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <?php if (empty($contacts)): ?>
        <div class="empty-state">
            <p>Geen contacten in deze groep.</p>
            <a href="?page=phonebook&action=add_contact&group_id=<?= $group['id'] ?>" class="btn btn-accent">+ Eerste Contact</a>
        </div>
        <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Naam</th>
                    <th>Bedrijf</th>
                    <th>Mobiel</th>
                    <th>Werk</th>
                    <th>Thuis</th>
                    <th>Email</th>
                    <th><?= t('actions') ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($contacts as $c): ?>
            <tr>
                <td class="font-bold"><?= sanitize(trim($c['first_name'] . ' ' . $c['last_name'])) ?></td>
                <td class="text-muted"><?= sanitize($c['company']) ?></td>
                <td class="mono text-sm"><?= sanitize($c['phone_mobile']) ?></td>
                <td class="mono text-sm"><?= sanitize($c['phone_work']) ?></td>
                <td class="mono text-sm"><?= sanitize($c['phone_home']) ?></td>
                <td class="text-sm"><?= sanitize($c['email']) ?></td>
                <td class="action-cell">
                    <a href="?page=phonebook&action=edit_contact&id=<?= $c['id'] ?>" class="btn btn-sm btn-ghost">Edit</a>
                    <a href="?page=phonebook&action=delete_contact&id=<?= $c['id'] ?>"
                       class="btn btn-sm btn-danger-ghost"
                       onclick="return confirm('Contact verwijderen?')">✕</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
