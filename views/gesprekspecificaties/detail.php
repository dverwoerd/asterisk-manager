<?php
// Zelfde opbouw als de PDF: samenvatting per categorie, detail per categorie
// met gesprekken gegroepeerd per nummer.
$t   = $spec['totaal'];
$dur = fn(int $sec) => GesprekspecificatiesController::leesbareDuur($sec);
$eur = fn(float $v, int $d = 2) => '€ ' . number_format($v, $d, ',', '.');
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

<p class="text-muted text-sm mb-4">
    Bron: Asterisk-gespreksgegevens en geïmporteerde CDR-records.
    <?php if (!empty($dubbelen)): ?>
    <?= (int)$dubbelen ?> dubbel geregistreerd<?= $dubbelen === 1 ? '' : 'e' ?> gesprek<?= $dubbelen === 1 ? '' : 'ken' ?> (zelfde toestel, nummer en tijdstip) <?= $dubbelen === 1 ? 'is' : 'zijn' ?> één keer geteld.
    <?php endif; ?>
</p>

<!-- Zelfde opbouw als de PDF. Pagina 1: samenvatting van alle categorieen -->
<div class="card mb-4">
    <div class="card-header"><h3 class="card-title">Samenvatting</h3></div>
    <div class="card-body p-0">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Categorie</th>
                    <th class="text-center">Aantal gesprekken</th>
                    <th class="text-center">Minuten</th>
                    <th class="text-right">Totaal (&euro;)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($spec['categorieen'] as $cat => $g): ?>
                <tr>
                    <td><?= sanitize($cat) ?></td>
                    <td class="text-center mono"><?= (int)$g['aantal'] ?></td>
                    <td class="text-center mono"><?= (int)$g['minuten'] ?></td>
                    <td class="text-right mono"><?= $eur($g['kosten']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:var(--accent);color:#fff;font-weight:700">
                    <td>Totaal</td><td></td><td></td>
                    <td class="text-right mono"><?= $eur($t['kosten']) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Pagina 2: per categorie en nummer de afzonderlijke gesprekken -->
<div class="card mb-4">
    <div class="card-header"><h3 class="card-title">Specificatie per nummer</h3></div>
    <div class="card-body p-0">
        <table class="data-table data-table-sm">
            <thead>
                <tr>
                    <th>Datum en tijd</th>
                    <th>Toestel / nummer</th>
                    <th class="text-center">Duur</th>
                    <th class="text-center">Minuten</th>
                    <th class="text-right">Kosten (&euro;)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($spec['categorieen'] as $cat => $g): if ($g['aantal'] === 0) continue; ?>
                <tr style="background:var(--bg-secondary);font-weight:700;border-top:2px solid var(--primary)">
                    <td colspan="2"><?= sanitize($cat) ?></td>
                    <td class="text-center mono"><?= $dur((int)$g['sec']) ?></td>
                    <td class="text-center mono"><?= (int)$g['minuten'] ?></td>
                    <td class="text-right mono"><?= $eur($g['kosten']) ?></td>
                </tr>
                <?php foreach ($g['nummers'] as $n): ?>
                <tr style="font-weight:600">
                    <td colspan="2">
                        <span class="mono"><?= sanitize($n['nummer']) ?></span>
                        <?php if (!empty($n['land'])): ?><span class="text-muted" style="font-weight:400"> &middot; <?= sanitize($n['land']) ?></span><?php endif; ?>
                        <?php if (empty($n['tarief_bekend'])): ?><span style="color:var(--warning);font-weight:400"> &middot; geen tarief</span><?php endif; ?>
                    </td>
                    <td class="text-center mono"><?= $dur((int)$n['sec']) ?></td>
                    <td class="text-center mono"><?= (int)$n['minuten'] ?></td>
                    <td class="text-right mono"><?= $eur($n['kosten'], 4) ?></td>
                </tr>
                <?php foreach ($n['calls'] as $c): ?>
                <tr>
                    <td class="mono" style="white-space:nowrap"><?= date('d-m-Y H:i', strtotime($c['calldate'])) ?></td>
                    <td class="mono">Toestel <?= sanitize($c['src']) ?><?= !empty($c['doorgeschakeld']) ? ' (doorgeschakeld)' : '' ?></td>
                    <td class="text-center mono"><?= $dur((int)$c['billsec']) ?></td>
                    <td class="text-center mono"><?= (int)$c['minuten'] ?></td>
                    <td class="text-right mono"><?= $eur($c['kosten'], 4) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:var(--accent);color:#fff;font-weight:700">
                    <td colspan="2">Totaal</td>
                    <td class="text-center mono"><?= $dur((int)$t['sec']) ?></td>
                    <td class="text-center mono"><?= (int)$t['minuten'] ?></td>
                    <td class="text-right mono"><?= $eur($t['kosten']) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
