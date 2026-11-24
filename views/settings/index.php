<div class="page-header">
    <h1 class="page-title"><?= t('settings') ?></h1>
</div>

<div class="tabs-container">
    <div class="tab-bar">
        <button class="tab-item active" onclick="showTab('company')">Company</button>
        <button class="tab-item" onclick="showTab('asterisk')">Asterisk</button>
        <button class="tab-item" onclick="showTab('users')">Users</button>
        <button class="tab-item" onclick="showTab('provisioning')">Provisioning</button>
        <button class="tab-item" onclick="showTab('system')">System</button>
        <button class="tab-item" onclick="showTab('smtp')">✉ SMTP</button>
        <button class="tab-item" onclick="showTab('boekhouding')">📒 Boekhouding</button>
        <button class="tab-item" onclick="showTab('links')">🔗 Meer</button>
    </div>

    <form method="POST" action="?page=settings&action=post_save">
        <?= csrf() ?>

        <!-- Company Tab -->
        <div class="tab-pane active" id="tab-company">
            <div class="card">
                <div class="card-header"><h3 class="card-title"><?= t('company_settings') ?></h3></div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Company Name</label>
                            <input type="text" name="company_name" class="form-control" value="<?= sanitize($settings['company_name'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>VAT Number</label>
                            <input type="text" name="company_vat" class="form-control mono" value="<?= sanitize($settings['company_vat'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="company_address" class="form-control" rows="3"><?= sanitize($settings['company_address'] ?? '') ?></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="company_email" class="form-control" value="<?= sanitize($settings['company_email'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="company_phone" class="form-control mono" value="<?= sanitize($settings['company_phone'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Asterisk Tab -->
        <div class="tab-pane" id="tab-asterisk" style="display:none">
            <div class="card">
                <div class="card-header"><h3 class="card-title"><?= t('asterisk_settings') ?></h3></div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label><?= t('asterisk_host') ?></label>
                            <input type="text" name="asterisk_host" id="ami_host" class="form-control mono" value="<?= sanitize($settings['asterisk_host'] ?? '127.0.0.1') ?>">
                        </div>
                        <div class="form-group">
                            <label>AMI Port</label>
                            <input type="number" name="asterisk_ami_port" id="ami_port" class="form-control mono" value="<?= (int)($settings['asterisk_ami_port'] ?? 5038) ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>AMI Username</label>
                            <input type="text" name="asterisk_ami_user" id="ami_user" class="form-control mono" value="<?= sanitize($settings['asterisk_ami_user'] ?? 'manager') ?>">
                        </div>
                        <div class="form-group">
                            <label>AMI Secret</label>
                            <input type="password" name="asterisk_ami_secret" id="ami_secret" class="form-control mono" placeholder="Leave blank to keep">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Config Files Path</label>
                        <input type="text" name="asterisk_config_path" class="form-control mono" value="<?= sanitize($settings['asterisk_config_path'] ?? '/etc/asterisk') ?>">
                        <small class="form-hint">Apache user (www-data) must have write access to this path.</small>
                    </div>
                    <div class="form-group">
                        <label>External Hostname / IP</label>
                        <input type="text" name="asterisk_external_host" class="form-control mono" value="<?= sanitize($settings['asterisk_external_host'] ?? '') ?>" placeholder="e.g. pbx.clearvoip.nl">
                        <small class="form-hint">Public hostname or IP used in SIP registration. Required if behind NAT.</small>
                    </div>
                    <div class="form-group">
                        <label>Local Networks (NAT)</label>
                        <input type="text" name="asterisk_local_nets" class="form-control mono" value="<?= sanitize($settings['asterisk_local_nets'] ?? '192.168.0.0/16,172.16.0.0/12,10.0.0.0/8') ?>" placeholder="192.168.0.0/16,172.16.0.0/12,10.0.0.0/8">
                        <small class="form-hint">Comma-separated local network ranges for NAT detection.</small>
                    </div>
                    <div class="form-group">
                        <button type="button" class="btn btn-ghost" onclick="testAMI()">⚡ <?= t('test_connection') ?></button>
                        <span id="ami-test-result" class="ml-3"></span>
                    </div>
                    <hr>
                    <div class="form-row">
                        <div class="form-group">
                            <label>CDR Sync Enabled</label>
                            <select name="cdr_sync_enabled" class="form-control">
                                <option value="1" <?= ($settings['cdr_sync_enabled']??1) ? 'selected':'' ?>>Yes</option>
                                <option value="0" <?= !($settings['cdr_sync_enabled']??1) ? 'selected':'' ?>>No</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>CDR Sync Interval (seconds)</label>
                            <input type="number" name="cdr_sync_interval" class="form-control" value="<?= (int)($settings['cdr_sync_interval']??300) ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Provisioning Tab -->
        <div class="tab-pane" id="tab-provisioning" style="display:none">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Yealink Provisioning</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Provisioning Base URL</label>
                        <input type="text" name="provision_base_url" class="form-control mono"
                               value="<?= sanitize($settings['provision_base_url'] ?? '') ?>"
                               placeholder="http://pbx.clearvoip.nl/provision">
                        <small class="form-hint">
                            Volledige URL naar de provisioning map. Het toestel haalt {mac}.cfg op van dit adres.<br>
                            Stel in het Yealink toestel in: <strong>Settings → Auto Provision → Server URL</strong>
                        </small>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Standaard Admin Wachtwoord</label>
                            <input type="text" name="provision_admin_pass" class="form-control mono"
                                   value="<?= sanitize($settings['provision_admin_pass'] ?? 'admin') ?>">
                        </div>
                        <div class="form-group">
                            <label>NTP Tijdserver</label>
                            <input type="text" name="provision_ntp_server" class="form-control mono"
                                   value="<?= sanitize($settings['provision_ntp_server'] ?? 'pool.ntp.org') ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Standaard Tijdzone</label>
                            <select name="provision_timezone" class="form-control">
                                <?php foreach (['Europe/Amsterdam','Europe/London','Europe/Berlin','Europe/Paris','UTC'] as $tz): ?>
                                <option value="<?= $tz ?>" <?= ($settings['provision_timezone']??'Europe/Amsterdam')===$tz?'selected':'' ?>><?= $tz ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Config Bestanden Pad</label>
                            <input type="text" name="provision_tftp_path" class="form-control mono"
                                   value="<?= sanitize($settings['provision_tftp_path'] ?? '/var/www/asterisk-manager/provision') ?>">
                            <small class="form-hint">Map moet leesbaar zijn via HTTP (zie Apache config).</small>
                        </div>
                    </div>
                    <div class="card" style="border-color:var(--info);margin-top:12px">
                        <div class="card-body" style="padding:12px 16px;font-size:12px">
                            <strong style="color:var(--info)">Apache configuratie voor provisioning:</strong><br>
                            <code class="mono" style="color:var(--accent)">
                                Alias /provision /var/www/asterisk-manager/provision<br>
                                &lt;Directory /var/www/asterisk-manager/provision&gt;<br>
                                &nbsp;&nbsp;&nbsp;&nbsp;Options -Indexes<br>
                                &nbsp;&nbsp;&nbsp;&nbsp;Require all granted<br>
                                &lt;/Directory&gt;
                            </code>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Tab -->
        <div class="tab-pane" id="tab-system" style="display:none">
            <div class="card">
                <div class="card-header"><h3 class="card-title">System Settings</h3></div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Default Language</label>
                            <select name="default_language" class="form-control">
                                <?php foreach ($languages as $code => $name): ?>
                                <option value="<?= $code ?>" <?= ($settings['default_language']??'en')===$code?'selected':'' ?>><?= $name ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Timezone</label>
                            <select name="timezone" class="form-control">
                                <?php foreach (['Europe/Amsterdam','Europe/London','UTC','America/New_York','America/Los_Angeles'] as $tz): ?>
                                <option value="<?= $tz ?>" <?= ($settings['timezone']??'Europe/Amsterdam')===$tz?'selected':'' ?>><?= $tz ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Date Format</label>
                        <select name="date_format" class="form-control">
                            <?php foreach (['d-m-Y'=>'DD-MM-YYYY','Y-m-d'=>'YYYY-MM-DD','m/d/Y'=>'MM/DD/YYYY','d/m/Y'=>'DD/MM/YYYY'] as $fmt => $label): ?>
                            <option value="<?= $fmt ?>" <?= ($settings['date_format']??'d-m-Y')===$fmt?'selected':'' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-accent">💾 <?= t('save') ?> <?= t('settings') ?></button>
        </div>
    </form>

    <!-- Users Tab (separate form) -->
    <div class="tab-pane" id="tab-users" style="display:none">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Users</h3>
            </div>
            <div class="card-body p-0">
                <table class="data-table">
                    <thead><tr><th>Username</th><th>Full Name</th><th>Role</th><th>Last Login</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($users as $u): if (!$u['active']) continue; ?>
                    <tr>
                        <td class="mono"><?= sanitize($u['username']) ?></td>
                        <td><?= sanitize($u['full_name']) ?></td>
                        <td><span class="badge badge-<?= $u['role']==='admin'?'danger':'info' ?>"><?= $u['role'] ?></span></td>
                        <td class="mono text-sm"><?= $u['last_login'] ?? '—' ?></td>
                        <td class="action-cell">
                            <?php if ($u['id'] != ($_SESSION['user_id']??0)): ?>
                            <a href="?page=settings&action=delete_user&id=<?= $u['id'] ?>"
                               class="btn btn-sm btn-danger-ghost"
                               onclick="return confirm('Delete user?')">Delete</a>
                            <?php else: ?>
                            <span class="text-muted">(current)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header"><h3 class="card-title">Add User</h3></div>
            <div class="card-body">
                <form method="POST" action="?page=settings&action=add_user">
                    <?= csrf() ?>
                    <div class="form-row">
                        <div class="form-group"><label>Username</label><input type="text" name="username" class="form-control mono" required></div>
                        <div class="form-group"><label>Full Name</label><input type="text" name="full_name" class="form-control" required></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control"></div>
                        <div class="form-group"><label>Password</label><input type="password" name="password" class="form-control" minlength="8" required></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Role</label>
                            <select name="role" class="form-control">
                                <option value="viewer">Viewer</option>
                                <option value="operator" selected>Operator</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Language</label>
                            <select name="language" class="form-control">
                                <?php foreach ($languages as $code => $name): ?>
                                <option value="<?= $code ?>"><?= $name ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-accent">Add User</button>
                </form>
            </div>
        </div>
    </div>

    <!-- SMTP Tab -->
    <div class="tab-pane" id="tab-smtp" style="display:none">
        <div class="card mt-4" style="max-width:700px">
            <div class="card-header"><h3 class="card-title">✉ SMTP / Email</h3></div>
            <div class="card-body">
                <form method="POST" action="?page=settings&action=post_smtp_save">
                    <?= csrf() ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label>SMTP Host</label>
                            <input type="text" name="smtp_host" class="form-control mono"
                                   value="<?= sanitize($smtp['smtp_host'] ?? '') ?>"
                                   placeholder="172.25.5.12 of smtp.gmail.com">
                        </div>
                        <div class="form-group">
                            <label>Poort</label>
                            <input type="number" name="smtp_port" class="form-control mono"
                                   value="<?= sanitize($smtp['smtp_port'] ?? '25') ?>">
                        </div>
                        <div class="form-group">
                            <label>Beveiliging</label>
                            <select name="smtp_secure" class="form-control">
                                <option value="none" <?= ($smtp['smtp_secure']??'none')==='none'?'selected':'' ?>>Geen (poort 25)</option>
                                <option value="tls"  <?= ($smtp['smtp_secure']??'')==='tls'?'selected':'' ?>>STARTTLS (587)</option>
                                <option value="ssl"  <?= ($smtp['smtp_secure']??'')==='ssl'?'selected':'' ?>>SSL (465)</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Gebruikersnaam <small class="text-muted">(optioneel)</small></label>
                            <input type="text" name="smtp_user" class="form-control mono"
                                   value="<?= sanitize($smtp['smtp_user'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Wachtwoord <small class="text-muted">(optioneel)</small></label>
                            <input type="password" name="smtp_pass" class="form-control mono"
                                   value="<?= sanitize($smtp['smtp_pass'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Van adres</label>
                            <input type="email" name="smtp_from" class="form-control mono"
                                   value="<?= sanitize($smtp['smtp_from'] ?? '') ?>"
                                   placeholder="pbx@bedrijf.nl">
                        </div>
                        <div class="form-group">
                            <label>Van naam</label>
                            <input type="text" name="smtp_from_name" class="form-control"
                                   value="<?= sanitize($smtp['smtp_from_name'] ?? '') ?>"
                                   placeholder="PBX Voicemail">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-accent">💾 Opslaan</button>
                </form>
                <hr style="margin:20px 0;border-color:var(--border)">
                <form method="POST" action="?page=settings&action=test_smtp">
                    <?= csrf() ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Testmail naar</label>
                            <input type="email" name="test_email" class="form-control mono" required>
                        </div>
                        <div class="form-group" style="display:flex;align-items:flex-end">
                            <button type="submit" class="btn btn-ghost">✉ Verzend</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Boekhouding Tab -->
    <?php $bk = fn($k, $d = '') => (string)($settings[$k] ?? $d); ?>
    <div class="tab-pane" id="tab-boekhouding" style="display:none">
        <div class="card mt-4" style="max-width:760px">
            <div class="card-header"><h3 class="card-title">📒 Koppeling met de boekhouding</h3></div>
            <div class="card-body">
                <p class="text-muted text-sm mb-4">
                    Met de knop <strong>⇪ Boekhouding</strong> bij Gespreksspecificaties maakt de boekhouding een factuur voor de klant over die maand,
                    met de specificatie-PDF als bijlage (die gaat mee met de factuurmail). Opnieuw versturen vervangt de conceptfactuur van dezelfde klant en maand.
                    De sleutel maak je in de boekhouding onder het tandwiel &gt; Bedrijfsgegevens &gt; Koppeling (API).
                </p>
                <form method="POST" action="?page=settings&action=boekhouding_save">
                    <?= csrf() ?>
                    <div class="form-group">
                        <label>Adres van de boekhouding (api.php)</label>
                        <input type="url" name="boekhouding_url" class="form-control mono" value="<?= sanitize($bk('boekhouding_url')) ?>"
                               placeholder="https://boekhouding.pantronic.nl/api.php">
                    </div>
                    <div class="form-group">
                        <label>API-sleutel</label>
                        <input type="password" name="boekhouding_api_key" class="form-control mono" autocomplete="new-password"
                               placeholder="<?= $bk('boekhouding_api_key') !== '' ? 'opgeslagen, leeg laten = niet wijzigen' : 'bk_…' ?>">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Factuur in de boekhouding</label>
                            <select name="boekhouding_status" class="form-control">
                                <?php foreach (['boekhouding' => 'Volgens instelling in de boekhouding', 'concept' => 'Als concept (zelf controleren)',
                                                'definitief' => 'Direct definitief', 'definitief_mailen' => 'Direct definitief en mailen naar de klant'] as $w => $l): ?>
                                <option value="<?= $w ?>" <?= $bk('boekhouding_status', 'boekhouding') === $w ? 'selected' : '' ?>><?= $l ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Factuurregels</label>
                            <select name="boekhouding_regels" class="form-control">
                                <option value="totaal" <?= $bk('boekhouding_regels', 'totaal') === 'totaal' ? 'selected' : '' ?>>Eén regel met het totaal (specificatie in de PDF)</option>
                                <option value="categorie" <?= $bk('boekhouding_regels') === 'categorie' ? 'selected' : '' ?>>Een bedragregel per categorie</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Referentie van de factuur</label>
                        <input type="text" name="boekhouding_referentie" class="form-control" value="<?= sanitize($bk('boekhouding_referentie', 'VoIP-abonnement {maand}')) ?>">
                        <small class="text-muted">{maand} = bijv. november 2026, {jaar} = 2026, {klant} = naam van de klant.</small>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Omschrijving regel gesprekskosten ({maand} = bijv. november 2026)</label>
                            <input type="text" name="boekhouding_omschrijving" class="form-control" value="<?= sanitize($bk('boekhouding_omschrijving', 'Gesprekskosten {maand}')) ?>">
                        </div>
                        <div class="form-group">
                            <label>BTW</label>
                            <select name="boekhouding_btw" class="form-control">
                                <?php foreach (['VH' => '21%', 'VL' => '9%', 'V0' => '0% / vrijgesteld', 'VV' => 'Verlegd (NL)', 'VEU' => 'Verlegd (EU)', 'VEX' => 'Export buiten EU'] as $w => $l): ?>
                                <option value="<?= $w ?>" <?= $bk('boekhouding_btw', 'VH') === $w ? 'selected' : '' ?>><?= $l ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Artikel voor de regel met gesprekskosten (optioneel, meestal leeg)</label>
                        <input type="text" name="boekhouding_artikel" class="form-control mono" value="<?= sanitize($bk('boekhouding_artikel')) ?>"
                               placeholder="leeg = BTW hierboven; bijv. TEL-GESPREK: omzetrekening en BTW van dat artikel">
                    </div>
                    <div class="form-group">
                        <label>Vaste artikelen bovenaan elke factuur (optioneel)</label>
                        <input type="text" name="boekhouding_vaste_artikelen" class="form-control mono" value="<?= sanitize($bk('boekhouding_vaste_artikelen')) ?>"
                               placeholder="bijv. DIENST01 (meerdere: komma ertussen)">
                        <small class="text-muted">Volgorde op de factuur: deze artikelen (1 × elk), dan de toestellen, dan de gesprekskosten.</small>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Artikel eerste toestel (optioneel)</label>
                            <input type="text" name="boekhouding_toestel_artikel" class="form-control mono" value="<?= sanitize($bk('boekhouding_toestel_artikel')) ?>"
                                   placeholder="bijv. VOIP-BASIS">
                            <small class="text-muted">Klant met 1 toestel: 1 × dit artikel. Prijs, BTW en omzetrekening komen van het artikel in de boekhouding.</small>
                        </div>
                        <div class="form-group">
                            <label>Artikel extra toestellen (optioneel)</label>
                            <input type="text" name="boekhouding_toestel_extra_artikel" class="form-control mono" value="<?= sanitize($bk('boekhouding_toestel_extra_artikel')) ?>"
                                   placeholder="bijv. VOIP-EXTRA">
                            <small class="text-muted">Klant met meerdere toestellen: 1 × eerste-toestelartikel + dit artikel voor elk volgend toestel. Leeg = eerste-toestelartikel × aantal.</small>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Omschrijving toestelregel (optioneel)</label>
                            <input type="text" name="boekhouding_toestel_omschrijving" class="form-control" value="<?= sanitize($bk('boekhouding_toestel_omschrijving')) ?>"
                                   placeholder="leeg = omschrijving van het artikel">
                            <small class="text-muted">{maand}, {aantal} en {toestellen} worden ingevuld, bijv. "Toestellen {maand} ({toestellen})".</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label style="display:flex;gap:8px;align-items:center"><input type="checkbox" name="boekhouding_pdf" value="1" <?= $bk('boekhouding_pdf', '1') === '1' ? 'checked' : '' ?>> Specificatie-PDF meesturen als bijlage</label>
                        <label style="display:flex;gap:8px;align-items:center"><input type="checkbox" name="boekhouding_nul" value="1" <?= $bk('boekhouding_nul') === '1' ? 'checked' : '' ?>> Ook klanten met € 0,00 aan gesprekskosten versturen</label>
                        <label style="display:flex;gap:8px;align-items:center"><input type="checkbox" name="boekhouding_auto" value="1" <?= $bk('boekhouding_auto') === '1' ? 'checked' : '' ?>> Elke maand automatisch versturen (cron, zie hieronder)</label>
                    </div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap">
                        <button type="submit" class="btn btn-accent">Opslaan</button>
                        <button type="submit" class="btn btn-ghost" formaction="?page=settings&action=boekhouding_test">Opslaan en verbinding testen</button>
                    </div>
                </form>
                <p class="text-muted text-sm mt-4">
                    Automatisch: cronjob op de 1e van de maand, bijvoorbeeld om 06:00, verstuurt de vorige maand:<br>
                    <code>0 6 1 * * php <?= sanitize(APP_ROOT) ?>/cron/boekhouding_push.php</code><br>
                    Handmatig een maand versturen: <code>php <?= sanitize(APP_ROOT) ?>/cron/boekhouding_push.php 2026-10</code>
                </p>
            </div>
        </div>
    </div>

    <!-- Meer Tab -->
    <div class="tab-pane" id="tab-links" style="display:none">
        <div class="card mt-4">
            <div class="card-header"><h3 class="card-title">🔗 Overige</h3></div>
            <div class="card-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;max-width:500px">
                    <a href="?page=sounds"    class="btn btn-ghost">🔊 Sound Files →</a>
                    <a href="?page=phonebook" class="btn btn-ghost">📒 Adresboek →</a>
                    <a href="?page=security"  class="btn btn-ghost">🔒 Login Security →</a>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function showTab(name) {
    document.querySelectorAll('.tab-pane').forEach(p => p.style.display = 'none');
    document.querySelectorAll('.tab-item').forEach(t => t.classList.remove('active'));
    document.getElementById('tab-' + name).style.display = '';
    event.target.classList.add('active');
}

// tab openen via #naam in de URL (bijv. na opslaan: ?page=settings#boekhouding)
document.addEventListener('DOMContentLoaded', function () {
    const h = location.hash.slice(1);
    if (!h || !document.getElementById('tab-' + h)) return;
    const knop = [...document.querySelectorAll('.tab-item')].find(b => b.getAttribute('onclick') === "showTab('" + h + "')");
    if (knop) knop.click();
});

async function testAMI() {
    const result = document.getElementById('ami-test-result');
    result.textContent = 'Testing...';
    const form = new FormData();
    form.append('host', document.getElementById('ami_host').value);
    form.append('port', document.getElementById('ami_port').value);
    form.append('user', document.getElementById('ami_user').value);
    form.append('secret', document.getElementById('ami_secret').value);
    form.append('<?= CSRF_TOKEN_NAME ?>', '<?= csrfToken() ?>');
    const r = await fetch('?page=settings&action=test_ami', {method:'POST', body: form});
    const data = await r.json();
    result.textContent = data.message;
    result.style.color = data.success ? '#00d4aa' : '#ef4444';
}
</script>
