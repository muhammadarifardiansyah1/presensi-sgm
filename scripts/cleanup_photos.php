<?php
/**
 * CLI & Cron Script: Pembersihan Berkas Foto Presensi Lampau (Data Retention)
 * Absensi Surya Gemilang Mojopahit (SGM)
 *
 * Penggunaan:
 *   php scripts/cleanup_photos.php [--days=180] [--dry-run] [--force]
 *
 * Contoh Cron Job Mingguan (Setiap Minggu Jam 02:00 Pagi):
 *   0 2 * * 0 php /path/to/absensi_sgm/scripts/cleanup_photos.php --days=180 --force >> /path/to/cleanup.log 2>&1
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo "Akses ditolak. Script ini hanya dapat dijalankan melalui Command Line Interface (CLI).\n";
    exit(1);
}

require_once __DIR__ . '/../app/Config/Config.php';
require_once __DIR__ . '/../app/Helpers/StorageManager.php';

// Parsing argumen CLI
$options = getopt('', ['days::', 'dry-run', 'force', 'help']);

if (isset($options['help'])) {
    echo "========================================================================\n";
    echo "   ABSENSI SGM - STORAGE RETENTION & PHOTO CLEANUP UTILITY\n";
    echo "========================================================================\n";
    echo "Opsi yang tersedia:\n";
    echo "  --days=<angka>   Batas usia foto dalam hari (default: 180 hari / 6 bulan).\n";
    echo "                   Minimal: 7 hari.\n";
    echo "  --dry-run        Hanya simulasi: menghitung berkas & ukuran tanpa menghapus.\n";
    echo "  --force          Eksekusi penghapusan berkas fisik dan update database.\n";
    echo "  --help           Menampilkan bantuan ini.\n\n";
    echo "Catatan Keamanan:\n";
    echo "  - Jika --force tidak diberikan, sistem otomatis berjalan dalam mode --dry-run.\n";
    echo "  - Data riwayat presensi (jam, tanggal, lokasi, status) TETAP UTUH di DB.\n";
    echo "========================================================================\n";
    exit(0);
}

$days = isset($options['days']) ? (int) $options['days'] : 180;
if ($days < 7) {
    echo "[ERROR] Parameter --days harus minimal 7 hari untuk keamanan data aktif.\n";
    exit(1);
}

$isForce = isset($options['force']);
$isDryRun = !$isForce; // Default adalah dry-run kecuali jika --force dispesifikasikan

$modeLabel = $isDryRun ? "SIMULASI (DRY-RUN)" : "EKSEKUSI NYATA (FORCE)";

echo "========================================================================\n";
echo "  ABSENSI SGM - PEMBERSIHAN BERKAS FOTO PRESENSI LAMPAU\n";
echo "========================================================================\n";
echo "Waktu Eksekusi   : " . date('Y-m-d H:i:s') . "\n";
echo "Mode Operasi     : {$modeLabel}\n";
echo "Ambang Batas     : Lebih tua dari {$days} hari (" . date('Y-m-d', strtotime("-{$days} days")) . ")\n";
echo "Direktori Target : " . StorageManager::getStorageDirectory() . "\n";
echo "------------------------------------------------------------------------\n";

try {
    $startTime = microtime(true);
    $result = StorageManager::cleanupOldPhotos($days, $isDryRun);
    $elapsed = round(microtime(true) - $startTime, 2);

    echo "[HASIL OPERASI]\n";
    echo "- Baris Presensi Diproses : " . number_format($result['records_affected']) . " data\n";
    echo "- Berkas Foto Ditemukan   : " . number_format($result['files_count']) . " berkas\n";
    echo "- Ruang Disk " . ($isDryRun ? "Dapat Dihemat" : "Berhasil Dihemat") . " : " . $result['mb_freed'] . " MB (" . number_format($result['bytes_freed']) . " bytes)\n";
    
    if (!$isDryRun) {
        echo "- Folder Kosong Dibersihkan: " . $result['pruned_dirs'] . " direktori\n";
        echo "✅ Berkas fisik foto lama berhasil dihapus dari server.\n";
        echo "✅ Kolom foto_masuk dan foto_keluar di database telah diatur ke NULL.\n";
        echo "✅ Data teks kehadiran (jam, tanggal, status) TETAP UTUH & AMAN.\n";
    } else {
        echo "\nℹ️  Ini adalah SIMULASI (Dry-Run). Tidak ada berkas fisik atau data DB yang diubah.\n";
        echo "   Untuk menghapus secara permanen, jalankan:\n";
        echo "   php scripts/cleanup_photos.php --days={$days} --force\n";
    }

    echo "Durasi Proses    : {$elapsed} detik\n";
    echo "========================================================================\n";
    exit(0);

} catch (Exception $e) {
    echo "[FATAL ERROR] " . $e->getMessage() . "\n";
    exit(1);
}
