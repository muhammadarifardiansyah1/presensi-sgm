<?php
/**
 * Storage Manager Helper
 * Absensi Surya Gading Mandiri (SGM)
 *
 * Mengelola pemantauan kapasitas penyimpanan foto presensi,
 * kalkulasi statistik disk, dan eksekusi retensi berkas lama secara aman.
 */

require_once __DIR__ . '/../Config/Database.php';

class StorageManager {

    /**
     * Dapatkan path direktori absolut untuk penyimpanan foto presensi
     */
    public static function getStorageDirectory(): string {
        $path = __DIR__ . '/../../assets/images/uploads/presensi/';
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        return realpath($path) ?: $path;
    }

    /**
     * Dapatkan statistik kapasitas penyimpanan saat ini
     */
    public static function getStats(): array {
        $baseDir = self::getStorageDirectory();
        $totalFiles = 0;
        $totalBytes = 0;

        if (is_dir($baseDir)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($baseDir, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $item) {
                if ($item->isFile()) {
                    // Hanya hitung berkas gambar .jpg / .jpeg / .png
                    $ext = strtolower($item->getExtension());
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                        $totalFiles++;
                        $totalBytes += $item->getSize();
                    }
                }
            }
        }

        // Statistik dari Database
        $pdo = Database::getConnection();
        
        // Total data presensi
        $totalPresensi = (int) $pdo->query("SELECT COUNT(*) FROM `presensi`")->fetchColumn();
        
        // Total foto masuk & keluar terdata
        $totalFotoMasuk = (int) $pdo->query("SELECT COUNT(*) FROM `presensi` WHERE `foto_masuk` IS NOT NULL AND `foto_masuk` != ''")->fetchColumn();
        $totalFotoKeluar = (int) $pdo->query("SELECT COUNT(*) FROM `presensi` WHERE `foto_keluar` IS NOT NULL AND `foto_keluar` != ''")->fetchColumn();

        // Breakdown usia berkas berdasarkan tanggal presensi
        $breakdown = [];
        $thresholds = [
            '30_hari'  => 30,
            '90_hari'  => 90,
            '180_hari' => 180,
            '365_hari' => 365,
        ];

        foreach ($thresholds as $key => $days) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM `presensi` 
                WHERE `tanggal` < DATE_SUB(CURDATE(), INTERVAL ? DAY)
                  AND (`foto_masuk` IS NOT NULL OR `foto_keluar` IS NOT NULL)
            ");
            $stmt->execute([$days]);
            $breakdown[$key] = (int) $stmt->fetchColumn();
        }

        return [
            'storage_directory'   => $baseDir,
            'disk_total_files'    => $totalFiles,
            'disk_total_bytes'    => $totalBytes,
            'disk_total_mb'       => round($totalBytes / (1024 * 1024), 2),
            'disk_total_gb'       => round($totalBytes / (1024 * 1024 * 1024), 3),
            'db_total_presensi'   => $totalPresensi,
            'db_foto_masuk'       => $totalFotoMasuk,
            'db_foto_keluar'      => $totalFotoKeluar,
            'breakdown_by_age'    => $breakdown,
        ];
    }

    /**
     * Bersihkan foto presensi lampau berdasarkan umur tanggal (Data Retention Policy)
     *
     * @param int $daysOlderThan Hapus foto dengan presensi lebih tua dari X hari (minimal 7 hari untuk keamanan)
     * @param bool $dryRun Jika true, hanya simulasi hitung tanpa menghapus file dan DB
     * @return array Hasil eksekusi pembersihan
     */
    public static function cleanupOldPhotos(int $daysOlderThan = 180, bool $dryRun = true): array {
        // Proteksi pengaman: minimal 7 hari untuk mencegah terhapusnya foto hari ini atau minggu ini
        if ($daysOlderThan < 7) {
            throw new InvalidArgumentException("Batas retensi minimal adalah 7 hari.");
        }

        $baseDir = self::getStorageDirectory();
        $pdo = Database::getConnection();

        $targetDate = date('Y-m-d', strtotime("-{$daysOlderThan} days"));

        $filesCount = 0;
        $bytesFreed = 0;
        $recordsAffected = 0;
        $deletedFilePaths = [];

        // Query bertahap (chunking) untuk mencegah beban memori pada dataset puluhan ribu baris
        $offset = 0;
        $batchSize = 500;

        while (true) {
            $stmt = $pdo->prepare("
                SELECT `id`, `tanggal`, `foto_masuk`, `foto_keluar` 
                FROM `presensi` 
                WHERE `tanggal` < ? 
                  AND (`foto_masuk` IS NOT NULL OR `foto_keluar` IS NOT NULL)
                ORDER BY `id` ASC 
                LIMIT ? OFFSET ?
            ");
            $stmt->bindValue(1, $targetDate, PDO::PARAM_STR);
            $stmt->bindValue(2, $batchSize, PDO::PARAM_INT);
            $stmt->bindValue(3, $offset, PDO::PARAM_INT);
            $stmt->execute();

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($rows)) {
                break;
            }

            foreach ($rows as $row) {
                $hasUpdate = false;
                $updateFields = [];

                // 1. Cek Foto Masuk
                if (!empty($row['foto_masuk'])) {
                    $cleanedRelPath = ltrim(str_replace(['../', '..\\'], '', $row['foto_masuk']), '/\\');
                    $fullPath = $baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $cleanedRelPath);

                    // Validasi Path Traversal Security: pastikan file berada di dalam $baseDir
                    if (self::isPathWithinDir($fullPath, $baseDir) && file_exists($fullPath) && is_file($fullPath)) {
                        $size = filesize($fullPath) ?: 0;
                        $filesCount++;
                        $bytesFreed += $size;
                        $deletedFilePaths[] = $row['foto_masuk'];

                        if (!$dryRun) {
                            @unlink($fullPath);
                        }
                    }
                    $updateFields['foto_masuk'] = null;
                    $hasUpdate = true;
                }

                // 2. Cek Foto Keluar
                if (!empty($row['foto_keluar'])) {
                    $cleanedRelPath = ltrim(str_replace(['../', '..\\'], '', $row['foto_keluar']), '/\\');
                    $fullPath = $baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $cleanedRelPath);

                    if (self::isPathWithinDir($fullPath, $baseDir) && file_exists($fullPath) && is_file($fullPath)) {
                        $size = filesize($fullPath) ?: 0;
                        $filesCount++;
                        $bytesFreed += $size;
                        $deletedFilePaths[] = $row['foto_keluar'];

                        if (!$dryRun) {
                            @unlink($fullPath);
                        }
                    }
                    $updateFields['foto_keluar'] = null;
                    $hasUpdate = true;
                }

                // Update database jika bukan mode simulasi (dry run)
                if ($hasUpdate && !$dryRun) {
                    $updStmt = $pdo->prepare("
                        UPDATE `presensi` 
                        SET `foto_masuk` = NULL, `foto_keluar` = NULL 
                        WHERE `id` = ?
                    ");
                    $updStmt->execute([$row['id']]);
                    $recordsAffected++;
                } else if ($hasUpdate) {
                    $recordsAffected++;
                }
            }

            // Jika dalam mode hapus nyata, baris yang telah di-NULL tidak akan muncul lagi pada query berikutnya,
            // sehingga offset tidak perlu dinaikkan. Sebaliknya pada dryRun, naikkan offset.
            if ($dryRun) {
                $offset += $batchSize;
            }
        }

        // Hapus subfolder kosong (misal: 2024/05/ yang seluruh fotonya telah terhapus)
        $prunedDirs = 0;
        if (!$dryRun) {
            $prunedDirs = self::pruneEmptyDirectories($baseDir);
        }

        return [
            'success'          => true,
            'dry_run'          => $dryRun,
            'days_threshold'   => $daysOlderThan,
            'cutoff_date'      => $targetDate,
            'records_affected' => $recordsAffected,
            'files_count'      => $filesCount,
            'bytes_freed'      => $bytesFreed,
            'mb_freed'         => round($bytesFreed / (1024 * 1024), 2),
            'pruned_dirs'      => $prunedDirs,
            'sample_files'     => array_slice($deletedFilePaths, 0, 10),
        ];
    }

    /**
     * Validasi keamanan path traversal
     */
    private static function isPathWithinDir(string $path, string $parentDir): bool {
        $realParent = realpath($parentDir);
        if (!$realParent) {
            return false;
        }

        $realPath = realpath($path);
        // Jika file belum ter-resolve oleh realpath (misal baru dicek), cek normalisasinya
        if (!$realPath) {
            $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
            return (strpos($normalized, $realParent) === 0);
        }

        return (strpos($realPath, $realParent) === 0);
    }

    /**
     * Hapus direktori kosong secara rekursif di dalam folder presensi
     */
    private static function pruneEmptyDirectories(string $dir): int {
        $pruned = 0;
        if (!is_dir($dir)) {
            return 0;
        }

        $items = scandir($dir);
        if ($items === false) {
            return 0;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $pruned += self::pruneEmptyDirectories($path);
                
                // Cek apakah direktori sudah kosong setelah subdirektorinya dihapus
                $remaining = scandir($path);
                if ($remaining !== false && count($remaining) <= 2) { // Hanya . dan ..
                    @rmdir($path);
                    $pruned++;
                }
            }
        }

        return $pruned;
    }
}
