<?php
$t   = $spec['totaal'];
$eur = fn(float $v) => '€ ' . number_format($v, 2, ',', '.');
$nl  = fn(float $v) => number_format($v, 3, ',', '.');
?>
<link rel="stylesheet" href="assets/css/gesprekspecificaties.css">
<div class="gs">

<header class="gs-header">
    <div class="gs-logo">PT</div>
    <div>
        <h1><?= sanitize($customer['company_name'] ?: $customer['contact_name']) ?></h1>
        <div class="sub">Gespreksspecificaties — <?= sanitize($maandLabel) ?></div>
    </div>
    <div class="gs-header-right">
        <a href="?page=gesprekspecificaties&maand=<?= $maand ?>" class="btn btn-ghost btn-sm">← Terug</a>
        <a href="?page=gesprekspecificaties&action=pdf&id=<?= $customer['id'] ?>&maand=<?= $maand ?>"
           class="btn btn-yellow" target="_blank">⬇ PDF downloaden</a>
    </div>
</header>

<div class="gs-main">

<?php if (!empty($spec['zonder_tarief'])): ?>
<div class="alert alert-warning">
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

<div class="stats-row">
    <div class="stat-chip"><div class="val"><?= number_format($t['aantal'], 0, ',', '.') ?></div><div class="lbl">Gesprekken</div></div>
    <div class="stat-chip"><div class="val"><?= number_format($t['minuten'], 0, ',', '.') ?></div><div class="lbl">Minuten</div></div>
    <div class="stat-chip"><div class="val"><?= count($spec['nummers']) ?></div><div class="lbl">Nummers</div></div>
    <div class="stat-chip geel"><div class="val"><?= $eur($t['kosten']) ?></div><div class="lbl">Totaal excl. BTW</div></div>
</div>

<!-- Per categorie (gelijk aan PDF) -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Per categorie</h3>
        <span class="card-hint">
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
                    <th class="text-center">Aantal gesprekken</th>
                    <th class="text-center">Minuten</th>
                    <th class="text-right">Totaal (€)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($spec['categorieen'] as $cat => $g): ?>
                <tr<?= $g['aantal'] === 0 ? ' class="leeg"' : '' ?>>
                    <td><?= sanitize($cat) ?></td>
                    <td class="text-center"><?= $g['aantal'] ?></td>
                    <td class="text-center"><?= $g['minuten'] ?></td>
                    <td class="text-right mono"><?= $eur($g['kosten']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td>Totaal</td>
                    <td class="text-center"><?= $t['aantal'] ?></td>
                    <td class="text-center"><?= $t['minuten'] ?></td>
                    <td class="text-right mono"><?= $eur($t['kosten']) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Per nummer (samengevoegd) -->
<div class="card">
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
                    <th class="text-center">Aantal</th>
                    <th class="text-center">Minuten</th>
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
                        <span class="warn-tag" title="Geen tarief in bel_tarieven">⚠ geen tarief</span>
                        <?php endif; ?>
                    </td>
                    <td class="mono"><?= sanitize(implode(', ', $n['toestellen'])) ?></td>
                    <td class="text-center"><?= $n['aantal'] ?></td>
                    <td class="text-center"><?= $n['minuten'] ?></td>
                    <td class="text-right mono text-muted">€ <?= $nl($n['tarief']) ?></td>
                    <td class="text-right mono"><strong><?= $eur($n['kosten']) ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4">Totaal</td>
                    <td class="text-center"><?= $t['aantal'] ?></td>
                    <td class="text-center"><?= $t['minuten'] ?></td>
                    <td></td>
                    <td class="text-right mono"><?= $eur($t['kosten']) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

</div><!-- /gs-main -->
</div><!-- /gs -->
