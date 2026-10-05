<?php
$pageTitle = 'Dashboard Admin - ' . Config::APP_NAME;
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

<!-- 4 KPI Cards -->
<div class="kpi-grid">
    <!-- Card 1: Total Pegawai Aktif -->
    <a href="<?= Config::baseUrl('pegawai') ?>" class="kpi-card">
        <div class="kpi-icon">
            <?= Icon::get('users', 24) ?>
        </div>
        <div class="kpi-info">
            <h4>Total Pegawai Aktif</h4>
            <div class="kpi-value"><?= (int)$totalPegawai ?> <span class="kpi-label">pegawai</span></div>
        </div>
    </a>

    <!-- Card 2: Jumlah Hadir -->
    <a href="<?= Config::baseUrl('laporan/harian') ?>" class="kpi-card">
        <div class="kpi-icon">
            <?= Icon::get('user-check', 24) ?>
        </div>
        <div class="kpi-info">
            <h4>Jumlah Hadir</h4>
            <div class="kpi-value"><?= (int)$jumlahHadir ?> <span class="kpi-label">pegawai</span></div>
        </div>
    </a>

    <!-- Card 3: Jumlah Alpa -->
    <a href="<?= Config::baseUrl('laporan/harian') ?>" class="kpi-card">
        <div class="kpi-icon">
            <?= Icon::get('user-x', 24) ?>
        </div>
        <div class="kpi-info">
            <h4>Jumlah Alpa</h4>
            <div class="kpi-value"><?= (int)$jumlahAlpa ?> <span class="kpi-label">pegawai</span></div>
        </div>
    </a>

    <!-- Card 4: Jumlah Sakit, Izin & Cuti -->
    <a href="<?= Config::baseUrl('ketidakhadiran') ?>" class="kpi-card">
        <div class="kpi-icon">
            <?= Icon::get('clipboard', 24) ?>
        </div>
        <div class="kpi-info">
            <h4>Jumlah Sakit, Izin & Cuti</h4>
            <div class="kpi-value"><?= (int)$jumlahSakitIzinCuti ?> <span class="kpi-label">pegawai</span></div>
        </div>
    </a>
</div>

<!-- Quick Links & System Status -->
<div style="display:grid; grid-template-columns: 2fr 1fr; gap: 20px;">
    <div class="table-card">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
            <h3 style="font-size: 1rem; font-weight: 700;">Aksi Cepat Presensi Karyawan</h3>
            <span class="badge badge-success">Sistem Online</span>
        </div>
        <div style="padding: 20px; display:flex; gap: 12px; flex-wrap:wrap;">
            <a href="<?= Config::baseUrl('gaji') ?>" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:6px;">
                <?= Icon::get('wallet', 16) ?> <span>Penggajian &amp; Slip Gaji</span>
            </a>
            <a href="<?= Config::baseUrl('laporan/harian') ?>" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:6px;">
                <?= Icon::get('plus', 16) ?> <span>Input Presensi Manual</span>
            </a>
            <a href="<?= Config::baseUrl('laporan/bulanan') ?>" class="btn btn-blue">
                <?= Icon::get('bar-chart', 16) ?> <span>Rekap Presensi Bulanan</span>
            </a>
            <a href="<?= Config::baseUrl('ketidakhadiran') ?>" class="btn btn-secondary">
                <?= Icon::get('clipboard-check', 16) ?> <span>Verifikasi Ketidakhadiran</span>
            </a>
            <a href="<?= Config::baseUrl('pegawai/tambah') ?>" class="btn btn-secondary">
                <?= Icon::get('user-plus', 16) ?> <span>Tambah Pegawai Baru</span>
            </a>
            <a href="<?= Config::baseUrl('master/lokasi') ?>" class="btn btn-secondary">
                <?= Icon::get('map-pin', 16) ?> <span>Kelola Titik Lokasi</span>
            </a>
        </div>
    </div>

    <div class="form-card" style="padding: 20px;">
        <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 12px; display:flex; align-items:center; gap:8px;">
            <?= Icon::get('shield', 18) ?> <span>Status Keamanan SGM</span>
        </h3>
        <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.6;">
            • <strong>Geofencing:</strong> Haversine Radius Active<br>
            • <strong>Verifikasi Wajah:</strong> Live Webcam Required<br>
            • <strong>Anti-Spoofing:</strong> Server-Side Validation
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
