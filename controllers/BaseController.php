<?php
// ============================================================
// BaseController - Shared controller methods
// ============================================================

class BaseController
{
    protected array $data = [];

    protected function view(string $view, array $data = []): void
    {
        $this->data = array_merge($this->data, $data);

        // Voeg AMI verbindingsstatus toe aan elke pagina
        if (!isset($this->data['amiConnected'])) {
            $this->data['amiConnected'] = $this->checkAMI();
        }

        extract($this->data);
        $viewFile   = APP_ROOT . '/views/' . str_replace('.', '/', $view) . '.php';
        $layoutFile = APP_ROOT . '/views/layout.php';
        if (!file_exists($viewFile)) {
            http_response_code(404);
            die("View not found: $view");
        }
        ob_start();
        require $viewFile;
        $content = ob_get_clean();
        if (isset($data['standalone']) && $data['standalone']) {
            echo $content;
            return;
        }
        require $layoutFile;
    }

    private function checkAMI(): bool
    {
        // Cache de AMI status in de sessie voor 30 seconden
        $cacheKey = 'ami_status_cache';
        $cacheTime = 'ami_status_time';

        if (isset($_SESSION[$cacheKey]) && isset($_SESSION[$cacheTime])) {
            if ((time() - $_SESSION[$cacheTime]) < 30) {
                return (bool)$_SESSION[$cacheKey];
            }
        }

        try {
            require_once APP_ROOT . '/includes/AsteriskAMI.php';
            $ami = AsteriskAMI::fromSettings();
            $ok  = $ami->connect();
            if ($ok) $ami->disconnect();
            $_SESSION[$cacheKey] = $ok;
            $_SESSION[$cacheTime] = time();
            return $ok;
        } catch (Exception $e) {
            $_SESSION[$cacheKey] = false;
            $_SESSION[$cacheTime] = time();
            return false;
        }
    }

    protected function json(mixed $data, int $code = 200): never
    {
        json($data, $code);
    }

    protected function redirect(string $url): never
    {
        redirect($url);
    }

    protected function flash(string $type, string $message): void
    {
        flash($type, $message);
    }

    protected function requireAdmin(): void
    {
        requireRole('admin');
    }

    protected function requireOperator(): void
    {
        requireRole('operator');
    }

    protected function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    protected function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    protected function id(): int
    {
        return (int)($_GET['id'] ?? 0);
    }

    public function index(): void
    {
        $this->view('dashboard.index');
    }
}
