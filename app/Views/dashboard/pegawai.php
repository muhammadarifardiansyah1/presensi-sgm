<?php
$pageTitle = 'Home - ' . Config::APP_NAME;
$activePage = 'dashboard';
require_once __DIR__ . '/../layouts/header.php';

// Format Hari dan Tanggal Bahasa Indonesia
$days = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
$months = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];

$hari = $days[date('l')] ?? date('l');
$tgl = date('d');
$bln = $months[(int)date('m')] ?? date('F');
$thn = date('Y');
$tanggalIndo = "{$hari}, {$tgl} {$bln} {$thn}";

// Sapaan Berbasis Waktu
$jamNow = (int)date('H');
if ($jamNow >= 4 && $jamNow < 11) {
    $salamWaktu = 'Semangat Pagi';
} elseif ($jamNow >= 11 && $jamNow < 15) {
    $salamWaktu = 'Semangat Siang';
} elseif ($jamNow >= 15 && $jamNow < 18) {
    $salamWaktu = 'Semangat Sore';
} else {
    $salamWaktu = 'Semangat Malam';
}

$sudahMasuk = !empty($presensiHariIni['jam_masuk']);
$sudahKeluar = !empty($presensiHariIni['jam_pulang']);

// Foto Profil & Inisial
$fotoUrl = '';
if (!empty($pegawaiProfile['foto_profil'])) {
    $fotoFile = $pegawaiProfile['foto_profil'];
    $fotoPath = __DIR__ . '/../../../assets/images/uploads/profil/' . $fotoFile;
    if (file_exists($fotoPath)) {
        $fotoUrl = Config::baseUrl('assets/images/uploads/profil/' . $fotoFile) . '?v=' . filemtime($fotoPath);
    }
}
$inisial = mb_strtoupper(mb_substr($pegawaiProfile['nama'] ?? 'P', 0, 1));
$namaLokasi = $pegawaiProfile['nama_lokasi'] ?? 'Kantor Pusat SGM';
$jamMasukLokasi = !empty($pegawaiProfile['jam_masuk_lokasi']) ? substr($pegawaiProfile['jam_masuk_lokasi'], 0, 5) : '08:00';
$jamPulangLokasi = !empty($pegawaiProfile['jam_pulang_lokasi']) ? substr($pegawaiProfile['jam_pulang_lokasi'], 0, 5) : '17:00';

// Perhitungan SVG Donut Chart
$circumference = 226.2; // 2 * PI * 36
$totalHadir = (int)($statBulanan['total_hadir'] ?? 0);
$tepatWaktu = (int)($statBulanan['total_tepat_waktu'] ?? 0);
$terlambat = (int)($statBulanan['total_terlambat'] ?? 0);
$izinDisetujui = (int)($statIzin['total_disetujui'] ?? 0);
$totalEvents = max(1, $totalHadir + $izinDisetujui);

$pctTepatWaktu = $totalHadir > 0 ? ($tepatWaktu / $totalEvents) : 0;
$pctTerlambat  = $totalHadir > 0 ? ($terlambat / $totalEvents) : 0;
$pctIzin       = $izinDisetujui > 0 ? ($izinDisetujui / $totalEvents) : 0;

$dashTepat = round($pctTepatWaktu * $circumference, 1);
$dashTelat = round($pctTerlambat * $circumference, 1);
$dashIzin  = round($pctIzin * $circumference, 1);
$offsetTelat = -$dashTepat;
$offsetIzin  = -($dashTepat + $dashTelat);
?>

<!-- 1. Hero Welcome Banner (Inspirasi BNNP Jatim) -->
<div class="pegawai-hero-banner">
    <div class="hero-profile-group">
        <div class="hero-avatar-wrapper">
            <?php if (!empty($fotoUrl)): ?>
                <img src="<?= $fotoUrl ?>" alt="<?= htmlspecialchars($pegawaiProfile['nama']) ?>" class="hero-avatar-img">
            <?php else: ?>
                <span class="hero-avatar-initials"><?= $inisial ?></span>
            <?php endif; ?>
        </div>
        <div class="hero-text-info">
            <h2><?= $salamWaktu ?>, <?= htmlspecialchars($pegawaiProfile['nama']) ?>!</h2>
            <p>
                Jangan lupa lakukan presensi dan catat progres harian Anda hari ini. &bull; 
                <strong><?= htmlspecialchars($namaLokasi) ?></strong> (<?= $jamMasukLokasi ?> - <?= $jamPulangLokasi ?> WIB)
            </p>
        </div>
    </div>
    <div class="hero-action-buttons">
        <?php if (!$sudahMasuk): ?>
            <a href="<?= Config::baseUrl('presensi/masuk') ?>" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px;">
                <?= Icon::get('camera', 18) ?> Presensi Masuk
            </a>
        <?php elseif (!$sudahKeluar): ?>
            <a href="<?= Config::baseUrl('presensi/keluar') ?>" class="btn btn-danger" style="display: inline-flex; align-items: center; gap: 8px;">
                <?= Icon::get('camera', 18) ?> Presensi Pulang
            </a>
        <?php else: ?>
            <a href="<?= Config::baseUrl('riwayat') ?>" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px;">
                <?= Icon::get('file-text', 18) ?> Rekap Riwayat
            </a>
        <?php endif; ?>
        <a href="<?= Config::baseUrl('profil') ?>" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 8px;">
            <?= Icon::get('user', 18) ?> Profil
        </a>
    </div>
</div>

<div class="pegawai-dashboard-body">
<?php if (!empty($penugasanHariIni)): ?>
    <!-- Banner Notifikasi Penugasan Lembur Hari Ini -->
    <div class="alert alert-warning spl-banner-today" style="margin-bottom: 24px; display: flex; align-items: flex-start; justify-content: space-between; gap: 14px; background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: var(--radius-md); padding: 14px 18px;">
        <div style="display: flex; align-items: flex-start; gap: 12px;">
            <div style="color: #f59e0b; line-height: 1; margin-top: 2px;">
                <?= Icon::get('clock', 22) ?>
            </div>
            <div>
                <strong style="color: var(--text-main); font-size: 0.95rem; display: block; margin-bottom: 4px;">
                    📢 Surat Perintah Lembur Resmi: <?= htmlspecialchars($penugasanHariIni['nomor_sp_lembur']) ?>
                </strong>
                <p style="margin: 0; font-size: 0.85rem; color: var(--text-muted); line-height: 1.5;">
                    Anda resmi ditugaskan lembur hari ini pukul <strong style="color: var(--text-main);"><?= substr($penugasanHariIni['jam_mulai'], 0, 5) ?> - <?= substr($penugasanHariIni['jam_selesai'], 0, 5) ?> WIB</strong> (Maksimal <?= (float)$penugasanHariIni['maksimal_jam'] ?> Jam).
                    <br>Uraian Tugas: <em>"<?= htmlspecialchars($penugasanHariIni['keterangan']) ?>"</em>
                </p>
            </div>
        </div>
        <span class="badge badge-warning" style="white-space: nowrap; font-size: 0.78rem;">SPL Hari Ini</span>
    </div>
<?php endif; ?>

<!-- 2. Empat Kartu Metrik KPI Kehadiran -->
<div class="pegawai-kpi-grid">
    <!-- Card 1: Total Jam Kerja -->
    <div class="pegawai-kpi-card kpi-stripe-blue">
        <div class="kpi-card-details">
            <span class="kpi-tag-title">Total Jam Kerja</span>
            <div class="kpi-main-number"><?= htmlspecialchars($statBulanan['total_jam_kerja_format']) ?></div>
            <span class="kpi-sub-label">Akumulasi <?= $bln ?> <?= $thn ?></span>
        </div>
        <div class="kpi-card-icon-badge kpi-badge-blue">
            <?= Icon::get('clock', 22) ?>
        </div>
    </div>

    <!-- Card 2: Tingkat Presensi -->
    <div class="pegawai-kpi-card kpi-stripe-emerald">
        <div class="kpi-card-details">
            <span class="kpi-tag-title">Tingkat Presensi</span>
            <div class="kpi-main-number"><?= (int)$statBulanan['persentase_kehadiran'] ?>%</div>
            <span class="kpi-sub-label"><?= (int)$statBulanan['total_hadir'] ?> Hari hadir bulan ini</span>
        </div>
        <div class="kpi-card-icon-badge kpi-badge-emerald">
            <?= Icon::get('user-check', 22) ?>
        </div>
    </div>

    <!-- Card 3: Jam Lembur -->
    <div class="pegawai-kpi-card kpi-stripe-violet">
        <div class="kpi-card-details">
            <span class="kpi-tag-title">Jam Lembur</span>
            <div class="kpi-main-number"><?= number_format($statBulanan['total_lembur'], 1, ',', '.') ?> <span style="font-size: 1rem; font-weight: 600;">Jam</span></div>
            <span class="kpi-sub-label">Terverifikasi sistem</span>
        </div>
        <div class="kpi-card-icon-badge kpi-badge-violet">
            <?= Icon::get('zap', 22) ?>
        </div>
    </div>

    <!-- Card 4: Izin, Sakit & Cuti -->
    <a href="<?= Config::baseUrl('ketidakhadiran') ?>" class="pegawai-kpi-card kpi-stripe-amber" style="text-decoration: none; color: inherit;" title="Lihat Data Ketidak Hadiran">
        <div class="kpi-card-details">
            <span class="kpi-tag-title">Izin & Cuti</span>
            <div class="kpi-main-number"><?= (int)$statIzin['total_disetujui'] ?> <span style="font-size: 1rem; font-weight: 600;">Hari</span></div>
            <span class="kpi-sub-label">
                <?= $statIzin['total_pending'] > 0 ? "({$statIzin['total_pending']} menunggu review)" : "Disetujui periode ini" ?>
            </span>
        </div>
        <div class="kpi-card-icon-badge kpi-badge-amber">
            <?= Icon::get('file-text', 22) ?>
        </div>
    </a>
</div>

<!-- 3. Dashboard Grid: 2 Kolom (Kiri 65% : Kanan 35%) -->
<div class="pegawai-dashboard-layout">
    
    <!-- ==================== KOLOM KIRI ==================== -->
    <div class="dashboard-col-left">
        
        <!-- Panel 1: Tren Jam Kerja Mingguan (Capsule Bar Chart) -->
        <div class="dash-panel">
            <div class="dash-panel-header">
                <div class="panel-title-group">
                    <h3><?= Icon::get('trending-up', 20) ?> Tren Jam Kerja 7 Hari Terakhir</h3>
                    <p>Standar 8 jam kerja reguler per hari</p>
                </div>
                <span class="panel-header-badge">Minggu Ini</span>
            </div>

            <div class="capsule-chart-container">
                <?php foreach ($trenTujuhHari as $col): ?>
                    <div class="capsule-column">
                        <span class="capsule-value-top"><?= $col['jam_kerja_format'] ?></span>
                        <div class="capsule-track" title="<?= htmlspecialchars($col['hari']) ?> (<?= $col['tanggal'] ?>): <?= $col['jam_kerja_format'] ?> - <?= htmlspecialchars($col['status_label']) ?>">
                            <div class="capsule-pill <?= ($col['jam_kerja_desimal'] > 0) ? 'pill-active' : 'pill-empty' ?>"
                                 style="height: <?= (int)$col['height_percent'] ?>%;"></div>
                        </div>
                        <span class="capsule-day-label <?= $col['is_today'] ? 'today' : '' ?>">
                            <?= htmlspecialchars($col['hari_pendek']) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Panel 2: Riwayat Presensi & Aktivitas Terakhir (Feed) -->
        <div class="dash-panel">
            <div class="dash-panel-header">
                <div class="panel-title-group">
                    <h3><?= Icon::get('activity', 20) ?> Riwayat Presensi Terakhir</h3>
                    <p>Catatan waktu masuk, pulang, dan status kehadiran Anda</p>
                </div>
                <a href="<?= Config::baseUrl('riwayat') ?>" style="font-size: 0.84rem; font-weight: 700; color: var(--accent-blue); text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                    Lihat Semua <?= Icon::get('arrow-right', 14) ?>
                </a>
            </div>

            <?php if (empty($riwayatPresensi)): ?>
                <div style="text-align: center; padding: 32px 16px; color: var(--text-muted);">
                    <div style="margin-bottom: 8px;"><?= Icon::get('calendar', 36) ?></div>
                    <div style="font-weight: 700; color: var(--text-main);">Belum ada riwayat aktivitas</div>
                    <p style="font-size: 0.85rem; margin-top: 4px;">Lakukan presensi atau ajukan izin untuk mencatat log kehadiran Anda.</p>
                </div>
            <?php else: ?>
                <div class="activity-feed-list">
                    <?php foreach ($riwayatPresensi as $rw): 
                        $tglFmt = date('d M Y', strtotime($rw['tanggal']));
                        $isKetidakhadiran = (($rw['tipe_aktivitas'] ?? '') === 'ketidakhadiran');
                    ?>
                        <?php if ($isKetidakhadiran): 
                            $statusApproval = $rw['status_approval'] ?? 'pending';
                            $kat = ucfirst($rw['kategori'] ?? 'Izin');
                            $badgeClass = ($statusApproval === 'disetujui') ? 'badge-success' : (($statusApproval === 'ditolak') ? 'badge-danger' : 'badge-warning');
                            $badgeText  = ($statusApproval === 'disetujui') ? 'Disetujui' : (($statusApproval === 'ditolak') ? 'Ditolak' : 'Menunggu Review');
                        ?>
                            <!-- Item Log Izin / Sakit / Cuti -->
                            <div class="activity-feed-item">
                                <div class="activity-feed-left">
                                    <div class="activity-icon-rounded" style="background: rgba(245, 158, 11, 0.12); color: #f59e0b;">
                                        <?= Icon::get('clipboard', 20) ?>
                                    </div>
                                    <div class="activity-text-info">
                                        <h5>
                                            Pengajuan <?= $kat ?> &bull; 
                                            <span style="font-weight: 600; color: var(--text-main); font-size: 0.86rem;">
                                                "<?= htmlspecialchars($rw['keterangan'] ?? '-') ?>"
                                            </span>
                                        </h5>
                                        <p>
                                            Periode: <strong><?= date('d M Y', strtotime($rw['tanggal_mulai'])) ?></strong>
                                            <?php if (!empty($rw['tanggal_selesai']) && $rw['tanggal_selesai'] !== $rw['tanggal_mulai']): ?>
                                                s/d <strong><?= date('d M Y', strtotime($rw['tanggal_selesai'])) ?></strong>
                                            <?php endif; ?>
                                            <?php if (!empty($rw['catatan_admin'])): ?>
                                                &bull; <span style="font-style: italic;"><?= htmlspecialchars($rw['catatan_admin']) ?></span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="activity-feed-right">
                                    <span class="badge <?= $badgeClass ?>"><?= $badgeText ?></span>
                                    <span class="activity-date-badge"><?= $tglFmt ?></span>
                                </div>
                            </div>
                        <?php else: 
                            $isLengkap = !empty($rw['jam_pulang']);
                            $isTerlambat = ($rw['status_kehadiran'] === 'terlambat' || (!empty($rw['total_terlambat']) && $rw['total_terlambat'] !== 'Tepat Waktu'));
                        ?>
                            <!-- Item Log Presensi Fisik -->
                            <div class="activity-feed-item">
                                <div class="activity-feed-left">
                                    <div class="activity-icon-rounded <?= $isLengkap ? 'activity-icon-success' : '' ?>">
                                        <?= Icon::get($isLengkap ? 'check-circle' : 'clock', 20) ?>
                                    </div>
                                    <div class="activity-text-info">
                                        <h5>
                                            Presensi <?= $isLengkap ? 'Lengkap' : 'Masuk' ?> &bull; 
                                            <span style="font-weight: 600; color: var(--text-muted); font-size: 0.84rem;">
                                                <?= htmlspecialchars($rw['nama_lokasi'] ?? 'Kantor SGM') ?>
                                            </span>
                                        </h5>
                                        <p>
                                            Masuk: <strong><?= substr($rw['jam_masuk'], 0, 5) ?> WIB</strong>
                                            <?php if ($isLengkap): ?>
                                                &bull; Pulang: <strong><?= substr($rw['jam_pulang'], 0, 5) ?> WIB</strong>
                                                &bull; Total Kerja: <?= htmlspecialchars($rw['total_jam_kerja'] ?? '-') ?>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="activity-feed-right">
                                    <?php if ($isTerlambat): ?>
                                        <span class="badge badge-danger">
                                            Terlambat <?= (!empty($rw['total_terlambat']) && $rw['total_terlambat'] !== 'Tepat Waktu') ? '(' . htmlspecialchars($rw['total_terlambat']) . ')' : '' ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-success">Tepat Waktu</span>
                                    <?php endif; ?>
                                    <span class="activity-date-badge"><?= $tglFmt ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- ==================== KOLOM KANAN ==================== -->
    <div class="dashboard-col-right">
        
        <!-- Panel 3: Status Presensi Hari Ini (Quick Action Box) -->
        <div class="dash-panel panel-presensi-cepat" id="section-presensi-cepat">
            <div class="dash-panel-header">
                <div class="panel-title-group">
                    <h3><?= Icon::get('calendar', 20) ?> Presensi Hari Ini</h3>
                    <p><?= $tanggalIndo ?></p>
                </div>
            </div>

            <div class="daily-quick-box">
                <!-- Digital Clock Terhubung dengan Engine Realtime SGM -->
                <div class="quick-clock-display" id="digital-clock">
                    <?= date('H:i:s') ?>
                </div>
                <div class="quick-date-text">Zona Waktu: WIB (Jakarta)</div>

                <div class="quick-status-indicators">
                    <div class="quick-status-item">
                        <div class="quick-status-item-label">Jam Masuk</div>
                        <div class="quick-status-item-val" style="color: <?= $sudahMasuk ? '#10b981' : 'var(--text-muted)' ?>;">
                            <?= $sudahMasuk ? substr($presensiHariIni['jam_masuk'], 0, 5) . ' WIB' : 'Belum Absen' ?>
                        </div>
                    </div>
                    <div class="quick-status-item">
                        <div class="quick-status-item-label">Jam Pulang</div>
                        <div class="quick-status-item-val" style="color: <?= $sudahKeluar ? '#10b981' : 'var(--text-muted)' ?>;">
                            <?= $sudahKeluar ? substr($presensiHariIni['jam_pulang'], 0, 5) . ' WIB' : 'Belum Absen' ?>
                        </div>
                    </div>
                </div>

                <!-- Tombol Aksi Cerdas Sesuai Kondisi -->
                <?php if (!$sudahMasuk): ?>
                    <a href="<?= Config::baseUrl('presensi/masuk') ?>" class="btn btn-primary btn-action-pulse" style="width: 100%; padding: 12px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                        <?= Icon::get('camera', 18) ?> Presensi Masuk Sekarang
                    </a>
                <?php elseif (!$sudahKeluar): ?>
                    <a href="<?= Config::baseUrl('presensi/keluar') ?>" class="btn btn-danger btn-action-pulse" style="width: 100%; padding: 12px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                        <?= Icon::get('camera', 18) ?> Presensi Pulang Sekarang
                    </a>
                <?php else: ?>
                    <div class="badge-complete-today">
                        <?= Icon::get('check-circle', 20) ?> Presensi Hari Ini Selesai
                    </div>
                <?php endif; ?>
            </div>

            <div style="font-size: 0.8rem; color: var(--text-muted); display: flex; align-items: center; gap: 6px; padding: 4px 6px;">
                <?= Icon::get('map-pin', 16) ?>
                <span>Lokasi: <strong><?= htmlspecialchars($namaLokasi) ?></strong> (Radius <?= (int)($pegawaiProfile['radius_lokasi'] ?? 500) ?>m)</span>
            </div>
        </div>

        <!-- Panel 4: Distribusi Status Kehadiran (Donut Chart SVG Native) -->
        <div class="dash-panel panel-donut">
            <div class="dash-panel-header">
                <div class="panel-title-group">
                    <h3><?= Icon::get('pie-chart', 20) ?> Status Kehadiran</h3>
                    <p>Distribusi Bulan <?= $bln ?> <?= $thn ?></p>
                </div>
            </div>

            <div class="donut-chart-wrapper">
                <svg width="150" height="150" viewBox="0 0 100 100">
                    <!-- Background Circle Ring -->
                    <circle cx="50" cy="50" r="36" fill="transparent" stroke="var(--border-color)" stroke-width="12"></circle>
                    
                    <?php if ($totalHadir > 0 || $izinDisetujui > 0): ?>
                        <!-- Slice 1: Tepat Waktu (Emerald) -->
                        <?php if ($dashTepat > 0): ?>
                            <circle cx="50" cy="50" r="36" fill="transparent" stroke="#10b981" stroke-width="12"
                                    stroke-dasharray="<?= $dashTepat ?> <?= $circumference ?>"
                                    stroke-dashoffset="0"
                                    transform="rotate(-90 50 50)"></circle>
                        <?php endif; ?>

                        <!-- Slice 2: Terlambat (Amber) -->
                        <?php if ($dashTelat > 0): ?>
                            <circle cx="50" cy="50" r="36" fill="transparent" stroke="#f59e0b" stroke-width="12"
                                    stroke-dasharray="<?= $dashTelat ?> <?= $circumference ?>"
                                    stroke-dashoffset="<?= $offsetTelat ?>"
                                    transform="rotate(-90 50 50)"></circle>
                        <?php endif; ?>

                        <!-- Slice 3: Izin / Cuti (Blue) -->
                        <?php if ($dashIzin > 0): ?>
                            <circle cx="50" cy="50" r="36" fill="transparent" stroke="#2563eb" stroke-width="12"
                                    stroke-dasharray="<?= $dashIzin ?> <?= $circumference ?>"
                                    stroke-dashoffset="<?= $offsetIzin ?>"
                                    transform="rotate(-90 50 50)"></circle>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- Text Center -->
                    <text x="50" y="47" text-anchor="middle" font-family="'JetBrains Mono', monospace" font-size="20" font-weight="800" fill="var(--text-main)"><?= (int)$totalHadir ?></text>
                    <text x="50" y="60" text-anchor="middle" font-size="7" font-weight="700" letter-spacing="0.5" fill="var(--text-muted)">HARI HADIR</text>
                </svg>

                <div class="donut-legend-list">
                    <div class="donut-legend-item">
                        <span class="legend-label-group">
                            <span class="legend-dot" style="background: #10b981;"></span>
                            Tepat Waktu
                        </span>
                        <span class="legend-value"><?= $tepatWaktu ?> Hari</span>
                    </div>
                    <div class="donut-legend-item">
                        <span class="legend-label-group">
                            <span class="legend-dot" style="background: #f59e0b;"></span>
                            Terlambat
                        </span>
                        <span class="legend-value"><?= $terlambat ?> Hari</span>
                    </div>
                    <div class="donut-legend-item">
                        <span class="legend-label-group">
                            <span class="legend-dot" style="background: #2563eb;"></span>
                            Izin / Sakit Disetujui
                        </span>
                        <span class="legend-value"><?= $izinDisetujui ?> Hari</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel 5: Mini Profil & Akses Cepat Dokumen -->
        <div class="dash-panel panel-mini-profile">
            <div class="dash-panel-header">
                <div class="panel-title-group">
                    <h3><?= Icon::get('shield', 20) ?> Profil & Akses Cepat</h3>
                    <p>Identitas pegawai dan dokumen</p>
                </div>
            </div>

            <div class="mini-profile-box">
                <div class="mini-profile-avatar">
                    <?= $inisial ?>
                </div>
                <div style="flex: 1; min-width: 0;">
                    <div style="font-weight: 700; font-size: 0.92rem; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        <?= htmlspecialchars($pegawaiProfile['nama']) ?>
                    </div>
                    <div style="font-size: 0.78rem; color: var(--text-muted); font-family: 'JetBrains Mono', monospace;">
                        <?= htmlspecialchars($pegawaiProfile['nik']) ?> &bull; <?= htmlspecialchars($pegawaiProfile['jabatan']) ?>
                    </div>
                </div>
                <span class="badge badge-success" style="font-size: 0.72rem;">Aktif</span>
            </div>

            <div class="quick-links-list">
                <a href="<?= Config::baseUrl('ketidakhadiran') ?>" class="quick-link-btn">
                    <span style="display: flex; align-items: center; gap: 8px;">
                        <?= Icon::get('file-text', 16) ?>
                        Data Ketidak Hadiran
                    </span>
                    <?= Icon::get('arrow-right', 14) ?>
                </a>
                <a href="<?= Config::baseUrl('profil') ?>" class="quick-link-btn">
                    <span style="display: flex; align-items: center; gap: 8px;">
                        <?= Icon::get('user', 16) ?>
                        Perbarui Data Profil Saya
                    </span>
                    <?= Icon::get('arrow-right', 14) ?>
                </a>
            </div>
        </div>

    </div>

</div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
