<div class="page-header">
    <h1 class="page-title">✉ SMTP / Email Instellingen</h1>
</div>

<form method="POST" action="?page=settings&action=post_smtp_save">
    <?= csrf() ?>
    <div class="card mb-4" style="max-width:700px">
        <div class="card-header"><h3 class="card-title">SMTP Server</h3></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group">
                    <label>SMTP Host</label>
                    <input type="text" name="smtp_host" class="form-control mono"
                           value="<?= sanitize($smtp['smtp_host']) ?>"
                           placeholder="smtp.gmail.com of mail.bedrijf.nl">
                </div>
                <div class="form-group">
                    <label>Poort</label>
                    <input type="number" name="smtp_port" class="form-control mono"
                           value="<?= sanitize($smtp['smtp_port']) ?>"
                           placeholder="587">
                </div>
                <div class="form-group">
                    <label>Beveiliging</label>
                    <select name="smtp_secure" class="form-control">
                        <option value="tls" <?= $smtp['smtp_secure']==='tls' ? 'selected' : '' ?>>STARTTLS (poort 587)</option>
                        <option value="ssl" <?= $smtp['smtp_secure']==='ssl' ? 'selected' : '' ?>>SSL/TLS (poort 465)</option>
                        <option value="none" <?= $smtp['smtp_secure']==='none' ? 'selected' : '' ?>>Geen (poort 25)</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Gebruikersnaam</label>
                    <input type="text" name="smtp_user" class="form-control mono"
                           value="<?= sanitize($smtp['smtp_user']) ?>"
                           placeholder="gebruiker@bedrijf.nl">
                </div>
                <div class="form-group">
                    <label>Wachtwoord</label>
                    <input type="password" name="smtp_pass" class="form-control mono"
                           value="<?= sanitize($smtp['smtp_pass']) ?>"
                           placeholder="••••••••">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Van e-mailadres</label>
                    <input type="email" name="smtp_from" class="form-control mono"
                           value="<?= sanitize($smtp['smtp_from']) ?>"
                           placeholder="voicemail@bedrijf.nl">
                </div>
                <div class="form-group">
                    <label>Van naam</label>
                    <input type="text" name="smtp_from_name" class="form-control"
                           value="<?= sanitize($smtp['smtp_from_name']) ?>"
                           placeholder="PBX Voicemail">
                </div>
            </div>
        </div>
    </div>
    <div class="form-actions">
        <a href="?page=settings" class="btn btn-ghost">← Instellingen</a>
        <button type="submit" class="btn btn-accent">💾 Opslaan</button>
    </div>
</form>

<!-- Test mail -->
<div class="card mt-4" style="max-width:700px">
    <div class="card-header"><h3 class="card-title">Test e-mail versturen</h3></div>
    <div class="card-body">
        <form method="POST" action="?page=settings&action=test_smtp">
            <?= csrf() ?>
            <div class="form-row">
                <div class="form-group">
                    <label>Stuur testmail naar</label>
                    <input type="email" name="test_email" class="form-control mono"
                           placeholder="jouw@email.nl" required>
                </div>
                <div class="form-group" style="display:flex;align-items:flex-end">
                    <button type="submit" class="btn btn-ghost">✉ Stuur testmail</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Installatie instructies -->
<div class="card mt-4" style="max-width:700px;border-color:var(--info)">
    <div class="card-header"><h3 class="card-title">Server configuratie</h3></div>
    <div class="card-body">
        <p class="text-sm text-muted" style="margin-bottom:8px">Installeer msmtp op de server voor het versturen van voicemail berichten:</p>
        <pre class="mono" style="background:var(--bg-secondary);padding:12px;border-radius:6px;font-size:12px">sudo apt install msmtp msmtp-mta -y
sudo chmod 644 /etc/msmtprc</pre>
        <p class="text-sm text-muted" style="margin-top:8px">Na opslaan wordt <code>/etc/msmtprc</code> automatisch bijgewerkt en gebruikt Asterisk msmtp voor voicemail emails.</p>
    </div>
</div>
