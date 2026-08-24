<?php
// Verwijder verlopen blokkades
Database::query("DELETE FROM ip_blacklist WHERE whitelisted=0 AND expires_at IS NOT NULL AND expires_at < NOW()");
$blacklist = Database::fetchAll("SELECT * FROM ip_blacklist ORDER BY whitelisted ASC, blocked_at DESC");
$recentAttempts = Database::fetchAll(
    "SELECT ip_address, username, COUNT(*) as attempts, MAX(attempted_at) as last_attempt
     FROM login_attempts
     WHERE attempted_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
     GROUP BY ip_address, username
     ORDER BY attempts DESC LIMIT 20"
);
?>
<div class="card mt-4 mb-4">
    <div class="card-header">
        <h3 class="card-title">Recente mislukte pogingen (24 uur)</h3>
        <a href="?page=security&action=clear_attempts" class="btn btn-ghost btn-sm">🗑 Opruimen</a>
    </div>
    <div class="card-body p-0">
        <?php if (empty($recentAttempts)): ?>
        <div class="empty-state">Geen mislukte pogingen.</div>
        <?php else: ?>
        <table class="data-table">
            <thead><tr><th>IP</th><th>Gebruiker</th><th>Pogingen</th><th>Laatste</th><th>Actie</th></tr></thead>
            <tbody>
            <?php foreach ($recentAttempts as $a): ?>
            <tr>
                <td class="mono"><?= sanitize($a['ip_address']) ?></td>
                <td class="mono"><?= sanitize($a['username']) ?></td>
                <td><span class="badge badge-<?= $a['attempts']>=5?'danger':($a['attempts']>=3?'warning':'info') ?>"><?= $a['attempts'] ?>x</span></td>
                <td class="mono text-sm text-muted"><?= sanitize($a['last_attempt']) ?></td>
                <td class="action-cell">
                    <a href="?page=security&action=block&ip=<?= urlencode($a['ip_address']) ?>"
                       class="btn btn-sm btn-danger-ghost"
                       onclick="return confirm('Blokkeer <?= sanitize($a['ip_address']) ?>?')">🚫 Blokkeer</a>
                    <a href="?page=security&action=whitelist&ip=<?= urlencode($a['ip_address']) ?>"
                       class="btn btn-sm btn-ghost">✓ Whitelist</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><h3 class="card-title">IP Blacklist & Whitelist</h3></div>
    <div class="card-body p-0">
        <?php if (empty($blacklist)): ?>
        <div class="empty-state">Geen geblokkeerde IPs.</div>
        <?php else: ?>
        <table class="data-table">
            <thead><tr><th>IP</th><th>Status</th><th>Reden</th><th>Verloopt</th><th>Actie</th></tr></thead>
            <tbody>
            <?php foreach ($blacklist as $b): ?>
            <tr>
                <td class="mono font-bold"><?= sanitize($b['ip_address']) ?></td>
                <td><?= $b['whitelisted'] ? '<span class="badge badge-success">✓ Whitelist</span>' : '<span class="badge badge-danger">🚫 Geblokkeerd</span>' ?></td>
                <td class="text-sm text-muted"><?= sanitize($b['reason']) ?></td>
                <td class="mono text-sm"><?= $b['whitelisted'] ? '—' : sanitize($b['expires_at'] ?? 'Permanent') ?></td>
                <td class="action-cell">
                    <?php if ($b['whitelisted']): ?>
                    <a href="?page=security&action=remove_whitelist&ip=<?= urlencode($b['ip_address']) ?>" class="btn btn-sm btn-danger-ghost">✕</a>
                    <?php else: ?>
                    <a href="?page=security&action=unblock&ip=<?= urlencode($b['ip_address']) ?>" class="btn btn-sm btn-ghost">✓ Deblokkeer</a>
                    <a href="?page=security&action=whitelist&ip=<?= urlencode($b['ip_address']) ?>" class="btn btn-sm btn-ghost">⭐ Whitelist</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

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
                    <input type="text" name="reason" class="form-control" value="Handmatig geblokkeerd">
                </div>
            </div>
            <button type="submit" class="btn btn-danger">🚫 Blokkeer IP</button>
        </form>
    </div>
</div>
