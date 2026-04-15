<div class="page-header">
    <h1 class="page-title"><?= $title ?></h1>
    <a href="?page=phonebook" class="btn btn-ghost">← Back</a>
</div>

<form method="POST" action="?page=phonebook&action=post_<?= $action ?><?= isset($group['id']) ? '&id='.$group['id'] : '' ?>">
    <?= csrf() ?>
    <div class="card" style="max-width:600px">
        <div class="card-body">
            <div class="form-group">
                <label>Groepsnaam *</label>
                <input type="text" name="name" class="form-control" 
                       value="<?= sanitize($group['name']) ?>" required placeholder="bijv. Klanten, Leveranciers">
            </div>
            <div class="form-group">
                <label>Omschrijving</label>
                <textarea name="description" class="form-control" rows="2"><?= sanitize($group['description']) ?></textarea>
            </div>
        </div>
    </div>
    <div class="form-actions">
        <a href="?page=phonebook" class="btn btn-ghost">Annuleren</a>
        <button type="submit" class="btn btn-accent">💾 Opslaan</button>
    </div>
</form>
