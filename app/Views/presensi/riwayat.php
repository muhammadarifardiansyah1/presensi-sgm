<?php
/**
 * Halaman Mandiri: Riwayat Aktivitas & Presensi Kehadiran Pegawai
 * Absensi Surya Gemilang Mojopahit (SGM)
 */

$pageTitle = 'Riwayat Presensi & Aktivitas - ' . Config::APP_NAME;
$activePage = 'riwayat';
require_once __DIR__ . '/../layouts/header.php';

$namaBulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

$selectedBulanText = $namaBulan[$bulan] ?? (string)$bulan;
$totalHadir = (int)($statBulanan['total_hadir'] ?? 0);
$tepatWaktu = (int)($statBulanan['total_tepat_waktu'] ?? 0);
$terlambat  = (int)($statBulanan['total_terlambat'] ?? 0);
$izinCuti   = (int)($statIzin['total_disetujui'] ?? 0);
$totalJamKerja = $statBulanan['total_jam_kerja_format'] ?? '0 Jam';
$totalLembur = (float)($statBulanan['total_jam_lembur'] ?? 0);
?>

<style>
/* Riwayat Page Styling */
.riwayat-header-banner {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: var(--shadow-sm);
    flex-wrap: wrap;
    gap: 16px;
}

.riwayat-title-group h2 {
    font-size: 1.4rem;
    font-weight: 800;
    color: var(--text-main);
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0 0 6px 0;
}

.riwayat-title-group p {
    font-size: 0.88rem;
    color: var(--text-muted);
    margin: 0;
}

.riwayat-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.riwayat-stat-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    padding: 16px 20px;
    box-shadow: var(--shadow-sm);
    display: flex;
    align-items: center;
    gap: 16px;
    transition: transform var(--transition-fast), box-shadow var(--transition-fast);
}

.riwayat-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.riwayat-stat-icon {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.riwayat-stat-info {
    min-width: 0;
}

.riwayat-stat-val {
    font-size: 1.35rem;
    font-weight: 800;
    color: var(--text-main);
    line-height: 1.2;
    font-family: 'JetBrains Mono', monospace;
}

.riwayat-stat-label {
    font-size: 0.78rem;
    color: var(--text-muted);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 2px;
}

/* Filter Bar */
.riwayat-filter-bar {
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    padding: 18px 20px;
    margin-bottom: 24px;
    box-shadow: var(--shadow-sm);
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
}

.riwayat-type-pills {
    display: inline-flex;
    background: var(--bg-subtle);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-full);
    padding: 4px;
    gap: 4px;
}

.riwayat-pill-item {
    padding: 7px 16px;
    border-radius: var(--radius-full);
    font-size: 0.84rem;
    font-weight: 600;
    color: var(--text-muted);
    text-decoration: none;
    transition: all var(--transition-fast);
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.riwayat-pill-item:hover {
    color: var(--text-main);
}

.riwayat-pill-item.active {
    background: var(--primary);
    color: #ffffff;
    box-shadow: var(--shadow-sm);
}

.riwayat-filter-form {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.riwayat-filter-form select {
    padding: 8px 14px;
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    color: var(--text-main);
    border-radius: var(--radius-sm);
    font-size: 0.86rem;
    font-weight: 500;
    outline: none;
    cursor: pointer;
    transition: border-color var(--transition-fast);
}

.riwayat-filter-form select:focus {
    border-color: var(--primary);
}

/* List Timeline Container */
.riwayat-container-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: 24px;
    box-shadow: var(--shadow-sm);
}

.riwayat-container-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 16px;
    margin-bottom: 20px;
    border-bottom: 1px solid var(--border-color);
    flex-wrap: wrap;
    gap: 10px;
}

.riwayat-container-header h3 {
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--text-main);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.riwayat-count-tag {
    font-size: 0.78rem;
    background: var(--bg-subtle);
    border: 1px solid var(--border-color);
    padding: 3px 10px;
    border-radius: var(--radius-full);
    color: var(--text-muted);
    font-weight: 600;
}

.riwayat-list {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.riwayat-item-card {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    background: var(--bg-subtle);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    padding: 18px 20px;
    gap: 16px;
    transition: all var(--transition-fast);
}

.riwayat-item-card:hover {
    background: var(--bg-surface-elevated, var(--bg-surface));
    border-color: var(--border-hover);
    box-shadow: var(--shadow-sm);
}

.riwayat-item-main {
    display: flex;
    gap: 16px;
    align-items: flex-start;
    flex: 1;
    min-width: 0;
}

.riwayat-item-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.riwayat-item-content {
    flex: 1;
    min-width: 0;
}

.riwayat-item-title-row {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 6px;
}

.riwayat-item-title {
    font-size: 0.98rem;
    font-weight: 700;
    color: var(--text-main);
    margin: 0;
}

.riwayat-item-details {
    font-size: 0.85rem;
    color: var(--text-muted);
    line-height: 1.6;
    margin: 0;
}

.riwayat-item-details strong {
    color: var(--text-main);
}

.riwayat-item-side {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 8px;
    flex-shrink: 0;
}

.riwayat-date-pill {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--text-main);
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    padding: 4px 10px;
    border-radius: var(--radius-sm);
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.riwayat-empty-state {
    text-align: center;
    padding: 56px 20px;
    color: var(--text-muted);
}

.riwayat-empty-state svg {
    margin-bottom: 14px;
    color: var(--text-subtle);
}

.riwayat-empty-state h4 {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--text-main);
    margin: 0 0 6px 0;
}

.riwayat-empty-state p {
    font-size: 0.88rem;
    max-width: 420px;
    margin: 0 auto 20px auto;
}

@media (max-width: 768px) {
    .riwayat-header-banner {
        flex-direction: column;
        align-items: flex-start;
    }
    .riwayat-filter-bar {
        flex-direction: column;
        align-items: flex-start;
    }
    .riwayat-item-card {
        flex-direction: column;
        align-items: flex-start;
    }
    .riwayat-item-side {
        align-items: flex-start;
        width: 100%;
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px dashed var(--border-color);
    }
}
</style>

<!-- Banner Header -->
<div class="riwayat-header-banner">
    <div class="riwayat-title-group">
        <h2><?= Icon::get('activity', 24) ?> Riwayat Presensi & Aktivitas</h2>
        <p>Catatan kehadiran fisik, pengajuan perizinan/cuti, serta penugasan lembur (SPL) resmi Anda di Surya Gemilang Mojopahit.</p>
    </div>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="<?= Config::baseUrl('ketidakhadiran/ajukan') ?>" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px;">
            <?= Icon::get('clipboard', 18) ?> Ajukan Izin / Cuti
        </a>
        <a href="<?= Config::baseUrl('dashboard') ?>" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 8px;">
            <?= Icon::get('arrow-left', 16) ?> Dashboard
        </a>
    </div>
</div>

<!-- Grid Ringkasan Statistik Periode Terpilih -->
<div class="riwayat-stats-grid">
    <div class="riwayat-stat-card">
        <div class="riwayat-stat-icon" style="background: rgba(16, 185, 129, 0.12); color: #10b981;">
            <?= Icon::get('check-circle', 22) ?>
        </div>
        <div class="riwayat-stat-info">
            <div class="riwayat-stat-val"><?= $totalHadir ?></div>
            <div class="riwayat-stat-label">Total Hadir Fisik</div>
        </div>
    </div>
    <div class="riwayat-stat-card">
        <div class="riwayat-stat-icon" style="background: rgba(5, 150, 105, 0.12); color: #059669;">
            <?= Icon::get('clock', 22) ?>
        </div>
        <div class="riwayat-stat-info">
            <div class="riwayat-stat-val"><?= $tepatWaktu ?></div>
            <div class="riwayat-stat-label">Tepat Waktu</div>
        </div>
    </div>
    <div class="riwayat-stat-card">
        <div class="riwayat-stat-icon" style="background: rgba(239, 68, 68, 0.12); color: #ef4444;">
            <?= Icon::get('alert-circle', 22) ?>
        </div>
        <div class="riwayat-stat-info">
            <div class="riwayat-stat-val"><?= $terlambat ?></div>
            <div class="riwayat-stat-label">Terlambat</div>
        </div>
    </div>
    <div class="riwayat-stat-card">
        <div class="riwayat-stat-icon" style="background: rgba(245, 158, 11, 0.12); color: #f59e0b;">
            <?= Icon::get('clipboard', 22) ?>
        </div>
        <div class="riwayat-stat-info">
            <div class="riwayat-stat-val"><?= $izinCuti ?></div>
            <div class="riwayat-stat-label">Izin / Cuti</div>
        </div>
    </div>
    <div class="riwayat-stat-card">
        <div class="riwayat-stat-icon" style="background: rgba(37, 99, 235, 0.12); color: #2563eb;">
            <?= Icon::get('calendar', 22) ?>
        </div>
        <div class="riwayat-stat-info">
            <div class="riwayat-stat-val" style="font-size: 1.15rem;"><?= htmlspecialchars($totalJamKerja) ?></div>
            <div class="riwayat-stat-label">Jam Kerja &bull; <?= $totalLembur > 0 ? $totalLembur . 'j Lembur' : 'Normal' ?></div>
        </div>
    </div>
</div>

<!-- Toolbar Filter Kategori & Bulan/Tahun -->
<div class="riwayat-filter-bar">
    <div class="riwayat-type-pills">
        <a href="<?= Config::baseUrl('riwayat?bulan=' . $bulan . '&tahun=' . $tahun . '&tipe=semua') ?>" 
           class="riwayat-pill-item <?= $tipe === 'semua' ? 'active' : '' ?>">
            <?= Icon::get('activity', 15) ?> Semua Aktivitas
        </a>
        <a href="<?= Config::baseUrl('riwayat?bulan=' . $bulan . '&tahun=' . $tahun . '&tipe=presensi') ?>" 
           class="riwayat-pill-item <?= $tipe === 'presensi' ? 'active' : '' ?>">
            <?= Icon::get('camera', 15) ?> Hadir Fisik
        </a>
        <a href="<?= Config::baseUrl('riwayat?bulan=' . $bulan . '&tahun=' . $tahun . '&tipe=ketidakhadiran') ?>" 
           class="riwayat-pill-item <?= $tipe === 'ketidakhadiran' ? 'active' : '' ?>">
            <?= Icon::get('clipboard', 15) ?> Izin & Cuti
        </a>
        <a href="<?= Config::baseUrl('riwayat?bulan=' . $bulan . '&tahun=' . $tahun . '&tipe=lembur') ?>" 
           class="riwayat-pill-item <?= $tipe === 'lembur' ? 'active' : '' ?>">
            <?= Icon::get('clock', 15) ?> Penugasan Lembur
        </a>
    </div>

    <form method="GET" action="<?= Config::baseUrl('riwayat') ?>" class="riwayat-filter-form">
        <input type="hidden" name="tipe" value="<?= htmlspecialchars($tipe) ?>">
        <label for="filterBulan" style="font-size: 0.84rem; font-weight: 600; color: var(--text-muted);">Periode:</label>
        <select id="filterBulan" name="bulan">
            <?php foreach ($namaBulan as $mNum => $mName): ?>
                <option value="<?= $mNum ?>" <?= $bulan === $mNum ? 'selected' : '' ?>><?= $mName ?></option>
            <?php endforeach; ?>
        </select>
        <select name="tahun">
            <?php 
                $curYear = (int)date('Y');
                for ($y = $curYear; $y >= $curYear - 2; $y--): 
            ?>
                <option value="<?= $y ?>" <?= $tahun === $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
        <button type="submit" class="btn btn-secondary" style="padding: 8px 14px; font-size: 0.84rem;">
            <?= Icon::get('search', 14) ?> Terapkan
        </button>
        <?php if ($bulan != (int)date('m') || $tahun != (int)date('Y') || $tipe !== 'semua'): ?>
            <a href="<?= Config::baseUrl('riwayat') ?>" class="btn btn-secondary" style="padding: 8px 12px; font-size: 0.84rem; color: var(--text-muted);" title="Reset ke Bulan Berjalan">
                <?= Icon::get('refresh', 14) ?> Reset
            </a>
        <?php endif; ?>
    </form>
</div>

<!-- Container Daftar Riwayat Lengkap -->
<div class="riwayat-container-card">
    <div class="riwayat-container-header">
        <h3>
            <?= Icon::get('calendar', 18) ?> 
            Log Aktivitas Periode <?= $selectedBulanText ?> <?= $tahun ?>
        </h3>
        <span class="riwayat-count-tag">
            <?= count($riwayatList) ?> Catatan Ditemukan
        </span>
    </div>

    <?php if (empty($riwayatList)): ?>
        <div class="riwayat-empty-state">
            <?= Icon::get('calendar', 48) ?>
            <h4>Tidak Ada Riwayat Aktivitas</h4>
            <p>Belum ada rekaman presensi fisik, perizinan, maupun penugasan lembur yang tercatat untuk periode <?= $selectedBulanText ?> <?= $tahun ?>.</p>
            <div style="display: flex; justify-content: center; gap: 10px;">
                <a href="<?= Config::baseUrl('ketidakhadiran/ajukan') ?>" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 6px;">
                    <?= Icon::get('clipboard', 16) ?> Ajukan Izin / Cuti Sekarang
                </a>
                <a href="<?= Config::baseUrl('riwayat') ?>" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 6px;">
                    <?= Icon::get('refresh', 14) ?> Kembali ke Bulan Ini
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="riwayat-list">
            <?php foreach ($riwayatList as $item): 
                $tipeAktivitas = $item['tipe_aktivitas'] ?? 'presensi';
                $tglFmt = date('d M Y', strtotime($item['tanggal']));
                $hariIndo = [
                    'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
                    'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
                ][date('l', strtotime($item['tanggal']))] ?? '';
            ?>
                <?php if ($tipeAktivitas === 'lembur'): 
                    $statusAssign = $item['status_approval'] ?? 'ditugaskan';
                    $statusSPL = $item['status_kehadiran'] ?? 'aktif';
                    
                    if ($statusAssign === 'selesai' || $statusSPL === 'selesai') {
                        $badgeCls = 'badge-success';
                        $badgeTxt = 'Selesai Realisasi';
                    } elseif ($statusAssign === 'dibatalkan' || $statusSPL === 'dibatalkan') {
                        $badgeCls = 'badge-danger';
                        $badgeTxt = 'Dibatalkan';
                    } else {
                        $badgeCls = 'badge-warning';
                        $badgeTxt = 'Ditugaskan (Aktif)';
                    }
                    $jamAktual = (float)($item['catatan_admin'] ?? 0);
                    $maksJam = (float)($item['jam_lembur'] ?? 0);
                    $nomorSPL = $item['kategori'] ?? 'SPL';
                ?>
                    <!-- Kartu Riwayat Penugasan Lembur (SPL) -->
                    <div class="riwayat-item-card" style="border-left: 4px solid #f59e0b;">
                        <div class="riwayat-item-main">
                            <div class="riwayat-item-icon" style="background: rgba(245, 158, 11, 0.14); color: #d97706;">
                                <?= Icon::get('clock', 22) ?>
                            </div>
                            <div class="riwayat-item-content">
                                <div class="riwayat-item-title-row">
                                    <h4 class="riwayat-item-title">
                                        Surat Perintah Lembur
                                    </h4>
                                    <span style="font-family: 'JetBrains Mono', monospace; font-size: 0.8rem; font-weight: 700; color: #d97706; background: rgba(245, 158, 11, 0.08); padding: 2px 8px; border-radius: 4px; border: 1px solid rgba(245, 158, 11, 0.25);">
                                        <?= htmlspecialchars($nomorSPL) ?>
                                    </span>
                                    <span class="badge <?= $badgeCls ?>"><?= $badgeTxt ?></span>
                                    <span style="font-size: 0.76rem; color: var(--text-muted); background: var(--bg-surface); padding: 2px 8px; border-radius: 4px; border: 1px solid var(--border-color);">
                                        <?= htmlspecialchars($item['nama_lokasi'] ?? 'Semua Lokasi') ?>
                                    </span>
                                </div>
                                <p class="riwayat-item-details">
                                    Jadwal: <strong><?= !empty($item['jam_masuk']) ? substr($item['jam_masuk'], 0, 5) : '00:00' ?> - <?= !empty($item['jam_pulang']) ? substr($item['jam_pulang'], 0, 5) : '00:00' ?> WIB</strong>
                                    (Maks. <strong style="color: var(--text-main);"><?= $maksJam ?> Jam</strong>)
                                    <br>
                                    Uraian Tugas: <strong>"<?= htmlspecialchars($item['keterangan'] ?? '-') ?>"</strong>
                                    <?php if ($jamAktual > 0): ?>
                                        <br>
                                        <span style="color: #10b981; font-weight: 600;">
                                            &bull; Realisasi Lembur Terverifikasi: <?= $jamAktual ?> Jam
                                        </span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        <div class="riwayat-item-side">
                            <div class="riwayat-date-pill">
                                <?= Icon::get('calendar', 14) ?> <?= $hariIndo ?>, <?= $tglFmt ?>
                            </div>
                            <span style="font-size: 0.78rem; color: #d97706; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                                <?= Icon::get('shield', 13) ?> Penugasan Resmi
                            </span>
                        </div>
                    </div>

                <?php elseif ($tipeAktivitas === 'ketidakhadiran'): 
                    $status = $item['status_approval'] ?? 'pending';
                    $badgeCls = ($status === 'disetujui') ? 'badge-success' : (($status === 'ditolak') ? 'badge-danger' : 'badge-warning');
                    $badgeTxt = ($status === 'disetujui') ? 'Disetujui' : (($status === 'ditolak') ? 'Ditolak' : 'Menunggu Review');
                    $kategori = ucfirst($item['kategori'] ?? 'Izin');
                ?>
                    <!-- Kartu Riwayat Izin / Sakit / Cuti -->
                    <div class="riwayat-item-card">
                        <div class="riwayat-item-main">
                            <div class="riwayat-item-icon" style="background: rgba(245, 158, 11, 0.14); color: #f59e0b;">
                                <?= Icon::get('clipboard', 22) ?>
                            </div>
                            <div class="riwayat-item-content">
                                <div class="riwayat-item-title-row">
                                    <h4 class="riwayat-item-title">
                                        Pengajuan <?= $kategori ?>
                                    </h4>
                                    <span class="badge <?= $badgeCls ?>"><?= $badgeTxt ?></span>
                                    <span style="font-size: 0.76rem; color: var(--text-muted); background: var(--bg-surface); padding: 2px 8px; border-radius: 4px; border: 1px solid var(--border-color);">
                                        Perizinan
                                    </span>
                                </div>
                                <p class="riwayat-item-details">
                                    Alasan: <strong>"<?= htmlspecialchars($item['keterangan'] ?? '-') ?>"</strong>
                                    <br>
                                    Periode: <strong><?= date('d M Y', strtotime($item['tanggal_mulai'])) ?></strong>
                                    <?php if (!empty($item['tanggal_selesai']) && $item['tanggal_selesai'] !== $item['tanggal_mulai']): ?>
                                        s/d <strong><?= date('d M Y', strtotime($item['tanggal_selesai'])) ?></strong>
                                    <?php endif; ?>
                                    <?php if (!empty($item['catatan_admin'])): ?>
                                        <br>
                                        <span style="color: var(--accent-blue); font-weight: 500;">
                                            &bull; Catatan Admin: <?= htmlspecialchars($item['catatan_admin']) ?>
                                        </span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        <div class="riwayat-item-side">
                            <div class="riwayat-date-pill">
                                <?= Icon::get('calendar', 14) ?> <?= $hariIndo ?>, <?= $tglFmt ?>
                            </div>
                            <a href="<?= Config::baseUrl('ketidakhadiran') ?>" style="font-size: 0.8rem; font-weight: 600; color: var(--accent-blue); text-decoration: none;">
                                Detail Permohonan &rarr;
                            </a>
                        </div>
                    </div>

                <?php else: 
                    $isLengkap = !empty($item['jam_pulang']);
                    $isTerlambat = ($item['status_kehadiran'] === 'terlambat' || (!empty($item['total_terlambat']) && $item['total_terlambat'] !== 'Tepat Waktu'));
                ?>
                    <!-- Kartu Riwayat Presensi Fisik -->
                    <div class="riwayat-item-card">
                        <div class="riwayat-item-main">
                            <div class="riwayat-item-icon <?= $isLengkap ? 'activity-icon-success' : '' ?>" style="background: <?= $isLengkap ? 'rgba(16, 185, 129, 0.12)' : 'rgba(37, 99, 235, 0.12)' ?>; color: <?= $isLengkap ? '#10b981' : '#2563eb' ?>;">
                                <?= Icon::get($isLengkap ? 'check-circle' : 'clock', 22) ?>
                            </div>
                            <div class="riwayat-item-content">
                                <div class="riwayat-item-title-row">
                                    <h4 class="riwayat-item-title">
                                        Presensi Harian <?= $isLengkap ? '(Lengkap)' : '(Masuk)' ?>
                                    </h4>
                                    <?php if ($isTerlambat): ?>
                                        <span class="badge badge-danger">
                                            Terlambat <?= (!empty($item['total_terlambat']) && $item['total_terlambat'] !== 'Tepat Waktu') ? '(' . htmlspecialchars($item['total_terlambat']) . ')' : '' ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-success">Tepat Waktu</span>
                                    <?php endif; ?>
                                    <span style="font-size: 0.76rem; color: var(--text-muted); background: var(--bg-surface); padding: 2px 8px; border-radius: 4px; border: 1px solid var(--border-color);">
                                        <?= htmlspecialchars($item['nama_lokasi'] ?? 'Kantor SGM') ?>
                                    </span>
                                </div>
                                <p class="riwayat-item-details">
                                    Masuk: <strong><?= !empty($item['jam_masuk']) ? substr($item['jam_masuk'], 0, 5) . ' WIB' : '-' ?></strong>
                                    &bull; 
                                    Pulang: <strong><?= !empty($item['jam_pulang']) ? substr($item['jam_pulang'], 0, 5) . ' WIB' : 'Belum Absen Pulang' ?></strong>
                                    <?php if ($isLengkap): ?>
                                        &bull; Total Kerja: <strong><?= htmlspecialchars($item['total_jam_kerja'] ?? '-') ?></strong>
                                    <?php endif; ?>
                                    <?php if (!empty($item['jam_lembur']) && (float)$item['jam_lembur'] > 0): ?>
                                        &bull; Lembur: <strong style="color: var(--accent-blue);"><?= (float)$item['jam_lembur'] ?> Jam</strong>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        <div class="riwayat-item-side">
                            <div class="riwayat-date-pill">
                                <?= Icon::get('calendar', 14) ?> <?= $hariIndo ?>, <?= $tglFmt ?>
                            </div>
                            <?php if (!$isLengkap && $item['tanggal'] === date('Y-m-d')): ?>
                                <a href="<?= Config::baseUrl('presensi/keluar') ?>" class="btn btn-danger" style="padding: 5px 12px; font-size: 0.78rem; text-decoration: none;">
                                    Absen Pulang
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
