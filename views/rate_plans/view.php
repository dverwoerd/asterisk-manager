<!-- views/rate_plans/view.php -->
<div class="page-header">
    <h1 class="page-title"><?= $title ?></h1>
    <div class="page-actions">
        <a href="?page=rate_plans" class="btn btn-ghost">← Back</a>
        <a href="?page=rate_plans&action=add_rate&plan_id=<?= $plan['id'] ?>" class="btn btn-ghost">+ Add Rate</a>
        <!-- CSV Import -->
        <button class="btn btn-ghost" onclick="document.getElementById('csvImport').style.display='block'">↑ Import CSV</button>
        <a href="?page=rate_plans&action=edit&id=<?= $plan['id'] ?>" class="btn btn-accent"><?= t('edit') ?> Plan</a>
    </div>
</div>

<div id="csvImport" style="display:none" class="card mb-4">
    <div class="card-header"><h3 class="card-title">Import Rates from CSV</h3></div>
    <div class="card-body">
        <form method="POST" action="?page=rate_plans&action=import_rates" enctype="multipart/form-data">
            <?= csrf() ?>
            <input type="hidden" name="plan_id" value="<?= $plan['id'] ?>">
            <p class="text-sm text-muted mb-4">CSV format: <span class="mono">destination_name, prefix, rate_per_minute, connection_fee, billing_increment</span></p>
            <div class="form-row">
                <div class="form-group"><input type="file" name="csv_file" class="form-control" accept=".csv" required></div>
                <div class="form-group form-group-btn"><button type="submit" class="btn btn-accent">Import</button><button type="button" class="btn btn-ghost" onclick="this.closest('#csvImport').style.display='none'">Cancel</button></div>
            </div>
        </form>
    </div>
</div>

<!-- Plan summary -->
<div class="stats-grid stats-grid-4 mb-4">
    <div class="stat-card"><div class="stat-body"><div class="stat-value mono"><?= $plan['currency'] ?></div><div class="stat-label">Currency</div></div></div>
    <div class="stat-card"><div class="stat-body"><div class="stat-value mono"><?= $plan['billing_increment'] ?>s</div><div class="stat-label">Billing Increment</div></div></div>
    <div class="stat-card"><div class="stat-body"><div class="stat-value mono">€<?= number_format($plan['connection_fee'],4,',','.') ?></div><div class="stat-label">Default Connection Fee</div></div></div>
    <div class="stat-card"><div class="stat-body"><div class="stat-value"><?= count($rates) ?></div><div class="stat-label">Rate Entries</div></div></div>
</div>

<div class="card">
    <div class="card-header">
        <form method="GET" class="search-form">
            <input type="hidden" name="page" value="rate_plans">
            <input type="hidden" name="action" value="view_plan">
            <input type="hidden" name="id" value="<?= $plan['id'] ?>">
            <input type="text" name="search" value="<?= sanitize($search) ?>" class="form-control form-control-sm search-input" placeholder="Search destination or prefix...">
            <button type="submit" class="btn btn-sm btn-ghost">⌕</button>
            <?php if ($search): ?><a href="?page=rate_plans&action=view_plan&id=<?= $plan['id'] ?>" class="btn btn-sm btn-ghost">✕</a><?php endif; ?>
        </form>
        <span class="record-count"><?= count($rates) ?> rates</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($rates)): ?>
        <div class="empty-state"><p>No rates defined. <a href="?page=rate_plans&action=add_rate&plan_id=<?= $plan['id'] ?>">Add the first rate.</a></p></div>
        <?php else: ?>
        <table class="data-table data-table-sm">
            <thead><tr><th>Destination</th><th>Prefix</th><th>Rate/min</th><th>Conn. Fee</th><th>Increment</th><th>Valid Hours</th><th><?= t('actions') ?></th></tr></thead>
            <tbody>
            <?php foreach ($rates as $r): ?>
            <tr>
                <td class="font-bold"><?= sanitize($r['destination_name']) ?></td>
                <td class="mono"><?= sanitize($r['prefix']) ?: '<span class="text-muted">(default)</span>' ?></td>
                <td class="mono font-bold" style="color:var(--accent)">€ <?= number_format($r['rate_per_minute'],4,',','.') ?></td>
                <td class="mono">€ <?= number_format($r['connection_fee'],4,',','.') ?></td>
                <td class="mono"><?= $r['billing_increment'] ?>s</td>
                <td class="mono text-sm"><?= substr($r['time_start'],0,5) ?>–<?= substr($r['time_end'],0,5) ?></td>
                <td class="action-cell">
                    <a href="?page=rate_plans&action=edit_rate&id=<?= $r['id'] ?>" class="btn btn-sm btn-ghost"><?= t('edit') ?></a>
                    <a href="?page=rate_plans&action=delete_rate&id=<?= $r['id'] ?>" class="btn btn-sm btn-danger-ghost" onclick="return confirm('Delete this rate?')">✕</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
