<?php
$pageTitle = 'Data Jabatan - ' . Config::APP_NAME;
$activePage = 'master_jabatan';
require_once __DIR__ . '/../layouts/header.php';
?>

<div style="margin-bottom: 20px; display:flex; justify-content:space-between; align-items:center;">
    <h2 style="font-size: 1.4rem; font-weight: 700;">Data Jabatan</h2>
    <a href="<?= Config::baseUrl('master/jabatan/tambah') ?>" class="btn btn-blue">
        <?= Icon::get('plus', 16) ?> <span>Tambah Data</span>
    </a>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success">
        <?php 
            if ($_GET['msg'] === 'added') echo '✅ Data jabatan berhasil ditambahkan!';
            if ($_GET['msg'] === 'updated') echo '✅ Data jabatan berhasil diperbarui!';
            if ($_GET['msg'] === 'deleted') echo '✅ Data jabatan berhasil dihapus!';
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['err']) && $_GET['err'] === 'in_use'): ?>
    <div class="alert alert-danger" style="margin-bottom: 20px;">
        <?= Icon::get('alert-triangle', 18) ?> <strong>Jabatan Tidak Dapat Dihapus:</strong> Jabatan <strong><?= htmlspecialchars($_GET['name'] ?? '') ?></strong> masih aktif digunakan oleh <strong><?= (int)($_GET['count'] ?? 1) ?> orang pegawai</strong>. Silakan ubah jabatan pegawai terkait terlebih dahulu sebelum menghapus master jabatan ini.
    </div>
<?php endif; ?>

<div class="table-card">
    <div class="table-toolbar">
        <div class="table-toolbar-left">
            <div class="table-toolbar-group">
                <label for="sortJabatan" style="font-weight:600;">Urutkan:</label>
                <select id="sortJabatan" class="table-sort-select" data-table="#jabatanTable">
                    <option value="default">Default (Urutan Asli)</option>
                    <option value="nama-asc">Nama Jabatan (A - Z)</option>
                    <option value="nama-desc">Nama Jabatan (Z - A)</option>
                </select>
            </div>
        </div>
        <div style="font-size:0.78rem; color:var(--text-muted);">
            💡 Klik header kolom untuk urutkan instan
        </div>
    </div>
    <div class="table-responsive">
        <table class="table-custom" id="jabatanTable">
            <thead>
                <tr>
                    <th style="width: 60px; text-align: center;">No.</th>
                    <th class="sortable-th" data-col="nama" data-sort-type="text">Nama Jabatan</th>
                    <th style="width: 160px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($jabatanList)): ?>
                    <tr>
                        <td colspan="3" style="text-align: center; color: var(--text-muted); padding: 30px;">
                            Data kosong, silahkan tambahkan data baru
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($jabatanList as $j): ?>
                        <tr>
                            <td style="text-align: center;"><?= $no++ ?></td>
                            <td data-sort-value="<?= htmlspecialchars($j['nama_jabatan']) ?>"><strong><?= htmlspecialchars($j['nama_jabatan']) ?></strong></td>
                            <td style="text-align: center;">
                                <div style="display:inline-flex; gap:6px;">
                                    <a href="<?= Config::baseUrl('master/jabatan/edit?id=' . $j['id']) ?>" class="btn btn-primary btn-sm">
                                        Edit
                                    </a>
                                    <a href="<?= Config::baseUrl('master/jabatan/hapus?id=' . $j['id']) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus jabatan ini?');">
                                        Hapus
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
