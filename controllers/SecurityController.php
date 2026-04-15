<?php
require_once APP_ROOT . '/controllers/BaseController.php';

class SecurityController extends BaseController
{
    public function index(): void
    {
        // Verwijder verlopen blokkades
        Database::query(
            "DELETE FROM ip_blacklist WHERE whitelisted=0 AND expires_at IS NOT NULL AND expires_at < NOW()"
        );

        $blacklist = Database::fetchAll(
            "SELECT * FROM ip_blacklist ORDER BY whitelisted ASC, blocked_at DESC"
        );
        $recentAttempts = Database::fetchAll(
            "SELECT ip_address, username, COUNT(*) as attempts, MAX(attempted_at) as last_attempt
             FROM login_attempts
             WHERE attempted_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
             GROUP BY ip_address, username
             ORDER BY attempts DESC
             LIMIT 20"
        );

        $this->view('security.index', [
            'title'          => '🔒 Login Security',
            'blacklist'      => $blacklist,
            'recentAttempts' => $recentAttempts,
        ]);
    }

    public function unblock(): void
    {
        $this->requireOperator();
        $ip = $this->get('ip', '');
        Database::query("DELETE FROM ip_blacklist WHERE ip_address=?", [$ip]);
        Database::query("DELETE FROM login_attempts WHERE ip_address=?", [$ip]);
        $this->flash('success', "IP {$ip} gedeblokkeerd.");
        redirect('?page=security');
    }

    public function whitelist(): void
    {
        $this->requireOperator();
        $ip = $this->get('ip', '');
        Database::query(
            "INSERT INTO ip_blacklist (ip_address, reason, whitelisted, expires_at)
             VALUES (?, 'Handmatig gewhitelist', 1, NULL)
             ON DUPLICATE KEY UPDATE whitelisted=1, expires_at=NULL",
            [$ip]
        );
        $this->flash('success', "IP {$ip} op whitelist gezet.");
        redirect('?page=security');
    }

    public function remove_whitelist(): void
    {
        $this->requireOperator();
        $ip = $this->get('ip', '');
        Database::query("DELETE FROM ip_blacklist WHERE ip_address=? AND whitelisted=1", [$ip]);
        $this->flash('success', "IP {$ip} van whitelist verwijderd.");
        redirect('?page=security');
    }

    public function block(): void
    {
        $this->requireOperator();
        $ip     = trim($this->post('ip', ''));
        $reason = trim($this->post('reason', 'Handmatig geblokkeerd'));
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $this->flash('danger', 'Ongeldig IP adres.');
            redirect('?page=security');
        }
        $expires = date('Y-m-d H:i:s', time() + 86400); // 24 uur
        Database::query(
            "INSERT INTO ip_blacklist (ip_address, reason, blocked_at, expires_at, whitelisted)
             VALUES (?, ?, NOW(), ?, 0)
             ON DUPLICATE KEY UPDATE reason=VALUES(reason), blocked_at=NOW(), expires_at=VALUES(expires_at), whitelisted=0",
            [$ip, $reason, $expires]
        );
        $this->flash('success', "IP {$ip} geblokkeerd voor 24 uur.");
        redirect('?page=security');
    }

    public function clear_attempts(): void
    {
        $this->requireOperator();
        Database::query("DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $this->flash('success', 'Oude loginpogingen verwijderd.');
        redirect('?page=security');
    }
}
