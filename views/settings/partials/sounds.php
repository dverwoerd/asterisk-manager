<div class="card mt-4 mb-4">
    <div class="card-header">
        <h3 class="card-title">🔊 Sound Files uploaden</h3>
        <form method="POST" action="?page=sounds&action=upload" enctype="multipart/form-data" style="display:flex;gap:8px;align-items:center">
            <?= csrf() ?>
            <input type="file" name="soundfile" class="form-control" style="width:auto" accept=".gsm,.wav,.mp3,.ulaw,.alaw,.g722,.sln">
            <button type="submit" class="btn btn-accent btn-sm">⬆ Upload</button>
        </form>
    </div>
    <div class="card-body p-0">
        <?php
        $soundCustom = [];
        $customPath  = '/var/lib/asterisk/sounds/custom';
        if (is_dir($customPath)) {
            foreach (glob($customPath . '/*.{gsm,wav,mp3,ulaw,alaw,g722,sln}', GLOB_BRACE) as $f) {
                $soundCustom[] = pathinfo($f, PATHINFO_FILENAME);
            }
            sort($soundCustom);
        }
        ?>
        <?php if (!empty($soundCustom)): ?>
        <table class="data-table">
            <thead><tr><th>Bestandsnaam</th><th>Gebruik als</th><th>Actie</th></tr></thead>
            <tbody>
            <?php foreach ($soundCustom as $s): ?>
            <tr>
                <td class="mono"><?= sanitize($s) ?></td>
                <td class="mono text-muted text-sm">custom/<?= sanitize($s) ?></td>
                <td class="action-cell">
                    <a href="?page=sounds&action=delete&file=<?= urlencode($s) ?>.gsm"
                       class="btn btn-sm btn-danger-ghost"
                       onclick="return confirm('Verwijder <?= sanitize($s) ?>?')">✕</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">Nog geen eigen soundfiles geüpload.</div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Standaard Asterisk Soundfiles</h3>
        <div style="display:flex;gap:6px">
            <?php
            $soundLangs = [];
            if (is_dir('/var/lib/asterisk/sounds')) {
                foreach (glob('/var/lib/asterisk/sounds/*', GLOB_ONLYDIR) as $d) {
                    $soundLangs[] = basename($d);
                }
            }
            ?>
            <?php foreach ($soundLangs as $sl): ?>
            <button type="button" class="btn btn-sm btn-ghost" onclick="loadSoundList('<?= $sl ?>')"><?= strtoupper($sl) ?></button>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="card-body p-0">
        <div id="soundListContainer" style="padding:12px;display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:4px;max-height:300px;overflow-y:auto">
            <span class="text-muted text-sm">Klik op een taal om de lijst te laden.</span>
        </div>
    </div>
</div>
<script>
function loadSoundList(lang) {
    fetch('?page=sounds&action=list&lang=' + lang)
        .then(r => r.json())
        .then(data => {
            const sounds = data.sounds || [];
            document.getElementById('soundListContainer').innerHTML = sounds.map(s =>
                `<div class="mono" style="padding:3px 8px;border-radius:4px;background:var(--bg-secondary);font-size:11px">${s}</div>`
            ).join('');
        });
}
</script>
