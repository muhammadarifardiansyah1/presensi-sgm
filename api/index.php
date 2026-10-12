<?php
// Vercel Serverless Function entry point

// Vercel ephemeral: session harus disimpan di /tmp sebelum session_start() dipanggil di Config.php
if (getenv('VERCEL') || getenv('VERCEL_ENV')) {
    ini_set('session.save_path', '/tmp');
}

// Auto-migration: jalankan migrate.php saat pertama kali deploy jika kolom belum ada
try {
    require_once __DIR__ . '/../app/Config/Config.php';
    require_once __DIR__ . '/../app/Config/Database.php';
    $db = Database::getConnection();
    $checkStmt = $db->query("SHOW COLUMNS FROM `users` LIKE 'session_id'");
    if ($checkStmt->rowCount() === 0) {
        // Kolom belum ada, jalankan migration (izinkan via web)
        define('ALLOW_WEB_MIGRATION', true);
        require_once __DIR__ . '/../migrate.php';
    }
} catch (PDOException $e) {
    // Tabel mungkin belum ada sama sekali, jalankan migration penuh
    if (!defined('ALLOW_WEB_MIGRATION')) {
        define('ALLOW_WEB_MIGRATION', true);
    }
    require_once __DIR__ . '/../migrate.php';
}

require_once __DIR__ . '/../index.php';
