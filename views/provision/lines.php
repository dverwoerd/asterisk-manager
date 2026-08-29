<div class="page-header">
    <h1 class="page-title">Extra lijnen — <?= sanitize($phone['mac_address']) ?></h1>
    <a href="?page=provision" class="btn btn-ghost">← Terug naar Provisioning</a>
</div>

<div class="card mb-4" style="border-color:var(--info)">
    <div class="card-body" style="padding:14px 18px">
        <p style="margin:0;font-size:13px;color:var(--text-muted)">
            Extra lijnen zijn losse SIP-accounts op hetzelfde fysieke toestel. Elke lijn heeft een
            <strong>vast label</strong> dat altijd getoond wordt op het scherm bij een inkomend gesprek —
            onafhankelijk van het telefoonboek. Koppel een DID (Inbound Route) aan een lijn via
            <strong>destination type "phone_line"</strong>.
        </p>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><h3 class="card-title">Nieuwe lijn toevoegen</h3></div>
    <div class="card-body">
        <form method="POST" action="?page=provision&action=post_add_line">
            <?= csrf() ?>
            <input type="hidden" name="phone_id" value="<?= $phone['id'] ?>">
            <div class="form-row">
                <div class="form-group">
                    <label>Label (getoond op scherm)</label>
                    <input type="text" name="label" class="form-control" placeholder="bijv. PC-Shop" required>
                </div>
                <div class="form-group" style="display:flex;align-items:flex-end">
                    <button type="submit" class="btn btn-accent">+ Lijn toevoegen</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title">Bestaande lijnen</h3></div>
    <div class="card-body p-0">
        <?php if (empty($lines)): ?>
        <div class="empty-state">Nog geen extra lijnen toegevoegd.</div>
        <?php else: ?>
        <table class="data-table">
            <thead><tr><th>Lijn</th><th>Label</th><th>Username</th><th>Wachtwoord</th><th>Actie</th></tr></thead>
            <tbody>
            <?php foreach ($lines as $l): ?>
            <tr>
                <td>Lijn <?= (int)$l['line_number'] ?></td>
                <td class="font-bold"><?= sanitize($l['label']) ?></td>
                <td class="mono"><?= sanitize($l['username']) ?></td>
                <td class="mono text-sm text-muted"><?= sanitize($l['secret']) ?></td>
                <td class="action-cell">
                    <a href="?page=provision&action=delete_line&id=<?= $l['id'] ?>"
                       class="btn btn-sm btn-danger-ghost"
                       onclick="return confirm('Lijn \'<?= sanitize($l['label']) ?>\' verwijderen?')">✕ Verwijder</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<div class="card mt-4" style="border-color:var(--warning)">
    <div class="card-body" style="padding:14px 18px">
        <p style="margin:0;font-size:13px;color:var(--text-muted)">
            ⚠ Na het toevoegen of verwijderen van een lijn: doe een <strong>Full Reload</strong> via Dialplan
            en herstart het toestel (Provisioning → Reboot), zodat de nieuwe account.N instellingen geladen worden.
        </p>
    </div>
</div>
