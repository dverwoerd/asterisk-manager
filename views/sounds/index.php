<div class="page-header">
    <h1 class="page-title">🔊 Sound Files</h1>
    <div class="page-actions">
        <form method="POST" action="?page=sounds&action=upload" enctype="multipart/form-data" style="display:flex;gap:8px;align-items:center">
            <?= csrf() ?>
            <input type="file" name="soundfile" class="form-control" style="width:auto"
                   accept=".gsm,.wav,.mp3,.ulaw,.alaw,.g722,.sln">
            <button type="submit" class="btn btn-accent">⬆ Upload</button>
        </form>
    </div>
</div>

<!-- Taal filter -->
<div class="card mb-4">
    <div class="card-body" style="padding:12px 16px">
        <strong>Taal:</strong>
        <?php foreach ($langs as $l): ?>
        <a href="?page=sounds&lang=<?= $l ?>"
           class="btn btn-sm <?= $lang === $l ? 'btn-accent' : 'btn-ghost' ?>"
           style="margin-left:4px"><?= sanitize($l) ?></a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Custom soundfiles -->
<?php if (!empty($custom)): ?>
<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title">Eigen Soundfiles (custom/)</h3>
        <span class="badge badge-info"><?= count($custom) ?> bestanden</span>
    </div>
    <div class="card-body p-0">
        <table class="data-table">
            <thead><tr><th>Bestandsnaam</th><th>Gebruik in queue</th><th>Actie</th></tr></thead>
            <tbody>
            <?php foreach ($custom as $s): ?>
            <tr>
                <td class="mono"><?= sanitize($s) ?></td>
                <td class="mono text-sm text-muted">custom/<?= sanitize($s) ?></td>
                <td class="action-cell">
                    <a href="?page=sounds&action=delete&file=<?= urlencode($s) ?>.gsm"
                       class="btn btn-sm btn-danger-ghost"
                       onclick="return confirm('Verwijder <?= sanitize($s) ?>?')">✕</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Standaard soundfiles -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Standaard Soundfiles (<?= sanitize($lang) ?>/)</h3>
        <span class="badge badge-default"><?= count($sounds) ?> bestanden</span>
    </div>
    <div class="card-body p-0">
        <div style="padding:12px;display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:4px;max-height:400px;overflow-y:auto">
            <?php foreach ($sounds as $s): ?>
            <div class="mono text-sm" style="padding:4px 8px;border-radius:4px;background:var(--bg-secondary)"><?= sanitize($s) ?></div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
