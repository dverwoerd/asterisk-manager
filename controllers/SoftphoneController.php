<?php
// ============================================================
// Softphone provisioning: eenmalige koppelcode per extensie.
// - index/post_create: beheerpagina (login nodig)
// - config: publiek endpoint, geeft het account een keer terug
//   en maakt de code daarna ongeldig. Het SIP-wachtwoord staat
//   dus nooit in de link of de QR-code.
// ============================================================
require_once APP_ROOT . '/controllers/BaseController.php';

class SoftphoneController extends BaseController
{
    private const SCHEME    = 'pantronic-phone';   // URL-schema van de eigen MicroSIP-build
    private const ALPHABET  = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // zonder 0/O/1/I
    private const CODE_LEN  = 20;                   // 20 tekens x 5 bit = 100 bit
    private const TTL_HOURS = 24;

    public function index(): void
    {
        $this->opruimen();
        $exts = Database::fetchAll(
            "SELECT e.id, e.extension, e.full_name,
                    (SELECT COUNT(*) FROM softphone_tokens t
                      WHERE t.extension_id=e.id AND t.used_at IS NULL AND t.expires_at > NOW()) AS open_codes,
                    (SELECT MAX(t.used_at) FROM softphone_tokens t WHERE t.extension_id=e.id) AS laatst_gekoppeld
             FROM extensions e WHERE e.enabled=1 ORDER BY e.extension"
        );
        $nieuw = $_SESSION['softphone_nieuw'] ?? null;
        unset($_SESSION['softphone_nieuw']);
        $this->view('softphone.index', [
            'title'  => 'Softphone koppelen',
            'exts'   => $exts,
            'nieuw'  => $nieuw,
            'ttl'    => self::TTL_HOURS,
            'scheme' => self::SCHEME,
        ]);
    }

    public function post_create(): void
    {
        $this->requireOperator();
        $id  = (int)($_POST['extension_id'] ?? 0);
        $ext = Database::fetchOne("SELECT id, extension, full_name FROM extensions WHERE id=? AND enabled=1", [$id]);
        if (!$ext) { $this->flash('error', 'Extensie niet gevonden'); $this->redirect('?page=softphone'); }

        $code = '';
        $n = strlen(self::ALPHABET);
        for ($i = 0; $i < self::CODE_LEN; $i++) $code .= self::ALPHABET[random_int(0, $n - 1)];

        Database::insert('softphone_tokens', [
            'extension_id' => $ext['id'],
            'token_hash'   => hash('sha256', $code),
            'expires_at'   => date('Y-m-d H:i:s', time() + self::TTL_HOURS * 3600),
            'created_by'   => $_SESSION['username'] ?? null,
        ]);

        $host = Database::getSetting('asterisk_external_host', $_SERVER['HTTP_HOST'] ?? 'localhost');
        $url  = 'https://' . $host . '/?page=softphone&action=config&token=' . $code;
        $_SESSION['softphone_nieuw'] = [
            'extension' => $ext['extension'],
            'name'      => $ext['full_name'],
            'code'      => implode('-', str_split($code, 5)),
            'url'       => $url,
            'deeplink'  => self::SCHEME . '://provision?u=' . rawurlencode($url),
            'expires'   => date('d-m-Y H:i', time() + self::TTL_HOURS * 3600),
        ];
        $this->redirect('?page=softphone');
    }

    /** Publiek: GET ?page=softphone&action=config&token=... (ook 'code' met streepjes toegestaan) */
    public function config(): void
    {
        $raw  = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string)($_GET['token'] ?? '')));
        $row  = null;
        if (strlen($raw) === self::CODE_LEN) {
            $row = Database::fetchOne(
                "SELECT t.id, e.extension, e.full_name, e.secret, e.transport, e.codecs, e.dtmf_mode, e.callerid_name
                 FROM softphone_tokens t JOIN extensions e ON e.id=t.extension_id
                 WHERE t.token_hash=? AND t.used_at IS NULL AND t.expires_at > NOW() AND e.enabled=1",
                [hash('sha256', $raw)]
            );
        }
        if (!$row) {
            usleep(400000);                      // remt het raden af
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Ongeldige of verlopen code']);
            exit;
        }
        // Eerst als gebruikt markeren, dan pas uitgeven (voorkomt dubbel gebruik bij gelijktijdige aanvragen)
        $upd = Database::query(
            "UPDATE softphone_tokens SET used_at=NOW(), used_ip=? WHERE id=? AND used_at IS NULL",
            [substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45), $row['id']]
        );
        if ($upd->rowCount() !== 1) { http_response_code(404); echo json_encode(['error' => 'Code al gebruikt']); exit; }

        $host = Database::getSetting('asterisk_external_host', 'pbx.clearvoip.nl');
        $tr   = str_contains((string)$row['transport'], 'tcp') ? 'tcp' : (str_contains((string)$row['transport'], 'tls') ? 'tls' : 'udp');
        $codecs = array_values(array_filter(array_map('trim', explode(',', (string)$row['codecs']))));
        header('Content-Type: application/json');
        header('Cache-Control: no-store');
        echo json_encode([
            'version'   => 1,
            'label'     => $row['full_name'] ?: $row['extension'],
            'server'    => $host,
            'port'      => 5060,
            'transport' => $tr,
            'domain'    => $host,
            'username'  => $row['extension'],
            'authId'    => $row['extension'],
            'password'  => $row['secret'],
            'displayName' => $row['callerid_name'] ?: $row['full_name'],
            'codecs'    => $codecs,
            'dtmf'      => $row['dtmf_mode'],
        ], JSON_UNESCAPED_SLASHES);
        exit;
    }

    private function opruimen(): void
    {
        Database::query("DELETE FROM softphone_tokens WHERE expires_at < NOW() - INTERVAL 7 DAY");
    }
}
