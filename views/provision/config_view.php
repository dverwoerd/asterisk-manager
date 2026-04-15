<div class="page-header">
    <h1 class="page-title"><?= $title ?></h1>
    <div class="page-actions">
        <a href="?page=provision" class="btn btn-ghost">← Back</a>
        <a href="?page=provision&action=edit&id=<?= $phone['id'] ?>" class="btn btn-ghost">Edit</a>
        <a href="?page=provision&action=generate&id=<?= $phone['id'] ?>" class="btn btn-accent">⟳ Regenerate</a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body" style="display:flex;gap:24px;flex-wrap:wrap">
        <div>
            <span class="text-muted text-sm">MAC:</span>
            <span class="mono font-bold"><?= sanitize($phone['mac_address']) ?></span>
        </div>
        <div>
            <span class="text-muted text-sm">Model:</span>
            <span class="badge badge-info"><?= sanitize($phone['model']) ?></span>
        </div>
        <div>
            <span class="text-muted text-sm">Extension:</span>
            <span class="mono font-bold"><?= sanitize($ext['extension'] ?? '') ?></span>
            <span class="text-muted"><?= sanitize($ext['full_name'] ?? '') ?></span>
        </div>
        <div>
            <span class="text-muted text-sm">Last provision:</span>
            <span class="mono"><?= $phone['last_provision'] ? date('d-m-Y H:i', strtotime($phone['last_provision'])) : 'Never' ?></span>
        </div>
    </div>
</div>

<?php
$baseUrl = getSetting('provision_base_url', '');
$mac     = strtolower(str_replace([':', '-'], '', $phone['mac_address']));
if ($baseUrl):
?>
<div class="card mb-4" style="border-color:var(--accent)">
    <div class="card-body" style="padding:10px 16px">
        <span class="text-sm text-muted">Provisioning URL voor dit toestel:</span><br>
        <span class="mono" style="color:var(--accent)"><?= sanitize($baseUrl) ?>/<?= $mac ?>.cfg</span>
        <button class="btn btn-sm btn-ghost" style="margin-left:8px"
                onclick="navigator.clipboard.writeText('<?= sanitize($baseUrl) ?>/<?= $mac ?>.cfg').then(()=>{this.textContent='✓ Gekopieerd';setTimeout(()=>this.textContent='⎘ Copy',1500)})">
            ⎘ Copy
        </button>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Gegenereerde Config</h3>
        <button class="btn btn-sm btn-ghost"
                onclick="copyConfig()">⎘ Copy</button>
    </div>
    <div class="card-body" style="padding:0">
        <pre id="configContent" style="
            background:var(--bg);
            color:var(--accent);
            font-family:var(--font-mono);
            font-size:12px;
            padding:16px;
            margin:0;
            overflow-x:auto;
            max-height:600px;
            overflow-y:auto;
            border-radius:0;
            line-height:1.6;
        "><?= htmlspecialchars($content) ?></pre>
    </div>
</div>

<script>
function copyConfig() {
    const text = document.getElementById('configContent').textContent;
    navigator.clipboard.writeText(text).then(() => {
        const btn = event.target;
        btn.textContent = '✓ Gekopieerd';
        setTimeout(() => btn.textContent = '⎘ Copy', 2000);
    });
}
</script>
