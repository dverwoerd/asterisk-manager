<?php
require_once APP_ROOT . '/controllers/BaseController.php';
require_once APP_ROOT . '/includes/AsteriskAMI.php';

class SettingsController extends BaseController
{
    public function index(): void
    {
        $settings = [];
        $rows = Database::fetchAll("SELECT setting_key, setting_value FROM settings");
        foreach ($rows as $r) $settings[$r['setting_key']] = $r['setting_value'];
        $languages = I18n::availableLanguages();
        $users     = Database::fetchAll("SELECT * FROM users ORDER BY username");
        $this->view('settings.index', ['title' => t('settings'), 'settings' => $settings, 'languages' => $languages, 'users' => $users]);
    }

    public function post_save(): void
    {
        $this->requireAdmin();
        $keys = [
            'company_name','company_address','company_vat','company_email','company_phone',
            'invoice_prefix','default_tax_rate','default_currency',
            'asterisk_host','asterisk_ami_port','asterisk_ami_user','asterisk_ami_secret','asterisk_config_path',
            'asterisk_external_host','asterisk_local_nets',
            'provision_base_url','provision_admin_pass','provision_ntp_server','provision_timezone','provision_tftp_path',
            'cdr_sync_enabled','cdr_sync_interval',
            'default_language','timezone','date_format',
        ];
        foreach ($keys as $key) {
            $val = $this->post($key, '');
            Database::setSetting($key, $val);
        }
        // Language for current session
        if ($lang = $this->post('default_language')) {
            $_SESSION['language'] = $lang;
            I18n::load($lang);
        }
        $this->flash('success', t('settings_saved'));
        redirect('?page=settings');
    }

    public function test_ami(): void
    {
        $host   = $this->post('host', getSetting('asterisk_host', '127.0.0.1'));
        $port   = (int)$this->post('port', getSetting('asterisk_ami_port', 5038));
        $user   = $this->post('user', getSetting('asterisk_ami_user', 'manager'));
        $secret = $this->post('secret', getSetting('asterisk_ami_secret', ''));
        $ami    = new AsteriskAMI($host, $port, $user, $secret);
        $ok     = $ami->connect();
        if ($ok) $ami->disconnect();
        $this->json(['success' => $ok, 'message' => t($ok ? 'connection_ok' : 'connection_failed')]);
    }

    public function add_user(): void
    {
        $this->requireAdmin();
        $username = trim($this->post('username', ''));
        $password = $this->post('password', '');
        $role     = $this->post('role', 'operator');
        if (empty($username) || empty($password)) { $this->flash('danger', 'Username and password required.'); redirect('?page=settings'); }
        Database::insert('users', [
            'username'      => $username,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'full_name'     => trim($this->post('full_name', '')),
            'email'         => trim($this->post('email', '')),
            'role'          => $role,
            'language'      => $this->post('language', 'en'),
            'active'        => 1,
        ]);
        $this->flash('success', 'User added.');
        redirect('?page=settings');
    }

    public function delete_user(): void
    {
        $this->requireAdmin();
        if ($this->id() == ($_SESSION['user_id'] ?? 0)) { $this->flash('danger', 'Cannot delete current user.'); redirect('?page=settings'); }
        Database::update('users', ['active' => 0], 'id=?', [$this->id()]);
        $this->flash('success', t('deleted'));
        redirect('?page=settings');
    }

    public function change_password(): void
    {
        $id    = $this->id() ?: ($_SESSION['user_id'] ?? 0);
        $pass  = $this->post('new_password', '');
        if (strlen($pass) < 8) { $this->flash('danger', 'Password must be at least 8 characters.'); redirect('?page=settings'); }
        Database::update('users', ['password_hash' => password_hash($pass, PASSWORD_BCRYPT)], 'id=?', [$id]);
        $this->flash('success', 'Password changed.');
        redirect('?page=settings');
    }

    public function set_language(): void
    {
        $lang = $this->post('lang', 'en');
        $_SESSION['language'] = $lang;
        if (isLoggedIn()) {
            Database::update('users', ['language' => $lang], 'id=?', [$_SESSION['user_id']]);
        }
        I18n::load($lang);
        redirect($_SERVER['HTTP_REFERER'] ?? '?page=dashboard');
    }
}
