<?php
// ============================================================
// Helper Functions
// ============================================================

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

function currentUser(): ?array
{
    if (!isLoggedIn()) return null;
    static $user = null;
    if ($user === null) {
        $user = Database::fetchOne("SELECT * FROM users WHERE id=?", [$_SESSION['user_id']]);
    }
    return $user;
}

function hasRole(string $role): bool
{
    $user = currentUser();
    if (!$user) return false;
    $hierarchy = ['viewer' => 1, 'operator' => 2, 'admin' => 3];
    return ($hierarchy[$user['role']] ?? 0) >= ($hierarchy[$role] ?? 99);
}

function requireRole(string $role): void
{
    if (!hasRole($role)) {
        http_response_code(403);
        die(t('error_access_denied'));
    }
}

function getSetting(string $key, mixed $default = null): mixed
{
    static $cache = [];
    if (!isset($cache[$key])) {
        try {
            $cache[$key] = Database::getSetting($key, $default);
        } catch (Exception $e) {
            return $default;
        }
    }
    return $cache[$key] ?? $default;
}

function csrf(): string
{
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . ($_SESSION['csrf_token'] ?? '') . '">';
}

function csrfToken(): string
{
    return $_SESSION['csrf_token'] ?? '';
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function getFlash(): array
{
    $flash = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flash;
}

function logError(string $message): void
{
    $logDir = APP_ROOT . '/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0750, true);
    }
    $logFile = $logDir . '/app.log';
    $line = '[' . date('Y-m-d H:i:s') . '] ERROR: ' . $message . PHP_EOL;
    file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    error_log($message);
}

function logInfo(string $message): void
{
    $logFile = APP_ROOT . '/logs/app.log';
    $line = '[' . date('Y-m-d H:i:s') . '] INFO: ' . $message . PHP_EOL;
    file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}

function formatDuration(int $seconds): string
{
    $h = floor($seconds / 3600);
    $m = floor(($seconds % 3600) / 60);
    $s = $seconds % 60;
    if ($h > 0) return sprintf('%d:%02d:%02d', $h, $m, $s);
    return sprintf('%d:%02d', $m, $s);
}

function formatCurrency(float $amount, string $currency = 'EUR'): string
{
    return number_format($amount, 4, ',', '.') . ' ' . $currency;
}

function formatDate(?string $date): string
{
    if (!$date) return '-';
    $fmt = getSetting('date_format', 'd-m-Y');
    return date($fmt, strtotime($date));
}

function sanitize(string $input): string
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function generateInvoiceNumber(): string
{
    $prefix = getSetting('invoice_prefix', 'INV-');
    $next = (int) getSetting('invoice_next_number', 1001);
    Database::setSetting('invoice_next_number', $next + 1);
    return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
}

function asteriskReload(?string $module = null): bool
{
    try {
        $config = AsteriskConfig::fromSettings();
        return $config->reload($module);
    } catch (Exception $e) {
        logError('Reload failed: ' . $e->getMessage());
        return false;
    }
}

function json(mixed $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function paginate(array $items, int $perPage = 25): array
{
    $page = max(1, (int)($_GET['p'] ?? 1));
    $total = count($items);
    $pages = ceil($total / $perPage);
    $offset = ($page - 1) * $perPage;
    return [
        'items'   => array_slice($items, $offset, $perPage),
        'page'    => $page,
        'pages'   => $pages,
        'total'   => $total,
        'perPage' => $perPage,
    ];
}

function activeNav(string $page): string
{
    return ($_GET['page'] ?? 'dashboard') === $page ? ' active' : '';
}
