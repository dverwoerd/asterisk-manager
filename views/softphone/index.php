<!-- views/softphone/index.php -->
<div class="page-header">
    <h1 class="page-title">Softphone koppelen</h1>
</div>

<div class="card mb-4">
    <div class="card-body text-sm">
        Maak een koppelcode voor een extensie. De code is <strong>eenmalig</strong> en <?= (int)$ttl ?> uur geldig en bevat
        <strong>geen wachtwoord</strong>. De softphone haalt daarmee één keer het account op en maakt de code daarna ongeldig.
    </div>
</div>

<?php if (!empty($nieuw)): ?>
<div class="card mb-4" style="border-left:4px solid #F37021">
    <div class="card-body">
        <h2 style="margin-top:0">Koppelcode voor <?= sanitize($nieuw['extension']) ?> (<?= sanitize($nieuw['name']) ?>)</h2>
        <p class="text-sm">Geldig tot <?= sanitize($nieuw['expires']) ?>. Deze code wordt maar één keer getoond; sluit je deze pagina, maak dan een nieuwe aan.</p>
        <div style="display:flex;gap:32px;flex-wrap:wrap;align-items:flex-start">
            <div>
                <div id="qr" style="background:#fff;padding:12px;display:inline-block;border:1px solid #ddd"></div>
                <div class="text-sm text-muted" style="margin-top:6px">QR-code met de koppellink</div>
            </div>
            <div style="flex:1;min-width:320px">
                <label class="text-sm">Koppelcode (handmatig invoeren)</label>
                <input type="text" class="form-control mono" readonly value="<?= sanitize($nieuw['code']) ?>" onclick="this.select()" style="font-size:1.2em">
                <label class="text-sm" style="margin-top:12px">Link</label>
                <input type="text" class="form-control mono" readonly value="<?= sanitize($nieuw['url']) ?>" onclick="this.select()">
                <label class="text-sm" style="margin-top:12px">Link voor de Pantronic-app (opent de app direct)</label>
                <input type="text" class="form-control mono" readonly value="<?= sanitize($nieuw['deeplink']) ?>" onclick="this.select()">
                <p style="margin-top:12px"><a class="btn btn-accent" href="<?= sanitize($nieuw['deeplink']) ?>">Open in softphone</a></p>
            </div>
        </div>
    </div>
</div>
<script src="assets/js/qrcode.js"></script>
<script>
(function () {
    var qr = qrcode(0, 'M');
    qr.addData(<?= json_encode($nieuw['url']) ?>);
    qr.make();
    document.getElementById('qr').innerHTML = qr.createSvgTag({ cellSize: 5, margin: 0, scalable: false });
})();
</script>
<?php endif; ?>

<div class="card">
    <div class="card-body p-0">
        <table class="data-table">
            <thead><tr><th>Extensie</th><th>Naam</th><th>Open codes</th><th>Laatst gekoppeld</th><th><?= t('actions') ?></th></tr></thead>
            <tbody>
            <?php foreach ($exts as $e): ?>
            <tr>
                <td class="mono font-bold"><?= sanitize($e['extension']) ?></td>
                <td><?= sanitize($e['full_name']) ?></td>
                <td><?= (int)$e['open_codes'] ?></td>
                <td class="text-sm"><?= $e['laatst_gekoppeld'] ? sanitize(date('d-m-Y H:i', strtotime($e['laatst_gekoppeld']))) : '-' ?></td>
                <td class="action-cell">
                    <form method="post" action="?page=softphone&action=create" style="display:inline">
                        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= sanitize($_SESSION['csrf_token'] ?? '') ?>">
                        <input type="hidden" name="extension_id" value="<?= (int)$e['id'] ?>">
                        <button class="btn btn-sm btn-accent">Koppelcode maken</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
