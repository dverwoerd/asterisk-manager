<?php
// Zelfde opbouw als de PDF: samenvatting per categorie, detail per categorie
// met gesprekken gegroepeerd per nummer.
$t   = $spec['totaal'];
$eur = fn(float $v, int $d = 2) => '€ ' . number_format($v, $d, ',', '.');
$catKleuren = [
    'Nationaal'      => '#1B3A6B',
    'Mobiel'         => '#2A5298',
    '088 Nummer'     => '#3D7ABF',
    'Internationaal' => '#C9571A',
    '0800 Gratis'    => '#27AE60',
    '0900 Premium'   => '#E74C3C',
];
$totE4 = (int)round($t['kosten'] * 10000);
?>
<div class="page-header">
    <h1 class="page-title">
        <?= sanitize($customer['company_name'] ?: $customer['contact_name']) ?>
        <span style="font-weight:400;font-size:16px;color:var(--text-muted)">— <?= sanitize($maandLabel) ?></span>
    </h1>
    <div class="page-actions">
        <a href="?page=gesprekspecificaties&maand=<?= $maand ?>" class="btn btn-ghost">← Terug</a>
        <a href="?page=gesprekspecificaties&action=pdf&id=<?= $customer['id'] ?>&maand=<?= $maand ?>"
           class="btn btn-accent" target="_blank">↓ PDF downloaden</a>
    </div>
</div>

<?php if (!empty($spec['zonder_tarief'])): ?>
<div class="alert alert-warning mb-4"><div>
    <strong>⚠ Geen tarief gevonden</strong> voor
    <?php
    $lijst = [];
    foreach ($spec['zonder_tarief'] as $nr => $cnt) {
        $lijst[] = '<span class="mono">' . sanitize(GesprekspecificatiesController::toonNummer((string)$nr)) . '</span> (' . $cnt . 'x)';
    }
    echo implode(', ', $lijst);
    ?>.
    Deze gesprekken zijn als € 0,00 meegeteld. Voeg de prefix toe in de tabel <code>bel_tarieven</code>.
</div></div>
<?php endif; ?>

<div class="stats-grid stats-grid-4 mb-4">
    <div class="stat-card"><div class="stat-body">
        <div class="stat-value"><?= number_format($t['aantal']) ?></div>
        <div class="stat-label">Gesprekken</div>
    </div></div>
    <div class="stat-card"><div class="stat-body">
        <div class="stat-value"><?= formatDuration($t['sec']) ?></div>
        <div class="stat-label">Totale duur</div>
    </div></div>
    <div class="stat-card"><div class="stat-body">
        <div class="stat-value"><?= count($spec['nummers']) ?></div>
        <div class="stat-label">Gebelde nummers</div>
    </div></div>
    <div class="stat-card stat-accent"><div class="stat-body">
        <div class="stat-value mono"><?= $eur($t['kosten']) ?></div>
        <div class="stat-label">Kosten excl. BTW</div>
    </div></div>
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
                    <th class="text-right">Duur</th>
                    <th class="text-right">Kosten</th>
                    <th class="text-right">% van totaal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($spec['categorieen'] as $cat => $g): if ($g['aantal'] === 0) continue; ?>
                <tr>
                    <td>
                        <span style="display:inline-block;padding:1px 8px;border-radius:4px;font-size:11px;font-weight:700;color:#fff;background:<?= $catKleuren[$cat] ?? '#1B3A6B' ?>">
                            <?= sanitize($cat) ?>
                        </span>
                    </td>
                    <td class="text-right mono"><?= $g['aantal'] ?></td>
                    <td class="text-right mono"><?= formatDuration($g['sec']) ?></td>
                    <td class="text-right mono"><strong><?= $eur($g['kosten']) ?></strong></td>
                    <td class="text-right mono text-muted"><?= $totE4 > 0 ? number_format($g['e4'] / $totE4 * 100, 1, ',', '.') : '0' ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:var(--bg-secondary);font-weight:600">
                    <td>Totaal</td>
                    <td class="text-right mono"><?= $t['aantal'] ?></td>
                    <td class="text-right mono"><?= formatDuration($t['sec']) ?></td>
                    <td class="text-right mono" style="color:var(--accent)"><?= $eur($t['kosten']) ?></td>
                    <td class="text-right mono">100%</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Detail per categorie, gegroepeerd per nummer -->
<?php foreach ($spec['categorieen'] as $cat => $g): if ($g['aantal'] === 0) continue;
    $id = 'cat-' . preg_replace('/[^a-z0-9]/i', '', $cat);
    $kleur = $catKleuren[$cat] ?? '#1B3A6B'; ?>
<div class="card mb-4" style="border-left:4px solid <?= $kleur ?>">
    <div class="card-header" style="cursor:pointer" onclick="toggleSection('<?= $id ?>')">
        <h3 class="card-title" style="color:<?= $kleur ?>">
            <?= sanitize($cat) ?>
            <span class="badge ms-2"><?= count($g['nummers']) ?> nummers</span>
            <span class="badge ms-2"><?= $g['aantal'] ?> gesprekken</span>
            <span style="margin-left:8px;color:var(--text-muted);font-size:13px;font-weight:400"><?= $eur($g['kosten']) ?></span>
        </h3>
        <span class="toggle-icon" id="icon-<?= $id ?>">▼</span>
    </div>
    <div id="<?= $id ?>" class="card-body p-0">
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
                <?php foreach ($g['nummers'] as $n): ?>
                <tr style="background:var(--bg-secondary);font-weight:600">
                    <td colspan="4">
                        <span class="mono"><?= sanitize($n['nummer']) ?></span>
                        <span class="text-muted" style="font-weight:400;font-size:12px">
                            — <?= sanitize($n['land'] ?: $cat) ?> — <?= $n['aantal'] ?> gesprek<?= $n['aantal'] === 1 ? '' : 'ken' ?>
                        </span>
                        <?php if (!$n['tarief_bekend']): ?>
                        <span style="color:var(--warning);font-size:12px">⚠ geen tarief</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-right mono"><?= formatDuration($n['sec']) ?></td>
                    <td class="text-right mono"><?= $eur($n['kosten'], 4) ?></td>
                </tr>
                <?php foreach ($n['calls'] as $c): ?>
                <tr>
                    <td class="mono"><?= date('d-m-Y', strtotime($c['calldate'])) ?></td>
                    <td class="mono"><?= date('H:i:s', strtotime($c['calldate'])) ?></td>
                    <td class="mono"><?= sanitize($c['src']) ?></td>
                    <td class="mono"><?= sanitize($c['dst']) ?></td>
                    <td class="text-right mono"><?= formatDuration((int)$c['billsec']) ?></td>
                    <td class="text-right mono"><?= $eur($c['kosten'], 4) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:var(--primary);color:#fff;font-weight:600">
                    <td colspan="4">Subtotaal <?= sanitize($cat) ?></td>
                    <td class="text-right mono"><?= formatDuration($g['sec']) ?></td>
                    <td class="text-right mono"><?= $eur($g['kosten']) ?></td>
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
    el.style.display = isHidden ? '' : 'none';
    if (icon) icon.textContent = isHidden ? '▼' : '▶';
}
</script>
