<?php
/**
 * Database Migration & Seeder Script for Absensi Surya Gading Mandiri (SGM)
 */

$host = '127.0.0.1';
$port = '3306';
$username = 'root';
$password = 'root';
$dbname = 'absensi_sgm';

try {
    // 1. Connect without database to create database if not exists
    $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database `$dbname` ready.\n";

    // 2. Connect to the database
    $pdo->exec("USE `$dbname`");

    // 2.5. Create table `jabatan`
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `jabatan` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `nama_jabatan` VARCHAR(100) NOT NULL UNIQUE,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "Table `jabatan` ready.\n";

    // Seed default jabatan if empty
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `jabatan`");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("
            INSERT INTO `jabatan` (`nama_jabatan`) VALUES
            ('Super Admin'),
            ('IT Support'),
            ('Staff Operasional'),
            ('Finance & Accounting'),
            ('Human Resource (HRD)')
        ");
        echo "Jabatan seeded.\n";
    }

    // 3. Create table `users`
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `nik` VARCHAR(30) UNIQUE NOT NULL,
            `nama` VARCHAR(100) NOT NULL,
            `username` VARCHAR(50) UNIQUE NOT NULL,
            `password` VARCHAR(255) NOT NULL,
            `role` ENUM('admin', 'pegawai') NOT NULL DEFAULT 'pegawai',
            `jabatan` VARCHAR(50) NOT NULL DEFAULT 'Pegawai',
            `foto_profil` VARCHAR(255) DEFAULT NULL,
            `status` ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "Table `users` ready.\n";

    // Add profile fields to `users` if missing
    $userCols = $pdo->query("SHOW COLUMNS FROM `users`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('jenis_kelamin', $userCols)) {
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `jenis_kelamin` ENUM('Laki-laki', 'Perempuan') NOT NULL DEFAULT 'Laki-laki' AFTER `nama`");
        echo "Column `jenis_kelamin` added to users.\n";
    }
    if (!in_array('alamat', $userCols)) {
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `alamat` TEXT DEFAULT NULL AFTER `jenis_kelamin`");
        echo "Column `alamat` added to users.\n";
    }
    if (!in_array('no_handphone', $userCols)) {
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `no_handphone` VARCHAR(30) DEFAULT NULL AFTER `alamat`");
        echo "Column `no_handphone` added to users.\n";
    }
    if (!in_array('lokasi_id', $userCols)) {
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `lokasi_id` INT DEFAULT NULL AFTER `jabatan`");
        echo "Column `lokasi_id` added to users.\n";
    }

    // 4. Create table `lokasi_presensi`
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `lokasi_presensi` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `nama_lokasi` VARCHAR(100) NOT NULL,
            `alamat_lokasi` TEXT NOT NULL,
            `tipe_lokasi` ENUM('Pusat', 'Cabang') NOT NULL DEFAULT 'Pusat',
            `latitude` DECIMAL(11, 8) NOT NULL,
            `longitude` DECIMAL(11, 8) NOT NULL,
            `radius` INT NOT NULL DEFAULT 500,
            `zona_waktu` VARCHAR(10) NOT NULL DEFAULT 'WIB',
            `jam_masuk` TIME NOT NULL DEFAULT '08:00:00',
            `jam_pulang` TIME NOT NULL DEFAULT '17:00:00',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "Table `lokasi_presensi` ready.\n";

    // 5. Create table `presensi`
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `presensi` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `lokasi_id` INT DEFAULT NULL,
            `tanggal` DATE NOT NULL,
            `jam_masuk` TIME DEFAULT NULL,
            `foto_masuk` VARCHAR(255) DEFAULT NULL,
            `lat_masuk` DECIMAL(11, 8) DEFAULT NULL,
            `long_masuk` DECIMAL(11, 8) DEFAULT NULL,
            `jarak_masuk` DECIMAL(10, 2) DEFAULT NULL,
            `jam_pulang` TIME DEFAULT NULL,
            `foto_keluar` VARCHAR(255) DEFAULT NULL,
            `lat_keluar` DECIMAL(11, 8) DEFAULT NULL,
            `long_keluar` DECIMAL(11, 8) DEFAULT NULL,
            `jarak_keluar` DECIMAL(10, 2) DEFAULT NULL,
            `total_jam_kerja` VARCHAR(50) DEFAULT NULL,
            `total_terlambat` VARCHAR(50) DEFAULT NULL,
            `status_kehadiran` ENUM('hadir', 'terlambat', 'alpa', 'izin', 'sakit', 'cuti') NOT NULL DEFAULT 'hadir',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (`user_id`),
            INDEX (`tanggal`),
            UNIQUE KEY `uq_presensi_user_tanggal` (`user_id`, `tanggal`),
            INDEX `idx_presensi_tanggal_status` (`tanggal`, `status_kehadiran`),
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "Table `presensi` ready.\n";

    // 6. Create table `ketidakhadiran`
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `ketidakhadiran` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `kategori` ENUM('izin', 'sakit', 'cuti') NOT NULL,
            `tanggal_mulai` DATE NOT NULL,
            `tanggal_selesai` DATE NOT NULL,
            `keterangan` TEXT NOT NULL,
            `dokumen_bukti` VARCHAR(255) DEFAULT NULL,
            `status_approval` ENUM('pending', 'disetujui', 'ditolak') NOT NULL DEFAULT 'pending',
            `catatan_admin` TEXT DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (`user_id`),
            INDEX `idx_ketidakhadiran_approval_dates` (`status_approval`, `tanggal_mulai`, `tanggal_selesai`),
            INDEX `idx_ketidakhadiran_user_status` (`user_id`, `status_approval`),
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "Table `ketidakhadiran` ready.\n";

    // 7. Seed Initial Data
    // Check if admin exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `users` WHERE `username` = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $hashedAdmin = password_hash('admin123', PASSWORD_BCRYPT);
        $hashedKasep = password_hash('kasep123', PASSWORD_BCRYPT);
        $hashedKisal = password_hash('kisal123', PASSWORD_BCRYPT);
        $hashedBudi  = password_hash('budi123', PASSWORD_BCRYPT);

        $pdo->prepare("
            INSERT INTO `users` (`nik`, `nama`, `username`, `password`, `role`, `jabatan`, `status`) VALUES
            ('ADM-0001', 'Administrator', 'admin', ?, 'admin', 'Super Admin', 'aktif'),
            ('PEG-0002', 'Kasep', 'kasep', ?, 'pegawai', 'IT Support', 'aktif'),
            ('PEG-0003', 'Kisal', 'kisal', ?, 'pegawai', 'Staff Operasional', 'aktif'),
            ('PEG-0004', 'Budi Santoso', 'budi', ?, 'pegawai', 'Finance & Accounting', 'aktif')
        ")->execute([$hashedAdmin, $hashedKasep, $hashedKisal, $hashedBudi]);
        echo "Users seeded.\n";
    }

    // Check if locations exist
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `lokasi_presensi`");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("
            INSERT INTO `lokasi_presensi` (`nama_lokasi`, `alamat_lokasi`, `tipe_lokasi`, `latitude`, `longitude`, `radius`, `zona_waktu`, `jam_masuk`, `jam_pulang`) VALUES
            ('Kantor Pusat Surya Gading Mandiri', 'Jl. Jenderal Sudirman No. 45, Jakarta Pusat', 'Pusat', -6.20876340, 106.84559900, 500, 'WIB', '08:00:00', '17:00:00'),
            ('Kantor Cabang Palembang', 'Jl. R. Soekamto No. 12, Palembang', 'Cabang', -2.97607350, 104.75783300, 1000, 'WIB', '08:00:00', '17:00:00'),
            ('Kantor Utama Demo SGM', 'Konoha Barat Daya', 'Pusat', -4.01771108, 103.23932119, 20000, 'WIB', '08:00:00', '17:00:00')
        ");
        echo "Lokasi presensi seeded.\n";
    }

    // 8. Add jam_lembur & keterangan_admin to `presensi` if not exist
    $presensiCols = $pdo->query("SHOW COLUMNS FROM `presensi`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('jam_lembur', $presensiCols)) {
        $pdo->exec("ALTER TABLE `presensi` ADD COLUMN `jam_lembur` DECIMAL(5, 2) NOT NULL DEFAULT 0.00 AFTER `total_terlambat`");
        echo "Column `jam_lembur` added to presensi.\n";
    }
    if (!in_array('keterangan_admin', $presensiCols)) {
        $pdo->exec("ALTER TABLE `presensi` ADD COLUMN `keterangan_admin` TEXT DEFAULT NULL AFTER `status_kehadiran`");
        echo "Column `keterangan_admin` added to presensi.\n";
    }

    // 9. Create table `pengaturan_gaji` (Tarif Gaji Pokok, Lembur, & Potongan per Jabatan)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `pengaturan_gaji` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `jabatan` VARCHAR(100) NOT NULL UNIQUE,
            `gaji_pokok` DECIMAL(12, 2) NOT NULL DEFAULT 3500000.00,
            `uang_makan_harian` DECIMAL(12, 2) NOT NULL DEFAULT 25000.00,
            `tarif_lembur_per_jam` DECIMAL(12, 2) NOT NULL DEFAULT 25000.00,
            `potongan_alpa_per_hari` DECIMAL(12, 2) NOT NULL DEFAULT 100000.00,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "Table `pengaturan_gaji` ready.\n";

    // Seed default pengaturan_gaji
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `pengaturan_gaji`");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("
            INSERT INTO `pengaturan_gaji` (`jabatan`, `gaji_pokok`, `uang_makan_harian`, `tarif_lembur_per_jam`, `potongan_alpa_per_hari`) VALUES
            ('Super Admin', 5500000.00, 35000.00, 35000.00, 150000.00),
            ('IT Support', 4500000.00, 25000.00, 30000.00, 120000.00),
            ('Finance & Accounting', 4800000.00, 30000.00, 30000.00, 120000.00),
            ('Human Resource (HRD)', 4800000.00, 30000.00, 30000.00, 120000.00),
            ('Staff Operasional', 3800000.00, 25000.00, 25000.00, 100000.00),
            ('Pegawai', 3500000.00, 25000.00, 25000.00, 100000.00)
        ");
        echo "Pengaturan gaji seeded.\n";
    }

    // 10. Create table `pengaturan_sistem` (Google Spreadsheet URL & Webhook URL)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `pengaturan_sistem` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `kunci` VARCHAR(100) NOT NULL UNIQUE,
            `nilai` TEXT DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "Table `pengaturan_sistem` ready.\n";

    // Seed spreadsheet URL
    $stmt = $pdo->prepare("INSERT IGNORE INTO `pengaturan_sistem` (`kunci`, `nilai`) VALUES 
        ('spreadsheet_url', 'https://docs.google.com/spreadsheets/d/1P1f94UXfxIgrZPOE-i0LLDi0s8MAeoXQ-p26Ls4UEzY/edit?usp=sharing'),
        ('spreadsheet_webhook_url', '')
    ");
    $stmt->execute();

    echo "Migration completed successfully!\n";
} catch (Exception $e) {
    echo "Migration Failed: " . $e->getMessage() . "\n";
    exit(1);
}
