<?php
/**
 * Database Singleton Connection
 * Absensi Surya Gading Mandiri (SGM)
 */

require_once __DIR__ . '/Config.php';

class Database {
    private static ?PDO $instance = null;

    private function __construct() {}
    private function __clone() {}

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $dsn = sprintf(
                "mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4",
                Config::getDbHost(),
                Config::getDbPort(),
                Config::getDbName()
            );

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+07:00'"
            ];

            try {
                self::$instance = new PDO($dsn, Config::getDbUser(), Config::getDbPass(), $options);
            } catch (PDOException $e) {
                die("Koneksi Database Gagal: " . htmlspecialchars($e->getMessage()));
            }
        }

        return self::$instance;
    }
}
