<?php
/**
 * Script Penyelarasan Jabatan & Pengaturan Gaji
 * Menyelaraskan seluruh data pegawai dan mendaftarkan jabatan orphan ke Master Data & Pengaturan Gaji
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit("Akses Ditolak: Skrip penyelarasan jabatan hanya dapat dijalankan melalui Terminal (CLI).\n");
}

require_once __DIR__ . '/../app/Config/Database.php';
require_once __DIR__ . '/../app/Models/Jabatan.php';

$db = Database::getConnection();
$jabatanModel = new Jabatan();

echo "=== MEMULAI SINKRONISASI JABATAN & PENGATURAN GAJI ===" . PHP_EOL;

// 1. Ambil seluruh jabatan unik yang ada di tabel users
$stmt = $db->query("SELECT DISTINCT jabatan FROM users WHERE jabatan IS NOT NULL AND TRIM(jabatan) != ''");
$userJabatans = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "Ditemukan " . count($userJabatans) . " variasi jabatan di tabel users:" . PHP_EOL;

foreach ($userJabatans as $rawJabatan) {
    echo "• Memproses: '{$rawJabatan}' -> ";
    $res = $jabatanModel->resolveOrRegisterJabatan($rawJabatan);
    $resolvedName = $res['nama_jabatan'];
    $isNew = $res['is_new'] ? '(Didaftarkan Baru ke Master & Gaji)' : '(Cocok dengan Master Eksisting)';
    
    echo "'{$resolvedName}' {$isNew}" . PHP_EOL;
    
    // Update users jika ada perbedaan ejaan (misal 'Human Resources' -> 'Human Resource (HRD)')
    if ($rawJabatan !== $resolvedName) {
        $updateStmt = $db->prepare("UPDATE users SET jabatan = ? WHERE jabatan = ?");
        $updateStmt->execute([$resolvedName, $rawJabatan]);
        echo "  [UPDATE] Seluruh user berjabatan '{$rawJabatan}' diselaraskan ke '{$resolvedName}'." . PHP_EOL;
    }
}

// 2. Pastikan seluruh baris di tabel `jabatan` memiliki tarif di `pengaturan_gaji`
$allMasterJabatans = $jabatanModel->getAll();
echo PHP_EOL . "Memeriksa kelengkapan tarif di tabel pengaturan_gaji..." . PHP_EOL;

$stmtCekGaji = $db->prepare("SELECT COUNT(*) FROM pengaturan_gaji WHERE LOWER(TRIM(jabatan)) = LOWER(TRIM(?))");
$stmtInsertGaji = $db->prepare("
    INSERT INTO pengaturan_gaji 
    (jabatan, gaji_pokok, uang_makan_harian, tarif_lembur_per_jam, potongan_alpa_per_hari, tarif_pengganti_libur_per_hari)
    VALUES (?, 3500000.00, 25000.00, 25000.00, 100000.00, 52000.00)
");

foreach ($allMasterJabatans as $m) {
    $namaJabatan = trim($m['nama_jabatan']);
    $stmtCekGaji->execute([$namaJabatan]);
    if ((int)$stmtCekGaji->fetchColumn() === 0) {
        $stmtInsertGaji->execute([$namaJabatan]);
        echo "  [INSERT GAJI] Dibuatkan konfigurasi default tarif untuk: '{$namaJabatan}'" . PHP_EOL;
    }
}

echo PHP_EOL . "=== SINKRONISASI SELESAI DENGAN SUKSES! ===" . PHP_EOL;
