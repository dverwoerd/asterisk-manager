<?php
$t   = $spec['totaal'];
$eur = fn(float $v) => '€ ' . number_format($v, 2, ',', '.');
$nl  = fn(float $v) => number_format($v, 3, ',', '.');
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
<div class="alert alert-warning mb-4">
    <strong>⚠ Geen tarief gevonden</strong> voor
    <?php
    $lijst = [];
    foreach ($spec['zonder_tarief'] as $nr => $cnt) {
        $lijst[] = '<span class="mono">' . sanitize(GesprekspecificatiesController::toonNummer((string)$nr)) . '</span> (' . $cnt . 'x)';
    }
    echo implode(', ', $lijst);
    ?>.
    Deze gesprekken zijn als € 0,00 meegeteld. Voeg de prefix toe in de tabel <code>bel_tarieven</code>.
</div>
<?php endif; ?>

<div class="stats-grid stats-grid-4 mb-4">
    <div class="stat-card"><div class="stat-body">
        <div class="stat-value"><?= number_format($t['aantal']) ?></div>
        <div class="stat-label">Gesprekken</div>
    </div></div>
    <div class="stat-card"><div class="stat-body">
        <div class="stat-value"><?= number_format($t['minuten']) ?></div>
        <div class="stat-label">Minuten (afgerond)</div>
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

<!-- Per categorie (gelijk aan PDF) -->
<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title">Per categorie</h3>
        <span class="text-muted" style="font-size:12px">
            Starttarief € <?= $nl($tarieven['start']) ?> per gesprek ·
            Nationaal/088 € <?= $nl($tarieven['nationaal']) ?>/min ·
            Mobiel € <?= $nl($tarieven['mobiel']) ?>/min ·
            Internationaal/0900 volgens tarieftabel · 0800 gratis ·
            minuten naar boven afgerond
        </span>
    </div>
    <div class="card-body p-0">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Categorie</th>
                    <th class="text-right">Aantal gesprekken</th>
                    <th class="text-right">Minuten</th>
                    <th class="text-right">Totaal (€)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($spec['categorieen'] as $cat => $g): ?>
                <tr<?= $g['aantal'] === 0 ? ' class="text-muted"' : '' ?>>
                    <td><?= sanitize($cat) ?></td>
                    <td class="text-right mono"><?= $g['aantal'] ?></td>
                    <td class="text-right mono"><?= $g['minuten'] ?></td>
                    <td class="text-right mono"><?= $eur($g['kosten']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:var(--bg-secondary);font-weight:600">
                    <td>Totaal</td>
                    <td class="text-right mono"><?= $t['aantal'] ?></td>
                    <td class="text-right mono"><?= $t['minuten'] ?></td>
                    <td class="text-right mono" style="color:var(--accent)"><?= $eur($t['kosten']) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Per nummer (samengevoegd) -->
<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title">Specificatie per nummer</h3>
    </div>
    <div class="card-body p-0">
        <table class="data-table data-table-sm">
            <thead>
                <tr>
                    <th>Nummer</th>
                    <th>Categorie</th>
                    <th>Bestemming</th>
                    <th>Toestel(len)</th>
                    <th class="text-right">Aantal</th>
                    <th class="text-right">Minuten</th>
                    <th class="text-right">Tarief/min</th>
                    <th class="text-right">Totaal (€)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($spec['nummers'] as $n): ?>
                <tr>
                    <td class="mono"><strong><?= sanitize($n['nummer']) ?></strong></td>
                    <td><?= sanitize($n['categorie']) ?></td>
                    <td>
                        <?= sanitize($n['land'] ?: '-') ?>
                        <?php if (!$n['tarief_bekend']): ?>
                        <span style="color:#C9A227" title="Geen tarief in bel_tarieven">⚠ geen tarief</span>
                        <?php endif; ?>
                    </td>
                    <td class="mono"><?= sanitize(implode(', ', $n['toestellen'])) ?></td>
                    <td class="text-right mono"><?= $n['aantal'] ?></td>
                    <td class="text-right mono"><?= $n['minuten'] ?></td>
                    <td class="text-right mono text-muted">€ <?= $nl($n['tarief']) ?></td>
                    <td class="text-right mono"><strong><?= $eur($n['kosten']) ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:var(--bg-secondary);font-weight:600">
                    <td colspan="4">Totaal</td>
                    <td class="text-right mono"><?= $t['aantal'] ?></td>
                    <td class="text-right mono"><?= $t['minuten'] ?></td>
                    <td></td>
                    <td class="text-right mono" style="color:var(--accent)"><?= $eur($t['kosten']) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
