<?php
$pageTitle = 'Dashboard Pegawai - ' . Config::APP_NAME;
$activePage = 'dashboard';
require_once __DIR__ . '/../layouts/header.php';

// Format Hari dan Tanggal Bahasa Indonesia
$days = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
$months = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];

$hari = $days[date('l')];
$tgl = date('d');
$bln = $months[(int)date('m')];
$thn = date('Y');
$tanggalIndo = "{$hari}, {$tgl} {$bln} {$thn}";

$sudahMasuk = !empty($presensiHariIni['jam_masuk']);
$sudahKeluar = !empty($presensiHariIni['jam_pulang']);
?>

<div style="margin-bottom: 8px;">
    <h2 style="font-size: 1.4rem; font-weight: 700;">Home</h2>
</div>

<!-- Clock & Date Widget -->
<div class="clock-widget-wrapper">
    <div class="digital-clock" id="digital-clock">
        <?= date('H:i:s') ?>
    </div>
    <div class="current-date">
        <?= $tanggalIndo ?>
    </div>
</div>

<!-- 2 Presensi Action Cards -->
<div class="presensi-action-grid">
    <!-- Card 1: Presensi Masuk -->
    <div class="presensi-card">
        <div class="presensi-card-header">
            Presensi Masuk
        </div>
        <div class="presensi-card-body">
            <?php if (!$sudahMasuk): ?>
                <div class="presensi-time-info">
                    <?= $tgl ?> <?= $bln ?> <?= $thn ?>
                </div>
                <div class="presensi-time-clock">
                    <?= date('H:i:s') ?>
                </div>
                <a href="<?= Config::baseUrl('presensi/masuk') ?>" class="btn btn-blue" style="min-width: 140px; padding: 10px 28px;">
                    Masuk
                </a>
            <?php else: ?>
                <div style="margin-bottom: 8px; color: var(--text-main);">
                    <?= Icon::get('check-circle', 48) ?>
                </div>
                <div style="font-weight: 700; font-size: 1.1rem; color: var(--primary); margin-bottom: 4px;">
                    Presensi Masuk Berhasil
                </div>
                <div style="font-family: 'JetBrains Mono', monospace; font-size: 1.3rem; font-weight: 700; margin-bottom: 8px;">
                    <?= htmlspecialchars($presensiHariIni['jam_masuk']) ?> WIB
                </div>
                <span class="badge <?= ($presensiHariIni['status_kehadiran'] === 'terlambat') ? 'badge-danger' : 'badge-success' ?>">
                    <?= htmlspecialchars(ucfirst($presensiHariIni['status_kehadiran'])) ?> 
                    <?php if (!empty($presensiHariIni['total_terlambat']) && $presensiHariIni['total_terlambat'] !== 'Tepat Waktu'): ?>
                        (<?= htmlspecialchars($presensiHariIni['total_terlambat']) ?>)
                    <?php endif; ?>
                </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Card 2: Presensi Keluar -->
    <div class="presensi-card">
        <div class="presensi-card-header">
            Presensi Keluar
        </div>
        <div class="presensi-card-body">
            <?php if (!$sudahMasuk): ?>
                <!-- Tampilan terkunci jika belum absen masuk (sesuai Screenshot 7) -->
                <div class="presensi-disabled-state">
                    <div class="presensi-disabled-icon">
                        <?= Icon::get('x-circle', 36) ?>
                    </div>
                    <div style="font-size: 0.95rem; font-weight: 600; color: var(--text-muted); max-width: 260px;">
                        Silahkan melakukan presensi masuk terlebih dahulu!
                    </div>
                </div>
            <?php elseif (!$sudahKeluar): ?>
                <div class="presensi-time-info">
                    <?= $tgl ?> <?= $bln ?> <?= $thn ?>
                </div>
                <div class="presensi-time-clock">
                    <?= date('H:i:s') ?>
                </div>
                <a href="<?= Config::baseUrl('presensi/keluar') ?>" class="btn btn-danger" style="min-width: 140px; padding: 10px 28px;">
                    Keluar
                </a>
            <?php else: ?>
                <div style="margin-bottom: 8px; color: var(--text-main);">
                    <?= Icon::get('check-circle', 48) ?>
                </div>
                <div style="font-weight: 700; font-size: 1.1rem; color: var(--primary); margin-bottom: 4px;">
                    Presensi Keluar Berhasil
                </div>
                <div style="font-family: 'JetBrains Mono', monospace; font-size: 1.3rem; font-weight: 700; margin-bottom: 8px;">
                    <?= htmlspecialchars($presensiHariIni['jam_pulang']) ?> WIB
                </div>
                <span class="badge badge-info">
                    Total Kerja: <?= htmlspecialchars($presensiHariIni['total_jam_kerja'] ?? '-') ?>
                </span>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
