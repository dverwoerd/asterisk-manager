<div class="page-header">
    <h1 class="page-title">
        <?= sanitize($customer['company_name'] ?: $customer['contact_name']) ?>
        <span style="font-weight:400;font-size:16px;color:var(--text-muted)">— <?= sanitize($maandLabel) ?></span>
    </h1>
    <div class="page-actions">
        <a href="?page=gesprekspecificaties&maand=<?= $maand ?>" class="btn btn-ghost">← Terug</a>
        <a href="?page=gesprekspecificaties&action=pdf&id=<?= $customer['id'] ?>&maand=<?= $maand ?>"
           class="btn btn-accent" target="_blank">
            ↓ PDF downloaden
        </a>
    </div>
</div>

<!-- Samenvatting -->
<div class="stats-grid stats-grid-4 mb-4">
    <div class="stat-card">
        <div class="stat-body">
            <div class="stat-value"><?= number_format($totaalAantal) ?></div>
            <div class="stat-label">Gesprekken</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-body">
            <div class="stat-value"><?= formatDuration($totaalSec) ?></div>
            <div class="stat-label">Totale duur</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-body">
            <div class="stat-value"><?= count($perCategorie) ?></div>
            <div class="stat-label">Categorieën</div>
        </div>
    </div>
    <div class="stat-card stat-accent">
        <div class="stat-body">
            <div class="stat-value mono">€ <?= number_format($totaalKosten, 2, ',', '.') ?></div>
            <div class="stat-label">Kosten excl. BTW</div>
        </div>
    </div>
</div>

<!-- Samenvatting per categorie -->
<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title">Samenvatting per categorie</h3>
    </div>
    <div class="card-body p-0">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Categorie</th>
                    <th class="text-right">Gesprekken</th>
                    <th class="text-right">Totale duur</th>
                    <th class="text-right">Kosten</th>
                    <th class="text-right">% van totaal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($perCategorie as $cat => $data): ?>
                <tr>
                    <td>
                        <span class="badge badge-<?= strtolower(preg_replace('/[^a-z0-9]/i', '', $cat)) ?>">
                            <?= sanitize($cat) ?>
                        </span>
                    </td>
                    <td class="text-right mono"><?= $data['aantal'] ?></td>
                    <td class="text-right mono"><?= formatDuration($data['sec']) ?></td>
                    <td class="text-right mono"><strong>€ <?= number_format($data['kosten'], 2, ',', '.') ?></strong></td>
                    <td class="text-right mono text-muted">
                        <?= $totaalKosten > 0 ? number_format($data['kosten'] / $totaalKosten * 100, 1) : '0' ?>%
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:var(--bg-secondary);font-weight:600">
                    <td>Totaal</td>
                    <td class="text-right mono"><?= $totaalAantal ?></td>
                    <td class="text-right mono"><?= formatDuration($totaalSec) ?></td>
                    <td class="text-right mono" style="color:var(--accent)">€ <?= number_format($totaalKosten, 2, ',', '.') ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Detail per categorie -->
<?php foreach ($perCategorie as $cat => $data): ?>
<div class="card mb-4">
    <div class="card-header" style="cursor:pointer"
         onclick="toggleSection('cat-<?= htmlspecialchars(preg_replace('/[^a-z0-9]/i','', $cat)) ?>')">
        <h3 class="card-title">
            <?= sanitize($cat) ?>
            <span class="badge ms-2"><?= $data['aantal'] ?> gesprekken</span>
            <span style="margin-left:8px;color:var(--text-muted);font-size:13px;font-weight:400">
                € <?= number_format($data['kosten'], 2, ',', '.') ?>
            </span>
        </h3>
        <span class="toggle-icon" id="icon-cat-<?= preg_replace('/[^a-z0-9]/i','', $cat) ?>">▼</span>
    </div>
    <div id="cat-<?= preg_replace('/[^a-z0-9]/i','', $cat) ?>" class="card-body p-0">
        <table class="data-table data-table-sm">
            <thead>
                <tr>
                    <th>Datum</th>
                    <th>Tijd</th>
                    <th>Toestel</th>
                    <th>Bestemming</th>
                    <th class="text-right">Duur</th>
                    <th class="text-right">Kosten</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['calls'] as $call): ?>
                <tr>
                    <td class="mono"><?= date('d-m-Y', strtotime($call['calldate'])) ?></td>
                    <td class="mono"><?= date('H:i:s', strtotime($call['calldate'])) ?></td>
                    <td class="mono"><?= sanitize($call['src']) ?></td>
                    <td class="mono">
                        <?= sanitize($call['dst']) ?>
                        <?php if (!empty($call['destination_name'])): ?>
                        <span class="text-muted" style="font-size:11px"> — <?= sanitize($call['destination_name']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-right mono"><?= formatDuration((int)$call['billsec']) ?></td>
                    <td class="text-right mono">€ <?= number_format((float)$call['cost'], 4, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:var(--bg-secondary);font-weight:600">
                    <td colspan="4">Subtotaal <?= sanitize($cat) ?></td>
                    <td class="text-right mono"><?= formatDuration($data['sec']) ?></td>
                    <td class="text-right mono" style="color:var(--accent)">€ <?= number_format($data['kosten'], 2, ',', '.') ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<?php endforeach; ?>

<script>
function toggleSection(id) {
    const el   = document.getElementById(id);
    const icon = document.getElementById('icon-' + id);
    if (!el) return;
    const isHidden = el.style.display === 'none';
    el.style.display   = isHidden ? '' : 'none';
    if (icon) icon.textContent = isHidden ? '▼' : '▶';
}
</script>
