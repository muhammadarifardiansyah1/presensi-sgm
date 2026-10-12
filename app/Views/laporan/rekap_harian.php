<?php
$pageTitle = 'Rekap Presensi Harian - ' . Config::APP_NAME;
$activePage = 'laporan_harian';
require_once __DIR__ . '/../layouts/header.php';

$namaBulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

function formatTanggalIndo(string $dateStr, array $namaBulan): string {
    $timestamp = strtotime($dateStr);
    $day = date('d', $timestamp);
    $month = (int)date('m', $timestamp);
    $year = date('Y', $timestamp);
    return $day . ' ' . ($namaBulan[$month] ?? date('F', $timestamp)) . ' ' . $year;
}

$tanggalMulaiSelected = $tanggalMulai ?? date('Y-m-d');
$tanggalSelesaiSelected = $tanggalSelesai ?? $tanggalMulaiSelected;
?>

<div style="margin-bottom: 20px;">
    <h2 style="font-size: 1.4rem; font-weight: 700;">Rekap Presensi Harian</h2>
</div>

<!-- Alert Notifikasi -->
<?php if (isset($_GET['msg']) && $_GET['msg'] === 'presensi_added'): ?>
    <div class="alert alert-success" style="margin-bottom: 20px;">
        <?= Icon::get('check-circle', 18) ?> Data presensi manual berhasil ditambahkan.
    </div>
<?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'presensi_updated'): ?>
    <div class="alert alert-success" style="margin-bottom: 20px;">
        <?= Icon::get('check-circle', 18) ?> Data presensi berhasil diperbarui.
    </div>
<?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div class="alert alert-success" style="margin-bottom: 20px;">
        <?= Icon::get('check-circle', 18) ?> Data presensi berhasil dihapus.
    </div>
<?php endif; ?>

<?php if (isset($_GET['err'])): ?>
    <?php if ($_GET['err'] === 'duplicate_presensi'): ?>
        <div class="alert alert-danger" style="margin-bottom: 20px;">
            <?= Icon::get('alert-triangle', 18) ?> <strong>Input Presensi Gagal:</strong> Pegawai yang dipilih sudah memiliki catatan presensi pada tanggal tersebut. Silakan gunakan tombol <strong>Edit</strong> pada tabel di bawah untuk memperbarui jam masuk atau pulang.
        </div>
    <?php elseif ($_GET['err'] === 'export_range_too_large'): ?>
        <div class="alert alert-danger" style="margin-bottom: 20px;">
            <?= Icon::get('alert-triangle', 18) ?> <strong>Rentang Ekspor Dibatasi:</strong> Ekspor harian dibatasi maksimal 31 hari per unduhan demi menjaga performa server hosting. Untuk rentang lebih panjang, gunakan menu <strong>Rekap Bulanan</strong>.
        </div>
    <?php elseif ($_GET['err'] === 'invalid_date'): ?>
        <div class="alert alert-danger" style="margin-bottom: 20px;">
            <?= Icon::get('alert-triangle', 18) ?> <strong>Format Tanggal Tidak Valid:</strong> Tanggal mulai tidak boleh melebihi tanggal selesai.
        </div>
    <?php elseif ($_GET['err'] === 'presensi_invalid'): ?>
        <div class="alert alert-danger" style="margin-bottom: 20px;">
            <?= Icon::get('alert-triangle', 18) ?> Pegawai dan tanggal wajib dipilih!
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Filter Bar (Matches Screenshot 5) -->
<div class="form-card" style="padding: 16px 20px; margin-bottom: 24px;">
    <form method="GET" action="<?= Config::baseUrl('laporan/harian') ?>" style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
        <?php if (($currentUser['role'] ?? '') === 'admin'): ?>
            <a href="<?= Config::baseUrl("laporan/export-excel-harian?tanggal_mulai={$tanggalMulaiSelected}&tanggal_selesai={$tanggalSelesaiSelected}") ?>" data-turbo="false" class="btn btn-blue">
                Export Excel
            </a>
            <button type="button" class="btn btn-primary" onclick="bukaModalTambahPresensi()" style="display:inline-flex; align-items:center; gap:6px;">
                <?= Icon::get('plus', 16) ?> Input Presensi Manual
            </button>
        <?php endif; ?>

        <div>
            <input type="date" name="tanggal_mulai" class="form-input" value="<?= htmlspecialchars($tanggalMulaiSelected) ?>">
        </div>

        <div>
            <input type="date" name="tanggal_selesai" class="form-input" value="<?= htmlspecialchars($tanggalSelesaiSelected) ?>">
        </div>

        <button type="submit" class="btn btn-blue">Tampilkan</button>
        <a href="<?= Config::baseUrl('laporan/harian') ?>" class="btn btn-success">Refresh</a>
    </form>
</div>

<!-- Subtitle / Periode info -->
<div style="margin-bottom: 12px; font-weight: 700; font-size: 1.05rem; color: var(--text-main);">
    <?php if ($tanggalMulaiSelected === $tanggalSelesaiSelected): ?>
        Rekap presensi tanggal <?= formatTanggalIndo($tanggalMulaiSelected, $namaBulan) ?>
    <?php else: ?>
        Rekap presensi tanggal <?= formatTanggalIndo($tanggalMulaiSelected, $namaBulan) ?> s/d <?= formatTanggalIndo($tanggalSelesaiSelected, $namaBulan) ?>
    <?php endif; ?>
</div>

<div class="table-card">
    <div class="looker-filter-bar" data-table="#rekapHarianTable">
        <div class="looker-filter-bar-left">
            <!-- Quick Search Input -->
            <div class="looker-search-box">
                <span class="looker-search-icon"><?= Icon::get('search', 14) ?></span>
                <input type="text" class="looker-search-input" data-table="#rekapHarianTable" placeholder="Cari Nama Pegawai...">
            </div>

            <!-- Sort Select -->
            <div class="looker-sort-box">
                <select class="table-sort-select" data-table="#rekapHarianTable">
                    <option value="default">Urutkan: Default</option>
                    <option value="masuk-desc">Jam Masuk (Paling Akhir)</option>
                    <option value="masuk-asc">Jam Masuk (Paling Awal)</option>
                    <option value="tanggal-desc">Tanggal (Terbaru)</option>
                    <option value="tanggal-asc">Tanggal (Terlama)</option>
                    <option value="nama-asc">Nama Pegawai (A - Z)</option>
                    <option value="nama-desc">Nama Pegawai (Z - A)</option>
                    <option value="durasi-desc">Total Jam Kerja (Terlama)</option>
                    <option value="terlambat-desc">Keterlambatan (Paling Lama)</option>
                </select>
            </div>

            <!-- Filter Pill: Keterlambatan -->
            <div class="looker-pill-wrapper" data-table="#rekapHarianTable" data-filter-col="terlambat" data-label="Ketepatan Waktu">
                <button type="button" class="looker-filter-pill">
                    <span class="looker-pill-text">Ketepatan Waktu</span>
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
                                <input type="checkbox" value="tepat" checked>
                                <span class="looker-check-label">Tepat Waktu</span>
                            </span>
                            <span class="looker-check-count"></span>
                        </label>
                        <label class="looker-check-item">
                            <span class="looker-check-left">
                                <input type="checkbox" value="terlambat" checked>
                                <span class="looker-check-label">Terlambat</span>
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
            <button type="button" class="looker-reset-all-btn" data-table="#rekapHarianTable" title="Reset semua filter">
                <?= Icon::get('refresh-cw', 13) ?> Reset
            </button>
        </div>
        <div class="looker-filter-bar-right">
            <span class="looker-row-count"></span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table-custom" id="rekapHarianTable">
            <thead>
                <tr>
                    <th style="width: 40px; text-align: center;">No.</th>
                    <th class="sortable-th" data-col="nama" data-sort-type="text">Nama</th>
                    <th class="sortable-th" data-col="tanggal" data-sort-type="date">Tanggal</th>
                    <th class="sortable-th" data-col="masuk" data-sort-type="time">Jam Masuk</th>
                    <th style="text-align: center;">Foto Masuk</th>
                    <th class="sortable-th" data-col="pulang" data-sort-type="time">Jam Pulang</th>
                    <th style="text-align: center;">Foto Keluar</th>
                    <th class="sortable-th" data-col="durasi" data-sort-type="duration">Total Jam</th>
                    <th class="sortable-th" data-col="terlambat" data-sort-type="duration">Terlambat</th>
                    <th class="sortable-th" data-col="lembur" data-sort-type="duration">Lembur</th>
                    <?php if (($currentUser['role'] ?? '') === 'admin'): ?>
                        <th style="width: 90px; text-align: center;">Aksi</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($dataPresensi)): ?>
                    <tr>
                        <td colspan="<?= (($currentUser['role'] ?? '') === 'admin') ? '11' : '10' ?>" style="text-align: center; color: var(--text-muted); padding: 36px;">
                            Data rekap presensi masih kosong!
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($dataPresensi as $row): ?>
                        <tr>
                            <td style="text-align: center;"><?= $no++ ?></td>
                            <td data-sort-value="<?= htmlspecialchars($row['nama_pegawai']) ?>">
                                <strong><?= htmlspecialchars($row['nama_pegawai']) ?></strong><br>
                                <small style="color:var(--text-muted);"><?= htmlspecialchars($row['nik']) ?></small>
                                <?php if (!empty($row['keterangan_admin'])): ?>
                                    <br><span class="badge badge-info" style="font-size:0.7rem;"><?= htmlspecialchars($row['keterangan_admin']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td data-sort-value="<?= htmlspecialchars($row['tanggal']) ?>">
                                <?= formatTanggalIndo($row['tanggal'], $namaBulan) ?>
                            </td>
                            <td data-sort-value="<?= htmlspecialchars($row['jam_masuk'] ?? '-') ?>" style="font-family:'JetBrains Mono', monospace; font-weight:600;">
                                <?= htmlspecialchars($row['jam_masuk'] ?? '-') ?>
                            </td>
                            <td style="text-align: center;">
                                <?php if (!empty($row['foto_masuk'])): 
                                    $urlFotoMasuk = ImageHelper::getPhotoUrl($row['foto_masuk'], 'presensi');
                                    $isDriveMasuk = (str_starts_with($row['foto_masuk'], 'http://') || str_starts_with($row['foto_masuk'], 'https://'));
                                ?>
                                    <div style="display:inline-flex; flex-direction:column; align-items:center; gap:2px;">
                                        <a href="<?= $urlFotoMasuk ?>" target="_blank" title="Lihat Foto Masuk (Klik untuk perbesar)">
                                            <img src="<?= $urlFotoMasuk ?>" class="img-thumb-table" alt="Foto Masuk" onerror="this.onerror=null; this.src='<?= Config::baseUrl('assets/images/default-avatar.png') ?>';">
                                        </a>
                                        <a href="<?= $urlFotoMasuk ?>" target="_blank" style="font-size:10px; color:var(--accent-blue); text-decoration:none; font-weight:600;">
                                            <?= $isDriveMasuk ? 'Buka Drive' : 'Buka Foto' ?>
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <span style="color:var(--text-subtle);">-</span>
                                <?php endif; ?>
                            </td>
                            <td data-sort-value="<?= htmlspecialchars($row['jam_pulang'] ?? '-') ?>" style="font-family:'JetBrains Mono', monospace; font-weight:600;">
                                <?= htmlspecialchars($row['jam_pulang'] ?? '-') ?>
                            </td>
                            <td style="text-align: center;">
                                <?php if (!empty($row['foto_keluar'])): 
                                    $urlFotoKeluar = ImageHelper::getPhotoUrl($row['foto_keluar'], 'presensi');
                                    $isDriveKeluar = (str_starts_with($row['foto_keluar'], 'http://') || str_starts_with($row['foto_keluar'], 'https://'));
                                ?>
                                    <div style="display:inline-flex; flex-direction:column; align-items:center; gap:2px;">
                                        <a href="<?= $urlFotoKeluar ?>" target="_blank" title="Lihat Foto Keluar (Klik untuk perbesar)">
                                            <img src="<?= $urlFotoKeluar ?>" class="img-thumb-table" alt="Foto Keluar" onerror="this.onerror=null; this.src='<?= Config::baseUrl('assets/images/default-avatar.png') ?>';">
                                        </a>
                                        <a href="<?= $urlFotoKeluar ?>" target="_blank" style="font-size:10px; color:var(--accent-blue); text-decoration:none; font-weight:600;">
                                            <?= $isDriveKeluar ? 'Buka Drive' : 'Buka Foto' ?>
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <span style="color:var(--text-subtle);">-</span>
                                <?php endif; ?>
                            </td>
                            <td data-sort-value="<?= htmlspecialchars($row['total_jam_kerja'] ?? '-') ?>">
                                <?= htmlspecialchars($row['total_jam_kerja'] ?? '-') ?>
                            </td>
                            <td data-sort-value="<?= htmlspecialchars($row['total_terlambat'] ?? 'Tepat Waktu') ?>">
                                <?php if (!empty($row['total_terlambat']) && $row['total_terlambat'] !== 'Tepat Waktu'): ?>
                                    <span class="badge badge-danger">
                                        <?= htmlspecialchars($row['total_terlambat']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-success">
                                        Tepat Waktu
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td data-sort-value="<?= (float)($row['jam_lembur'] ?? 0) ?>" style="font-family:'JetBrains Mono', monospace;">
                                <?php if (!empty($row['jam_lembur']) && $row['jam_lembur'] > 0): ?>
                                    <span class="badge badge-info" style="font-weight:600;">+<?= number_format($row['jam_lembur'], 2) ?> jam</span>
                                <?php else: ?>
                                    <span style="color:var(--text-subtle);">-</span>
                                <?php endif; ?>
                            </td>
                            <?php if (($currentUser['role'] ?? '') === 'admin'): ?>
                                <td style="text-align: center;">
                                    <div style="display:flex; gap:4px; justify-content:center;">
                                        <button type="button" 
                                                class="btn btn-secondary btn-sm" 
                                                onclick="bukaModalEditPresensi(<?= (int)$row['id'] ?>)" 
                                                title="Edit Informasi Presensi">
                                            <?= Icon::get('edit', 14) ?>
                                        </button>
                                        <a href="<?= Config::baseUrl('laporan/hapus-presensi?id=' . $row['id']) ?>" 
                                           class="btn btn-danger btn-sm" 
                                           data-confirm="Apakah Anda yakin ingin menghapus catatan presensi ini? Pegawai akan dapat melakukan presensi ulang untuk tanggal ini."
                                           data-confirm-title="Hapus / Reset Presensi Pegawai"
                                           data-confirm-type="danger"
                                           data-confirm-btn="Ya, Hapus Presensi"
                                           title="Hapus / Reset Presensi">
                                            <?= Icon::get('trash', 14) ?>
                                        </a>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (($currentUser['role'] ?? '') === 'admin'): ?>
<!-- MODAL: Tambah Presensi Manual -->
<div id="modalTambahPresensi" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="modalTambahPresensiTitle">
    <div class="modal-card modal-card-md">
        <div class="modal-header">
            <div class="modal-header-left">
                <div class="modal-header-icon">
                    <?= Icon::get('plus', 20) ?>
                </div>
                <div>
                    <h3 id="modalTambahPresensiTitle" class="modal-title">Input Presensi Manual Karyawan</h3>
                    <div class="modal-subtitle">Tambah absensi karyawan dengan kalkulasi otomatis</div>
                </div>
            </div>
            <button type="button" class="modal-close-btn" onclick="tutupModalTambahPresensi()" aria-label="Tutup">
                <?= Icon::get('x', 18) ?>
            </button>
        </div>

        <form method="POST" action="<?= Config::baseUrl('laporan/tambah-presensi') ?>" style="display:flex; flex-direction:column; flex:1; overflow:hidden;">
            <input type="hidden" name="redirect_url" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">

            <div class="modal-body">
                <div class="form-group" style="margin-bottom:16px;">
                    <label class="form-label" style="font-weight:600;">Pilih Pegawai:</label>
                    <select name="user_id" class="form-input" required>
                        <option value="">-- Pilih Pegawai --</option>
                        <?php foreach ($pegawaiList as $peg): ?>
                            <option value="<?= $peg['id'] ?>"><?= htmlspecialchars($peg['nama']) ?> (<?= htmlspecialchars($peg['nik']) ?> - <?= htmlspecialchars($peg['jabatan'] ?? '') ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom:16px;">
                    <label class="form-label" style="font-weight:600;">Lokasi Presensi:</label>
                    <select name="lokasi_id" class="form-input">
                        <?php foreach ($lokasiList as $lok): ?>
                            <option value="<?= $lok['id'] ?>"><?= htmlspecialchars($lok['nama_lokasi']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:16px;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label" style="font-weight:600;">Tanggal:</label>
                        <input type="date" name="tanggal" class="form-input" value="<?= $tanggalMulaiSelected ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label" style="font-weight:600;">Jam Masuk:</label>
                        <input type="time" step="1" name="jam_masuk" class="form-input" value="08:00:00" required>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:16px;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label" style="font-weight:600;">Jam Pulang:</label>
                        <input type="time" step="1" name="jam_pulang" class="form-input" value="17:00:00">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label" style="font-weight:600;">Lembur (Jam):</label>
                        <input type="number" step="0.25" min="0" max="24" name="jam_lembur" class="form-input" value="0">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label" style="font-weight:600;">Keterangan / Alasan:</label>
                    <input type="text" name="keterangan_admin" class="form-input" placeholder="Contoh: Koreksi absensi lapangan / input manual">
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="tutupModalTambahPresensi()">Batal</button>
                <button type="submit" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:6px;">
                    <?= Icon::get('save', 16) ?> Simpan Presensi
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: Edit Presensi Manual -->
<div id="modalEditPresensi" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="modalEditPresensiTitle">
    <div class="modal-card modal-card-md">
        <div class="modal-header">
            <div class="modal-header-left">
                <div class="modal-header-icon" style="background:var(--accent-blue-light); color:var(--accent-blue);">
                    <?= Icon::get('edit', 20) ?>
                </div>
                <div>
                    <h3 id="modalEditPresensiTitle" class="modal-title">Edit Informasi Presensi Karyawan</h3>
                    <div class="modal-subtitle">Perbarui jam kehadiran, durasi kerja, dan lembur</div>
                </div>
            </div>
            <button type="button" class="modal-close-btn" onclick="tutupModalEditPresensi()" aria-label="Tutup">
                <?= Icon::get('x', 18) ?>
            </button>
        </div>

        <form method="POST" action="<?= Config::baseUrl('laporan/edit-presensi') ?>" id="formEditPresensi" style="display:flex; flex-direction:column; flex:1; overflow:hidden;">
            <input type="hidden" name="id" id="edit_id" value="">
            <input type="hidden" name="redirect_url" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">

            <div class="modal-body">
                <div class="form-group" style="margin-bottom:16px;">
                    <label class="form-label" style="font-weight:600;">Pegawai:</label>
                    <input type="text" id="edit_nama_pegawai" class="form-input" style="background:var(--bg-subtle); font-weight:600;" readonly>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:16px;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label" style="font-weight:600;">Tanggal:</label>
                        <input type="date" name="tanggal" id="edit_tanggal" class="form-input" required>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label" style="font-weight:600;">Jam Masuk:</label>
                        <input type="time" step="1" name="jam_masuk" id="edit_jam_masuk" class="form-input" required>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:16px;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label" style="font-weight:600;">Jam Pulang:</label>
                        <input type="time" step="1" name="jam_pulang" id="edit_jam_pulang" class="form-input">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label" style="font-weight:600;">Lembur (Jam):</label>
                        <input type="number" step="0.25" min="0" max="24" name="jam_lembur" id="edit_jam_lembur" class="form-input" value="0">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label" style="font-weight:600;">Keterangan Admin:</label>
                    <input type="text" name="keterangan_admin" id="edit_keterangan_admin" class="form-input" placeholder="Catatan perubahan / penyesuaian">
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="tutupModalEditPresensi()">Batal</button>
                <button type="submit" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:6px;">
                    <?= Icon::get('save', 16) ?> Update Presensi
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function bukaModalTambahPresensi() {
    const modal = document.getElementById('modalTambahPresensi');
    if (modal) {
        modal.classList.add('show');
    }
}
function tutupModalTambahPresensi() {
    const modal = document.getElementById('modalTambahPresensi');
    if (modal) {
        modal.classList.remove('show');
    }
}

function bukaModalEditPresensi(id) {
    const modal = document.getElementById('modalEditPresensi');
    if (modal) {
        modal.classList.add('show');
    }
    
    // Fetch detail via AJAX
    fetch('<?= Config::baseUrl("laporan/detail-presensi?id=") ?>' + id)
        .then(response => response.json())
        .then(data => {
            if (data && !data.error) {
                document.getElementById('edit_id').value = data.id;
                document.getElementById('edit_nama_pegawai').value = (data.nama_pegawai || 'Pegawai') + ' (' + (data.nik || '') + ')';
                document.getElementById('edit_tanggal').value = data.tanggal;
                document.getElementById('edit_jam_masuk').value = data.jam_masuk || '';
                document.getElementById('edit_jam_pulang').value = data.jam_pulang || '';
                document.getElementById('edit_jam_lembur').value = data.jam_lembur || 0;
                document.getElementById('edit_keterangan_admin').value = data.keterangan_admin || '';
            } else {
                if (window.SGMDialog) {
                    window.SGMDialog.toast('Gagal mengambil data presensi', 'danger');
                } else {
                    alert('Gagal mengambil data presensi');
                }
                tutupModalEditPresensi();
            }
        })
        .catch(err => {
            console.error(err);
            if (window.SGMDialog) {
                window.SGMDialog.toast('Terjadi kesalahan koneksi ke server', 'danger');
            } else {
                alert('Terjadi kesalahan koneksi');
            }
            tutupModalEditPresensi();
        });
}

function tutupModalEditPresensi() {
    const modal = document.getElementById('modalEditPresensi');
    if (modal) {
        modal.classList.remove('show');
    }
}

window.addEventListener('click', function(e) {
    const mTambah = document.getElementById('modalTambahPresensi');
    const mEdit = document.getElementById('modalEditPresensi');
    if (e.target === mTambah) tutupModalTambahPresensi();
    if (e.target === mEdit) tutupModalEditPresensi();
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
