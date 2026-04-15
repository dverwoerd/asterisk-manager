<?php
require_once APP_ROOT . '/controllers/BaseController.php';
require_once APP_ROOT . '/includes/YealinkProvisioning.php';
require_once APP_ROOT . '/includes/GigasetProvisioning.php';

class ProvisionController extends BaseController
{
    public function index(): void
    {
        $phones = Database::fetchAll(
            "SELECT pp.*, e.extension, e.full_name,
             (SELECT COUNT(*) FROM provision_blf_keys WHERE phone_id=pp.id) as blf_count
             FROM provision_phones pp
             JOIN extensions e ON pp.extension_id=e.id
             ORDER BY e.extension"
        );
        $this->view('provision.index', [
            'title'  => 'Yealink Provisioning',
            'phones' => $phones,
        ]);
    }

    public function add(): void
    {
        $extensions = Database::fetchAll(
            "SELECT e.id, e.extension, e.full_name
             FROM extensions e
             WHERE e.enabled=1
             AND e.id NOT IN (SELECT extension_id FROM provision_phones)
             ORDER BY e.extension"
        );
        $this->view('provision.form', [
            'title'      => 'Add Phone',
            'phone'      => $this->defaults(),
            'extensions' => $extensions,
            'models'     => array_merge(
                ['--- Yealink ---' => ''], 
                YealinkProvisioning::getYealinkModels(),
                ['--- Gigaset ---' => ''],
                GigasetProvisioning::getModels()
            ),
            'timezones'  => YealinkProvisioning::getTimezones(),
            'languages'  => YealinkProvisioning::getLanguages(),
            'action'     => 'add',
        ]);
    }

    public function post_add(): void
    {
        $this->requireOperator();
        $data = $this->collectFormData();
        $id   = Database::insert('provision_phones', $data);
        $this->saveBLFKeys($id);
        $this->generateConfig($id);
        $this->flash('success', 'Toestel toegevoegd en config gegenereerd.');
        redirect('?page=provision');
    }

    public function edit(): void
    {
        $id    = $this->id();
        $phone = Database::fetchOne("SELECT * FROM provision_phones WHERE id=?", [$id]);
        if (!$phone) redirect('?page=provision');

        $blfKeys = Database::fetchAll(
            "SELECT * FROM provision_blf_keys WHERE phone_id=? ORDER BY key_number",
            [$id]
        );
        $extensions = Database::fetchAll(
            "SELECT id, extension, full_name FROM extensions WHERE enabled=1 ORDER BY extension"
        );
        $this->view('provision.form', [
            'title'      => 'Edit Phone — ' . $phone['mac_address'],
            'phone'      => $phone,
            'blfKeys'    => $blfKeys,
            'extensions' => $extensions,
            'models'     => array_merge(
                ['--- Yealink ---' => ''], 
                YealinkProvisioning::getYealinkModels(),
                ['--- Gigaset ---' => ''],
                GigasetProvisioning::getModels()
            ),
            'timezones'  => YealinkProvisioning::getTimezones(),
            'languages'  => YealinkProvisioning::getLanguages(),
            'action'     => 'edit',
        ]);
    }

    public function post_edit(): void
    {
        $this->requireOperator();
        $id   = $this->id();
        $data = $this->collectFormData();
        Database::update('provision_phones', $data, 'id=?', [$id]);
        Database::delete('provision_blf_keys', 'phone_id=?', [$id]);
        $this->saveBLFKeys($id);
        $this->generateConfig($id);
        $this->flash('success', 'Toestel bijgewerkt en config gegenereerd.');
        redirect('?page=provision');
    }

    public function delete(): void
    {
        $this->requireOperator();
        $phone = Database::fetchOne("SELECT mac_address FROM provision_phones WHERE id=?", [$this->id()]);
        if ($phone) {
            // Verwijder config bestand
            $provPath = Database::getSetting('provision_tftp_path', APP_ROOT . '/provision');
            $file     = $provPath . '/' . YealinkProvisioning::getMacFilename($phone['mac_address']);
            if (file_exists($file)) @unlink($file);
            Database::delete('provision_phones', 'id=?', [$this->id()]);
        }
        $this->flash('success', 'Toestel verwijderd.');
        redirect('?page=provision');
    }

    public function generate(): void
    {
        $this->requireOperator();
        $id  = $this->id();
        $ok  = $this->generateConfig($id);
        $this->flash($ok ? 'success' : 'danger', $ok ? 'Config bestand gegenereerd.' : 'Genereren mislukt.');
        redirect('?page=provision');
    }

    public function generate_all(): void
    {
        $this->requireOperator();
        $phones = Database::fetchAll("SELECT id FROM provision_phones");
        $count  = 0;
        foreach ($phones as $p) {
            if ($this->generateConfig($p['id'])) $count++;
        }
        $this->flash('success', "$count config bestanden gegenereerd.");
        redirect('?page=provision');
    }

    // Dient het config bestand op basis van MAC adres (voor TFTP/HTTP provisioning)
    public function config(): void
    {
        $mac = strtolower(preg_replace('/[^a-fA-F0-9]/', '', $this->get('mac', '')));
        if (strlen($mac) !== 12) {
            http_response_code(404);
            die('Invalid MAC address');
        }

        // Formatteer MAC met dubbele punten
        $macFormatted = implode(':', str_split($mac, 2));

        $phone = Database::fetchOne(
            "SELECT * FROM provision_phones WHERE LOWER(REPLACE(REPLACE(mac_address,':',''),'-',''))=?",
            [$mac]
        );

        if (!$phone) {
            http_response_code(404);
            die('Phone not found: ' . $macFormatted);
        }

        // Update last provision timestamp
        Database::update('provision_phones', ['last_provision' => date('Y-m-d H:i:s')], 'id=?', [$phone['id']]);

        $content = YealinkProvisioning::generate($phone);

        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: inline; filename="' . $mac . '.cfg"');
        header('Content-Length: ' . strlen($content));
        echo $content;
        exit;
    }

    public function view_config(): void
    {
        $id    = $this->id();
        $phone = Database::fetchOne("SELECT * FROM provision_phones WHERE id=?", [$id]);
        if (!$phone) redirect('?page=provision');

        $content = YealinkProvisioning::generate($phone);
        $ext     = Database::fetchOne("SELECT extension, full_name FROM extensions WHERE id=?", [$phone['extension_id']]);

        $this->view('provision.config_view', [
            'title'   => 'Config: ' . $phone['mac_address'],
            'phone'   => $phone,
            'ext'     => $ext,
            'content' => $content,
        ]);
    }

    public function reboot(): void
    {
        $this->requireOperator();
        $id    = $this->id();
        $phone = Database::fetchOne(
            "SELECT pp.*, e.extension FROM provision_phones pp
             JOIN extensions e ON pp.extension_id = e.id
             WHERE pp.id = ?",
            [$id]
        );

        if (!$phone) {
            $this->flash('danger', 'Toestel niet gevonden.');
            redirect('?page=provision');
        }

        try {
            require_once APP_ROOT . '/includes/AsteriskAMI.php';
            $ami = AsteriskAMI::fromSettings();
            if ($ami->connect()) {
                // Stuur SIP NOTIFY check-sync;reboot=true naar het endpoint
                $result = $ami->sendRawCommand(
                    "Action: PJSIPNotify
" .
                    "Endpoint: " . $phone['extension'] . "
" .
                    "Variable: Event=check-sync;reboot=true
"
                );
                $ami->disconnect();
                $this->flash('success', 'Reboot commando verzonden naar extensie ' . $phone['extension'] . '.');
            } else {
                // Fallback via asterisk CLI
                $ext = escapeshellarg($phone['extension']);
                shell_exec("sudo /usr/sbin/asterisk -rx 'pjsip send notify check-sync endpoint " . $phone['extension'] . "' 2>/dev/null");
                $this->flash('success', 'Reboot commando verzonden via CLI naar extensie ' . $phone['extension'] . '.');
            }
        } catch (Exception $e) {
            $this->flash('danger', 'Reboot mislukt: ' . $e->getMessage());
        }

        redirect('?page=provision');
    }

    public function reprovision(): void
    {
        $this->requireOperator();
        $id    = $this->id();
        $phone = Database::fetchOne(
            "SELECT pp.*, e.extension FROM provision_phones pp
             JOIN extensions e ON pp.extension_id = e.id
             WHERE pp.id = ?",
            [$id]
        );

        if (!$phone) {
            $this->flash('danger', 'Toestel niet gevonden.');
            redirect('?page=provision');
        }

        // Genereer eerst een nieuwe config
        $this->generateConfig($id);

        // Yealink reprovisioning via HTTP API (geen reboot)
        // Haal het IP op uit de PJSIP contacten
        $cliOutput = shell_exec('/usr/sbin/asterisk -rx "pjsip show contact ' . $phone['extension'] . '" 2>/dev/null');
        if (empty($cliOutput)) {
            $cliOutput = shell_exec('sudo /usr/sbin/asterisk -rx "pjsip show contacts" 2>/dev/null');
        }

        $phoneIp = null;
        foreach (explode("
", $cliOutput ?? '') as $line) {
            if (preg_match('/^\s+Contact:\s+' . preg_quote($phone['extension']) . '\/sip:[^@]+@([\d.]+):(\d+)/', $line, $m)) {
                $phoneIp = $m[1];
                break;
            }
        }

        if ($phoneIp) {
            // Yealink HTTP API: trigger autoprovision via webinterface
            $adminPass = $phone['admin_password'] ?? Database::getSetting('provision_admin_pass', 'admin');
            $url       = "http://$phoneIp/cgi-bin/cgiServer.exx";

            // Yealink autoprovisioning trigger via CGI
            $postData = http_build_query([
                'PATH'    => '0',
                'Command' => '30', // AutoProvision command
            ]);

            $ctx = stream_context_create([
                'http' => [
                    'method'  => 'POST',
                    'header'  => [
                        'Content-Type: application/x-www-form-urlencoded',
                        'Authorization: Basic ' . base64_encode('admin:' . $adminPass),
                    ],
                    'content' => $postData,
                    'timeout' => 5,
                ]
            ]);

            $result = @file_get_contents($url, false, $ctx);

            if ($result !== false) {
                $this->flash('success', "Reprovisioning getriggerd op $phoneIp voor extensie {$phone['extension']}. Het toestel laadt de nieuwe config zonder reboot.");
            } else {
                // Fallback: update config en geef instructie
                $this->flash('info', "Config gegenereerd voor extensie {$phone['extension']}. Toestel IP: $phoneIp. Het toestel haalt de config op bij de volgende provisioning check (elke 24 uur) of gebruik Reboot voor directe toepassing.");
            }
        } else {
            // Geen IP gevonden - config is wel gegenereerd
            $this->flash('info', "Config gegenereerd voor extensie {$phone['extension']}. Toestel is niet online of IP niet gevonden. De nieuwe config wordt automatisch opgehaald bij de volgende provisioning check.");
        }

        redirect('?page=provision');
    }

    // ---- Helpers ----

    private function generateConfig(int $id): bool
    {
        $phone = Database::fetchOne("SELECT * FROM provision_phones WHERE id=?", [$id]);
        if (!$phone) return false;
        try {
            $brand = self::detectBrand($phone['model'] ?? '');
            if ($brand === 'gigaset') {
                return GigasetProvisioning::writeConfigFile($phone);
            }
            return YealinkProvisioning::writeConfigFile($phone);
        } catch (Exception $e) {
            logError('Provision generate: ' . $e->getMessage());
            return false;
        }
    }

    private static function detectBrand(string $model): string
    {
        $gigasetModels = ['N300', 'N300A', 'N510', 'N720', 'N870'];
        foreach ($gigasetModels as $gm) {
            if (stripos($model, $gm) !== false || stripos($model, 'Gigaset') !== false) {
                return 'gigaset';
            }
        }
        return 'yealink';
    }

    private function saveBLFKeys(int $phoneId): void
    {
        $keyNumbers  = $this->post('key_numbers', []);
        $keyTypes    = $this->post('key_types', []);
        $keyLabels   = $this->post('key_labels', []);
        $keyValues   = $this->post('key_values', []);
        $keyExtIds   = $this->post('key_ext_ids', []);
        $keyPickups  = $this->post('key_pickups', []);

        foreach ($keyNumbers as $i => $keyNum) {
            if (empty($keyNum)) continue;
            $value   = trim($keyValues[$i]  ?? '');
            $extId   = !empty($keyExtIds[$i]) ? (int)$keyExtIds[$i] : null;

            // Als extensie geselecteerd: haal nummer op als value
            if ($extId) {
                $extRow = Database::fetchOne("SELECT extension FROM extensions WHERE id=?", [$extId]);
                if ($extRow && empty($value)) $value = $extRow['extension'];
            }

            Database::insert('provision_blf_keys', [
                'phone_id'     => $phoneId,
                'key_number'   => (int)$keyNum,
                'key_type'     => $keyTypes[$i]   ?? 'blf',
                'label'        => trim($keyLabels[$i] ?? ''),
                'value'        => $value,
                'extension_id' => $extId,
                'pickup_code'  => trim($keyPickups[$i] ?? '*8'),
            ]);
        }
    }

    private function collectFormData(): array
    {
        $mac = strtoupper(trim($this->post('mac_address', '')));
        // Normaliseer MAC naar AA:BB:CC:DD:EE:FF formaat
        $mac = preg_replace('/[^A-Fa-f0-9]/', '', $mac);
        if (strlen($mac) === 12) {
            $mac = implode(':', str_split($mac, 2));
        }
        return [
            'extension_id'  => (int)$this->post('extension_id', 0),
            'mac_address'   => $mac,
            'model'         => $this->post('model', 'T46U'),
            'admin_password'=> $this->post('admin_password', 'admin'),
            'display_name'  => trim($this->post('display_name', '')),
            'timezone'      => $this->post('timezone', 'Europe/Amsterdam'),
            'ntp_server'    => trim($this->post('ntp_server', 'pool.ntp.org')),
            'language'      => $this->post('language', 'Dutch'),
            'notes'            => trim($this->post('notes', '')),
            'extra_extensions'   => implode(',', array_filter(array_map('intval', $this->post('extra_extensions', [])))),
            'phonebook_group_id' => (int)$this->post('phonebook_group_id', 0) ?: null,
            'screensaver_time'  => (int)$this->post('screensaver_time', 0),
            'screensaver_clock' => (int)$this->post('screensaver_clock', 0),
            'backlight_time'    => (int)$this->post('backlight_time', 0),
        ];
    }

    private function defaults(): array
    {
        return [
            'extension_id'  => 0,
            'mac_address'   => '',
            'model'         => 'T46U',
            'admin_password'=> getSetting('provision_admin_pass', 'admin'),
            'display_name'  => '',
            'timezone'      => getSetting('provision_timezone', 'Europe/Amsterdam'),
            'ntp_server'    => getSetting('provision_ntp_server', 'pool.ntp.org'),
            'language'      => 'Dutch',
            'notes'         => '',
        ];
    }
}
