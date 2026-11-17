<?php
$maandNamen = ['01'=>'Januari','02'=>'Februari','03'=>'Maart','04'=>'April',
               '05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Augustus',
               '09'=>'September','10'=>'Oktober','11'=>'November','12'=>'December'];
$maandLabel = function (string $ym) use ($maandNamen): string {
    [$y, $mo] = explode('-', $ym . '-01');
    return ($maandNamen[$mo] ?? $mo) . ' ' . $y;
};
$eur = fn(float $v) => '€ ' . number_format($v, 2, ',', '.');
?>
<link rel="stylesheet" href="assets/css/gesprekspecificaties.css">
<div class="gs">

<header class="gs-header">
    <div class="gs-logo">PT</div>
    <div>
        <h1>Gespreksspecificaties</h1>
        <div class="sub"><?= sanitize(getSetting('company_name', 'Pantronic IT')) ?> — <?= sanitize($maandLabel($maand)) ?></div>
    </div>
</header>

<div class="gs-main">

<!-- Maandkiezer -->
<div class="card">
    <div class="card-body">
        <form method="GET" action="" class="gs-filter">
            <input type="hidden" name="page" value="gesprekspecificaties">
            <label for="gs-maand">Maand</label>
            <select id="gs-maand" name="maand" onchange="this.form.submit()">
                <?php foreach ($maanden as $m): ?>
                <option value="<?= $m['maand'] ?>" <?= $m['maand'] === $maand ? 'selected' : '' ?>><?= $maandLabel($m['maand']) ?></option>
                <?php endforeach; ?>
                <?php if (empty($maanden)): ?>
                <option value="<?= date('Y-m') ?>" selected><?= $maandLabel(date('Y-m')) ?></option>
                <?php endif; ?>
            </select>
            <button type="submit" class="btn btn-primary btn-sm">Tonen</button>
            <span class="gs-periode">Periode: <?= date('d-m-Y', strtotime($dateFrom)) ?> t/m <?= date('d-m-Y', strtotime($dateTo)) ?></span>
        </form>
    </div>
</div>

<?php if (empty($overzicht)): ?>
<div class="alert alert-info">
    Geen gesprekken gevonden voor deze periode. Controleer of CDR records zijn gesynchroniseerd
    en klanten zijn gekoppeld aan toestelnummers.
</div>
<?php else: ?>
<?php
$totAantal = array_sum(array_column($overzicht, 'aantal'));
$totMin    = array_sum(array_column($overzicht, 'totaal_min'));
$totKosten = array_sum(array_column($overzicht, 'totaal_kosten'));
?>

<div class="card">
    <div class="card-header">
        <div class="card-title">
            Resultaten
            <span style="background:var(--gs-blue-xlight);color:var(--gs-blue-dark);border-radius:4px;padding:2px 8px;font-size:11px;font-weight:500">
                <?= sanitize($maandLabel($maand)) ?>
            </span>
        </div>
    </div>
    <div class="card-body">
        <div class="stats-row">
            <div class="stat-chip"><div class="val"><?= number_format($totAantal, 0, ',', '.') ?></div><div class="lbl">Gesprekken</div></div>
            <div class="stat-chip"><div class="val"><?= count($overzicht) ?></div><div class="lbl">Klanten</div></div>
            <div class="stat-chip"><div class="val"><?= number_format($totMin, 0, ',', '.') ?></div><div class="lbl">Minuten</div></div>
            <div class="stat-chip"><div class="val"><?= $eur($totKosten) ?></div><div class="lbl">Totaal</div></div>
        </div>
        <div class="results-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Klant</th>
                    <th class="text-center">Gesprekken</th>
                    <th class="text-center">Minuten</th>
                    <th class="text-right">Kosten excl. BTW</th>
                    <th class="text-center" style="width:190px">Acties</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($overzicht as $r): ?>
                <tr>
                    <td><strong><?= sanitize($r['naam']) ?></strong></td>
                    <td class="text-center"><?= number_format($r['aantal'], 0, ',', '.') ?></td>
                    <td class="text-center"><?= number_format($r['totaal_min'], 0, ',', '.') ?></td>
                    <td class="text-right">
                        <span class="cell-cost"><?= $eur($r['totaal_kosten']) ?></span>
                        <?php if ($r['zonder_tarief'] > 0): ?>
                        <br><span class="warn-tag" title="Gesprekken zonder tarief in bel_tarieven, als € 0,00 meegeteld">⚠ <?= $r['zonder_tarief'] ?> zonder tarief</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <a href="?page=gesprekspecificaties&action=detail&id=<?= $r['id'] ?>&maand=<?= $r['maand'] ?>" class="btn btn-ghost btn-sm">☰ Detail</a>
                        <a href="?page=gesprekspecificaties&action=pdf&id=<?= $r['id'] ?>&maand=<?= $r['maand'] ?>" class="btn btn-yellow btn-sm" target="_blank">⬇ PDF</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td>Totaal</td>
                    <td class="text-center"><?= number_format($totAantal, 0, ',', '.') ?></td>
                    <td class="text-center"><?= number_format($totMin, 0, ',', '.') ?></td>
                    <td class="text-right"><?= $eur($totKosten) ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
        </div>
    </div>
</div>

<?php endif; ?>

</div><!-- /gs-main -->
</div><!-- /gs -->
