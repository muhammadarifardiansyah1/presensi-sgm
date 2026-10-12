<?php
/**
 * Application Global Configuration
 * Absensi Surya Gemilang Mojopahit (SGM)
 */

date_default_timezone_set('Asia/Jakarta');

// Start session with secure parameters & active garbage collection (mencegah penumpukan file session di hosting)
if (session_status() === PHP_SESSION_NONE) {
    // Pada environment Serverless (seperti Vercel / AWS Lambda), hanya folder /tmp yang dapat ditulis
    if (getenv('VERCEL') || !empty($_SERVER['VERCEL']) || getenv('AWS_LAMBDA_FUNCTION_NAME')) {
        if (is_dir('/tmp') && is_writable('/tmp')) {
            @session_save_path('/tmp');
        }
    }

    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.gc_probability', '1');
    ini_set('session.gc_divisor', '100');
    ini_set('session.gc_maxlifetime', '43200'); // 12 jam

    session_set_cookie_params([
        'lifetime' => 43200, // 12 jam
        'path'     => '/',
        'domain'   => '',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    @session_start();
}

class Config {
    const APP_NAME = 'Surya Gemilang Mojopahit';
    const APP_TITLE = 'Absensi Surya Gemilang Mojopahit';
    const APP_TAGLINE = 'Absen Foto & Lokasi GPS';
    const VERSION = '1.0.0';

    // Database Configuration (Default lokal Laragon, dapat di-override lewat ENV / TiDB Cloud Serverless)
    const DB_HOST = '127.0.0.1';
    const DB_PORT = '3306';
    const DB_USER = 'root';
    const DB_PASS = 'root';
    const DB_NAME = 'absensi_sgm';

    public static function getDbHost(): string { 
        return getenv('TIDB_HOST') ?: (getenv('DB_HOST') ?: self::DB_HOST); 
    }
    public static function getDbPort(): string { 
        return getenv('TIDB_PORT') ?: (getenv('DB_PORT') ?: self::DB_PORT); 
    }
    public static function getDbUser(): string { 
        return getenv('TIDB_USER') ?: (getenv('DB_USER') ?: self::DB_USER); 
    }
    public static function getDbPass(): string { 
        if (getenv('TIDB_PASSWORD') !== false && getenv('TIDB_PASSWORD') !== null) return getenv('TIDB_PASSWORD');
        if (getenv('TIDB_PASS') !== false && getenv('TIDB_PASS') !== null) return getenv('TIDB_PASS');
        if (getenv('DB_PASS') !== false && getenv('DB_PASS') !== null) return getenv('DB_PASS');
        return self::DB_PASS; 
    }
    public static function getDbName(): string { 
        return getenv('TIDB_DATABASE') ?: (getenv('DB_NAME') ?: self::DB_NAME); 
    }

    /**
     * Get Base URL dynamically (Mendukung SSL reguler, Cloudflare, dan reverse proxy hosting)
     */
    public static function baseUrl($path = '') {
        $isHttps = (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ||
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') ||
            (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on')
        );
        $protocol = $isHttps ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        // Find subfolder
        $scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        $base = rtrim($protocol . $host . $scriptName, '/');
        
        return $base . ($path ? '/' . ltrim($path, '/') : '');
    }

    const DEFAULT_SPREADSHEET_URL = 'https://docs.google.com/spreadsheets/d/1P1f94UXfxIgrZPOE-i0LLDi0s8MAeoXQ-p26Ls4UEzY/edit?usp=sharing';
    const DEFAULT_WEBHOOK_URL = '';

    /**
     * Get system setting from database with fallback
     */
    public static function getSetting(string $key, string $default = ''): string {
        try {
            require_once __DIR__ . '/Database.php';
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT `nilai` FROM `pengaturan_sistem` WHERE `kunci` = ? LIMIT 1");
            $stmt->execute([$key]);
            $val = $stmt->fetchColumn();
            return ($val !== false && $val !== null && $val !== '') ? (string)$val : $default;
        } catch (Exception $e) {
            return $default;
        }
    }

    /**
     * Set system setting in database
     */
    public static function setSetting(string $key, string $value): bool {
        try {
            require_once __DIR__ . '/Database.php';
            $db = Database::getConnection();
            $stmt = $db->prepare("INSERT INTO `pengaturan_sistem` (`kunci`, `nilai`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `nilai` = ?");
            return $stmt->execute([$key, $value, $value]);
        } catch (Exception $e) {
            return false;
        }
    }

    public static function getSpreadsheetUrl(): string {
        return self::getSetting('spreadsheet_url', self::DEFAULT_SPREADSHEET_URL);
    }

    public static function getSpreadsheetWebhookUrl(): string {
        return self::getSetting('spreadsheet_webhook_url', self::DEFAULT_WEBHOOK_URL);
    }

    /**
     * CSRF Token Generator & Validator
     */
    public static function generateCsrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validateCsrfToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
