<?php
$pageTitle = 'Ketidak Hadiran (Izin / Sakit / Cuti) - ' . Config::APP_NAME;
$activePage = 'ketidakhadiran';
require_once __DIR__ . '/../layouts/header.php';
?>

<div style="margin-bottom: 20px; display:flex; justify-content:space-between; align-items:center;">
    <h2 style="font-size: 1.4rem; font-weight: 700;">Data Ketidak Hadiran</h2>
    <?php if ($currentUser['role'] === 'pegawai'): ?>
        <a href="<?= Config::baseUrl('ketidakhadiran/ajukan') ?>" class="btn btn-blue">
            <?= Icon::get('clipboard-check', 16) ?> <span>Ajukan Izin / Sakit / Cuti</span>
        </a>
    <?php endif; ?>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert <?= ($_GET['msg'] === 'already_exists') ? 'alert-warning' : 'alert-success' ?>">
        <?php 
            if ($_GET['msg'] === 'submitted') echo '✅ Pengajuan ketidakhadiran berhasil dikirim!';
            if ($_GET['msg'] === 'approved') echo '✅ Pengajuan telah disetujui!';
            if ($_GET['msg'] === 'rejected') echo '✅ Pengajuan telah ditolak!';
            if ($_GET['msg'] === 'already_exists') echo 'ℹ️ Anda sudah memiliki pengajuan aktif pada rentang tanggal tersebut.';
        ?>
    </div>
<?php endif; ?>

<div class="table-card">
    <div class="table-toolbar">
        <div class="table-toolbar-left">
            <div class="table-toolbar-group">
                <label for="sortIzin" style="font-weight:600;">Urutkan:</label>
                <select id="sortIzin" class="table-sort-select" data-table="#ketidakhadiranTable">
                    <option value="default">Default (Urutan Asli)</option>
                    <option value="tanggal-desc">Tanggal Mulai (Terbaru)</option>
                    <option value="tanggal-asc">Tanggal Mulai (Terlama)</option>
                    <?php if ($currentUser['role'] === 'admin'): ?>
                        <option value="pegawai-asc">Nama Pegawai (A - Z)</option>
                        <option value="pegawai-desc">Nama Pegawai (Z - A)</option>
                    <?php endif; ?>
                    <option value="kategori-asc">Kategori (A - Z)</option>
                    <option value="status-asc">Status Approval</option>
                </select>
            </div>
            <div class="table-toolbar-group">
                <label for="filterKategoriIzin" style="font-weight:600;">Kategori:</label>
                <select id="filterKategoriIzin" class="table-filter-select" data-table="#ketidakhadiranTable" data-filter-col="kategori">
                    <option value="">Semua Kategori</option>
                    <option value="izin">Izin</option>
                    <option value="sakit">Sakit</option>
                    <option value="cuti">Cuti</option>
                </select>
            </div>
            <div class="table-toolbar-group">
                <label for="filterStatusIzin" style="font-weight:600;">Status:</label>
                <select id="filterStatusIzin" class="table-filter-select" data-table="#ketidakhadiranTable" data-filter-col="status">
                    <option value="">Semua Status</option>
                    <option value="menunggu">Menunggu</option>
                    <option value="disetujui">Disetujui</option>
                    <option value="ditolak">Ditolak</option>
                </select>
            </div>
        </div>
        <div style="font-size:0.78rem; color:var(--text-muted);">
            💡 Klik header kolom untuk urutkan instan
        </div>
    </div>
    <div class="table-responsive">
        <table class="table-custom" id="ketidakhadiranTable">
            <thead>
                <tr>
                    <th style="width: 40px; text-align: center;">No.</th>
                    <?php if ($currentUser['role'] === 'admin'): ?>
                        <th class="sortable-th" data-col="pegawai" data-sort-type="text">Pegawai</th>
                    <?php endif; ?>
                    <th class="sortable-th" data-col="kategori" data-sort-type="text">Kategori</th>
                    <th class="sortable-th" data-col="tanggal" data-sort-type="date">Periode Tanggal</th>
                    <th>Keterangan</th>
                    <th style="text-align: center;">Dokumen</th>
                    <th class="sortable-th" data-col="status" data-sort-type="text" style="text-align: center;">Status</th>
                    <?php if ($currentUser['role'] === 'admin'): ?>
                        <th style="text-align: center; width: 170px;">Aksi Verifikasi</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($dataIzin)): ?>
                    <tr>
                        <td colspan="<?= ($currentUser['role'] === 'admin') ? '8' : '6' ?>" style="text-align: center; color: var(--text-muted); padding: 36px;">
                            Belum ada riwayat pengajuan ketidakhadiran.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($dataIzin as $row): ?>
                        <tr>
                            <td style="text-align: center;"><?= $no++ ?></td>
                            <?php if ($currentUser['role'] === 'admin'): ?>
                                <td data-sort-value="<?= htmlspecialchars($row['nama_pegawai'] ?? '-') ?>">
                                    <strong><?= htmlspecialchars($row['nama_pegawai'] ?? '-') ?></strong><br>
                                    <small style="color:var(--text-muted);"><?= htmlspecialchars($row['nik'] ?? '-') ?></small>
                                </td>
                            <?php endif; ?>
                            <td data-sort-value="<?= htmlspecialchars(strtoupper($row['kategori'])) ?>">
                                <span class="badge <?= ($row['kategori'] === 'sakit') ? 'badge-danger' : (($row['kategori'] === 'cuti') ? 'badge-warning' : 'badge-info') ?>">
                                    <?= htmlspecialchars(strtoupper($row['kategori'])) ?>
                                </span>
                            </td>
                            <td data-sort-value="<?= htmlspecialchars($row['tanggal_mulai']) ?>">
                                <?= date('d M Y', strtotime($row['tanggal_mulai'])) ?> s/d <?= date('d M Y', strtotime($row['tanggal_selesai'])) ?>
                            </td>
                            <td><?= nl2br(htmlspecialchars($row['keterangan'])) ?></td>
                            <td style="text-align: center;">
                                <?php if (!empty($row['dokumen_bukti'])): ?>
                                    <a href="<?= Config::baseUrl('assets/images/uploads/dokumen/' . $row['dokumen_bukti']) ?>" target="_blank" class="btn btn-secondary btn-sm" title="Buka Dokumen">
                                        <?= Icon::get('file-text', 14) ?> Lihat
                                    </a>
                                <?php else: ?>
                                    <span style="color:var(--text-subtle);">-</span>
                                <?php endif; ?>
                            </td>
                            <td data-sort-value="<?= htmlspecialchars($row['status_approval']) ?>" style="text-align: center;">
                                <?php if ($row['status_approval'] === 'disetujui'): ?>
                                    <span class="badge badge-success">Disetujui</span>
                                <?php elseif ($row['status_approval'] === 'ditolak'): ?>
                                    <span class="badge badge-danger">Ditolak</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Menunggu</span>
                                <?php endif; ?>
                            </td>
                            <?php if ($currentUser['role'] === 'admin'): ?>
                                <td style="text-align: center;">
                                    <?php if ($row['status_approval'] === 'pending'): ?>
                                        <div style="display:inline-flex; gap: 6px;">
                                            <form method="POST" action="<?= Config::baseUrl('ketidakhadiran/approve') ?>" style="display:inline;">
                                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Setujui pengajuan ini?');">Setujui</button>
                                            </form>
                                            <form method="POST" action="<?= Config::baseUrl('ketidakhadiran/reject') ?>" style="display:inline;">
                                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Tolak pengajuan ini?');">Tolak</button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <small style="color:var(--text-muted);"><?= htmlspecialchars($row['catatan_admin'] ?? '-') ?></small>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
