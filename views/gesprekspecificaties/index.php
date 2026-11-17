<div class="page-header">
    <h1 class="page-title">Gespreksspecificaties</h1>
    <div class="page-actions">
        <!-- leeg: acties staan per rij -->
    </div>
</div>

<!-- Maandkiezer -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="filter-form">
            <input type="hidden" name="page" value="gesprekspecificaties">
            <div class="filter-row">
                <div class="form-group">
                    <label>Maand</label>
                    <select name="maand" class="form-control form-control-sm" onchange="this.form.submit()">
                        <?php foreach ($maanden as $m): ?>
                        <?php
                            [$y, $mo] = explode('-', $m['maand'] . '-01');
                            $namen = ['01'=>'Januari','02'=>'Februari','03'=>'Maart','04'=>'April',
                                      '05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Augustus',
                                      '09'=>'September','10'=>'Oktober','11'=>'November','12'=>'December'];
                            $label = ($namen[$mo] ?? $mo) . ' ' . $y;
                        ?>
                        <option value="<?= $m['maand'] ?>" <?= $m['maand'] === $maand ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                        <?php endforeach; ?>
                        <?php if (empty($maanden)): ?>
                        <option value="<?= date('Y-m') ?>" selected><?= date('m-Y') ?></option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="form-group form-group-btn">
                    <button type="submit" class="btn btn-accent btn-sm">Tonen</button>
                </div>
                <div style="margin-left:auto;font-size:12px;color:var(--text-muted);align-self:center">
                    Periode: <?= date('d-m-Y', strtotime($dateFrom)) ?> t/m <?= date('d-m-Y', strtotime($dateTo)) ?>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if (empty($overzicht)): ?>
<div class="alert alert-info">
    Geen gesprekken gevonden voor deze periode. Controleer of CDR records zijn gesynchroniseerd
    en klanten zijn gekoppeld aan toestelnummers.
</div>
<?php else: ?>

<!-- Overzicht per klant -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <?php
                [$y, $mo] = explode('-', $maand . '-01');
                $namen = ['01'=>'Januari','02'=>'Februari','03'=>'Maart','04'=>'April',
                          '05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Augustus',
                          '09'=>'September','10'=>'Oktober','11'=>'November','12'=>'December'];
                echo ($namen[$mo] ?? $mo) . ' ' . $y;
            ?>
            — <?= count($overzicht) ?> klant<?= count($overzicht) !== 1 ? 'en' : '' ?>
        </h3>
    </div>
    <div class="card-body p-0">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Klant</th>
                    <th class="text-right">Gesprekken</th>
                    <th class="text-right">Minuten</th>
                    <th class="text-right">Kosten excl. BTW</th>
                    <th class="text-center" style="width:180px">Acties</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($overzicht as $r): ?>
                <tr>
                    <td><strong><?= sanitize($r['naam']) ?></strong></td>
                    <td class="text-right mono"><?= number_format($r['aantal']) ?></td>
                    <td class="text-right mono"><?= number_format($r['totaal_min']) ?></td>
                    <td class="text-right mono">
                        <strong style="color:var(--accent)">
                            € <?= number_format($r['totaal_kosten'], 2, ',', '.') ?>
                        </strong>
                        <?php if ($r['zonder_tarief'] > 0): ?>
                        <br><span style="color:var(--warning, #C9A227);font-size:11px" title="Gesprekken zonder tarief in bel_tarieven, als € 0,00 meegeteld">
                            ⚠ <?= $r['zonder_tarief'] ?> zonder tarief
                        </span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <a href="?page=gesprekspecificaties&action=detail&id=<?= $r['id'] ?>&maand=<?= $r['maand'] ?>"
                           class="btn btn-ghost btn-sm">
                            ☰ Detail
                        </a>
                        <a href="?page=gesprekspecificaties&action=pdf&id=<?= $r['id'] ?>&maand=<?= $r['maand'] ?>"
                           class="btn btn-accent btn-sm" target="_blank">
                            ↓ PDF
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:var(--bg-secondary);font-weight:600">
                    <td>Totaal</td>
                    <td class="text-right mono"><?= number_format(array_sum(array_column($overzicht, 'aantal'))) ?></td>
                    <td class="text-right mono"><?= number_format(array_sum(array_column($overzicht, 'totaal_min'))) ?></td>
                    <td class="text-right mono" style="color:var(--accent)">
                        € <?= number_format(array_sum(array_column($overzicht, 'totaal_kosten')), 2, ',', '.') ?>
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php endif; ?>
