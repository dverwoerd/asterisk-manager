<div class="page-header">
    <h1 class="page-title"><?= $title ?></h1>
    <a href="?page=phonebook&action=contacts&id=<?= $group['id'] ?>" class="btn btn-ghost">← Terug</a>
</div>

<div class="card" style="max-width:600px">
    <div class="card-header"><h3 class="card-title">Import CSV — <?= sanitize($group['name']) ?></h3></div>
    <div class="card-body">
        <div class="card mb-3" style="border-color:var(--info)">
            <div class="card-body" style="padding:10px 14px;font-size:11px">
                <strong>CSV formaat (puntkomma gescheiden):</strong><br>
                <code class="mono">Voornaam;Achternaam;Bedrijf;Mobiel;Werk;Thuis;Email</code><br><br>
                <strong>Voorbeeld:</strong><br>
                <code class="mono">Jan;Jansen;Bedrijf BV;0612345678;0201234567;;jan@bedrijf.nl</code>
            </div>
        </div>
        <form method="POST" action="?page=phonebook&action=post_import" enctype="multipart/form-data">
            <?= csrf() ?>
            <input type="hidden" name="group_id" value="<?= $group['id'] ?>">
            <div class="form-group">
                <label>CSV Bestand</label>
                <input type="file" name="csv_file" class="form-control" accept=".csv" required>
            </div>
            <button type="submit" class="btn btn-accent">⬆ Importeren</button>
        </form>
    </div>
</div>
