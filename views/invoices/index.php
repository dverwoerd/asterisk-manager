<!-- views/invoices/index.php -->
<div class="page-header">
    <h1 class="page-title"><?= t('invoices') ?></h1>
    <div class="page-actions">
        <a href="?page=invoices&action=generate" class="btn btn-accent">+ <?= t('generate_invoice') ?></a>
    </div>
</div>

<!-- Status Filter -->
<div class="tab-bar mb-4">
    <?php foreach ([''=> 'All', 'draft'=>t('draft'), 'sent'=>t('sent'), 'paid'=>t('paid'), 'overdue'=>t('overdue')] as $s => $label): ?>
    <a href="?page=invoices&status=<?= $s ?>" class="tab-item <?= $status===$s ? 'active' : '' ?>"><?= $label ?></a>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <?php if (empty($invoices)): ?>
        <div class="empty-state">
            <div class="empty-icon">▣</div>
            <p><?= t('no_records') ?></p>
            <a href="?page=invoices&action=generate" class="btn btn-accent"><?= t('generate_invoice') ?></a>
        </div>
        <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th><?= t('invoice_number') ?></th>
                    <th><?= t('customer') ?></th>
                    <th>Period</th>
                    <th><?= t('issue_date') ?></th>
                    <th><?= t('due_date') ?></th>
                    <th><?= t('total') ?></th>
                    <th><?= t('invoice_status') ?></th>
                    <th><?= t('actions') ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($invoices as $inv): ?>
            <tr>
                <td class="mono font-bold">
                    <a href="?page=invoices&action=detail&id=<?= $inv['id'] ?>"><?= sanitize($inv['invoice_number']) ?></a>
                </td>
                <td><?= sanitize($inv['company_name'] ?: $inv['contact_name']) ?></td>
                <td class="mono text-sm"><?= formatDate($inv['period_start']) ?> — <?= formatDate($inv['period_end']) ?></td>
                <td class="mono"><?= formatDate($inv['issue_date']) ?></td>
                <td class="mono <?= $inv['status']==='overdue'?'text-danger':'' ?>"><?= formatDate($inv['due_date']) ?></td>
                <td class="mono font-bold">
                    <?= $inv['currency'] ?> <?= number_format($inv['total'],2,',','.') ?>
                </td>
                <td>
                    <?php $badgeMap = ['draft'=>'default','sent'=>'info','paid'=>'success','overdue'=>'danger','cancelled'=>'warning']; ?>
                    <span class="badge badge-<?= $badgeMap[$inv['status']] ?? 'default' ?>"><?= t($inv['status']) ?></span>
                </td>
                <td class="action-cell">
                    <a href="?page=invoices&action=detail&id=<?= $inv['id'] ?>" class="btn btn-sm btn-ghost">View</a>
                    <a href="?page=invoices&action=print&id=<?= $inv['id'] ?>" class="btn btn-sm btn-ghost" target="_blank">Print</a>
                    <a href="?page=invoices&action=pdf&id=<?= $inv['id'] ?>" class="btn btn-sm btn-ghost">⬇ PDF</a>
                    <?php if ($inv['status'] !== 'paid'): ?>
                    <form method="POST" action="?page=invoices&action=update_status&id=<?= $inv['id'] ?>" style="display:inline">
                        <?= csrf() ?>
                        <input type="hidden" name="status" value="paid">
                        <button type="submit" class="btn btn-sm btn-success-ghost">✓ Paid</button>
                    </form>
                    <?php endif; ?>
                    <a href="?page=invoices&action=delete&id=<?= $inv['id'] ?>"
                       class="btn btn-sm btn-danger-ghost"
                       onclick="return confirm('Factuur <?= sanitize($inv['invoice_number']) ?> verwijderen? De CDR records worden ook vrijgegeven.')">✕</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
