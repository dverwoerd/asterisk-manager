<?php
require_once APP_ROOT . '/controllers/BaseController.php';

class AuthController extends BaseController
{
    private const MAX_ATTEMPTS  = 5;    // Max pogingen
    private const BLOCK_MINUTES = 15;   // Blokkeer tijd in minuten
    private const WINDOW_MINUTES = 10;  // Tijdvenster voor pogingen

    public function index(): void
    {
        $this->login();
    }

    public function login(): void
    {
        if (isLoggedIn()) redirect('?page=dashboard');

        $ip      = $this->getClientIp();
        $blocked = $this->isBlocked($ip);

        $this->view('auth.login', [
            'title'   => t('login'),
            'blocked' => $blocked,
            'ip'      => $ip,
            'standalone' => true,
        ]);
    }

    public function post_login(): void
    {
        $ip = $this->getClientIp();

        // Check blacklist
        if ($this->isBlocked($ip)) {
            $this->flash('danger', "Toegang geblokkeerd. Te veel mislukte pogingen. Probeer het later opnieuw.");
            redirect('?page=login');
        }

        $username = trim($this->post('username', ''));
        $password = $this->post('password', '');

        $user = Database::fetchOne(
            "SELECT * FROM users WHERE username=? AND active=1",
            [$username]
        );

        if ($user && password_verify($password, $user['password_hash'])) {
            // Geslaagd — verwijder pogingen voor dit IP
            Database::query("DELETE FROM login_attempts WHERE ip_address=?", [$ip]);
            session_regenerate_id(true);
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];
            $_SESSION['language'] = $user['language'] ?? 'en';
            Database::query("UPDATE users SET last_login=NOW() WHERE id=?", [$user['id']]);
            logInfo("User {$username} logged in from {$ip}.");
            redirect('?page=dashboard');
        }

        // Mislukt — registreer poging
        $this->registerFailedAttempt($ip, $username);
        $attempts = $this->getRecentAttempts($ip);

        if ($attempts >= self::MAX_ATTEMPTS) {
            $this->blockIp($ip, "Te veel mislukte loginpogingen ($attempts)");
            logInfo("IP {$ip} geblokkeerd na {$attempts} mislukte pogingen.");
            $this->flash('danger', "IP adres {$ip} is geblokkeerd voor " . self::BLOCK_MINUTES . " minuten wegens te veel mislukte pogingen.");
        } else {
            $remaining = self::MAX_ATTEMPTS - $attempts;
            $this->flash('danger', t('login_failed') . " ({$remaining} pogingen resterend)");
        }

        redirect('?page=login');
    }

    public function logout(): void
    {
        session_destroy();
        redirect('?page=login');
    }

    // ---- Security helpers ----

    private function getClientIp(): string
    {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = trim(explode(',', $_SERVER[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    private function isBlocked(string $ip): bool
    {
        $row = Database::fetchOne(
            "SELECT * FROM ip_blacklist WHERE ip_address=?",
            [$ip]
        );
        if (!$row) return false;

        // Whitelisted — nooit blokkeren
        if ($row['whitelisted']) return false;

        // Verlopen blokkade — verwijder
        if ($row['expires_at'] && $row['expires_at'] < date('Y-m-d H:i:s')) {
            Database::query("DELETE FROM ip_blacklist WHERE ip_address=?", [$ip]);
            return false;
        }

        return true;
    }

    private function registerFailedAttempt(string $ip, string $username): void
    {
        Database::query(
            "INSERT INTO login_attempts (ip_address, username) VALUES (?, ?)",
            [$ip, $username]
        );
        // Ruim oude pogingen op
        Database::query(
            "DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL ? MINUTE)",
            [self::WINDOW_MINUTES]
        );
    }

    private function getRecentAttempts(string $ip): int
    {
        $row = Database::fetchOne(
            "SELECT COUNT(*) as cnt FROM login_attempts
             WHERE ip_address=? AND attempted_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)",
            [$ip, self::WINDOW_MINUTES]
        );
        return (int)($row['cnt'] ?? 0);
    }

    private function blockIp(string $ip, string $reason): void
    {
        $expires = date('Y-m-d H:i:s', time() + self::BLOCK_MINUTES * 60);
        Database::query(
            "INSERT INTO ip_blacklist (ip_address, reason, blocked_at, expires_at, whitelisted)
             VALUES (?, ?, NOW(), ?, 0)
             ON DUPLICATE KEY UPDATE reason=VALUES(reason), blocked_at=NOW(), expires_at=VALUES(expires_at), whitelisted=0",
            [$ip, $reason, $expires]
        );
    }
}
