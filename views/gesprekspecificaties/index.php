<div class="page-header">
    <h1 class="page-title">Gespreksspecificaties</h1>
    <div class="page-actions">
        <?php if (!empty($boekhouding) && !empty($overzicht)): ?>
        <form method="POST" action="?page=gesprekspecificaties&action=boekhouding_alle" style="display:inline"
              onsubmit="return confirm('Voor alle klanten van deze maand een factuur in de boekhouding laten maken?\nConceptfacturen van eerdere verzendingen worden vervangen; definitieve facturen blijven staan.')">
            <?= csrf() ?>
            <input type="hidden" name="maand" value="<?= sanitize($maand) ?>">
            <button type="submit" class="btn btn-accent">⇪ Alle klanten naar boekhouding</button>
        </form>
        <?php elseif (empty($boekhouding)): ?>
        <a href="?page=settings" class="btn btn-ghost btn-sm" title="Koppeling met de boekhouding instellen (Settings, tab Boekhouding)">⚙ Boekhouding koppelen</a>
        <?php endif; ?>
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
                    <th class="text-right">Totale duur</th>
                    <th class="text-right">Kosten excl. BTW</th>
                    <?php if (!empty($boekhouding)): ?><th>Boekhouding</th><?php endif; ?>
                    <th class="text-center" style="width:<?= !empty($boekhouding) ? 300 : 180 ?>px">Acties</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($overzicht as $r): ?>
                <tr>
                    <td><strong><?= sanitize($r['naam']) ?></strong>
                        <?php if (!empty($r['toestellen'])): ?><br><span class="text-muted" style="font-size:12px"><?= (int)$r['toestellen'] ?> toestel<?= $r['toestellen'] == 1 ? '' : 'len' ?></span><?php endif; ?></td>
                    <td class="text-right mono"><?= number_format($r['aantal']) ?></td>
                    <td class="text-right mono"><?= formatDuration($r['totaal_sec']) ?></td>
                    <td class="text-right mono">
                        <strong style="color:var(--accent)">
                            € <?= number_format($r['totaal_kosten'], 2, ',', '.') ?>
                        </strong>
                        <?php if ($r['zonder_tarief'] > 0): ?>
                        <br><span style="color:var(--warning);font-size:11px" title="Gesprekken zonder tarief in bel_tarieven, als € 0,00 meegeteld">
                            ⚠ <?= $r['zonder_tarief'] ?> zonder tarief
                        </span>
                        <?php endif; ?>
                    </td>
                    <?php $bs = !empty($boekhouding) ? ($boekhouding_status[$r['id']] ?? null) : null; ?>
                    <?php if (!empty($boekhouding)): ?>
                    <td style="font-size:12px">
                        <?php if (!$bs): ?><span class="text-muted">nog niet verstuurd</span>
                        <?php elseif ($bs['gelukt']): ?>
                            <span style="color:var(--success)">✓ <?= $bs['factuurnummer'] ? 'Factuur ' . sanitize($bs['factuurnummer']) : 'Concept' ?></span>
                            <br><span class="text-muted"><?= date('d-m-Y H:i', strtotime($bs['verstuurd_op'])) ?></span>
                        <?php else: ?>
                            <span style="color:var(--warning)" title="<?= sanitize($bs['bericht']) ?>">⚠ <?= sanitize(mb_strimwidth($bs['bericht'], 0, 60, '…')) ?></span>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                    <td class="text-center">
                        <?php if (!empty($boekhouding)): $def = $bs && $bs['status'] !== '' && $bs['status'] !== 'concept'; ?>
                        <form method="POST" action="?page=gesprekspecificaties&action=boekhouding" style="display:inline"
                              <?php if ($def): ?>onsubmit="return confirm('Er is al een definitieve factuur (<?= sanitize((string)$bs['factuurnummer']) ?>). Opnieuw versturen en die factuur vervangen?')"<?php endif; ?>>
                            <?= csrf() ?>
                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                            <input type="hidden" name="maand" value="<?= sanitize($r['maand']) ?>">
                            <?php if ($def): ?><input type="hidden" name="overschrijven" value="1"><?php endif; ?>
                            <button type="submit" class="btn btn-ghost btn-sm" title="Factuur laten maken in de boekhouding (met de specificatie als bijlage)">⇪ <?= $bs && $bs['gelukt'] ? 'Opnieuw' : 'Boekhouding' ?></button>
                        </form>
                        <?php endif; ?>
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
                    <td class="text-right mono"><?= formatDuration(array_sum(array_column($overzicht, 'totaal_sec'))) ?></td>
                    <td class="text-right mono" style="color:var(--accent)">
                        € <?= number_format(array_sum(array_column($overzicht, 'totaal_kosten')), 2, ',', '.') ?>
                    </td>
                    <?php if (!empty($boekhouding)): ?><td></td><?php endif; ?>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php endif; ?>
