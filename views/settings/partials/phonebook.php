<div class="card mt-4 mb-4">
    <div class="card-header">
        <h3 class="card-title">📒 Adresboek Groepen</h3>
        <a href="?page=phonebook&action=add_group" class="btn btn-accent btn-sm">+ Nieuwe Groep</a>
    </div>
    <div class="card-body p-0">
        <?php
        $pbGroups = Database::fetchAll(
            "SELECT pg.*, COUNT(pc.id) as contact_count
             FROM phonebook_groups pg
             LEFT JOIN phonebook_contacts pc ON pg.id = pc.group_id
             GROUP BY pg.id ORDER BY pg.name"
        );
        ?>
        <?php if (empty($pbGroups)): ?>
        <div class="empty-state">Nog geen adresboek groepen.</div>
        <?php else: ?>
        <table class="data-table">
            <thead><tr><th>Naam</th><th>Contacten</th><th>XML URL</th><th>Actie</th></tr></thead>
            <tbody>
            <?php foreach ($pbGroups as $g): ?>
            <tr>
                <td class="font-bold"><?= sanitize($g['name']) ?></td>
                <td><span class="badge badge-info"><?= $g['contact_count'] ?></span></td>
                <td>
                    <a href="?page=phonebook&action=xml&group_id=<?= $g['id'] ?>" class="mono text-sm" target="_blank">
                        📄 XML
                    </a>
                </td>
                <td class="action-cell">
                    <a href="?page=phonebook&action=contacts&id=<?= $g['id'] ?>" class="btn btn-sm btn-ghost">Contacten</a>
                    <a href="?page=phonebook&action=edit_group&id=<?= $g['id'] ?>" class="btn btn-sm btn-ghost">Edit</a>
                    <a href="?page=phonebook&action=delete_group&id=<?= $g['id'] ?>"
                       class="btn btn-sm btn-danger-ghost"
                       onclick="return confirm('Groep verwijderen?')">✕</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
