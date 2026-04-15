<div class="page-header">
    <h1 class="page-title"><?= t('settings') ?></h1>
</div>

<div class="tabs-container">
    <div class="tab-bar">
        <button class="tab-item active" onclick="showTab('company')">Company</button>
        <button class="tab-item" onclick="showTab('asterisk')">Asterisk</button>
        <button class="tab-item" onclick="showTab('invoicing')">Invoicing</button>
        <button class="tab-item" onclick="showTab('users')">Users</button>
        <button class="tab-item" onclick="showTab('provisioning')">Provisioning</button>
        <button class="tab-item" onclick="showTab('system')">System</button>
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

        <!-- Invoicing Tab -->
        <div class="tab-pane" id="tab-invoicing" style="display:none">
            <div class="card">
                <div class="card-header"><h3 class="card-title"><?= t('invoice_settings') ?></h3></div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Invoice Number Prefix</label>
                            <input type="text" name="invoice_prefix" class="form-control mono" value="<?= sanitize($settings['invoice_prefix'] ?? 'INV-') ?>">
                        </div>
                        <div class="form-group">
                            <label>Default VAT Rate (%)</label>
                            <input type="number" name="default_tax_rate" class="form-control" value="<?= (int)($settings['default_tax_rate']??21) ?>" step="0.5">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Default Currency</label>
                        <select name="default_currency" class="form-control">
                            <?php foreach (['EUR'=>'Euro (€)','USD'=>'US Dollar ($)','GBP'=>'Pound (£)'] as $code => $name): ?>
                            <option value="<?= $code ?>" <?= ($settings['default_currency']??'EUR')===$code?'selected':'' ?>><?= $name ?></option>
                            <?php endforeach; ?>
                        </select>
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
</div>

<script>
function showTab(name) {
    document.querySelectorAll('.tab-pane').forEach(p => p.style.display = 'none');
    document.querySelectorAll('.tab-item').forEach(t => t.classList.remove('active'));
    document.getElementById('tab-' + name).style.display = '';
    event.target.classList.add('active');
}

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
