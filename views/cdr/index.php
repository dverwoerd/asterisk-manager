<div class="page-header">
    <h1 class="page-title"><?= t('cdr') ?></h1>
    <div class="page-actions">
        <a href="?page=cdr&action=sync" class="btn btn-ghost">⟳ <?= t('sync_cdr') ?></a>
        <a href="?page=cdr&action=calculate_costs" class="btn btn-ghost">€ Calculate Costs</a>
        <a href="?page=cdr&action=export&<?= http_build_query($filters) ?>" class="btn btn-ghost">↓ <?= t('export') ?> CSV</a>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="filter-form">
            <input type="hidden" name="page" value="cdr">
            <div class="filter-row">
                <div class="form-group">
                    <label><?= t('period_start') ?></label>
                    <input type="date" name="date_from" value="<?= $filters['date_from'] ?>" class="form-control form-control-sm">
                </div>
                <div class="form-group">
                    <label><?= t('period_end') ?></label>
                    <input type="date" name="date_to" value="<?= $filters['date_to'] ?>" class="form-control form-control-sm">
                </div>
                <div class="form-group">
                    <label><?= t('src') ?></label>
                    <input type="text" name="src" value="<?= sanitize($filters['src']) ?>" class="form-control form-control-sm mono" placeholder="Source...">
                </div>
                <div class="form-group">
                    <label><?= t('dst') ?></label>
                    <input type="text" name="dst" value="<?= sanitize($filters['dst']) ?>" class="form-control form-control-sm mono" placeholder="Destination...">
                </div>
                <div class="form-group">
                    <label><?= t('disposition') ?></label>
                    <select name="disposition" class="form-control form-control-sm">
                        <option value="">All</option>
                        <?php foreach (['ANSWERED','NO ANSWER','BUSY','FAILED'] as $d): ?>
                        <option value="<?= $d ?>" <?= $filters['disposition']===$d?'selected':'' ?>><?= $d ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group form-group-btn">
                    <button type="submit" class="btn btn-accent btn-sm"><?= t('filter') ?></button>
                    <a href="?page=cdr" class="btn btn-ghost btn-sm">Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Summary -->
<?php if ($summary): ?>
<div class="stats-grid stats-grid-4 mb-4">
    <div class="stat-card">
        <div class="stat-body">
            <div class="stat-value"><?= number_format((int)($summary['total_calls'] ?? 0)) ?></div>
            <div class="stat-label">Total Calls</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-body">
            <div class="stat-value"><?= formatDuration((int)$summary['total_seconds']) ?></div>
            <div class="stat-label">Total Duration</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-body">
            <div class="stat-value"><?= number_format((int)($summary['answered'] ?? 0)) ?></div>
            <div class="stat-label">Answered</div>
        </div>
    </div>
    <div class="stat-card stat-accent">
        <div class="stat-body">
            <div class="stat-value mono">€ <?= number_format($summary['total_cost'] ?? 0, 2, ',', '.') ?></div>
            <div class="stat-label">Total Cost</div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Table -->
<div class="card">
    <div class="card-header">
        <span class="record-count"><?= number_format((int)($total ?? 0)) ?> records (page <?= $page ?>/<?= $pages ?>)</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($records)): ?>
        <div class="empty-state"><?= t('no_records') ?></div>
        <?php else: ?>
        <table class="data-table data-table-sm">
            <thead>
                <tr>
                    <th><?= t('calldate') ?></th>
                    <th><?= t('src') ?></th>
                    <th><?= t('dst') ?></th>
                    <th>Destination</th>
                    <th><?= t('duration') ?></th>
                    <th>Bill.</th>
                    <th><?= t('disposition') ?></th>
                    <th><?= t('cost') ?></th>
                    <th>Inv.</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($records as $r): ?>
            <tr>
                <td class="mono text-sm"><?= substr($r['calldate'],0,16) ?></td>
                <td class="mono"><?= sanitize($r['src']) ?></td>
                <td class="mono"><?= sanitize($r['dst']) ?></td>
                <td class="text-sm text-muted"><?= sanitize($r['destination_name'] ?? '') ?></td>
                <td class="mono"><?= formatDuration($r['duration']) ?></td>
                <td class="mono"><?= $r['billsec'] ?>s</td>
                <td>
                    <span class="badge badge-<?= match($r['disposition']) {
                        'ANSWERED'=>'success','BUSY'=>'warning',default=>'danger'
                    } ?>"><?= $r['disposition'] ?></span>
                </td>
                <td class="mono">
                    <?= $r['cost'] !== null ? '€ '.number_format((float)($r['cost']), 4,',','.') : '<span class="text-muted">—</span>' ?>
                </td>
                <td>
                    <?php if ($r['invoiced']): ?>
                    <a href="?page=invoices&action=view&id=<?= $r['invoice_id'] ?>" class="badge badge-success">✓</a>
                    <?php else: ?>
                    <span class="badge badge-default">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php if ($pages > 1): ?>
    <div class="card-footer">
        <div class="pagination">
            <?php for ($i = max(1,$page-3); $i <= min($pages,$page+3); $i++): ?>
            <a href="?page=cdr&<?= http_build_query(array_merge($filters, ['p'=>$i])) ?>"
               class="page-btn <?= $i===$page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
