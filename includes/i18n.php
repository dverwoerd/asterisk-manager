<?php
// ============================================================
// i18n - Internationalization / Translation
// ============================================================

class I18n
{
    private static array $translations = [];
    private static string $currentLang = 'en';

    public static function load(string $lang): void
    {
        self::$currentLang = $lang;
        $file = APP_ROOT . "/lang/$lang.php";
        if (file_exists($file)) {
            self::$translations = require $file;
        } else {
            // Fallback to English
            $fallback = APP_ROOT . '/lang/en.php';
            if (file_exists($fallback)) {
                self::$translations = require $fallback;
            }
        }
    }

    public static function t(string $key, array $replace = []): string
    {
        $text = self::$translations[$key] ?? $key;
        foreach ($replace as $k => $v) {
            $text = str_replace(':' . $k, $v, $text);
        }
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    public static function availableLanguages(): array
    {
        $langs = [];
        foreach (glob(APP_ROOT . '/lang/*.php') as $file) {
            $code = basename($file, '.php');
            $data = require $file;
            $langs[$code] = $data['_language_name'] ?? strtoupper($code);
        }
        return $langs;
    }

    public static function currentLang(): string
    {
        return self::$currentLang;
    }
}

// Global shorthand
function t(string $key, array $replace = []): string
{
    return I18n::t($key, $replace);
}
