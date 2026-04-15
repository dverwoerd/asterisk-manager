<?php
require_once APP_ROOT . '/controllers/BaseController.php';
require_once APP_ROOT . '/includes/AsteriskConfig.php';

class ExtensionsController extends BaseController
{
    public function index(): void
    {
        $search = $this->get('search', '');
        $sql = "SELECT e.*, pp.id as phone_id, pp.model as phone_model, pp.mac_address as phone_mac
                 FROM extensions e
                 LEFT JOIN provision_phones pp ON pp.extension_id = e.id";
        $params = [];
        if ($search) {
            $sql .= " WHERE extension LIKE ? OR full_name LIKE ? OR email LIKE ?";
            $params = ["%$search%", "%$search%", "%$search%"];
        }
        $sql .= " ORDER BY CAST(extension AS UNSIGNED)";
        $extensions = Database::fetchAll($sql, $params);
        $this->view('extensions.index', [
            'title'      => t('extensions'),
            'extensions' => $extensions,
            'search'     => $search,
        ]);
    }

    public function add(): void
    {
        $this->view('extensions.form', [
            'title'  => t('add_new') . ' ' . t('extension'),
            'ext'    => $this->defaults(),
            'action' => 'add',
        ]);
    }

    public function post_add(): void
    {
        $this->requireOperator();
        $data = $this->collectFormData();
        $errors = $this->validate($data);
        if ($errors) {
            $this->flash('danger', implode('<br>', $errors));
            redirect('?page=extensions&action=add');
        }
        Database::insert('extensions', $data);
        $this->applyAndReload();
        $this->flash('success', t('ext_added'));
        redirect('?page=extensions');
    }

    public function edit(): void
    {
        $id  = $this->id();
        $ext = Database::fetchOne("SELECT * FROM extensions WHERE id=?", [$id]);
        if (!$ext) { $this->flash('danger', 'Not found'); redirect('?page=extensions'); }

        // Haal provisioning data op
        $phone = Database::fetchOne(
            "SELECT * FROM provision_phones WHERE extension_id=?", [$id]
        );
        $phonebookGroups = Database::fetchAll(
            "SELECT id, name FROM phonebook_groups ORDER BY name"
        );
        $allExtensions = Database::fetchAll(
            "SELECT id, extension, full_name FROM extensions WHERE enabled=1 AND id != ? ORDER BY extension",
            [$id]
        );

        $this->view('extensions.form', [
            'title'          => t('edit') . ' ' . t('extension'),
            'ext'            => $ext,
            'action'         => 'edit',
            'phone'          => $phone,
            'phonebookGroups'=> $phonebookGroups,
            'allExtensions'  => $allExtensions,
            'yealinkModels'  => YealinkProvisioning::getYealinkModels(),
            'gigasetModels'  => GigasetProvisioning::getModels(),
            'timezones'      => YealinkProvisioning::getTimezones(),
            'languages'      => YealinkProvisioning::getLanguages(),
        ]);
    }

    public function post_edit(): void
    {
        $this->requireOperator();
        $id = $this->id();
        $data = $this->collectFormData();
        // Don't overwrite password if blank
        if (empty($data['secret'])) unset($data['secret']);
        $errors = $this->validate($data, $id);
        if ($errors) {
            $this->flash('danger', implode('<br>', $errors));
            redirect("?page=extensions&action=edit&id=$id");
        }
        Database::update('extensions', $data, 'id=?', [$id]);
        $this->applyAndReload();
        $this->flash('success', t('ext_updated'));
        redirect('?page=extensions');
    }

    public function save_provision(): void
    {
        $this->requireOperator();
        $id  = $this->id();
        $ext = Database::fetchOne("SELECT * FROM extensions WHERE id=?", [$id]);
        if (!$ext) redirect('?page=extensions');

        require_once APP_ROOT . '/includes/YealinkProvisioning.php';
        require_once APP_ROOT . '/includes/GigasetProvisioning.php';

        $data = [
            'extension_id'       => $id,
            'mac_address'        => strtoupper(trim($this->post('mac_address', ''))),
            'model'              => $this->post('model', 'T46U'),
            'display_name'       => trim($this->post('display_name', $ext['full_name'])),
            'admin_password'     => trim($this->post('admin_password', 'admin')),
            'timezone'           => $this->post('timezone', 'Europe/Amsterdam'),
            'language'           => $this->post('language', 'Dutch'),
            'screensaver_time'   => (int)$this->post('screensaver_time', 0),
            'screensaver_clock'  => (int)$this->post('screensaver_clock', 0),
            'backlight_time'     => (int)$this->post('backlight_time', 0),
            'phonebook_group_id' => (int)$this->post('phonebook_group_id', 0) ?: null,
            'extra_extensions'   => implode(',', array_filter(array_map('intval', $this->post('extra_extensions', [])))),
            'notes'              => trim($this->post('notes', '')),
        ];

        // BLF keys
        $blfKeys = [];
        $blfExtensions = $this->post('blf_extension', []);
        $blfLabels     = $this->post('blf_label', []);
        foreach ($blfExtensions as $i => $blfExt) {
            if (!empty($blfExt)) {
                $blfKeys[] = [
                    'key_number' => $i + 1,
                    'extension'  => $blfExt,
                    'label'      => $blfLabels[$i] ?? $blfExt,
                ];
            }
        }

        // Bestaande phone record updaten of aanmaken
        $existing = Database::fetchOne(
            "SELECT id FROM provision_phones WHERE extension_id=?", [$id]
        );

        if ($existing) {
            Database::update('provision_phones', $data, 'id=?', [$existing['id']]);
            $phoneId = $existing['id'];
            Database::delete('provision_blf_keys', 'phone_id=?', [$phoneId]);
        } else {
            $phoneId = Database::insert('provision_phones', $data);
        }

        // BLF keys opslaan
        foreach ($blfKeys as $key) {
            Database::insert('provision_blf_keys', array_merge($key, ['phone_id' => $phoneId]));
        }

        // Genereer config
        $phone = Database::fetchOne("SELECT * FROM provision_phones WHERE id=?", [$phoneId]);
        $brand = (stripos($phone['model'], 'N300') !== false || stripos($phone['model'], 'N510') !== false) ? 'gigaset' : 'yealink';
        if ($brand === 'gigaset') {
            GigasetProvisioning::writeConfigFile($phone);
        } else {
            YealinkProvisioning::writeConfigFile($phone);
        }

        $this->flash('success', 'Provisioning opgeslagen en config gegenereerd.');
        redirect("?page=extensions&action=edit&id=$id");
    }

    public function delete(): void
    {
        $this->requireOperator();
        Database::delete('extensions', 'id=?', [$this->id()]);
        $this->applyAndReload();
        $this->flash('success', t('ext_deleted'));
        redirect('?page=extensions');
    }

    private function collectFormData(): array
    {
        return [
            'extension'        => trim($this->post('extension', '')),
            'full_name'        => trim($this->post('full_name', '')),
            'email'            => trim($this->post('email', '')),
            'secret'           => trim($this->post('secret', '')),
            'context'          => $this->post('context', 'from-internal'),
            'callerid_name'    => trim($this->post('callerid_name', '')),
            'callerid_number'  => trim($this->post('callerid_number', '')),
            'voicemail_enabled'=> $this->post('voicemail_enabled', 0) ? 1 : 0,
            'voicemail_pin'    => trim($this->post('voicemail_pin', '')),
            'max_contacts'     => (int)$this->post('max_contacts', 1),
            'codecs'           => implode(',', $this->post('codecs', ['ulaw', 'alaw'])),
            'dtmf_mode'        => $this->post('dtmf_mode', 'rfc4733'),
            'call_waiting'     => $this->post('call_waiting', 0) ? 1 : 0,
            'call_recording'   => $this->post('call_recording', 'never'),
            'notes'            => trim($this->post('notes', '')),
            'allowed_ips'       => trim($this->post('allowed_ips', '')),
            'cf_always'        => trim(\$this->post('cf_always', '')),
            'cf_noanswer'      => trim(\$this->post('cf_noanswer', '')),
            'cf_busy'          => trim(\$this->post('cf_busy', '')),
            'cf_voicemail'     => \$this->post('cf_voicemail', 0) ? 1 : 0,
            'ring_time'        => (int)\$this->post('ring_time', 20),
            'enabled'          => $this->post('enabled', 0) ? 1 : 0,
        ];
    }

    private function validate(array $data, ?int $excludeId = null): array
    {
        $errors = [];
        if (empty($data['extension'])) $errors[] = 'Extension number is required.';
        if (!preg_match('/^\d{3,6}$/', $data['extension'] ?? ''))
            $errors[] = 'Extension must be 3-6 digits.';
        if (empty($data['full_name'])) $errors[] = 'Full name is required.';

        // Check unique
        if (!empty($data['extension'])) {
            $existing = Database::fetchOne(
                "SELECT id FROM extensions WHERE extension=?" . ($excludeId ? " AND id!=?" : ""),
                $excludeId ? [$data['extension'], $excludeId] : [$data['extension']]
            );
            if ($existing) $errors[] = 'Extension number already in use.';
        }
        return $errors;
    }

    private function defaults(): array
    {
        return [
            'extension' => '', 'full_name' => '', 'email' => '',
            'secret' => '', 'context' => 'from-internal',
            'callerid_name' => '', 'callerid_number' => '',
            'voicemail_enabled' => 0, 'voicemail_pin' => '',
            'max_contacts' => 1, 'codecs' => 'ulaw,alaw,g722',
            'dtmf_mode' => 'rfc4733', 'call_waiting' => 1,
            'call_recording' => 'never', 'notes' => '', 'enabled' => 1,
        ];
    }

    private function applyAndReload(): void
    {
        try {
            $cfg = AsteriskConfig::fromSettings();
            $cfg->generatePJSIP();
            $cfg->generateDialplan();
            $cfg->reload('res_pjsip');
        } catch (Exception $e) {
            logError('Extension reload failed: ' . $e->getMessage());
        }
    }
}
