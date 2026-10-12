<?php
// Vercel Serverless Function entry point

// Auto-migration: jalankan HANYA SEKALI saat tabel belum ada
// Cek cepat via flag di pengaturan_sistem (bukan SHOW COLUMNS tiap request)
try {
    require_once __DIR__ . '/../app/Config/Config.php';
    require_once __DIR__ . '/../app/Config/Database.php';
    $db = Database::getConnection();
    
    // Cek ringan: apakah tabel pengaturan_sistem punya flag migration_done
    $checkStmt = $db->prepare("SELECT `nilai` FROM `pengaturan_sistem` WHERE `kunci` = 'migration_done' LIMIT 1");
    $checkStmt->execute();
    $migrationDone = $checkStmt->fetchColumn();
    
    if ($migrationDone !== 'yes') {
        // Belum pernah migration atau flag belum ada, jalankan migration
        define('ALLOW_WEB_MIGRATION', true);
        require_once __DIR__ . '/../migrate.php';
        
        // Set flag agar tidak jalan lagi
        try {
            $db->exec("INSERT INTO `pengaturan_sistem` (`kunci`, `nilai`) VALUES ('migration_done', 'yes') ON DUPLICATE KEY UPDATE `nilai` = 'yes'");
        } catch (PDOException $e) {
            // Silently continue
        }
    }
} catch (PDOException $e) {
    // Tabel belum ada sama sekali, jalankan migration penuh
    if (!defined('ALLOW_WEB_MIGRATION')) {
        define('ALLOW_WEB_MIGRATION', true);
    }
    require_once __DIR__ . '/../migrate.php';
    
    // Set flag setelah migration
    try {
        $db = Database::getConnection();
        $db->exec("INSERT INTO `pengaturan_sistem` (`kunci`, `nilai`) VALUES ('migration_done', 'yes') ON DUPLICATE KEY UPDATE `nilai` = 'yes'");
    } catch (PDOException $e2) {
        // Silently continue
    }
}

require_once __DIR__ . '/../index.php';
