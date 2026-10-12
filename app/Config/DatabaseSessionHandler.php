<?php
/**
 * Database Session Handler
 * Menyimpan session di database MySQL agar persistent di Vercel Serverless
 * (Vercel ephemeral: /tmp tidak dipertahankan antar request / container)
 */

require_once __DIR__ . '/Database.php';

class DatabaseSessionHandler implements SessionHandlerInterface {
    private ?PDO $db = null;

    private function getDb(): PDO {
        if ($this->db === null) {
            $this->db = Database::getConnection();
        }
        return $this->db;
    }

    public function open(string $path, string $name): bool {
        return true;
    }

    public function close(): bool {
        return true;
    }

    public function read(string $id): string|false {
        try {
            $stmt = $this->getDb()->prepare(
                "SELECT `data` FROM `sessions` WHERE `id` = ? AND `expires_at` > ? LIMIT 1"
            );
            $stmt->execute([$id, time()]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? $row['data'] : '';
        } catch (PDOException $e) {
            return '';
        }
    }

    public function write(string $id, string $data): bool {
        try {
            $expiresAt = time() + 43200; // 12 jam
            $stmt = $this->getDb()->prepare(
                "INSERT INTO `sessions` (`id`, `data`, `expires_at`) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE `data` = VALUES(`data`), `expires_at` = VALUES(`expires_at`)"
            );
            return $stmt->execute([$id, $data, $expiresAt]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function destroy(string $id): bool {
        try {
            $stmt = $this->getDb()->prepare("DELETE FROM `sessions` WHERE `id` = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function gc(int $max_lifetime): int|false {
        try {
            $stmt = $this->getDb()->prepare("DELETE FROM `sessions` WHERE `expires_at` < ?");
            $stmt->execute([time()]);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Buat tabel sessions jika belum ada
     */
    public static function ensureTable(): void {
        try {
            $db = Database::getConnection();
            $db->exec("
                CREATE TABLE IF NOT EXISTS `sessions` (
                    `id` VARCHAR(128) NOT NULL PRIMARY KEY,
                    `data` MEDIUMTEXT NOT NULL,
                    `expires_at` INT UNSIGNED NOT NULL,
                    INDEX `idx_sessions_expires` (`expires_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (PDOException $e) {
            // Silently fail — will use file session as fallback
        }
    }
}
