<?php
$pageTitle = 'Manajemen Pegawai - ' . Config::APP_NAME;
$activePage = 'pegawai';
require_once __DIR__ . '/../layouts/header.php';
?>

<div style="margin-bottom: 20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
    <h2 style="font-size: 1.4rem; font-weight: 700;">Data Pegawai</h2>
    <div style="display:flex; gap:10px; align-items:center;">
        <button type="button" class="btn btn-secondary" onclick="openImportModal()">
            <?= Icon::get('file-text', 16) ?> <span>Import Excel / CSV</span>
        </button>
        <a href="<?= Config::baseUrl('pegawai/tambah') ?>" class="btn btn-blue">
            <?= Icon::get('user-plus', 16) ?> <span>Tambah Pegawai</span>
        </a>
    </div>
</div>

<?php if (!empty($_SESSION['import_result'])): 
    $importRes = $_SESSION['import_result'];
    unset($_SESSION['import_result']);
?>
    <div class="alert <?= $importRes['success'] > 0 ? 'alert-success' : 'alert-danger' ?>" style="margin-bottom: 20px;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:6px;">
            <strong style="font-size: 0.95rem;">
                <?= $importRes['success'] > 0 ? '✅ Hasil Import Pegawai Selesai' : '⚠️ Gagal Mengimpor Pegawai' ?>
            </strong>
        </div>
        <div style="font-size: 0.88rem; line-height: 1.5;">
            Berhasil ditambahkan: <strong><?= $importRes['success'] ?> pegawai</strong>.
            <?php if (!empty($importRes['skipped'])): ?>
                &nbsp;|&nbsp; Dilewati / gagal: <strong><?= $importRes['skipped'] ?> baris</strong>.
            <?php endif; ?>
        </div>
        <?php if (!empty($importRes['new_jabatans'])): ?>
            <div style="margin-top: 6px; font-size: 0.82rem; color: #065f46; background: rgba(16, 185, 129, 0.12); padding: 6px 10px; border-radius: var(--radius-sm);">
                ✨ <strong><?= count($importRes['new_jabatans']) ?> Jabatan Baru</strong> otomatis didaftarkan ke Master Data & Pengaturan Gaji: <em><?= htmlspecialchars(implode(', ', $importRes['new_jabatans'])) ?></em>.
            </div>
        <?php endif; ?>
        <?php if (!empty($importRes['errors'])): ?>
            <div style="margin-top: 10px; font-size: 0.82rem; max-height: 140px; overflow-y: auto; background: rgba(0,0,0,0.06); padding: 8px 12px; border-radius: var(--radius-sm); border-left: 3px solid <?= $importRes['success'] > 0 ? '#10b981' : '#ef4444' ?>;">
                <?php foreach ($importRes['errors'] as $err): ?>
                    <div style="margin-bottom: 3px;">• <?= htmlspecialchars($err) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success">
        <?php 
            if ($_GET['msg'] === 'added') echo '✅ Data pegawai berhasil ditambahkan!';
            if ($_GET['msg'] === 'updated') echo '✅ Data pegawai berhasil diperbarui!';
            if ($_GET['msg'] === 'deleted') echo '✅ Data pegawai berhasil dihapus!';
            if ($_GET['msg'] === 'imported' && empty($importRes)) echo '✅ Data pegawai berhasil diimpor!';
            if ($_GET['msg'] === 'status_toggled') {
                $stText = (($_GET['status'] ?? '') === 'aktif') ? 'Aktif' : 'Nonaktif';
                echo '✅ Status akun pegawai <strong>' . htmlspecialchars($_GET['name'] ?? '') . '</strong> berhasil diubah menjadi <strong>' . $stText . '</strong>!';
            }
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['err'])): ?>
    <?php if ($_GET['err'] === 'has_riwayat'): ?>
        <div class="alert alert-warning" style="margin-bottom: 20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px;">
            <div>
                <strong>⚠️ Perlindungan Integritas Data Historis:</strong>
                <div style="font-size:0.88rem; margin-top:4px;">
                    Pegawai <strong><?= htmlspecialchars($_GET['name'] ?? '') ?></strong> tidak dapat dihapus permanen karena memiliki catatan riwayat presensi/ketidakhadiran.
                    <br><small style="color:var(--text-muted);">Menghapus akun fisik akan merusak arsip audit dan data penggajian. Silakan nonaktifkan akun agar akses pegawai ditutup tanpa menghilangkan riwayat historis.</small>
                </div>
            </div>
            <?php if (!empty($_GET['id'])): ?>
                <a href="<?= Config::baseUrl('pegawai/toggle-status?id=' . (int)$_GET['id']) ?>" class="btn btn-warning" style="font-weight: 600; white-space: nowrap;">
                    Nonaktifkan Akun Sekarang
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-danger">
            <?php 
                if ($_GET['err'] === 'self_delete') echo '⚠️ Anda tidak dapat menghapus akun Anda sendiri saat sedang login!';
                if ($_GET['err'] === 'self_status') echo '⚠️ Anda tidak dapat menonaktifkan akun Anda sendiri saat sedang login!';
            ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php
$uniqueJabatan = !empty($pegawaiList) ? array_unique(array_filter(array_column($pegawaiList, 'jabatan'))) : [];
sort($uniqueJabatan);
?>

<div class="table-card">
    <div class="looker-filter-bar" data-table="#pegawaiTable">
        <div class="looker-filter-bar-left">
            <!-- Quick Search Input -->
            <div class="looker-search-box">
                <span class="looker-search-icon"><?= Icon::get('search', 14) ?></span>
                <input type="text" class="looker-search-input" data-table="#pegawaiTable" placeholder="Cari Pegawai...">
            </div>

            <!-- Sort Select -->
            <div class="looker-sort-box">
                <select class="table-sort-select" data-table="#pegawaiTable">
                    <option value="default">Urutkan: Default</option>
                    <option value="nama-asc">Nama Pegawai (A - Z)</option>
                    <option value="nama-desc">Nama Pegawai (Z - A)</option>
                    <option value="nik-asc">NIP / NIK (Terkecil)</option>
                    <option value="nik-desc">NIP / NIK (Terbesar)</option>
                    <option value="jabatan-asc">Jabatan (A - Z)</option>
                    <option value="lokasi-asc">Lokasi Presensi (A - Z)</option>
                    <option value="status-asc">Status (Aktif Dulu)</option>
                </select>
            </div>

            <!-- Filter Pill: Role -->
            <div class="looker-pill-wrapper" data-table="#pegawaiTable" data-filter-col="role" data-label="Role">
                <button type="button" class="looker-filter-pill">
                    <span class="looker-pill-text">Role</span>
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
                        <input type="text" class="looker-popover-input" placeholder="Cari role...">
                    </div>
                    <div class="looker-checklist">
                        <label class="looker-check-item">
                            <span class="looker-check-left">
                                <input type="checkbox" value="admin" checked>
                                <span class="looker-check-label">Admin</span>
                            </span>
                            <span class="looker-check-count"></span>
                        </label>
                        <label class="looker-check-item">
                            <span class="looker-check-left">
                                <input type="checkbox" value="pegawai" checked>
                                <span class="looker-check-label">Pegawai</span>
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

            <!-- Filter Pill: Jabatan (Auto-discovered) -->
            <div class="looker-pill-wrapper" data-table="#pegawaiTable" data-filter-col="jabatan" data-label="Jabatan">
                <button type="button" class="looker-filter-pill">
                    <span class="looker-pill-text">Jabatan</span>
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
                        <input type="text" class="looker-popover-input" placeholder="Cari jabatan...">
                    </div>
                    <div class="looker-checklist">
                        <!-- Auto-discovered by JavaScript engine -->
                    </div>
                    <div class="looker-popover-footer">
                        <span class="looker-footer-status">Semua dipilih</span>
                        <button type="button" class="looker-clear-btn">Reset</button>
                    </div>
                </div>
            </div>

            <!-- Filter Pill: Status -->
            <div class="looker-pill-wrapper" data-table="#pegawaiTable" data-filter-col="status" data-label="Status">
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
                                <input type="checkbox" value="nonaktif" checked>
                                <span class="looker-check-label">Nonaktif</span>
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
            <button type="button" class="looker-reset-all-btn" data-table="#pegawaiTable" title="Reset semua filter">
                <?= Icon::get('refresh-cw', 13) ?> Reset
            </button>
        </div>
        <div class="looker-filter-bar-right">
            <span class="looker-row-count"></span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table-custom" id="pegawaiTable">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th class="sortable-th" data-col="nik" data-sort-type="text">NIP / NIK</th>
                    <th class="sortable-th" data-col="nama" data-sort-type="text">Nama Pegawai</th>
                    <th class="sortable-th" data-col="username" data-sort-type="text">Username</th>
                    <th class="sortable-th" data-col="role" data-sort-type="text">Role</th>
                    <th class="sortable-th" data-col="jabatan" data-sort-type="text">Jabatan</th>
                    <th class="sortable-th" data-col="lokasi" data-sort-type="text">Lokasi Presensi</th>
                    <th class="sortable-th" data-col="status" data-sort-type="text">Status</th>
                    <th style="width: 150px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pegawaiList)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; color: var(--text-muted); padding: 30px;">
                            Data kosong, silahkan tambahkan data baru
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($pegawaiList as $peg): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td data-sort-value="<?= htmlspecialchars($peg['nik']) ?>"><code style="font-weight:700;"><?= htmlspecialchars($peg['nik']) ?></code></td>
                            <td data-sort-value="<?= htmlspecialchars($peg['nama']) ?>">
                                <strong><?= htmlspecialchars($peg['nama']) ?></strong>
                            </td>
                            <td><?= htmlspecialchars($peg['username']) ?></td>
                            <td data-sort-value="<?= htmlspecialchars(strtolower($peg['role'])) ?>">
                                <span class="badge <?= ($peg['role'] === 'admin') ? 'badge-danger' : 'badge-info' ?>">
                                    <?= htmlspecialchars(ucfirst($peg['role'])) ?>
                                </span>
                            </td>
                            <td data-sort-value="<?= htmlspecialchars(strtolower($peg['jabatan'])) ?>"><?= htmlspecialchars($peg['jabatan']) ?></td>
                            <td><?= htmlspecialchars($peg['nama_lokasi'] ?? '-') ?></td>
                            <td data-sort-value="<?= htmlspecialchars(strtolower($peg['status'])) ?>">
                                <span class="badge <?= ($peg['status'] === 'aktif') ? 'badge-success' : 'badge-warning' ?>">
                                    <?= htmlspecialchars(ucfirst($peg['status'])) ?>
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <div style="display:inline-flex; gap:6px; align-items:center;">
                                    <a href="<?= Config::baseUrl('pegawai/edit?id=' . $peg['id']) ?>" class="btn btn-primary btn-sm" title="Edit Data Pegawai">
                                        Edit
                                    </a>
                                    <?php if ($peg['id'] !== (int)$_SESSION['user_id']): ?>
                                        <?php if ($peg['status'] === 'aktif'): ?>
                                            <button type="button" 
                                                    class="btn btn-warning btn-sm btn-toggle-status"
                                                    data-id="<?= (int)$peg['id'] ?>"
                                                    data-nama="<?= htmlspecialchars($peg['nama'], ENT_QUOTES) ?>"
                                                    data-nik="<?= htmlspecialchars($peg['nik'], ENT_QUOTES) ?>"
                                                    data-status="aktif"
                                                    title="Tutup Akses Login (Data Historis Aman)">
                                                Nonaktifkan
                                            </button>
                                        <?php else: ?>
                                            <button type="button" 
                                                    class="btn btn-success btn-sm btn-toggle-status"
                                                    data-id="<?= (int)$peg['id'] ?>"
                                                    data-nama="<?= htmlspecialchars($peg['nama'], ENT_QUOTES) ?>"
                                                    data-nik="<?= htmlspecialchars($peg['nik'], ENT_QUOTES) ?>"
                                                    data-status="nonaktif"
                                                    title="Buka Kembali Akses Login">
                                                Aktifkan
                                            </button>
                                        <?php endif; ?>

                                        <?php 
                                        $hasRiwayat = (($peg['total_presensi'] ?? 0) > 0 || ($peg['total_izin'] ?? 0) > 0);
                                        ?>
                                        <button type="button" 
                                                class="btn btn-danger btn-sm btn-delete-pegawai"
                                                data-id="<?= (int)$peg['id'] ?>"
                                                data-nama="<?= htmlspecialchars($peg['nama'], ENT_QUOTES) ?>"
                                                data-nik="<?= htmlspecialchars($peg['nik'], ENT_QUOTES) ?>"
                                                data-has-riwayat="<?= $hasRiwayat ? '1' : '0' ?>"
                                                title="<?= $hasRiwayat ? 'Hapus Akun (Dilindungi Sistem)' : 'Hapus Permanen' ?>">
                                            Hapus
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Konfirmasi Hapus Pegawai (In-App Dialog) -->
<div id="modalDeletePegawai" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="modalDeletePegawaiTitle">
    <div class="modal-card modal-confirm" style="max-width: 460px !important;">
        <div class="modal-icon-danger">
            <?= Icon::get('trash', 28) ?>
        </div>
        <h3 id="modalDeletePegawaiTitle" class="modal-title">Hapus Akun Pegawai?</h3>
        <p class="modal-desc" style="margin-bottom: 12px;">
            Apakah Anda yakin ingin menghapus akun pegawai <strong id="deletePegawaiNama" style="color:var(--text-main);">-</strong> (<code id="deletePegawaiNik">-</code>)?
        </p>

        <!-- Warning jika memiliki riwayat -->
        <div id="deletePegawaiWarningRiwayat" style="display:none; padding:10px 12px; font-size:0.82rem; margin-bottom:14px; border-radius:var(--radius-sm); border:1px solid #fed7aa; background:#fff7ed; color:#9a3412; text-align:left;">
            <div style="font-weight:700; margin-bottom:2px;">⚠️ Dilindungi Sistem (Arsip Audit)</div>
            <span>Pegawai ini memiliki riwayat presensi/izin. Penghapusan fisik dicegah oleh sistem agar arsip audit penggajian tidak hilang.</span>
        </div>

        <form id="formDeletePegawai" method="GET" action="<?= Config::baseUrl('pegawai/hapus') ?>">
            <input type="hidden" name="id" id="deletePegawaiId" value="">
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" id="btnCancelDeletePegawai">Batal</button>
                <button type="submit" class="btn btn-danger" id="btnConfirmDeletePegawai">
                    <?= Icon::get('trash', 14) ?> <span>Ya, Hapus Akun</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Konfirmasi Ubah Status Pegawai (In-App Dialog) -->
<div id="modalToggleStatusPegawai" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="modalToggleStatusTitle">
    <div class="modal-card modal-confirm" style="max-width: 460px !important;">
        <div id="toggleStatusIconBox" class="modal-icon-warning">
            <?= Icon::get('lock', 28) ?>
        </div>
        <h3 id="modalToggleStatusTitle" class="modal-title">Nonaktifkan Akses Pegawai?</h3>
        <p class="modal-desc" id="modalToggleStatusDesc" style="margin-bottom: 16px;">
            Pegawai <strong id="toggleStatusNama" style="color:var(--text-main);">-</strong> (<code id="toggleStatusNik">-</code>) tidak akan bisa login ke aplikasi absensi lagi. Seluruh data presensi tetap aman tersimpan.
        </p>

        <form id="formToggleStatusPegawai" method="GET" action="<?= Config::baseUrl('pegawai/toggle-status') ?>">
            <input type="hidden" name="id" id="toggleStatusId" value="">
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" id="btnCancelToggleStatus">Batal</button>
                <button type="submit" class="btn btn-warning" id="btnConfirmToggleStatus">
                    <span id="btnConfirmToggleStatusText">Ya, Nonaktifkan</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Import Pegawai -->
<div id="importModal" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="importModalTitle">
    <div class="modal-card">
        <div style="display:flex; justify-content:space-between; align-items:center; padding:18px 24px; border-bottom:1px solid var(--border-color);">
            <div style="display:flex; align-items:center; gap:12px;">
                <div style="width:38px; height:38px; border-radius:var(--radius-sm); background:var(--primary-light); color:var(--primary); display:flex; align-items:center; justify-content:center;">
                    <?= Icon::get('file-text', 20) ?>
                </div>
                <div>
                    <h3 id="importModalTitle" style="font-size:1.15rem; font-weight:700; color:var(--text-main); margin:0;">Import Data Pegawai</h3>
                    <span style="font-size:0.78rem; color:var(--text-muted);">Mendukung file Excel (.xlsx) atau CSV (.csv)</span>
                </div>
            </div>
            <button type="button" onclick="closeImportModal()" aria-label="Tutup Modal" style="background:none; border:none; color:var(--text-muted); font-size:1.4rem; cursor:pointer; line-height:1; padding:4px;">
                <?= Icon::get('x', 20) ?>
            </button>
        </div>

        <form action="<?= Config::baseUrl('pegawai/import') ?>" method="POST" enctype="multipart/form-data" data-turbo="false" style="padding:24px;">
            <!-- Petunjuk & Download Template -->
            <div style="background:var(--bg-subtle); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:16px; margin-bottom:20px;">
                <div style="font-size:0.86rem; font-weight:700; color:var(--text-main); margin-bottom:8px; display:flex; align-items:center; gap:6px;">
                    <span>💡</span> <span>Panduan Pengisian:</span>
                </div>
                <ul style="font-size:0.82rem; color:var(--text-muted); margin-left:18px; line-height:1.6; margin-bottom:12px;">
                    <li>Unduh template resmi agar penamaan kolom sesuai.</li>
                    <li><strong>NIK di-generate otomatis</strong> oleh sistem secara berurutan.</li>
                    <li>Jika kolom password kosong, otomatis diisi: <code>12345678</code>.</li>
                    <li>Ukuran file maksimal: <strong>5 MB</strong>.</li>
                </ul>
                <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:8px;">
                    <a href="<?= Config::baseUrl('pegawai/template?format=xlsx') ?>" data-turbo="false" class="btn btn-primary btn-sm" style="display:inline-flex; align-items:center; gap:6px; font-weight:600;">
                        <?= Icon::get('save', 14) ?> <span>Download Template Excel (.xlsx)</span>
                    </a>
                    <a href="<?= Config::baseUrl('pegawai/template?format=csv') ?>" data-turbo="false" class="btn btn-secondary btn-sm" style="display:inline-flex; align-items:center; gap:6px; font-weight:600;">
                        <?= Icon::get('file-text', 14) ?> <span>Format CSV (.csv)</span>
                    </a>
                </div>
            </div>

            <!-- Upload Zone -->
            <div class="form-group" style="margin-bottom:24px;">
                <label class="form-label" for="file_import" style="font-weight:600; margin-bottom:8px;">Pilih File Excel / CSV</label>
                <input type="file" id="file_import" name="file_import" accept=".csv, .xlsx, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, text/csv" class="form-input" required style="padding:10px 14px; cursor:pointer;">
                <span style="font-size:0.75rem; color:var(--text-subtle); margin-top:4px; display:block;">Pilih file .csv atau .xlsx yang sudah diisi data karyawan.</span>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn btn-secondary" onclick="closeImportModal()">Batal</button>
                <button type="submit" class="btn btn-blue" id="btnSubmitImport">
                    <?= Icon::get('check', 16) ?> <span>Proses Import</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openImportModal() {
    const modal = document.getElementById('importModal');
    if (modal) {
        modal.classList.add('show');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function closeImportModal() {
    const modal = document.getElementById('importModal');
    if (modal) {
        modal.classList.remove('show');
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

(() => {
    function initPegawaiModals() {
        const modalDelete = document.getElementById('modalDeletePegawai');
        const deleteNama = document.getElementById('deletePegawaiNama');
        const deleteNik = document.getElementById('deletePegawaiNik');
        const deleteId = document.getElementById('deletePegawaiId');
        const deleteWarning = document.getElementById('deletePegawaiWarningRiwayat');

        const modalToggle = document.getElementById('modalToggleStatusPegawai');
        const toggleNama = document.getElementById('toggleStatusNama');
        const toggleNik = document.getElementById('toggleStatusNik');
        const toggleId = document.getElementById('toggleStatusId');
        const toggleTitle = document.getElementById('modalToggleStatusTitle');
        const toggleDesc = document.getElementById('modalToggleStatusDesc');
        const toggleIcon = document.getElementById('toggleStatusIconBox');
        const toggleBtn = document.getElementById('btnConfirmToggleStatus');
        const toggleBtnText = document.getElementById('btnConfirmToggleStatusText');

        function closeAll() {
            const mDel = document.getElementById('modalDeletePegawai');
            const mTog = document.getElementById('modalToggleStatusPegawai');
            if (mDel) mDel.classList.remove('show');
            if (mTog) mTog.classList.remove('show');
            document.body.style.overflow = '';
        }

        // Delegated click handler on document (Single Guarded Listener)
        if (!window._hasPegawaiDelegatedClickAttached) {
            window._hasPegawaiDelegatedClickAttached = true;

            document.addEventListener('click', (e) => {
                const modalDelete = document.getElementById('modalDeletePegawai');
                const modalToggle = document.getElementById('modalToggleStatusPegawai');

                // Guard Turbo Drive: Pastikan modal pegawai masih terpasang di DOM aktif
                if ((!modalDelete || !modalDelete.isConnected) && (!modalToggle || !modalToggle.isConnected)) return;

                // Dismiss buttons
                if (e.target.closest('#btnCancelDeletePegawai') || e.target.closest('#btnCancelToggleStatus') ||
                    e.target === modalDelete || e.target === modalToggle) {
                    closeAll();
                    return;
                }

                // Delete Pegawai button
                const btnDel = e.target.closest('.btn-delete-pegawai');
                if (btnDel && modalDelete) {
                    e.preventDefault();
                    const deleteId = document.getElementById('deletePegawaiId');
                    const deleteNama = document.getElementById('deletePegawaiNama');
                    const deleteNik = document.getElementById('deletePegawaiNik');
                    const deleteWarning = document.getElementById('deletePegawaiWarningRiwayat');

                    if (deleteId) deleteId.value = btnDel.getAttribute('data-id');
                    if (deleteNama) deleteNama.textContent = btnDel.getAttribute('data-nama');
                    if (deleteNik) deleteNik.textContent = btnDel.getAttribute('data-nik');
                    const hasRiwayat = btnDel.getAttribute('data-has-riwayat') === '1';
                    if (deleteWarning) {
                        deleteWarning.style.display = hasRiwayat ? 'block' : 'none';
                    }
                    modalDelete.classList.add('show');
                    document.body.style.overflow = 'hidden';
                    return;
                }

                // Toggle Status button
                const btnTog = e.target.closest('.btn-toggle-status');
                if (btnTog && modalToggle) {
                    e.preventDefault();
                    const toggleId = document.getElementById('toggleStatusId');
                    const toggleNama = document.getElementById('toggleStatusNama');
                    const toggleNik = document.getElementById('toggleStatusNik');
                    const toggleTitle = document.getElementById('modalToggleStatusTitle');
                    const toggleDesc = document.getElementById('modalToggleStatusDesc');
                    const toggleIcon = document.getElementById('toggleStatusIconBox');
                    const toggleBtn = document.getElementById('btnConfirmToggleStatus');
                    const toggleBtnText = document.getElementById('btnConfirmToggleStatusText');

                    const id = btnTog.getAttribute('data-id');
                    const nama = btnTog.getAttribute('data-nama');
                    const nik = btnTog.getAttribute('data-nik');
                    const currentStatus = btnTog.getAttribute('data-status');

                    if (toggleId) toggleId.value = id;
                    if (toggleNama) toggleNama.textContent = nama;
                    if (toggleNik) toggleNik.textContent = nik;

                    if (currentStatus === 'aktif') {
                        if (toggleTitle) toggleTitle.textContent = 'Nonaktifkan Akses Pegawai?';
                        if (toggleDesc) toggleDesc.innerHTML = `Pegawai <strong style="color:var(--text-main);">${nama}</strong> (<code>${nik}</code>) tidak akan bisa login ke aplikasi absensi lagi. Seluruh riwayat presensi tetap aman.`;
                        if (toggleIcon) {
                            toggleIcon.className = 'modal-icon-warning';
                            toggleIcon.innerHTML = `<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>`;
                        }
                        if (toggleBtn) toggleBtn.className = 'btn btn-warning';
                        if (toggleBtnText) toggleBtnText.textContent = 'Ya, Nonaktifkan Akun';
                    } else {
                        if (toggleTitle) toggleTitle.textContent = 'Aktifkan Kembali Akses Pegawai?';
                        if (toggleDesc) toggleDesc.innerHTML = `Buka kembali akses login untuk pegawai <strong style="color:var(--text-main);">${nama}</strong> (<code>${nik}</code>). Pegawai dapat kembali mencatat presensi.`;
                        if (toggleIcon) {
                            toggleIcon.className = 'modal-icon-success';
                            toggleIcon.innerHTML = `<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>`;
                        }
                        if (toggleBtn) toggleBtn.className = 'btn btn-primary';
                        if (toggleBtnText) toggleBtnText.textContent = 'Ya, Aktifkan Akun';
                    }

                    modalToggle.classList.add('show');
                    document.body.style.overflow = 'hidden';
                    return;
                }
            });
        }

        if (!window._hasPegawaiEscapeAttached) {
            window._hasPegawaiEscapeAttached = true;
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    const modalDelete = document.getElementById('modalDeletePegawai');
                    const modalToggle = document.getElementById('modalToggleStatusPegawai');
                    if ((modalDelete && modalDelete.isConnected) || (modalToggle && modalToggle.isConnected)) {
                        closeAll();
                        if (typeof closeImportModal === 'function') closeImportModal();
                    }
                }
            });
        }
    }

    initPegawaiModals();
})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
