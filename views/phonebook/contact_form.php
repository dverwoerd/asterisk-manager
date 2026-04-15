<div class="page-header">
    <h1 class="page-title"><?= $title ?></h1>
    <a href="?page=phonebook&action=contacts&id=<?= $group['id'] ?>" class="btn btn-ghost">← Contacten</a>
</div>

<form method="POST" action="?page=phonebook&action=post_<?= $action ?><?= isset($contact['id']) ? '&id='.$contact['id'] : '' ?>">
    <?= csrf() ?>
    <input type="hidden" name="group_id" value="<?= $group['id'] ?>">
    <div class="card" style="max-width:700px">
        <div class="card-header"><h3 class="card-title">Contact — <?= sanitize($group['name']) ?></h3></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group">
                    <label>Voornaam</label>
                    <input type="text" name="first_name" class="form-control" value="<?= sanitize($contact['first_name']) ?>">
                </div>
                <div class="form-group">
                    <label>Achternaam</label>
                    <input type="text" name="last_name" class="form-control" value="<?= sanitize($contact['last_name']) ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Bedrijf</label>
                <input type="text" name="company" class="form-control" value="<?= sanitize($contact['company']) ?>">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Mobiel</label>
                    <input type="tel" name="phone_mobile" class="form-control mono" value="<?= sanitize($contact['phone_mobile']) ?>" placeholder="06...">
                </div>
                <div class="form-group">
                    <label>Werk</label>
                    <input type="tel" name="phone_work" class="form-control mono" value="<?= sanitize($contact['phone_work']) ?>" placeholder="010...">
                </div>
                <div class="form-group">
                    <label>Thuis</label>
                    <input type="tel" name="phone_home" class="form-control mono" value="<?= sanitize($contact['phone_home']) ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="<?= sanitize($contact['email']) ?>">
            </div>
            <div class="form-group">
                <label>Notities</label>
                <textarea name="notes" class="form-control" rows="2"><?= sanitize($contact['notes'] ?? '') ?></textarea>
            </div>
        </div>
    </div>
    <div class="form-actions">
        <a href="?page=phonebook&action=contacts&id=<?= $group['id'] ?>" class="btn btn-ghost">Annuleren</a>
        <button type="submit" class="btn btn-accent">💾 Opslaan</button>
    </div>
</form>
