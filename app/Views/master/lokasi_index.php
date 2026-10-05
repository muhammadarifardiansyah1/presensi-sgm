<?php
$pageTitle = 'Data Lokasi Presensi - ' . Config::APP_NAME;
$activePage = 'master_lokasi';
require_once __DIR__ . '/../layouts/header.php';
?>

<div style="margin-bottom: 20px; display:flex; justify-content:space-between; align-items:center;">
    <h2 style="font-size: 1.4rem; font-weight: 700;">Data Lokasi Presensi</h2>
    <a href="<?= Config::baseUrl('master/lokasi/tambah') ?>" class="btn btn-blue">
        <?= Icon::get('plus', 16) ?> <span>Tambah Data</span>
    </a>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success">
        <?php 
            if ($_GET['msg'] === 'added') echo '✅ Lokasi presensi berhasil ditambahkan!';
            if ($_GET['msg'] === 'updated') echo '✅ Data lokasi berhasil diperbarui!';
            if ($_GET['msg'] === 'deleted') echo '✅ Lokasi presensi berhasil dihapus!';
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['err']) && $_GET['err'] === 'in_use'): ?>
    <div class="alert alert-danger" style="margin-bottom: 20px;">
        <?= Icon::get('alert-triangle', 18) ?> <strong>Lokasi Tidak Dapat Dihapus:</strong> Lokasi ini masih terhubung dengan <strong><?= htmlspecialchars($_GET['detail'] ?? 'data lain') ?></strong>. Demi menjaga integritas data presensi dan penugasan karyawan, pindahkan lokasi pegawai terlebih dahulu sebelum menghapus lokasi ini.
    </div>
<?php endif; ?>

<div class="table-card">
    <div class="table-toolbar">
        <div class="table-toolbar-left">
            <div class="table-toolbar-group">
                <label for="sortLokasi" style="font-weight:600;">Urutkan:</label>
                <select id="sortLokasi" class="table-sort-select" data-table="#lokasiTable">
                    <option value="default">Default (Urutan Asli)</option>
                    <option value="nama-asc">Nama Lokasi (A - Z)</option>
                    <option value="nama-desc">Nama Lokasi (Z - A)</option>
                    <option value="tipe-asc">Tipe Lokasi (A - Z)</option>
                    <option value="radius-asc">Radius (Terkecil - Terbesar)</option>
                    <option value="radius-desc">Radius (Terbesar - Terkecil)</option>
                </select>
            </div>
            <div class="table-toolbar-group">
                <label for="filterTipeLokasi" style="font-weight:600;">Kategori / Tipe:</label>
                <select id="filterTipeLokasi" class="table-filter-select" data-table="#lokasiTable" data-filter-col="tipe">
                    <option value="">Semua Tipe</option>
                    <option value="pusat">Pusat</option>
                    <option value="cabang">Cabang</option>
                </select>
            </div>
        </div>
        <div style="font-size:0.78rem; color:var(--text-muted);">
            💡 Klik header kolom untuk urutkan instan
        </div>
    </div>
    <div class="table-responsive">
        <table class="table-custom" id="lokasiTable">
            <thead>
                <tr>
                    <th style="width: 50px; text-align: center;">No.</th>
                    <th class="sortable-th" data-col="nama" data-sort-type="text">Nama Lokasi</th>
                    <th class="sortable-th" data-col="tipe" data-sort-type="text">Tipe Lokasi</th>
                    <th>Latitude / Longitude</th>
                    <th class="sortable-th" data-col="radius" data-sort-type="number">Radius</th>
                    <th style="width: 220px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($lokasiList)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 30px;">
                            Data kosong, silahkan tambahkan data baru
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($lokasiList as $lok): ?>
                        <tr>
                            <td style="text-align: center;"><?= $no++ ?></td>
                            <td data-sort-value="<?= htmlspecialchars($lok['nama_lokasi']) ?>"><strong><?= htmlspecialchars($lok['nama_lokasi']) ?></strong></td>
                            <td data-sort-value="<?= htmlspecialchars($lok['tipe_lokasi']) ?>">
                                <span class="badge <?= ($lok['tipe_lokasi'] === 'Pusat') ? 'badge-success' : 'badge-info' ?>">
                                    <?= htmlspecialchars($lok['tipe_lokasi']) ?>
                                </span>
                            </td>
                            <td style="font-family:'JetBrains Mono', monospace; font-size:0.8rem;">
                                <?= htmlspecialchars($lok['latitude']) ?> / <?= htmlspecialchars($lok['longitude']) ?>
                            </td>
                            <td data-sort-value="<?= (int)$lok['radius'] ?>"><?= (int)$lok['radius'] ?> meter</td>
                            <td style="text-align: center;">
                                <div style="display:inline-flex; gap:6px;">
                                    <a href="<?= Config::baseUrl('master/lokasi/detail?id=' . $lok['id']) ?>" class="btn btn-blue btn-sm">
                                        Detail
                                    </a>
                                    <a href="<?= Config::baseUrl('master/lokasi/edit?id=' . $lok['id']) ?>" class="btn btn-primary btn-sm">
                                        Edit
                                    </a>
                                    <a href="<?= Config::baseUrl('master/lokasi/hapus?id=' . $lok['id']) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus lokasi ini?');">
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
