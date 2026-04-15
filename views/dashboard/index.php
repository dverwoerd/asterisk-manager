<div class="page-header">
    <h1 class="page-title"><?= t('dashboard_title') ?></h1>
    <div class="page-actions">
        <a href="?page=dialplan&action=reload_all" class="btn btn-accent" onclick="return confirm('<?= t('reload') ?>?')">
            ⟳ <?= t('reload') ?>
        </a>
    </div>
</div>

<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card <?= $amiStatus['connected'] ? 'stat-ok' : 'stat-danger' ?>">
        <div class="stat-icon">◈</div>
        <div class="stat-body">
            <div class="stat-value"><?= $activeChannels ?></div>
            <div class="stat-label"><?= t('active_channels') ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">☎</div>
        <div class="stat-body">
            <div class="stat-value"><?= $stats['total_extensions'] ?></div>
            <div class="stat-label"><?= t('total_extensions') ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">▤</div>
        <div class="stat-body">
            <div class="stat-value"><?= $stats['calls_today'] ?></div>
            <div class="stat-label"><?= t('calls_today') ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">◫</div>
        <div class="stat-body">
            <div class="stat-value"><?= $stats['calls_month'] ?></div>
            <div class="stat-label"><?= t('calls_this_month') ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">⟷</div>
        <div class="stat-body">
            <div class="stat-value"><?= $stats['total_trunks'] ?></div>
            <div class="stat-label"><?= t('total_trunks') ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">⋮⋮</div>
        <div class="stat-body">
            <div class="stat-value"><?= $stats['total_queues'] ?></div>
            <div class="stat-label"><?= t('queues') ?></div>
        </div>
    </div>
</div>

<!-- Endpoint Status -->
<?php if (!empty($endpoints)): ?>
<div class="card mt-4 mb-4">
    <div class="card-header">
        <h3 class="card-title">📞 Geregistreerde Toestellen</h3>
        <span class="badge badge-success"><?= count($endpoints) ?> geregistreerd</span>
    </div>
    <div class="card-body p-0">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Ext</th>
                    <th>Naam</th>
                    <th>Toestel / Firmware</th>
                    <th>MAC</th>
                    <th>IP Adres</th>
                    <th>Status</th>
                    <th>RTT</th>
                    <th>Provisioned</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($endpoints as $ep): ?>
            <?php
                $isOnline  = in_array($ep['status'], ['Avail', 'Reachable']);
                $isOffline = in_array($ep['status'], ['Unreachable', 'Unavail']);
                $badgeClass = $isOnline ? 'success' : ($isOffline ? 'danger' : 'warning');
                $statusLabel = $isOnline ? '✓ Online' : ($isOffline ? '✗ Offline' : '⚠ ' . $ep['status']);
            ?>
            <tr>
                <td class="mono font-bold"><?= sanitize($ep['extension']) ?></td>
                <td><?= sanitize($ep['full_name']) ?></td>
                <td>
                    <?php if ($ep['user_agent'] !== '-'): ?>
                    <span class="badge badge-info"><?= sanitize($ep['user_agent']) ?></span>
                    <?php if ($ep['firmware'] !== '-'): ?>
                    <br><span class="mono text-sm text-muted" style="font-size:10px">fw: <?= sanitize($ep['firmware']) ?></span>
                    <?php endif; ?>
                    <?php else: ?>
                    <span class="badge badge-default"><?= sanitize($ep['model']) ?></span>
                    <?php endif; ?>
                </td>
                <td class="mono text-sm"><?= sanitize($ep['mac']) ?></td>
                <td class="mono text-sm">
                    <?= sanitize($ep['ip']) ?><span class="text-muted">:<?= sanitize($ep['port']) ?></span>
                </td>
                <td>
                    <span class="badge badge-<?= $badgeClass ?>"><?= $statusLabel ?></span>
                </td>
                <td class="mono text-sm"><?= sanitize($ep['rtt']) ?></td>
                <td class="mono text-sm text-muted">
                    <?= $ep['last_prov'] ? date('d-m H:i', strtotime($ep['last_prov'])) : '—' ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="dashboard-grid">
    <!-- Call Chart -->
    <div class="card card-wide">
        <div class="card-header">
            <h3 class="card-title">Call Activity — Last 7 Days</h3>
        </div>
        <div class="card-body">
            <canvas id="callChart" height="180"></canvas>
        </div>
    </div>

    <!-- System Status -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?= t('system_status') ?></h3>
        </div>
        <div class="card-body">
            <table class="info-table">
                <tr>
                    <td class="info-key">AMI Status</td>
                    <td><span class="badge <?= $amiStatus['connected'] ? 'badge-success' : 'badge-danger' ?>"><?= $amiStatus['connected'] ? 'Connected' : 'Offline' ?></span></td>
                </tr>
                <?php if ($amiStatus['connected']): ?>
                <tr>
                    <td class="info-key"><?= t('asterisk_version') ?></td>
                    <td class="mono"><?= sanitize($amiStatus['version'] ?? '-') ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td class="info-key">Extensions</td>
                    <td><?= $stats['total_extensions'] ?> active</td>
                </tr>
                <tr>
                    <td class="info-key">Ring Groups</td>
                    <td><?= $stats['total_ring_groups'] ?></td>
                </tr>
                <tr>
                    <td class="info-key">Answered Today</td>
                    <td><?= $stats['answered_today'] ?> / <?= $stats['calls_today'] ?></td>
                </tr>
                <tr>
                    <td class="info-key">PHP Version</td>
                    <td class="mono"><?= PHP_VERSION ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>

<!-- Recent Calls -->
<div class="card mt-4">
    <div class="card-header">
        <h3 class="card-title"><?= t('recent_calls') ?></h3>
        <a href="?page=cdr" class="btn btn-sm btn-ghost">View All →</a>
    </div>
    <div class="card-body p-0">
        <?php if (empty($recentCalls)): ?>
        <div class="empty-state"><?= t('no_records') ?></div>
        <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th><?= t('calldate') ?></th>
                    <th><?= t('src') ?></th>
                    <th><?= t('dst') ?></th>
                    <th><?= t('duration') ?></th>
                    <th><?= t('disposition') ?></th>
                    <th><?= t('cost') ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($recentCalls as $call): ?>
                <tr>
                    <td class="mono"><?= sanitize($call['calldate']) ?></td>
                    <td class="mono"><?= sanitize($call['src']) ?></td>
                    <td class="mono"><?= sanitize($call['dst']) ?></td>
                    <td class="mono"><?= formatDuration($call['duration']) ?></td>
                    <td>
                        <span class="badge badge-<?= match($call['disposition']) {
                            'ANSWERED' => 'success', 'BUSY' => 'warning',
                            'FAILED','NO ANSWER' => 'danger', default => 'default'
                        } ?>"><?= sanitize($call['disposition']) ?></span>
                    </td>
                    <td class="mono"><?= $call['cost'] !== null ? '€ '.number_format($call['cost'],4,',','.') : '-' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<script>
// Chart
const chartData = <?= json_encode($callChart) ?>;
const labels = chartData.map(d => d.day);
const answered = chartData.map(d => parseInt(d.answered));
const noAnswer = chartData.map(d => parseInt(d.no_answer));
const totalCalls = chartData.map(d => parseInt(d.total));

// Simple canvas bar chart (no external library needed)
document.addEventListener('DOMContentLoaded', function() {
    const canvas = document.getElementById('callChart');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const W = canvas.width = canvas.parentElement.clientWidth;
    const H = canvas.height = 180;
    const pad = {t:20, r:20, b:40, l:40};
    const barW = Math.max(8, (W - pad.l - pad.r) / Math.max(labels.length, 1) * 0.5);
    const maxVal = Math.max(...totalCalls, 1);

    ctx.clearRect(0,0,W,H);
    const chartW = W - pad.l - pad.r;
    const chartH = H - pad.t - pad.b;
    const step = chartW / Math.max(labels.length, 1);

    const cs = getComputedStyle(document.documentElement);
    const colorAcc = cs.getPropertyValue('--accent').trim() || '#00d4aa';
    const colorMut = cs.getPropertyValue('--muted').trim() || '#334155';
    const colorTxt = cs.getPropertyValue('--text-muted').trim() || '#64748b';

    labels.forEach((label, i) => {
        const x = pad.l + i * step + step / 2;
        const answH = (answered[i] / maxVal) * chartH;
        const naH   = (noAnswer[i] / maxVal) * chartH;

        // Answered bar
        ctx.fillStyle = colorAcc + 'cc';
        ctx.fillRect(x - barW - 2, H - pad.b - answH, barW, answH);

        // No answer bar
        ctx.fillStyle = '#ef4444aa';
        ctx.fillRect(x + 2, H - pad.b - naH, barW, naH);

        // Label
        ctx.fillStyle = colorTxt;
        ctx.font = '10px IBM Plex Mono';
        ctx.textAlign = 'center';
        ctx.fillText(label.slice(5), x, H - pad.b + 14);
    });

    // Y axis
    ctx.strokeStyle = colorMut;
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.moveTo(pad.l, pad.t);
    ctx.lineTo(pad.l, H - pad.b);
    ctx.lineTo(W - pad.r, H - pad.b);
    ctx.stroke();

    // Legend
    ctx.fillStyle = colorAcc;
    ctx.fillRect(pad.l, pad.t - 10, 10, 8);
    ctx.fillStyle = colorTxt;
    ctx.font = '10px IBM Plex Sans';
    ctx.textAlign = 'left';
    ctx.fillText('Answered', pad.l + 14, pad.t - 3);

    ctx.fillStyle = '#ef4444';
    ctx.fillRect(pad.l + 90, pad.t - 10, 10, 8);
    ctx.fillStyle = colorTxt;
    ctx.fillText('No Answer', pad.l + 104, pad.t - 3);
});
</script>
