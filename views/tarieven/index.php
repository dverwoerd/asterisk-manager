<!-- views/tarieven/index.php -->
<div class="page-header">
    <h1 class="page-title"><?= t('nav_tarieven') ?></h1>
</div>

<?php if (!$tabel): ?>
<div class="card mb-4"><div class="card-body">
    De tabel <span class="mono">bel_tarieven</span> bestaat nog niet. Voer uit op de server:
    <pre class="mono">mysql asterisk_manager &lt; migrate_gespecificeerd.sql
mysql asterisk_manager &lt; migrate_bel_tarieven.sql</pre>
</div></div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header"><h3 class="card-title">Basistarieven (per minuut, excl. BTW)</h3></div>
    <div class="card-body">
        <form method="POST" action="?page=tarieven&action=post_instellingen">
            <?= csrf() ?>
            <div class="form-row">
                <div class="form-group"><label>Starttarief (per gesprek)</label><input type="text" name="start" class="form-control mono" value="<?= sanitize((string)$inst['start']) ?>"></div>
                <div class="form-group"><label>Nationaal vast / 088</label><input type="text" name="nationaal" class="form-control mono" value="<?= sanitize((string)$inst['nationaal']) ?>"></div>
                <div class="form-group"><label>Mobiel</label><input type="text" name="mobiel" class="form-control mono" value="<?= sanitize((string)$inst['mobiel']) ?>"></div>
                <div class="form-group"><label>BTW (%)</label><input type="text" name="btw" class="form-control mono" value="<?= sanitize((string)$inst['btw']) ?>"></div>
            </div>
            <small class="form-hint">Kosten per gesprek = starttarief + (minuten, naar boven afgerond) x minuuttarief. 0800 is gratis; internationaal en 0900 gebruiken de tabel hieronder (prefix 4, 3 of 2 cijfers).</small>
            <div style="margin-top:12px"><button type="submit" class="btn btn-accent"><?= t('save') ?></button></div>
        </form>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><h3 class="card-title">Tarief toevoegen of wijzigen</h3></div>
    <div class="card-body">
        <form method="POST" action="?page=tarieven&action=post_opslaan" id="tariefForm">
            <?= csrf() ?>
            <input type="hidden" name="oud_prefix" id="oud_prefix" value="">
            <div class="form-row">
                <div class="form-group"><label>Prefix *</label><input type="text" name="prefix" id="f_prefix" class="form-control mono" required placeholder="bv. 4420"></div>
                <div class="form-group"><label>Tarief per minuut *</label><input type="text" name="tarief" id="f_tarief" class="form-control mono" required placeholder="0,0400"></div>
                <div class="form-group"><label>Land</label><input type="text" name="land" id="f_land" class="form-control"></div>
                <div class="form-group"><label>Regio</label><input type="text" name="regio" id="f_regio" class="form-control"></div>
            </div>
            <button type="submit" class="btn btn-accent"><?= t('save') ?></button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <h3 class="card-title">Internationale en 0900-tarieven (<?= (int)$totaal ?>)</h3>
        <form method="GET" style="display:flex;gap:6px">
            <input type="hidden" name="page" value="tarieven">
            <input type="text" name="q" value="<?= sanitize($zoek) ?>" class="form-control" placeholder="Zoek op prefix, land of regio">
            <button class="btn btn-ghost">Zoek</button>
        </form>
    </div>
    <div class="card-body p-0">
        <table class="data-table">
            <thead><tr><th>Prefix</th><th>Land</th><th>Regio</th><th>Tarief / min</th><th><?= t('actions') ?></th></tr></thead>
            <tbody>
            <?php foreach ($rijen as $r): ?>
            <tr>
                <td class="mono font-bold"><?= sanitize($r['prefix']) ?></td>
                <td><?= sanitize($r['land']) ?></td>
                <td><?= sanitize($r['regio']) ?></td>
                <td class="mono">€ <?= number_format((float)$r['tarief'], 4, ',', '.') ?></td>
                <td class="action-cell">
                    <button type="button" class="btn btn-sm btn-ghost"
                        onclick='bewerk(<?= json_encode($r, JSON_HEX_APOS|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_QUOT) ?>)'><?= t('edit') ?></button>
                    <a href="?page=tarieven&action=delete&prefix=<?= urlencode($r['prefix']) ?>" class="btn btn-sm btn-danger-ghost" onclick="return confirm('<?= t('confirm_delete') ?>')">✕</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($rijen)): ?><tr><td colspan="5" class="text-muted">Geen tarieven gevonden.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
function bewerk(r){
    document.getElementById('oud_prefix').value = r.prefix;
    document.getElementById('f_prefix').value = r.prefix;
    document.getElementById('f_tarief').value = String(r.tarief).replace('.', ',');
    document.getElementById('f_land').value = r.land;
    document.getElementById('f_regio').value = r.regio;
    document.getElementById('tariefForm').scrollIntoView({behavior:'smooth'});
}
</script>
