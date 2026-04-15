<div class="page-header">
    <h1 class="page-title">📒 Company Adresboek</h1>
    <a href="?page=phonebook&action=add_group" class="btn btn-accent">+ Nieuwe Groep</a>
</div>

<?php if (empty($groups)): ?>
<div class="card"><div class="empty-state">
    <div class="empty-icon">📒</div>
    <p>Nog geen adresboek groepen aangemaakt.</p>
    <a href="?page=phonebook&action=add_group" class="btn btn-accent">+ Eerste Groep Aanmaken</a>
</div></div>
<?php else: ?>
<div class="card">
    <div class="card-body p-0">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Naam</th>
                    <th>Omschrijving</th>
                    <th>Contacten</th>
                    <th>XML URL</th>
                    <th><?= t('actions') ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($groups as $g): ?>
            <tr>
                <td class="font-bold"><?= sanitize($g['name']) ?></td>
                <td class="text-muted"><?= sanitize($g['description']) ?></td>
                <td><span class="badge badge-info"><?= $g['contact_count'] ?> contacten</span></td>
                <td>
                    <a href="?page=phonebook&action=xml&group_id=<?= $g['id'] ?>" 
                       class="mono text-sm" target="_blank">
                        📄 phonebook_<?= $g['id'] ?>.xml
                    </a>
                </td>
                <td class="action-cell">
                    <a href="?page=phonebook&action=contacts&id=<?= $g['id'] ?>" class="btn btn-sm btn-ghost">Contacten</a>
                    <a href="?page=phonebook&action=import&group_id=<?= $g['id'] ?>" class="btn btn-sm btn-ghost">⬆ Import</a>
                    <a href="?page=phonebook&action=edit_group&id=<?= $g['id'] ?>" class="btn btn-sm btn-ghost">Edit</a>
                    <a href="?page=phonebook&action=delete_group&id=<?= $g['id'] ?>" 
                       class="btn btn-sm btn-danger-ghost"
                       onclick="return confirm('Groep en alle contacten verwijderen?')">✕</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card mt-4" style="border-color:var(--info)">
    <div class="card-body" style="padding:12px 16px;font-size:12px">
        <strong style="color:var(--info)">Yealink toestel configuratie:</strong><br>
        Ga naar <strong>Yealink Phones → Edit</strong> en selecteer een adresboek groep.<br>
        Het toestel laadt het adresboek via: <code class="mono"><?= getSetting('provision_base_url', 'http://pbx') ?>/phonebook/{group_id}.xml</code>
    </div>
</div>
<?php endif; ?>
