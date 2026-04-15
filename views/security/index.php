<div class="page-header">
    <h1 class="page-title">🔒 Login Security</h1>
    <div class="page-actions">
        <a href="?page=security&action=clear_attempts" class="btn btn-ghost btn-sm">🗑 Verwijder oude pogingen</a>
    </div>
</div>

<!-- Recente loginpogingen -->
<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title">Recente mislukte pogingen (laatste 24 uur)</h3>
    </div>
    <div class="card-body p-0">
        <?php if (empty($recentAttempts)): ?>
        <div class="empty-state">Geen mislukte loginpogingen.</div>
        <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>IP Adres</th>
                    <th>Gebruikersnaam</th>
                    <th>Pogingen</th>
                    <th>Laatste poging</th>
                    <th>Actie</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($recentAttempts as $a): ?>
            <tr>
                <td class="mono"><?= sanitize($a['ip_address']) ?></td>
                <td class="mono"><?= sanitize($a['username']) ?></td>
                <td>
                    <span class="badge badge-<?= $a['attempts'] >= 5 ? 'danger' : ($a['attempts'] >= 3 ? 'warning' : 'info') ?>">
                        <?= $a['attempts'] ?>x
                    </span>
                </td>
                <td class="mono text-sm text-muted"><?= sanitize($a['last_attempt']) ?></td>
                <td class="action-cell">
                    <a href="?page=security&action=block&ip=<?= urlencode($a['ip_address']) ?>"
                       class="btn btn-sm btn-danger-ghost"
                       onclick="return confirm('IP <?= sanitize($a['ip_address']) ?> blokkeren?')">🚫 Blokkeer</a>
                    <a href="?page=security&action=whitelist&ip=<?= urlencode($a['ip_address']) ?>"
                       class="btn btn-sm btn-ghost"
                       onclick="return confirm('IP <?= sanitize($a['ip_address']) ?> op whitelist zetten?')">✓ Whitelist</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Blacklist / Whitelist -->
<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title">IP Blacklist & Whitelist</h3>
    </div>
    <div class="card-body p-0">
        <?php if (empty($blacklist)): ?>
        <div class="empty-state">Geen geblokkeerde of gewhiteliste IPs.</div>
        <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>IP Adres</th>
                    <th>Status</th>
                    <th>Reden</th>
                    <th>Geblokkeerd op</th>
                    <th>Verloopt op</th>
                    <th>Actie</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($blacklist as $b): ?>
            <tr>
                <td class="mono font-bold"><?= sanitize($b['ip_address']) ?></td>
                <td>
                    <?php if ($b['whitelisted']): ?>
                    <span class="badge badge-success">✓ Whitelist</span>
                    <?php else: ?>
                    <span class="badge badge-danger">🚫 Geblokkeerd</span>
                    <?php endif; ?>
                </td>
                <td class="text-sm text-muted"><?= sanitize($b['reason']) ?></td>
                <td class="mono text-sm"><?= sanitize($b['blocked_at']) ?></td>
                <td class="mono text-sm">
                    <?php if ($b['whitelisted']): ?>
                    <span class="text-muted">—</span>
                    <?php elseif ($b['expires_at']): ?>
                    <?= sanitize($b['expires_at']) ?>
                    <?php else: ?>
                    <span class="badge badge-warning">Permanent</span>
                    <?php endif; ?>
                </td>
                <td class="action-cell">
                    <?php if ($b['whitelisted']): ?>
                    <a href="?page=security&action=remove_whitelist&ip=<?= urlencode($b['ip_address']) ?>"
                       class="btn btn-sm btn-danger-ghost"
                       onclick="return confirm('Van whitelist verwijderen?')">✕ Verwijder</a>
                    <?php else: ?>
                    <a href="?page=security&action=unblock&ip=<?= urlencode($b['ip_address']) ?>"
                       class="btn btn-sm btn-ghost">✓ Deblokkeer</a>
                    <a href="?page=security&action=whitelist&ip=<?= urlencode($b['ip_address']) ?>"
                       class="btn btn-sm btn-ghost">⭐ Whitelist</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Handmatig blokkeren -->
<div class="card" style="max-width:500px">
    <div class="card-header"><h3 class="card-title">Handmatig IP blokkeren</h3></div>
    <div class="card-body">
        <form method="POST" action="?page=security&action=block">
            <?= csrf() ?>
            <div class="form-row">
                <div class="form-group">
                    <label>IP Adres</label>
                    <input type="text" name="ip" class="form-control mono" placeholder="1.2.3.4" required>
                </div>
                <div class="form-group">
                    <label>Reden</label>
                    <input type="text" name="reason" class="form-control" placeholder="Handmatig geblokkeerd" value="Handmatig geblokkeerd">
                </div>
            </div>
            <button type="submit" class="btn btn-danger">🚫 Blokkeer IP</button>
        </form>
    </div>
</div>
