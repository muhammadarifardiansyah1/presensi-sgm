<?php
$pageTitle = 'Command Center - ' . Config::APP_NAME;
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

// Perhitungan SVG Donut Chart Admin (Distribusi Kehadiran Hari Ini)
$circumference = 226.2; // 2 * PI * 36
$totalItems = max(1, $totalPegawai);

$pctTepat = $tepatWaktu / $totalItems;
$pctTelat = $terlambat / $totalItems;
$pctIzin  = $jumlahSakitIzinCuti / $totalItems;
$pctAlpa  = $jumlahBelumAbsen / $totalItems;

$dashTepat = round($pctTepat * $circumference, 1);
$dashTelat = round($pctTelat * $circumference, 1);
$dashIzin  = round($pctIzin * $circumference, 1);
$dashAlpa  = round($pctAlpa * $circumference, 1);

$offsetTelat = -$dashTepat;
$offsetIzin  = -($dashTepat + $dashTelat);
$offsetAlpa  = -($dashTepat + $dashTelat + $dashIzin);

// Persentase Kepatuhan Waktu (On-Time Rate)
$rateKepatuhan = ($jumlahHadir > 0) ? round(($tepatWaktu / $jumlahHadir) * 100) : 100;
?>

<!-- 1. Hero Command Center Banner -->
<div class="admin-hero-banner">
    <div class="admin-hero-left">
        <h2>
            <?= Icon::get('activity', 24) ?> Command Center SGM
        </h2>
        <p>Pantauan operasional kehadiran, kepatuhan jadwal, dan aktivitas presensi karyawan secara real-time.</p>
    </div>
    <div class="admin-clock-card">
        <div>
            <div class="admin-clock-digits" id="digital-clock"><?= date('H:i:s') ?></div>
            <div class="admin-clock-date"><?= $tanggalIndo ?> (WIB)</div>
        </div>
        <div style="color: var(--accent-blue); opacity: 0.85;">
            <?= Icon::get('clock', 28) ?>
        </div>
    </div>
</div>

<!-- 2. Alert Pengajuan Menunggu Verifikasi (Jika Ada) -->
<?php if (!empty($jumlahPendingIzin) && $jumlahPendingIzin > 0): ?>
    <div class="alert alert-warning" style="margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; gap: 14px; background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: var(--radius-md); padding: 14px 18px; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="color: #f59e0b; line-height: 1;">
                <?= Icon::get('alert-circle', 22) ?>
            </div>
            <div>
                <strong style="color: var(--text-main); font-size: 0.95rem; display: block;">
                    Perhatian: Terdapat <?= (int)$jumlahPendingIzin ?> Pengajuan Izin / Cuti Menunggu Verifikasi
                </strong>
                <span style="font-size: 0.84rem; color: var(--text-muted);">
                    Segera verifikasi dokumen pendukung sebelum rekap bulanan diproses untuk penggajian.
                </span>
            </div>
        </div>
        <a href="<?= Config::baseUrl('ketidakhadiran') ?>" class="btn btn-warning" style="white-space: nowrap; font-size: 0.84rem; font-weight: 700; padding: 8px 16px; display: inline-flex; align-items: center; gap: 6px;">
            Tinjau Sekarang <?= Icon::get('arrow-right', 14) ?>
        </a>
    </div>
<?php endif; ?>

<!-- 3. Empat Kartu Metrik KPI Eksekutif -->
<div class="pegawai-kpi-grid">
    <!-- Card 1: Total Pegawai Aktif -->
    <a href="<?= Config::baseUrl('pegawai') ?>" class="pegawai-kpi-card kpi-stripe-blue" style="text-decoration: none; color: inherit;">
        <div class="kpi-card-details">
            <span class="kpi-tag-title">Total Pegawai</span>
            <div class="kpi-main-number"><?= (int)$totalPegawai ?> <span style="font-size: 0.95rem; font-weight: 600; color: var(--text-muted);">orang</span></div>
            <span class="kpi-sub-label">Pegawai aktif terdaftar</span>
        </div>
        <div class="kpi-card-icon-badge kpi-badge-blue">
            <?= Icon::get('users', 22) ?>
        </div>
    </a>

    <!-- Card 2: Kehadiran Hari Ini -->
    <a href="<?= Config::baseUrl('laporan/harian') ?>" class="pegawai-kpi-card kpi-stripe-emerald" style="text-decoration: none; color: inherit;">
        <div class="kpi-card-details">
            <span class="kpi-tag-title">Hadir Hari Ini</span>
            <div class="kpi-main-number">
                <?= (int)$jumlahHadir ?> 
                <span style="font-size: 0.95rem; font-weight: 700; color: #10b981;">(<?= $persenHadir ?>%)</span>
            </div>
            <span class="kpi-sub-label"><?= (int)$tepatWaktu ?> tepat waktu &bull; <?= (int)$terlambat ?> telat</span>
        </div>
        <div class="kpi-card-icon-badge kpi-badge-emerald">
            <?= Icon::get('user-check', 22) ?>
        </div>
    </a>

    <!-- Card 3: Terlambat Hari Ini -->
    <a href="<?= Config::baseUrl('laporan/harian') ?>" class="pegawai-kpi-card kpi-stripe-amber" style="text-decoration: none; color: inherit;">
        <div class="kpi-card-details">
            <span class="kpi-tag-title">Terlambat</span>
            <div class="kpi-main-number"><?= (int)$terlambat ?> <span style="font-size: 0.95rem; font-weight: 600; color: var(--text-muted);">orang</span></div>
            <span class="kpi-sub-label">Tingkat tepat waktu: <strong><?= $rateKepatuhan ?>%</strong></span>
        </div>
        <div class="kpi-card-icon-badge kpi-badge-amber">
            <?= Icon::get('clock', 22) ?>
        </div>
    </a>

    <!-- Card 4: Izin, Sakit & Cuti -->
    <a href="<?= Config::baseUrl('ketidakhadiran') ?>" class="pegawai-kpi-card kpi-stripe-violet" style="text-decoration: none; color: inherit;">
        <div class="kpi-card-details">
            <span class="kpi-tag-title">Izin / Sakit / Cuti</span>
            <div class="kpi-main-number"><?= (int)$jumlahSakitIzinCuti ?> <span style="font-size: 0.95rem; font-weight: 600; color: var(--text-muted);">hari ini</span></div>
            <span class="kpi-sub-label"><?= (int)$jumlahBelumAbsen ?> pegawai belum absen</span>
        </div>
        <div class="kpi-card-icon-badge kpi-badge-violet">
            <?= Icon::get('clipboard', 22) ?>
        </div>
    </a>
</div>

<!-- 4. Layout 2 Kolom: Analitik & Visualisasi Data -->
<div class="admin-dashboard-layout">
    
    <!-- ==================== KOLOM KIRI ==================== -->
    <div style="display: flex; flex-direction: column; gap: 24px;">
        
        <!-- Panel 1: Tren Kehadiran 7 Hari Terakhir (Capsule Trend Chart) -->
        <div class="dash-panel">
            <div class="dash-panel-header">
                <div class="panel-title-group">
                    <h3><?= Icon::get('trending-up', 20) ?> Tren Kehadiran 7 Hari Terakhir</h3>
                    <p>Perbandingan volume presensi harian seluruh karyawan (H-6 s/d Hari Ini)</p>
                </div>
                <span class="panel-header-badge">7 Hari Kerja</span>
            </div>

            <div class="capsule-chart-container">
                <?php foreach ($trenTujuhHari as $col): ?>
                    <div class="capsule-column">
                        <span class="capsule-value-top"><?= (int)$col['total_hadir'] ?></span>
                        <div class="capsule-track" title="<?= htmlspecialchars($col['hari']) ?> (<?= $col['tanggal'] ?>): <?= $col['status_label'] ?>">
                            <div class="capsule-pill <?= ($col['total_hadir'] > 0) ? 'pill-active' : 'pill-empty' ?>"
                                 style="height: <?= (int)$col['height_percent'] ?>%; background: <?= $col['is_today'] ? 'linear-gradient(180deg, #10b981 0%, #059669 100%)' : 'linear-gradient(180deg, #3b82f6 0%, #2563eb 100%)' ?>;"></div>
                        </div>
                        <span class="capsule-day-label <?= $col['is_today'] ? 'today' : '' ?>">
                            <?= htmlspecialchars($col['hari_pendek']) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 16px; padding-top: 12px; border-top: 1px dashed var(--border-color); font-size: 0.8rem; color: var(--text-muted);">
                <div style="display: flex; gap: 14px;">
                    <span style="display: flex; align-items: center; gap: 6px;">
                        <span style="width: 8px; height: 8px; border-radius: 50%; background: #10b981;"></span> Hari Ini
                    </span>
                    <span style="display: flex; align-items: center; gap: 6px;">
                        <span style="width: 8px; height: 8px; border-radius: 50%; background: #3b82f6;"></span> Hari Sebelumnya
                    </span>
                </div>
                <span>Total Pegawai: <strong><?= (int)$totalPegawai ?></strong></span>
            </div>
        </div>

        <!-- Panel 2: Aktivitas & Notifikasi Terkini (Unified Real-time Activity Stream) -->
        <div class="dash-panel">
            <div class="dash-panel-header">
                <div class="panel-title-group">
                    <h3><?= Icon::get('activity', 20) ?> Aktivitas &amp; Notifikasi Terkini</h3>
                    <p>Kehadiran masuk &amp; pulang, pengajuan izin/cuti, dan penugasan lembur terbaru</p>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <a href="<?= Config::baseUrl('notifikasi') ?>" style="font-size: 0.82rem; font-weight: 600; color: var(--text-muted); text-decoration: none; display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); background: var(--bg-surface);">
                        <?= Icon::get('bell', 13) ?> Notifikasi
                    </a>
                    <a href="<?= Config::baseUrl('laporan/harian') ?>" style="font-size: 0.82rem; font-weight: 700; color: var(--accent-blue); text-decoration: none; display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); background: var(--bg-surface);">
                        Rekap Presensi <?= Icon::get('arrow-right', 13) ?>
                    </a>
                </div>
            </div>

            <?php if (empty($aktivitasTerbaru)): ?>
                <div style="text-align: center; padding: 36px 16px; color: var(--text-muted);">
                    <div style="margin-bottom: 8px;"><?= Icon::get('activity', 36) ?></div>
                    <div style="font-weight: 700; color: var(--text-main);">Belum ada aktivitas terkini</div>
                    <p style="font-size: 0.85rem; margin-top: 4px;">Log kehadiran, permohonan izin, dan surat lembur akan otomatis tampil di sini.</p>
                </div>
            <?php else: ?>
                <div class="activity-feed-list">
                    <?php foreach ($aktivitasTerbaru as $act): 
                        $tipe = $act['tipe'] ?? 'presensi';
                        $targetUrl = $act['link'] ?? Config::baseUrl('dashboard');
                    ?>
                        <a href="<?= htmlspecialchars($targetUrl) ?>" class="activity-feed-item" style="text-decoration: none; color: inherit; display: flex; cursor: pointer;">
                            
                            <?php if ($tipe === 'ketidakhadiran'): ?>
                                <!-- Card Tipe: Ketidakhadiran (Izin / Sakit / Cuti) -->
                                <div class="activity-feed-left">
                                    <div class="activity-icon-rounded" style="background: rgba(245, 158, 11, 0.12); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.25); border-radius: 12px;">
                                        <?= Icon::get('clipboard', 20) ?>
                                    </div>
                                    <div class="activity-text-info">
                                        <h5>
                                            <?= htmlspecialchars($act['judul']) ?>
                                            <span style="font-size: 0.74rem; font-weight: 700; color: #d97706; background: rgba(245, 158, 11, 0.12); border: 1px solid rgba(245, 158, 11, 0.25); padding: 1px 7px; border-radius: 6px; margin-left: 2px;">
                                                <?= htmlspecialchars($act['kategori'] ?? 'IZIN') ?>
                                            </span>
                                            <span style="font-size: 0.78rem; font-weight: 500; color: var(--text-muted);">&bull; <?= htmlspecialchars($act['subjudul'] ?? '-') ?></span>
                                        </h5>
                                        <p>
                                            <?= Icon::get('calendar', 12) ?> Periode: <strong><?= htmlspecialchars($act['periode'] ?? '-') ?></strong> &bull; 
                                            <span style="color: var(--text-muted); font-style: italic;">"<?= htmlspecialchars(mb_strimwidth($act['keterangan'] ?? '-', 0, 42, '...')) ?>"</span>
                                        </p>
                                    </div>
                                </div>
                                <div class="activity-feed-right">
                                    <span class="badge badge-<?= htmlspecialchars($act['badge_type'] ?? 'warning') ?>">
                                        <?= htmlspecialchars($act['badge_text'] ?? 'Pending') ?>
                                    </span>
                                    <span class="activity-date-badge"><?= htmlspecialchars($act['waktu_display']) ?></span>
                                </div>

                            <?php elseif ($tipe === 'lembur'): ?>
                                <!-- Card Tipe: Penugasan Lembur (SPL) -->
                                <div class="activity-feed-left">
                                    <div class="activity-icon-rounded" style="background: rgba(59, 130, 246, 0.12); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.25); border-radius: 12px;">
                                        <?= Icon::get('file-text', 20) ?>
                                    </div>
                                    <div class="activity-text-info">
                                        <h5>
                                            <?= htmlspecialchars($act['judul']) ?>
                                            <span style="font-size: 0.78rem; font-weight: 500; color: var(--text-muted);">&bull; <?= htmlspecialchars($act['subjudul'] ?? '-') ?></span>
                                        </h5>
                                        <p>
                                            <?= Icon::get('clock', 12) ?> <?= htmlspecialchars(mb_strimwidth($act['keterangan'] ?? '-', 0, 52, '...')) ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="activity-feed-right">
                                    <span class="badge badge-info">
                                        <?= htmlspecialchars($act['badge_text'] ?? 'SPL Aktif') ?>
                                    </span>
                                    <span class="activity-date-badge"><?= htmlspecialchars($act['waktu_display']) ?></span>
                                </div>

                            <?php else: 
                                // Card Tipe: Presensi Fisik Pegawai
                                $fotoUrl = '';
                                if (!empty($act['foto_profil'])) {
                                    $fotoPath = __DIR__ . '/../../../assets/images/uploads/profil/' . basename($act['foto_profil']);
                                    if (file_exists($fotoPath)) {
                                        $fotoUrl = Config::baseUrl('assets/images/uploads/profil/' . basename($act['foto_profil']));
                                    }
                                }
                                $inisialPegawai = mb_strtoupper(mb_substr($act['judul'] ?? 'P', 0, 1));
                            ?>
                                <div class="activity-feed-left">
                                    <div style="width: 42px; height: 42px; border-radius: 50%; overflow: hidden; background: var(--bg-surface); border: 2px solid var(--border-color); display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-weight: 700; color: var(--accent-blue);">
                                        <?php if ($fotoUrl): ?>
                                            <img src="<?= $fotoUrl ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                        <?php else: ?>
                                            <?= $inisialPegawai ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="activity-text-info">
                                        <h5>
                                            <?= htmlspecialchars($act['judul']) ?>
                                            <span style="font-size: 0.78rem; font-weight: 500; color: var(--text-muted);">&bull; <?= htmlspecialchars($act['subjudul'] ?? '-') ?></span>
                                        </h5>
                                        <p>
                                            <?= Icon::get('map-pin', 12) ?> <?= htmlspecialchars($act['lokasi'] ?? 'Kantor SGM') ?> &bull; 
                                            Masuk: <strong><?= htmlspecialchars($act['jam_masuk'] ?? '-') ?> WIB</strong>
                                            <?php if (!empty($act['jam_pulang'])): ?>
                                                &bull; Pulang: <strong><?= htmlspecialchars($act['jam_pulang']) ?> WIB</strong>
                                            <?php endif; ?>
                                            <?php if (empty($act['is_hari_ini'])): ?>
                                                &bull; <span class="badge badge-secondary" style="font-size: 0.7rem;"><?= htmlspecialchars($act['tanggal_text'] ?? '') ?></span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="activity-feed-right">
                                    <span class="badge badge-<?= htmlspecialchars($act['badge_type'] ?? 'success') ?>">
                                        <?= htmlspecialchars($act['badge_text'] ?? 'Tepat Waktu') ?>
                                    </span>
                                    <span class="activity-date-badge"><?= htmlspecialchars($act['waktu_display']) ?></span>
                                </div>
                            <?php endif; ?>

                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- ==================== KOLOM KANAN ==================== -->
    <div style="display: flex; flex-direction: column; gap: 24px;">
        
        <!-- Panel 3: Diagram Lingkaran (Native SVG Donut Chart) Proporsi Status Hari Ini -->
        <div class="dash-panel">
            <div class="dash-panel-header">
                <div class="panel-title-group">
                    <h3><?= Icon::get('pie-chart', 20) ?> Distribusi Kehadiran</h3>
                    <p>Proporsi kehadiran hari ini</p>
                </div>
                <span class="panel-header-badge" style="color: #10b981; border-color: rgba(16, 185, 129, 0.3);">
                    ● Live
                </span>
            </div>

            <div class="donut-chart-wrapper">
                <svg width="160" height="160" viewBox="0 0 100 100">
                    <!-- Background Circle Ring -->
                    <circle cx="50" cy="50" r="36" fill="transparent" stroke="var(--border-color)" stroke-width="12"></circle>
                    
                    <?php if ($jumlahHadir > 0 || $jumlahSakitIzinCuti > 0): ?>
                        <!-- Slice 1: Tepat Waktu (Emerald) -->
                        <?php if ($dashTepat > 0): ?>
                            <circle cx="50" cy="50" r="36" fill="transparent" stroke="#10b981" stroke-width="12"
                                    stroke-dasharray="<?= $dashTepat ?> <?= $circumference ?>"
                                    stroke-dashoffset="0"
                                    transform="rotate(-90 50 50)">
                                <title>Tepat Waktu: <?= $tepatWaktu ?> orang (<?= round($pctTepat * 100) ?>%)</title>
                            </circle>
                        <?php endif; ?>

                        <!-- Slice 2: Terlambat (Amber) -->
                        <?php if ($dashTelat > 0): ?>
                            <circle cx="50" cy="50" r="36" fill="transparent" stroke="#f59e0b" stroke-width="12"
                                    stroke-dasharray="<?= $dashTelat ?> <?= $circumference ?>"
                                    stroke-dashoffset="<?= $offsetTelat ?>"
                                    transform="rotate(-90 50 50)">
                                <title>Terlambat: <?= $terlambat ?> orang (<?= round($pctTelat * 100) ?>%)</title>
                            </circle>
                        <?php endif; ?>

                        <!-- Slice 3: Izin / Sakit / Cuti (Blue) -->
                        <?php if ($dashIzin > 0): ?>
                            <circle cx="50" cy="50" r="36" fill="transparent" stroke="#3b82f6" stroke-width="12"
                                    stroke-dasharray="<?= $dashIzin ?> <?= $circumference ?>"
                                    stroke-dashoffset="<?= $offsetIzin ?>"
                                    transform="rotate(-90 50 50)">
                                <title>Izin & Cuti: <?= $jumlahSakitIzinCuti ?> orang (<?= round($pctIzin * 100) ?>%)</title>
                            </circle>
                        <?php endif; ?>

                        <!-- Slice 4: Belum Absen / Alpa (Rose Red) -->
                        <?php if ($dashAlpa > 0): ?>
                            <circle cx="50" cy="50" r="36" fill="transparent" stroke="rgba(239, 68, 68, 0.4)" stroke-width="12"
                                    stroke-dasharray="<?= $dashAlpa ?> <?= $circumference ?>"
                                    stroke-dashoffset="<?= $offsetAlpa ?>"
                                    transform="rotate(-90 50 50)">
                                <title>Belum Absen: <?= $jumlahBelumAbsen ?> orang (<?= round($pctAlpa * 100) ?>%)</title>
                            </circle>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- Text Center -->
                    <text x="50" y="46" text-anchor="middle" font-family="'JetBrains Mono', monospace" font-size="19" font-weight="800" fill="var(--text-main)"><?= $persenHadir ?>%</text>
                    <text x="50" y="59" text-anchor="middle" font-size="6.5" font-weight="700" letter-spacing="0.5" fill="var(--text-muted)">HADIR HARI INI</text>
                </svg>

                <div class="donut-legend-list">
                    <div class="donut-legend-item">
                        <span class="legend-label-group">
                            <span class="legend-dot" style="background: #10b981;"></span>
                            Tepat Waktu
                        </span>
                        <span class="legend-value"><?= (int)$tepatWaktu ?> orang <span style="font-size: 0.76rem; font-weight: 500; color: var(--text-muted);">(<?= round($pctTepat * 100) ?>%)</span></span>
                    </div>
                    <div class="donut-legend-item">
                        <span class="legend-label-group">
                            <span class="legend-dot" style="background: #f59e0b;"></span>
                            Terlambat
                        </span>
                        <span class="legend-value"><?= (int)$terlambat ?> orang <span style="font-size: 0.76rem; font-weight: 500; color: var(--text-muted);">(<?= round($pctTelat * 100) ?>%)</span></span>
                    </div>
                    <div class="donut-legend-item">
                        <span class="legend-label-group">
                            <span class="legend-dot" style="background: #3b82f6;"></span>
                            Izin / Sakit / Cuti
                        </span>
                        <span class="legend-value"><?= (int)$jumlahSakitIzinCuti ?> orang <span style="font-size: 0.76rem; font-weight: 500; color: var(--text-muted);">(<?= round($pctIzin * 100) ?>%)</span></span>
                    </div>
                    <div class="donut-legend-item">
                        <span class="legend-label-group">
                            <span class="legend-dot" style="background: #ef4444;"></span>
                            Belum Absen
                        </span>
                        <span class="legend-value"><?= (int)$jumlahBelumAbsen ?> orang <span style="font-size: 0.76rem; font-weight: 500; color: var(--text-muted);">(<?= round($pctAlpa * 100) ?>%)</span></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel 4: Sebaran Kehadiran per Lokasi / Cabang -->
        <div class="dash-panel">
            <div class="dash-panel-header">
                <div class="panel-title-group">
                    <h3><?= Icon::get('map-pin', 20) ?> Sebaran Lokasi Kerja</h3>
                    <p>Distribusi presensi di setiap kantor/cabang</p>
                </div>
                <a href="<?= Config::baseUrl('master/lokasi') ?>" style="font-size: 0.82rem; font-weight: 600; color: var(--accent-blue); text-decoration: none;">
                    Kelola <?= Icon::get('arrow-right', 12) ?>
                </a>
            </div>

            <div style="display: flex; flex-direction: column; gap: 14px;">
                <?php if (empty($lokasiStatistik)): ?>
                    <p style="font-size: 0.85rem; color: var(--text-muted); text-align: center; margin: 8px 0;">Belum ada master lokasi terdaftar.</p>
                <?php else: ?>
                    <?php foreach ($lokasiStatistik as $lok): 
                        $pctLok = ($lok['total_pegawai'] > 0) ? min(100, round(($lok['total_hadir'] / $lok['total_pegawai']) * 100)) : 0;
                    ?>
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; font-size: 0.85rem;">
                                <div>
                                    <strong style="color: var(--text-main);"><?= htmlspecialchars($lok['nama_lokasi']) ?></strong>
                                    <span class="badge badge-secondary" style="font-size: 0.68rem; margin-left: 4px;"><?= htmlspecialchars($lok['tipe_lokasi']) ?></span>
                                </div>
                                <span style="font-weight: 700; color: var(--text-main); font-variant-numeric: tabular-nums;">
                                    <?= (int)$lok['total_hadir'] ?> / <?= (int)$lok['total_pegawai'] ?> (<?= $pctLok ?>%)
                                </span>
                            </div>
                            <div style="width: 100%; height: 7px; background: var(--bg-subtle); border: 1px solid var(--border-color); border-radius: 4px; overflow: hidden;">
                                <div style="height: 100%; width: <?= $pctLok ?>%; background: linear-gradient(90deg, #3b82f6, #10b981); border-radius: 4px; transition: width 0.4s ease;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Panel 5: Aksi Cepat & Navigasi Operasional -->
        <div class="dash-panel">
            <div class="dash-panel-header">
                <div class="panel-title-group">
                    <h3><?= Icon::get('zap', 20) ?> Aksi Cepat Operasional</h3>
                    <p>Shortcut modul administrasi utama</p>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <a href="<?= Config::baseUrl('gaji') ?>" class="quick-link-btn" title="Kelola Penggajian">
                    <span style="display: flex; align-items: center; gap: 8px;">
                        <?= Icon::get('wallet', 16) ?> Penggajian
                    </span>
                    <?= Icon::get('arrow-right', 12) ?>
                </a>
                <a href="<?= Config::baseUrl('laporan/bulanan') ?>" class="quick-link-btn" title="Rekap Presensi Bulanan">
                    <span style="display: flex; align-items: center; gap: 8px;">
                        <?= Icon::get('bar-chart', 16) ?> Rekap Presensi
                    </span>
                    <?= Icon::get('arrow-right', 12) ?>
                </a>
                <a href="<?= Config::baseUrl('ketidakhadiran') ?>" class="quick-link-btn" title="Verifikasi Pengajuan Izin">
                    <span style="display: flex; align-items: center; gap: 8px;">
                        <?= Icon::get('clipboard-check', 16) ?> Verifikasi Izin
                    </span>
                    <?= Icon::get('arrow-right', 12) ?>
                </a>
                <a href="<?= Config::baseUrl('pegawai/tambah') ?>" class="quick-link-btn" title="Tambah Pegawai Baru">
                    <span style="display: flex; align-items: center; gap: 8px;">
                        <?= Icon::get('user-plus', 16) ?> Tambah Pegawai
                    </span>
                    <?= Icon::get('arrow-right', 12) ?>
                </a>
                <a href="<?= Config::baseUrl('master/lokasi') ?>" class="quick-link-btn" title="Kelola Titik Lokasi">
                    <span style="display: flex; align-items: center; gap: 8px;">
                        <?= Icon::get('map-pin', 16) ?> Kelola Lokasi
                    </span>
                    <?= Icon::get('arrow-right', 12) ?>
                </a>
                <a href="<?= Config::baseUrl('laporan/harian') ?>" class="quick-link-btn" title="Input Presensi Manual">
                    <span style="display: flex; align-items: center; gap: 8px;">
                        <?= Icon::get('plus', 16) ?> Presensi Manual
                    </span>
                    <?= Icon::get('arrow-right', 12) ?>
                </a>
            </div>
        </div>

        <!-- Panel 6: Status Keamanan & Integritas SGM -->
        <div class="dash-panel" style="background: var(--bg-subtle);">
            <div class="dash-panel-header" style="margin-bottom: 12px;">
                <div class="panel-title-group">
                    <h3 style="font-size: 0.95rem;"><?= Icon::get('shield', 18) ?> Status Keamanan SGM</h3>
                </div>
                <span class="badge badge-success" style="font-size: 0.72rem;">Active</span>
            </div>
            <p style="font-size: 0.82rem; color: var(--text-muted); line-height: 1.6; margin: 0;">
                &bull; <strong>Geofencing:</strong> Haversine Radius Active<br>
                &bull; <strong>Verifikasi Wajah:</strong> Live Webcam Required<br>
                &bull; <strong>Anti-Spoofing:</strong> Server-Side Validation
            </p>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
