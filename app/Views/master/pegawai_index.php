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
    <div class="table-toolbar">
        <div class="table-toolbar-left">
            <div class="table-toolbar-group">
                <label for="sortPegawai" style="font-weight:600;">Urutkan:</label>
                <select id="sortPegawai" class="table-sort-select" data-table="#pegawaiTable">
                    <option value="default">Default (Urutan Asli)</option>
                    <option value="nama-asc">Nama Pegawai (A - Z)</option>
                    <option value="nama-desc">Nama Pegawai (Z - A)</option>
                    <option value="nik-asc">NIP / NIK (Terkecil - Terbesar)</option>
                    <option value="nik-desc">NIP / NIK (Terbesar - Terkecil)</option>
                    <option value="jabatan-asc">Jabatan (A - Z)</option>
                    <option value="lokasi-asc">Lokasi Presensi (A - Z)</option>
                    <option value="status-asc">Status (Aktif Dulu)</option>
                </select>
            </div>
            <div class="table-toolbar-group">
                <label for="filterRolePegawai" style="font-weight:600;">Kategori / Role:</label>
                <select id="filterRolePegawai" class="table-filter-select" data-table="#pegawaiTable" data-filter-col="role">
                    <option value="">Semua Role</option>
                    <option value="admin">Admin</option>
                    <option value="pegawai">Pegawai</option>
                </select>
            </div>
            <div class="table-toolbar-group">
                <label for="filterJabatanPegawai" style="font-weight:600;">Jabatan:</label>
                <select id="filterJabatanPegawai" class="table-filter-select" data-table="#pegawaiTable" data-filter-col="jabatan">
                    <option value="">Semua Jabatan</option>
                    <?php foreach ($uniqueJabatan as $jb): ?>
                        <option value="<?= htmlspecialchars(strtolower($jb)) ?>"><?= htmlspecialchars($jb) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="table-toolbar-group">
                <label for="filterStatusPegawai" style="font-weight:600;">Status:</label>
                <select id="filterStatusPegawai" class="table-filter-select" data-table="#pegawaiTable" data-filter-col="status">
                    <option value="">Semua Status</option>
                    <option value="aktif">Aktif</option>
                    <option value="nonaktif">Nonaktif</option>
                </select>
            </div>
        </div>
        <div style="font-size:0.78rem; color:var(--text-muted);">
            💡 Klik header kolom untuk urutkan instan
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
                                            <a href="<?= Config::baseUrl('pegawai/toggle-status?id=' . $peg['id']) ?>" class="btn btn-warning btn-sm" onclick="return confirm('Nonaktifkan akun <?= htmlspecialchars(addslashes($peg['nama'])) ?>? Pegawai tidak akan bisa login lagi.');" title="Tutup Akses Login (Data Historis Aman)">
                                                Nonaktifkan
                                            </a>
                                        <?php else: ?>
                                            <a href="<?= Config::baseUrl('pegawai/toggle-status?id=' . $peg['id']) ?>" class="btn btn-success btn-sm" onclick="return confirm('Aktifkan kembali akun <?= htmlspecialchars(addslashes($peg['nama'])) ?>?');" title="Buka Kembali Akses Login">
                                                Aktifkan
                                            </a>
                                        <?php endif; ?>

                                        <?php 
                                        $hasRiwayat = (($peg['total_presensi'] ?? 0) > 0 || ($peg['total_izin'] ?? 0) > 0);
                                        $confirmMsg = $hasRiwayat 
                                            ? "PERHATIAN: Pegawai ini memiliki catatan riwayat presensi/izin. Sistem melindungi data ini dari penghapusan fisik agar arsip audit tidak hilang. Lanjutkan untuk verifikasi?" 
                                            : "Hapus permanen akun pegawai ini?";
                                        ?>
                                        <a href="<?= Config::baseUrl('pegawai/hapus?id=' . $peg['id']) ?>" class="btn btn-danger btn-sm" onclick="return confirm('<?= $confirmMsg ?>');" title="<?= $hasRiwayat ? 'Hapus Akun (Dilindungi Sistem)' : 'Hapus Permanen' ?>">
                                            Hapus
                                        </a>
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

        <form action="<?= Config::baseUrl('pegawai/import') ?>" method="POST" enctype="multipart/form-data" style="padding:24px;">
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
                    <a href="<?= Config::baseUrl('pegawai/template?format=xlsx') ?>" class="btn btn-primary btn-sm" style="display:inline-flex; align-items:center; gap:6px; font-weight:600;">
                        <?= Icon::get('save', 14) ?> <span>Download Template Excel (.xlsx)</span>
                    </a>
                    <a href="<?= Config::baseUrl('pegawai/template?format=csv') ?>" class="btn btn-secondary btn-sm" style="display:inline-flex; align-items:center; gap:6px; font-weight:600;">
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

document.getElementById('importModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeImportModal();
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeImportModal();
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
