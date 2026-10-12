<?php
/**
 * Rate Limiter & Anti-Brute Force Protection Helper
 * Absensi Surya Gemilang Mojopahit (SGM)
 *
 * Provides ultra-lightweight, zero-overhead protection against:
 * - Brute force & credential stuffing on authentication endpoints
 * - High-frequency DoS / HTTP flood on sensitive POST actions
 * - Camera upload spam & form submission flood
 *
 * Zero external library dependencies, 100% native PHP & atomic MySQL operations.
 */

require_once __DIR__ . '/../Config/Database.php';

class RateLimiter {
    private static bool $tableChecked = false;

    /**
     * Dapatkan IP address klien yang akurat (mendukung Cloudflare & Reverse Proxy)
     */
    public static function getIp(): string {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', (string)$_SERVER[$header]);
                $clientIp = trim($ips[0]);
                if (filter_var($clientIp, FILTER_VALIDATE_IP)) {
                    return $clientIp;
                }
            }
        }

        return '127.0.0.1';
    }

    /**
     * Pastikan tabel rate_limits tersedia (self-healing / auto-provisioning)
     */
    private static function ensureTableExists(PDO $pdo): void {
        if (self::$tableChecked) {
            return;
        }

        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `rate_limits` (
                    `rate_key` VARCHAR(100) NOT NULL PRIMARY KEY,
                    `hits` INT NOT NULL DEFAULT 1,
                    `last_attempt` INT NOT NULL,
                    `expires_at` INT NOT NULL,
                    INDEX `idx_rate_limits_expires` (`expires_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
            self::$tableChecked = true;
        } catch (\Throwable $e) {
            // Fail open / silent log to avoid blocking application if DDL is restricted
            error_log("RateLimiter Table Init Error: " . $e->getMessage());
        }
    }

    /**
     * Cek apakah sebuah key telah melebihi batas maksimum percobaan
     */
    public static function tooManyAttempts(string $key, int $maxAttempts): bool {
        try {
            $pdo = Database::getConnection();
            self::ensureTableExists($pdo);

            $stmt = $pdo->prepare("
                SELECT `hits`, `expires_at`
                FROM `rate_limits`
                WHERE `rate_key` = :key AND `expires_at` > :now
                LIMIT 1
            ");
            $now = time();
            $stmt->execute(['key' => $key, 'now' => $now]);
            $record = $stmt->fetch();

            if ($record && (int)$record['hits'] >= $maxAttempts) {
                return true;
            }

            return false;
        } catch (\Throwable $e) {
            error_log("RateLimiter tooManyAttempts Error: " . $e->getMessage());
            return false; // Fail open
        }
    }

    /**
     * Tambahkan hit count pada sebuah key (Atomic Upsert)
     * Mengembalikan jumlah hit terkini
     */
    public static function hit(string $key, int $decaySeconds = 60): int {
        try {
            $pdo = Database::getConnection();
            self::ensureTableExists($pdo);

            // Garbage collection probabilitas 2% (1 in 50) untuk membersihkan record kadaluarsa
            if (mt_rand(1, 50) === 1) {
                self::cleanup($pdo);
            }

            $now = time();
            $expiresAt = $now + $decaySeconds;

            // Atomic MySQL Upsert
            $stmt = $pdo->prepare("
                INSERT INTO `rate_limits` (`rate_key`, `hits`, `last_attempt`, `expires_at`)
                VALUES (:key, 1, :now, :expires_at)
                ON DUPLICATE KEY UPDATE
                    hits = IF(expires_at < VALUES(last_attempt), 1, hits + 1),
                    expires_at = IF(expires_at < VALUES(last_attempt), VALUES(expires_at), expires_at),
                    last_attempt = VALUES(last_attempt)
            ");
            $stmt->execute([
                'key' => $key,
                'now' => $now,
                'expires_at' => $expiresAt
            ]);

            return self::attempts($key);
        } catch (\Throwable $e) {
            error_log("RateLimiter hit Error: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Dapatkan sisa percobaan yang diperbolehkan
     */
    public static function remainingAttempts(string $key, int $maxAttempts): int {
        $attempts = self::attempts($key);
        return max(0, $maxAttempts - $attempts);
    }

    /**
     * Dapatkan jumlah hit terkini pada key yang masih aktif
     */
    public static function attempts(string $key): int {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("
                SELECT `hits`
                FROM `rate_limits`
                WHERE `rate_key` = :key AND `expires_at` > :now
                LIMIT 1
            ");
            $stmt->execute(['key' => $key, 'now' => time()]);
            $hits = $stmt->fetchColumn();

            return $hits !== false ? (int)$hits : 0;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Hitung sisa detik hingga key bisa dicoba kembali
     */
    public static function availableIn(string $key): int {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("
                SELECT `expires_at`
                FROM `rate_limits`
                WHERE `rate_key` = :key
                LIMIT 1
            ");
            $stmt->execute(['key' => $key]);
            $expiresAt = $stmt->fetchColumn();

            if ($expiresAt !== false) {
                return max(0, (int)$expiresAt - time());
            }

            return 0;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Hapus key (misal saat login berhasil)
     */
    public static function clear(string $key): void {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("DELETE FROM `rate_limits` WHERE `rate_key` = :key");
            $stmt->execute(['key' => $key]);
        } catch (\Throwable $e) {
            error_log("RateLimiter clear Error: " . $e->getMessage());
        }
    }

    /**
     * Bersihkan data rate limit yang sudah kadaluarsa
     */
    public static function cleanup(?PDO $pdo = null): void {
        try {
            $pdo = $pdo ?: Database::getConnection();
            $stmt = $pdo->prepare("DELETE FROM `rate_limits` WHERE `expires_at` < :now");
            $stmt->execute(['now' => time()]);
        } catch (\Throwable $e) {
            // silent
        }
    }
}
