<?php
/**
 * Penugasan Lembur - Index View
 * Absensi Surya Gemilang Mojopahit (SGM)
 */

$pageTitle = 'Penugasan Lembur Karyawan - ' . Config::APP_NAME;
$activePage = 'lembur';
require_once __DIR__ . '/../layouts/header.php';

$namaBulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
?>

<div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
    <div>
        <h2 style="font-size: 1.4rem; font-weight: 800; color: var(--text-main); display: flex; align-items: center; gap: 10px; margin: 0 0 6px 0;">
            <?= Icon::get('clock', 24) ?> Penugasan Lembur Karyawan
        </h2>
        <p style="font-size: 0.88rem; color: var(--text-muted); margin: 0;">
            Kelola Surat Perintah Lembur (SPL). Pegawai hanya dapat mencatat uang lembur jika resmi ditugaskan oleh Admin.
        </p>
    </div>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="<?= Config::baseUrl('lembur/tambah') ?>" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px;">
            <?= Icon::get('plus', 16) ?> <span>Buat Penugasan Lembur</span>
        </a>
    </div>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success">
        <?php 
            if ($_GET['msg'] === 'created') echo '✅ Surat Perintah Lembur berhasil dibuat dan ditugaskan ke pegawai!';
            if ($_GET['msg'] === 'updated') echo '✅ Data penugasan lembur berhasil diperbarui!';
            if ($_GET['msg'] === 'deleted') echo '✅ Penugasan lembur berhasil dihapus dari sistem!';
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['err'])): ?>
    <div class="alert alert-danger">
        <?php
            if ($_GET['err'] === 'empty_fields') echo '⚠️ Tanggal, lokasi, jam, dan keterangan wajib diisi!';
            if ($_GET['err'] === 'invalid_time') echo '⚠️ Jam selesai lembur harus lebih besar dari jam mulai lembur!';
            if ($_GET['err'] === 'no_employee_selected') echo '⚠️ Minimal harus memilih 1 pegawai yang ditugaskan lembur!';
            if ($_GET['err'] === 'save_failed') echo '⚠️ Gagal menyimpan data penugasan lembur ke database!';
            if ($_GET['err'] === 'not_found') echo '⚠️ Data penugasan lembur tidak ditemukan!';
            if ($_GET['err'] === 'delete_failed') echo '⚠️ Gagal menghapus data penugasan lembur!';
        ?>
    </div>
<?php endif; ?>

<div class="table-card">
    <!-- Toolbar Filter -->
    <div class="table-toolbar" style="padding: 16px 20px; border-bottom: 1px solid var(--border-color);">
        <form method="GET" action="<?= Config::baseUrl('lembur') ?>" style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; width: 100%; gap: 12px;">
            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <label style="font-size: 0.84rem; font-weight: 700; color: var(--text-muted);">Periode:</label>
                <select name="bulan" class="form-input" style="padding: 6px 12px; font-size: 0.85rem; width: auto;">
                    <?php foreach ($namaBulan as $mNum => $mName): ?>
                        <option value="<?= $mNum ?>" <?= $bulan === $mNum ? 'selected' : '' ?>><?= $mName ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="tahun" class="form-input" style="padding: 6px 12px; font-size: 0.85rem; width: auto;">
                    <?php 
                        $curYear = (int)date('Y');
                        for ($y = $curYear; $y >= $curYear - 2; $y--): 
                    ?>
                        <option value="<?= $y ?>" <?= $tahun === $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>

                <label style="font-size: 0.84rem; font-weight: 700; color: var(--text-muted); margin-left: 6px;">Lokasi:</label>
                <select name="lokasi_id" class="form-input" style="padding: 6px 12px; font-size: 0.85rem; width: auto;">
                    <option value="">Semua Lokasi</option>
                    <?php foreach ($lokasiList as $lok): ?>
                        <option value="<?= $lok['id'] ?>" <?= $lokasiId === (int)$lok['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($lok['nama_lokasi']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label style="font-size: 0.84rem; font-weight: 700; color: var(--text-muted); margin-left: 6px;">Status:</label>
                <select name="status" class="form-input" style="padding: 6px 12px; font-size: 0.85rem; width: auto;">
                    <option value="">Semua Status</option>
                    <option value="aktif" <?= $status === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                    <option value="selesai" <?= $status === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                    <option value="dibatalkan" <?= $status === 'dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
                </select>

                <button type="submit" class="btn btn-secondary" style="padding: 6px 14px; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 6px;">
                    <?= Icon::get('search', 14) ?> Filter
                </button>
                <?php if ($bulan != (int)date('m') || $tahun != (int)date('Y') || !empty($lokasiId) || !empty($status)): ?>
                    <a href="<?= Config::baseUrl('lembur') ?>" class="btn btn-secondary" style="padding: 6px 10px; font-size: 0.85rem;" title="Reset Filter">
                        <?= Icon::get('refresh-cw', 14) ?>
                    </a>
                <?php endif; ?>
            </div>
            
            <div style="font-size: 0.82rem; color: var(--text-muted); font-weight: 600;">
                Total: <?= count($penugasanList) ?> Surat Perintah
            </div>
        </form>
    </div>

    <!-- Looker Studio Filter Bar -->
    <div class="looker-filter-bar" data-table="#lemburTable">
        <div class="looker-filter-bar-left">
            <!-- Quick Search Input -->
            <div class="looker-search-box">
                <span class="looker-search-icon"><?= Icon::get('search', 14) ?></span>
                <input type="text" class="looker-search-input" data-table="#lemburTable" placeholder="Cari No. SPL / Tugas...">
            </div>

            <!-- Sort Select -->
            <div class="looker-sort-box">
                <select class="table-sort-select" data-table="#lemburTable">
                    <option value="default">Urutkan: Default</option>
                    <option value="spl-desc">No. SPL (Terbaru)</option>
                    <option value="spl-asc">No. SPL (Terlama)</option>
                    <option value="lokasi-asc">Lokasi (A - Z)</option>
                    <option value="status-asc">Status (A - Z)</option>
                </select>
            </div>

            <!-- Filter Pill: Status -->
            <div class="looker-pill-wrapper" data-table="#lemburTable" data-filter-col="status" data-label="Status Lembur">
                <button type="button" class="looker-filter-pill">
                    <span class="looker-pill-text">Status</span>
                    <span class="looker-pill-badge" style="display:none;"></span>
                    <span class="looker-pill-caret">▼</span>
                </button>
                <div class="looker-popover">
                    <div class="looker-popover-header">
                        <label class="looker-select-all-label">
                            <input type="checkbox" class="looker-select-all-input" checked> Pilih Semua
                        </label>
                    </div>
                    <div class="looker-popover-search">
                        <span class="looker-popover-search-icon"><?= Icon::get('search', 12) ?></span>
                        <input type="text" class="looker-popover-input" placeholder="Cari status...">
                    </div>
                    <div class="looker-checklist">
                        <label class="looker-check-item">
                            <span class="looker-check-left">
                                <input type="checkbox" value="aktif" checked>
                                <span class="looker-check-label">Aktif</span>
                            </span>
                            <span class="looker-check-count"></span>
                        </label>
                        <label class="looker-check-item">
                            <span class="looker-check-left">
                                <input type="checkbox" value="selesai" checked>
                                <span class="looker-check-label">Selesai</span>
                            </span>
                            <span class="looker-check-count"></span>
                        </label>
                        <label class="looker-check-item">
                            <span class="looker-check-left">
                                <input type="checkbox" value="dibatalkan" checked>
                                <span class="looker-check-label">Dibatalkan</span>
                            </span>
                            <span class="looker-check-count"></span>
                        </label>
                    </div>
                    <div class="looker-popover-footer">
                        <span class="looker-footer-status">Semua dipilih</span>
                        <button type="button" class="looker-clear-btn">Reset</button>
                    </div>
                </div>
            </div>

            <!-- Reset All Button -->
            <button type="button" class="looker-reset-all-btn" data-table="#lemburTable" title="Reset semua filter">
                <?= Icon::get('refresh-cw', 13) ?> Reset
            </button>
        </div>
        <div class="looker-filter-bar-right">
            <span class="looker-row-count"></span>
        </div>
    </div>

    <!-- Table Responsive -->
    <div class="table-responsive">
        <table class="table-custom" id="lemburTable">
            <thead>
                <tr>
                    <th style="width: 48px; min-width: 48px; text-align: center;">No</th>
                    <th class="sortable-th" data-col="spl" data-sort-type="text" style="min-width: 140px;">No. SPL & Tanggal</th>
                    <th class="sortable-th" data-col="lokasi" data-sort-type="text" style="min-width: 170px;">Lokasi Penugasan</th>
                    <th style="min-width: 135px;">Waktu Lembur</th>
                    <th style="min-width: 155px;">Pegawai Ditugaskan</th>
                    <th style="min-width: 170px;">Uraian Tugas</th>
                    <th class="sortable-th" data-col="status" data-sort-type="text" style="min-width: 85px; text-align: center;">Status</th>
                    <th style="text-align: center; width: 110px; min-width: 110px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($penugasanList)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 48px 16px; color: var(--text-muted);">
                            <div style="margin-bottom: 10px;"><?= Icon::get('clock', 40) ?></div>
                            <div style="font-weight: 700; color: var(--text-main); font-size: 1rem;">Tidak ada penugasan lembur</div>
                            <p style="font-size: 0.85rem; margin-top: 4px;">Belum ada Surat Perintah Lembur yang dibuat untuk periode atau kriteria yang dipilih.</p>
                            <a href="<?= Config::baseUrl('lembur/tambah') ?>" class="btn btn-primary" style="margin-top: 12px; display: inline-flex; align-items: center; gap: 6px; font-size: 0.85rem;">
                                <?= Icon::get('plus', 14) ?> Buat Penugasan Sekarang
                            </a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($penugasanList as $row): 
                        $badgeCls = ($row['status'] === 'aktif') ? 'badge-success' : (($row['status'] === 'selesai') ? 'badge-info' : 'badge-danger');
                        $tglFmt = date('d M Y', strtotime($row['tanggal']));
                        $jamMulaiFmt = substr($row['jam_mulai'], 0, 5);
                        $jamSelesaiFmt = substr($row['jam_selesai'], 0, 5);
                    ?>
                        <tr>
                            <td style="text-align: center; font-weight: 600; color: var(--text-muted);"><?= $no++ ?></td>
                            <td data-sort-value="<?= htmlspecialchars($row['nomor_sp_lembur']) ?>">
                                <div style="font-weight: 700; color: var(--text-main); font-family: 'JetBrains Mono', monospace; font-size: 0.88rem;">
                                    <?= htmlspecialchars($row['nomor_sp_lembur']) ?>
                                </div>
                                <div style="font-size: 0.78rem; color: var(--text-muted); display: flex; align-items: center; gap: 4px; margin-top: 2px;">
                                    <?= Icon::get('calendar', 12) ?> <?= $tglFmt ?>
                                </div>
                            </td>
                            <td data-sort-value="<?= htmlspecialchars($row['nama_lokasi'] ?? 'Kantor SGM') ?>">
                                <div style="font-weight: 600; color: var(--text-main); font-size: 0.88rem;">
                                    <?= htmlspecialchars($row['nama_lokasi'] ?? 'Kantor SGM') ?>
                                </div>
                                <div style="font-size: 0.76rem; color: var(--text-muted);">
                                    Jam pulang reguler: <?= !empty($row['jam_pulang_standar']) ? substr($row['jam_pulang_standar'], 0, 5) . ' WIB' : '17:00' ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 700; color: var(--accent-blue); font-size: 0.86rem; font-family: 'JetBrains Mono', monospace;">
                                    <?= $jamMulaiFmt ?> - <?= $jamSelesaiFmt ?> WIB
                                </div>
                                <div style="font-size: 0.76rem; color: var(--text-muted);">
                                    Maks: <strong><?= (float)$row['maksimal_jam'] ?> Jam</strong>
                                </div>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <span class="badge badge-info" style="font-weight: 700; font-size: 0.78rem;">
                                        <?= (int)$row['total_pegawai'] ?> Pegawai
                                    </span>
                                </div>
                                <div style="font-size: 0.76rem; color: var(--text-muted); max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 3px;" title="<?= htmlspecialchars($row['daftar_pegawai_text'] ?? '') ?>">
                                    <?= htmlspecialchars($row['daftar_pegawai_text'] ?? '-') ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-size: 0.84rem; color: var(--text-main); max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($row['keterangan']) ?>">
                                    <?= htmlspecialchars($row['keterangan']) ?>
                                </div>
                                <?php if (!empty($row['nama_creator'])): ?>
                                    <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 2px;">
                                        Oleh: <?= htmlspecialchars($row['nama_creator']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td data-sort-value="<?= htmlspecialchars($row['status']) ?>" style="text-align: center;">
                                <span class="badge <?= $badgeCls ?>"><?= ucfirst($row['status']) ?></span>
                            </td>
                            <td style="text-align: center;">
                                <div class="action-btn-group">
                                    <a href="<?= Config::baseUrl('lembur/detail?id=' . $row['id']) ?>" 
                                       class="btn-action btn-lembur-view" 
                                       title="Lihat Detail & Status Pegawai">
                                        <?= Icon::get('eye', 15) ?>
                                    </a>
                                    <a href="<?= Config::baseUrl('lembur/edit?id=' . $row['id']) ?>" 
                                       class="btn-action btn-lembur-edit" 
                                       title="Edit Penugasan">
                                        <?= Icon::get('edit', 15) ?>
                                    </a>
                                    <a href="<?= Config::baseUrl('lembur/hapus?id=' . $row['id']) ?>" 
                                       class="btn-action btn-lembur-delete" 
                                       title="Hapus Penugasan"
                                       data-confirm="Apakah Anda yakin ingin menghapus penugasan lembur <?= htmlspecialchars($row['nomor_sp_lembur']) ?>? Tindakan ini tidak dapat dibatalkan."
                                       data-confirm-title="Hapus Penugasan Lembur"
                                       data-confirm-type="delete"
                                       data-confirm-btn="Ya, Hapus">
                                        <?= Icon::get('trash', 15) ?>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
