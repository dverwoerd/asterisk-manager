<div class="page-header">
    <h1 class="page-title">Yealink Provisioning</h1>
    <div class="page-actions">
        <a href="?page=provision&action=generate_all" class="btn btn-ghost"
           onclick="return confirm('Alle config bestanden opnieuw genereren?')">
            ⟳ Generate All
        </a>
        <a href="?page=provision&action=add" class="btn btn-accent">+ Add Phone</a>
    </div>
</div>

<!-- Provisioning URL info -->
<?php
$baseUrl    = getSetting('provision_base_url', '');
$provPath   = getSetting('provision_tftp_path', APP_ROOT . '/provision');
?>
<div class="card mb-4" style="border-color:var(--info)">
    <div class="card-body" style="padding:12px 16px">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
            <div>
                <span class="mono text-sm" style="color:var(--info)">📡 Provisioning URL: </span>
                <?php if ($baseUrl): ?>
                <span class="mono" style="color:var(--accent)"><?= sanitize($baseUrl) ?>/{mac}.cfg</span>
                <?php else: ?>
                <span class="text-muted text-sm">Niet ingesteld — ga naar Settings → Provisioning</span>
                <?php endif; ?>
            </div>
            <div class="text-sm text-muted mono">
                Config pad: <?= sanitize($provPath) ?>
            </div>
        </div>
        <p class="text-sm text-muted" style="margin-top:8px">
            Stel in het Yealink toestel de provisioning server in op bovenstaande URL.
            Het toestel haalt automatisch de config op op basis van het MAC adres.
        </p>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <?php if (empty($phones)): ?>
        <div class="empty-state">
            <div class="empty-icon" style="font-size:48px">📞</div>
            <p>Geen toestellen geconfigureerd.</p>
            <a href="?page=provision&action=add" class="btn btn-accent">+ Add First Phone</a>
        </div>
        <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Extension</th>
                    <th>MAC Address</th>
                    <th>Model</th>
                    <th>Display Name</th>
                    <th>Timezone</th>
                    <th>BLF Keys</th>
                    <th>Last Provision</th>
                    <th>Config URL</th>
                    <th><?= t('actions') ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($phones as $p): ?>
            <?php $mac = strtolower(str_replace([':', '-'], '', $p['mac_address'])); ?>
            <tr>
                <td class="mono font-bold"><?= sanitize($p['extension']) ?></td>
                <td class="mono"><?= sanitize($p['mac_address']) ?></td>
                <td><span class="badge badge-info"><?= sanitize($p['model']) ?></span></td>
                <td><?= sanitize($p['display_name'] ?: $p['full_name']) ?></td>
                <td class="mono text-sm"><?= sanitize($p['timezone']) ?></td>
                <td>
                    <a href="?page=provision&action=edit&id=<?= $p['id'] ?>"
                       class="badge badge-<?= $p['blf_count'] > 0 ? 'success' : 'default' ?>">
                        <?= $p['blf_count'] ?> keys
                    </a>
                </td>
                <td class="mono text-sm text-muted">
                    <?= $p['last_provision'] ? date('d-m-Y H:i', strtotime($p['last_provision'])) : '—' ?>
                </td>
                <td>
                    <?php if ($baseUrl): ?>
                    <a href="<?= sanitize($baseUrl) ?>/<?= $mac ?>.cfg"
                       class="btn btn-sm btn-ghost" target="_blank" title="Open config bestand">
                        🔗 <?= $mac ?>.cfg
                    </a>
                    <?php else: ?>
                    <span class="text-muted text-sm mono"><?= $mac ?>.cfg</span>
                    <?php endif; ?>
                </td>
                <td class="action-cell">
                    <a href="?page=provision&action=view_config&id=<?= $p['id'] ?>"
                       class="btn btn-sm btn-ghost">View</a>
                    <a href="?page=provision&action=generate&id=<?= $p['id'] ?>"
                       class="btn btn-sm btn-ghost" title="Hergeneer config">⟳</a>
                    <a href="?page=provision&action=reboot&id=<?= $p['id'] ?>" class="btn btn-sm btn-ghost" title="Toestel herstarten" onclick="return confirm('Toestel herstarten?')">↺ Reboot</a>
                    <a href="?page=provision&action=reprovision&id=<?= $p['id'] ?>" class="btn btn-sm btn-ghost" title="Herlaad configuratie zonder reboot" onclick="return confirm('Configuratie opnieuw laden?')">⟳ Reprovision</a>
                    <a href="?page=provision&action=edit&id=<?= $p['id'] ?>"
                       class="btn btn-sm btn-ghost"><?= t('edit') ?></a>
                    <a href="?page=provision&action=delete&id=<?= $p['id'] ?>"
                       class="btn btn-sm btn-danger-ghost"
                       onclick="return confirm('<?= t('confirm_delete') ?>')">✕</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
