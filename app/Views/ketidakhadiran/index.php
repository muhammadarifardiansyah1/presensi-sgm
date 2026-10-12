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
            if ($_GET['msg'] === 'updated') echo '✅ Data ketidakhadiran berhasil diperbarui!';
            if ($_GET['msg'] === 'deleted') echo '✅ Data ketidakhadiran berhasil dihapus!';
            if ($_GET['msg'] === 'already_exists') echo 'ℹ️ Anda sudah memiliki pengajuan aktif pada rentang tanggal tersebut.';
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['err'])): ?>
    <div class="alert alert-danger">
        <?php
            if ($_GET['err'] === 'empty_fields') echo '⚠️ Tanggal dan keterangan wajib diisi!';
            if ($_GET['err'] === 'invalid_date') echo '⚠️ Tanggal selesai tidak boleh lebih awal dari tanggal mulai!';
            if ($_GET['err'] === 'bentrok_disetujui') echo '⚠️ Gagal! Tanggal pengajuan bertabrakan dengan izin lain yang sudah disetujui untuk pegawai ini.';
            if ($_GET['err'] === 'not_found') echo '⚠️ Data pengajuan tidak ditemukan!';
            if ($_GET['err'] === 'delete_failed') echo '⚠️ Gagal menghapus data pengajuan!';
            if ($_GET['err'] === 'invalid_id') echo '⚠️ ID pengajuan tidak valid!';
        ?>
    </div>
<?php endif; ?>

<div class="table-card">
    <div class="looker-filter-bar" data-table="#ketidakhadiranTable">
        <div class="looker-filter-bar-left">
            <!-- Quick Search Input -->
            <div class="looker-search-box">
                <span class="looker-search-icon"><?= Icon::get('search', 14) ?></span>
                <input type="text" class="looker-search-input" data-table="#ketidakhadiranTable" placeholder="Cari Ketidakhadiran...">
            </div>

            <!-- Sort Select -->
            <div class="looker-sort-box">
                <select class="table-sort-select" data-table="#ketidakhadiranTable">
                    <option value="default">Urutkan: Default</option>
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

            <!-- Filter Pill: Kategori -->
            <div class="looker-pill-wrapper" data-table="#ketidakhadiranTable" data-filter-col="kategori" data-label="Kategori">
                <button type="button" class="looker-filter-pill">
                    <span class="looker-pill-text">Kategori</span>
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
                        <input type="text" class="looker-popover-input" placeholder="Cari kategori...">
                    </div>
                    <div class="looker-checklist">
                        <label class="looker-check-item">
                            <span class="looker-check-left">
                                <input type="checkbox" value="izin" checked>
                                <span class="looker-check-label">Izin</span>
                            </span>
                            <span class="looker-check-count"></span>
                        </label>
                        <label class="looker-check-item">
                            <span class="looker-check-left">
                                <input type="checkbox" value="sakit" checked>
                                <span class="looker-check-label">Sakit</span>
                            </span>
                            <span class="looker-check-count"></span>
                        </label>
                        <label class="looker-check-item">
                            <span class="looker-check-left">
                                <input type="checkbox" value="cuti" checked>
                                <span class="looker-check-label">Cuti</span>
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

            <!-- Filter Pill: Status -->
            <div class="looker-pill-wrapper" data-table="#ketidakhadiranTable" data-filter-col="status" data-label="Status">
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
                                <input type="checkbox" value="menunggu" checked>
                                <span class="looker-check-label">Menunggu</span>
                            </span>
                            <span class="looker-check-count"></span>
                        </label>
                        <label class="looker-check-item">
                            <span class="looker-check-left">
                                <input type="checkbox" value="disetujui" checked>
                                <span class="looker-check-label">Disetujui</span>
                            </span>
                            <span class="looker-check-count"></span>
                        </label>
                        <label class="looker-check-item">
                            <span class="looker-check-left">
                                <input type="checkbox" value="ditolak" checked>
                                <span class="looker-check-label">Ditolak</span>
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
            <button type="button" class="looker-reset-all-btn" data-table="#ketidakhadiranTable" title="Reset semua filter">
                <?= Icon::get('refresh-cw', 13) ?> Reset
            </button>
        </div>
        <div class="looker-filter-bar-right">
            <span class="looker-row-count"></span>
            <?php if ($currentUser['role'] === 'admin'): ?>
                <a href="<?= Config::baseUrl('ketidakhadiran/export-excel') ?>" id="btnExportExcelIzin" class="btn btn-success btn-sm" data-turbo="false" style="display:inline-flex; align-items:center; gap:6px; font-weight:600; padding:5px 12px; font-size:0.8rem;" title="Unduh Rekap Ketidakhadiran ke File Excel">
                    <?= Icon::get('download', 14) ?> <span>Export Excel</span>
                </a>
            <?php endif; ?>
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
                        <th style="text-align: center; width: 190px;">Aksi</th>
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
                                <div><?= date('d M Y', strtotime($row['tanggal_mulai'])) ?> s/d <?= date('d M Y', strtotime($row['tanggal_selesai'])) ?></div>
                                <?php if (!empty($row['bentrok_dengan']) && $row['status_approval'] === 'pending'): ?>
                                    <div style="margin-top: 5px; display:inline-flex; align-items:center; gap:5px; font-size:0.75rem; color:#b45309; background:#fffbeb; padding:2px 8px; border-radius:4px; border:1px solid #fde68a;" title="Pegawai ini sudah memiliki pengajuan berstatus disetujui pada rentang tanggal ini">
                                        <span>⚠️ Bentrok: <?= htmlspecialchars($row['bentrok_dengan']) ?></span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?= nl2br(htmlspecialchars($row['keterangan'])) ?></td>
                            <td style="text-align: center;">
                                <?php if (!empty($row['dokumen_bukti'])): ?>
                                    <a href="<?= ImageHelper::getDocumentUrl($row['dokumen_bukti']) ?>" target="_blank" data-turbo="false" class="btn btn-secondary btn-sm" title="Buka Dokumen">
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
                                <td style="text-align: center; vertical-align: middle;">
                                    <?php if ($row['status_approval'] === 'pending'): ?>
                                        <div style="display:inline-flex; flex-direction:column; gap:5px; align-items:center; justify-content:center; width:100%; max-width:170px;">
                                            <div style="display:flex; gap:5px; width:100%;">
                                                <button type="button" 
                                                        class="btn btn-primary btn-sm btn-action-approve"
                                                        style="flex: 1; padding: 4px 6px; font-size: 0.76rem; justify-content: center;"
                                                        data-id="<?= (int)$row['id'] ?>"
                                                        data-nama="<?= htmlspecialchars($row['nama_pegawai'] ?? 'Pegawai') ?>"
                                                        data-kategori="<?= htmlspecialchars(strtoupper($row['kategori'])) ?>"
                                                        data-periode="<?= date('d M Y', strtotime($row['tanggal_mulai'])) . ' s/d ' . date('d M Y', strtotime($row['tanggal_selesai'])) ?>"
                                                        data-keterangan="<?= htmlspecialchars($row['keterangan'] ?? '-') ?>"
                                                        data-bentrok="<?= htmlspecialchars($row['bentrok_dengan'] ?? '') ?>"
                                                        title="Setujui Pengajuan Ini">
                                                    <?= Icon::get('check', 13) ?> <span>Setujui</span>
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-danger btn-sm btn-action-reject"
                                                        style="flex: 1; padding: 4px 6px; font-size: 0.76rem; justify-content: center;"
                                                        data-id="<?= (int)$row['id'] ?>"
                                                        data-nama="<?= htmlspecialchars($row['nama_pegawai'] ?? 'Pegawai') ?>"
                                                        data-kategori="<?= htmlspecialchars(strtoupper($row['kategori'])) ?>"
                                                        data-periode="<?= date('d M Y', strtotime($row['tanggal_mulai'])) . ' s/d ' . date('d M Y', strtotime($row['tanggal_selesai'])) ?>"
                                                        title="Tolak Pengajuan Ini">
                                                    <?= Icon::get('x', 13) ?> <span>Tolak</span>
                                                </button>
                                            </div>
                                            <div style="display:flex; gap:5px; width:100%;">
                                                <button type="button" 
                                                        class="btn btn-secondary btn-sm btn-action-edit"
                                                        style="flex: 1; padding: 4px 6px; font-size: 0.76rem; justify-content: center;"
                                                        data-id="<?= (int)$row['id'] ?>"
                                                        data-nama="<?= htmlspecialchars($row['nama_pegawai'] ?? 'Pegawai') ?>"
                                                        data-nik="<?= htmlspecialchars($row['nik'] ?? '-') ?>"
                                                        data-jabatan="<?= htmlspecialchars($row['jabatan'] ?? '-') ?>"
                                                        data-kategori="<?= htmlspecialchars($row['kategori']) ?>"
                                                        data-tanggal-mulai="<?= htmlspecialchars($row['tanggal_mulai']) ?>"
                                                        data-tanggal-selesai="<?= htmlspecialchars($row['tanggal_selesai']) ?>"
                                                        data-keterangan="<?= htmlspecialchars($row['keterangan'] ?? '') ?>"
                                                        data-status="<?= htmlspecialchars($row['status_approval']) ?>"
                                                        data-catatan="<?= htmlspecialchars($row['catatan_admin'] ?? '') ?>"
                                                        title="Edit / Koreksi Data Izin">
                                                    <?= Icon::get('edit', 13) ?> <span>Edit</span>
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-danger btn-sm btn-action-delete"
                                                        style="flex: 1; padding: 4px 6px; font-size: 0.76rem; justify-content: center;"
                                                        data-id="<?= (int)$row['id'] ?>"
                                                        data-nama="<?= htmlspecialchars($row['nama_pegawai'] ?? 'Pegawai') ?>"
                                                        data-kategori="<?= htmlspecialchars(strtoupper($row['kategori'])) ?>"
                                                        data-periode="<?= date('d M Y', strtotime($row['tanggal_mulai'])) . ' s/d ' . date('d M Y', strtotime($row['tanggal_selesai'])) ?>"
                                                        data-status="<?= htmlspecialchars($row['status_approval']) ?>"
                                                        title="Hapus Pengajuan Ini">
                                                    <?= Icon::get('trash', 13) ?> <span>Hapus</span>
                                                </button>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div style="display:inline-flex; flex-direction:column; gap:5px; align-items:center; justify-content:center; width:100%; max-width:170px;">
                                            <div style="display:flex; gap:5px; width:100%;">
                                                <button type="button" 
                                                        class="btn btn-secondary btn-sm btn-action-edit"
                                                        style="flex: 1; padding: 4px 6px; font-size: 0.76rem; justify-content: center;"
                                                        data-id="<?= (int)$row['id'] ?>"
                                                        data-nama="<?= htmlspecialchars($row['nama_pegawai'] ?? 'Pegawai') ?>"
                                                        data-nik="<?= htmlspecialchars($row['nik'] ?? '-') ?>"
                                                        data-jabatan="<?= htmlspecialchars($row['jabatan'] ?? '-') ?>"
                                                        data-kategori="<?= htmlspecialchars($row['kategori']) ?>"
                                                        data-tanggal-mulai="<?= htmlspecialchars($row['tanggal_mulai']) ?>"
                                                        data-tanggal-selesai="<?= htmlspecialchars($row['tanggal_selesai']) ?>"
                                                        data-keterangan="<?= htmlspecialchars($row['keterangan'] ?? '') ?>"
                                                        data-status="<?= htmlspecialchars($row['status_approval']) ?>"
                                                        data-catatan="<?= htmlspecialchars($row['catatan_admin'] ?? '') ?>"
                                                        title="Edit / Koreksi Data Izin">
                                                    <?= Icon::get('edit', 13) ?> <span>Edit</span>
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-danger btn-sm btn-action-delete"
                                                        style="flex: 1; padding: 4px 6px; font-size: 0.76rem; justify-content: center;"
                                                        data-id="<?= (int)$row['id'] ?>"
                                                        data-nama="<?= htmlspecialchars($row['nama_pegawai'] ?? 'Pegawai') ?>"
                                                        data-kategori="<?= htmlspecialchars(strtoupper($row['kategori'])) ?>"
                                                        data-periode="<?= date('d M Y', strtotime($row['tanggal_mulai'])) . ' s/d ' . date('d M Y', strtotime($row['tanggal_selesai'])) ?>"
                                                        data-status="<?= htmlspecialchars($row['status_approval']) ?>"
                                                        title="Hapus Pengajuan Ini">
                                                    <?= Icon::get('trash', 13) ?> <span>Hapus</span>
                                                </button>
                                            </div>
                                            <?php if (!empty($row['catatan_admin'])): ?>
                                                <div style="font-size: 0.73rem; color: var(--text-muted); width: 100%; text-overflow: ellipsis; overflow: hidden; white-space: nowrap; background:var(--bg-subtle); padding:2px 6px; border-radius:4px; border:1px solid var(--border-color); text-align:center;" title="Catatan Admin: <?= htmlspecialchars($row['catatan_admin']) ?>">
                                                    <?= htmlspecialchars($row['catatan_admin']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
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

<?php if ($currentUser['role'] === 'admin'): ?>
<!-- Modal Konfirmasi Persetujuan Izin (In-App Dialog) -->
<div id="modalApproveIzin" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="modalApproveTitle">
    <div class="modal-card modal-confirm" style="max-width: 480px !important;">
        <div class="modal-icon-success">
            <?= Icon::get('check', 28) ?>
        </div>
        <h3 id="modalApproveTitle" class="modal-title">Setujui Pengajuan Ketidakhadiran?</h3>
        <p class="modal-desc" style="margin-bottom: 14px;">
            Verifikasi dan berikan persetujuan untuk permohonan izin pegawai berikut:
        </p>

        <!-- Warning Jika Ada Tanggal Bentrok / Overlap -->
        <div id="modalApproveBentrokNotice" style="display:none; padding:10px 12px; font-size:0.82rem; margin-bottom:12px; border-radius:var(--radius-sm); border:1px solid #fed7aa; background:#fff7ed; color:#9a3412; text-align:left;">
            <div style="font-weight:700; display:flex; align-items:center; gap:6px; margin-bottom:2px;">
                ⚠️ Perhatian: Tanggal Bertabrakan
            </div>
            <span>Pegawai ini sudah memiliki izin <strong>Disetujui</strong> pada rentang yang sama: <strong id="modalApproveBentrokText"></strong>.</span>
        </div>

        <!-- Ringkasan Info Pengajuan -->
        <div class="presensi-summary-box" style="text-align: left; margin-bottom: 16px;">
            <div class="presensi-summary-row">
                <span style="color:var(--text-muted);">Pegawai:</span>
                <strong id="modalApproveNama" style="color:var(--text-main);">-</strong>
            </div>
            <div class="presensi-summary-row">
                <span style="color:var(--text-muted);">Kategori:</span>
                <span id="modalApproveKategori" class="badge badge-info">-</span>
            </div>
            <div class="presensi-summary-row">
                <span style="color:var(--text-muted);">Periode:</span>
                <strong id="modalApprovePeriode" style="color:var(--text-main);">-</strong>
            </div>
            <div style="margin-top: 4px; padding-top: 6px; border-top: 1px dashed var(--border-color);">
                <span style="color:var(--text-muted); font-size:0.8rem; display:block; margin-bottom:2px;">Keterangan Pegawai:</span>
                <span id="modalApproveKeterangan" style="font-size:0.82rem; color:var(--text-main); font-style:italic;">-</span>
            </div>
        </div>

        <form id="formApproveIzin" method="POST" action="<?= Config::baseUrl('ketidakhadiran/approve') ?>" data-turbo="false" style="text-align: left;">
            <input type="hidden" name="id" id="modalApproveId" value="">
            <div style="margin-bottom: 18px;">
                <label for="modalApproveCatatan" style="font-size:0.82rem; font-weight:600; color:var(--text-main); display:block; margin-bottom:6px;">
                    Catatan Administrator (Opsional):
                </label>
                <input type="text" 
                       name="catatan" 
                       id="modalApproveCatatan" 
                       class="form-input" 
                       placeholder="Contoh: Disetujui oleh HRD / Istirahat yang cukup" 
                       style="font-size:0.85rem; padding:8px 12px;">
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" id="btnCancelApprove">
                    Batal
                </button>
                <button type="submit" class="btn btn-primary" id="btnConfirmApprove">
                    <?= Icon::get('check', 14) ?> <span id="btnConfirmApproveText">Ya, Setujui Pengajuan</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Konfirmasi Penolakan Izin (In-App Dialog Dengan Alasan) -->
<div id="modalRejectIzin" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="modalRejectTitle">
    <div class="modal-card modal-confirm" style="max-width: 480px !important;">
        <div class="modal-icon-danger">
            <?= Icon::get('x', 28) ?>
        </div>
        <h3 id="modalRejectTitle" class="modal-title">Tolak Pengajuan Ketidakhadiran?</h3>
        <p class="modal-desc" style="margin-bottom: 14px;">
            Berikan alasan penolakan agar pegawai mengetahui kendala pada permohonannya:
        </p>

        <!-- Ringkasan Info Pengajuan -->
        <div class="presensi-summary-box" style="text-align: left; margin-bottom: 14px;">
            <div class="presensi-summary-row">
                <span style="color:var(--text-muted);">Pegawai:</span>
                <strong id="modalRejectNama" style="color:var(--text-main);">-</strong>
            </div>
            <div class="presensi-summary-row">
                <span style="color:var(--text-muted);">Kategori & Periode:</span>
                <span id="modalRejectInfo" style="color:var(--text-main); font-weight:600;">-</span>
            </div>
        </div>

        <form id="formRejectIzin" method="POST" action="<?= Config::baseUrl('ketidakhadiran/reject') ?>" data-turbo="false" style="text-align: left;">
            <input type="hidden" name="id" id="modalRejectId" value="">
            <div style="margin-bottom: 18px;">
                <label for="modalRejectCatatan" style="font-size:0.82rem; font-weight:600; color:var(--text-main); display:block; margin-bottom:6px;">
                    Alasan Penolakan <span style="color:var(--danger);">*</span>:
                </label>
                <textarea name="catatan" 
                          id="modalRejectCatatan" 
                          class="form-textarea" 
                          rows="3" 
                          required 
                          placeholder="Contoh: Dokumen surat dokter belum diunggah / tanggal bertabrakan dengan izin sebelumnya..."
                          style="font-size:0.85rem; padding:8px 12px; resize:vertical;"></textarea>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" id="btnCancelReject">
                    Batal
                </button>
                <button type="submit" class="btn btn-danger" id="btnConfirmReject">
                    <?= Icon::get('x', 14) ?> <span id="btnConfirmRejectText">Ya, Tolak Pengajuan</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Pengajuan Ketidakhadiran (In-App Modal) -->
<div id="modalEditIzin" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="modalEditTitle">
    <div class="modal-card modal-card-md" style="max-width: 520px !important;">
        <div class="modal-header">
            <div class="modal-header-left">
                <div class="modal-header-icon" style="background:var(--accent-blue-light); color:var(--accent-blue);">
                    <?= Icon::get('edit', 20) ?>
                </div>
                <div>
                    <h3 id="modalEditTitle" class="modal-title">Edit Data Ketidakhadiran</h3>
                    <div class="modal-subtitle">Koreksi detail perizinan atau status persetujuan</div>
                </div>
            </div>
            <button type="button" class="modal-close-btn" id="btnHeaderCloseEdit" aria-label="Tutup">
                <?= Icon::get('x', 18) ?>
            </button>
        </div>

        <form id="formEditIzin" method="POST" action="<?= Config::baseUrl('ketidakhadiran/edit') ?>" data-turbo="false" style="display:flex; flex-direction:column; flex:1;">
            <input type="hidden" name="id" id="editIzinId" value="">

            <div class="modal-body" style="padding: 16px 20px;">
                <div class="form-group" style="margin-bottom: 14px;">
                    <label class="form-label" style="font-weight:600; font-size:0.85rem;">Pegawai:</label>
                    <input type="text" id="editIzinNamaPegawai" class="form-input" style="background:var(--bg-subtle); font-weight:600;" readonly>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:14px;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label" style="font-weight:600; font-size:0.85rem;">Kategori:</label>
                        <select name="kategori" id="editIzinKategori" class="form-select" required>
                            <option value="izin">Izin</option>
                            <option value="sakit">Sakit</option>
                            <option value="cuti">Cuti</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label" style="font-weight:600; font-size:0.85rem;">Status Approval:</label>
                        <select name="status_approval" id="editIzinStatus" class="form-select" required>
                            <option value="pending">Menunggu (Pending)</option>
                            <option value="disetujui">Disetujui</option>
                            <option value="ditolak">Ditolak</option>
                        </select>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:14px;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label" style="font-weight:600; font-size:0.85rem;">Tanggal Mulai:</label>
                        <input type="date" name="tanggal_mulai" id="editIzinTanggalMulai" class="form-input" required>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label" style="font-weight:600; font-size:0.85rem;">Tanggal Selesai:</label>
                        <input type="date" name="tanggal_selesai" id="editIzinTanggalSelesai" class="form-input" required>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom:14px;">
                    <label class="form-label" style="font-weight:600; font-size:0.85rem;">Keterangan / Alasan Pegawai:</label>
                    <textarea name="keterangan" id="editIzinKeterangan" class="form-textarea" rows="2" required placeholder="Alasan izin / sakit / cuti..."></textarea>
                </div>

                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label" style="font-weight:600; font-size:0.85rem;">Catatan Administrator:</label>
                    <textarea name="catatan_admin" id="editIzinCatatanAdmin" class="form-textarea" rows="2" placeholder="Catatan persetujuan atau alasan penolakan..."></textarea>
                </div>
            </div>

            <div class="modal-actions" style="padding: 12px 20px; border-top: 1px solid var(--border-color); display:flex; justify-content:flex-end; gap:8px;">
                <button type="button" class="btn btn-secondary" id="btnCancelEdit">
                    Batal
                </button>
                <button type="submit" class="btn btn-primary" id="btnConfirmEdit">
                    <?= Icon::get('check', 14) ?> <span id="btnConfirmEditText">Simpan Perubahan</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Konfirmasi Hapus Ketidakhadiran (In-App Dialog) -->
<div id="modalDeleteIzin" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="modalDeleteTitle">
    <div class="modal-card modal-confirm" style="max-width: 460px !important;">
        <div class="modal-icon-danger">
            <?= Icon::get('trash', 28) ?>
        </div>
        <h3 id="modalDeleteTitle" class="modal-title">Hapus Pengajuan Ketidakhadiran?</h3>
        <p class="modal-desc" style="margin-bottom: 14px;">
            Apakah Anda yakin ingin menghapus data perizinan ini secara permanen dari sistem?
        </p>

        <!-- Warning jika status disetujui -->
        <div id="modalDeleteWarningDisetujui" style="display:none; padding:10px 12px; font-size:0.82rem; margin-bottom:12px; border-radius:var(--radius-sm); border:1px solid #fed7aa; background:#fff7ed; color:#9a3412; text-align:left;">
            <div style="font-weight:700; margin-bottom:2px;">⚠️ Perhatian: Izin Disetujui</div>
            <span>Menghapus data ini akan membatalkan status izin dan dapat mempengaruhi rekapitulasi absensi serta penggajian pegawai.</span>
        </div>

        <!-- Ringkasan Info Pengajuan -->
        <div class="presensi-summary-box" style="text-align: left; margin-bottom: 16px;">
            <div class="presensi-summary-row">
                <span style="color:var(--text-muted);">Pegawai:</span>
                <strong id="modalDeleteNama" style="color:var(--text-main);">-</strong>
            </div>
            <div class="presensi-summary-row">
                <span style="color:var(--text-muted);">Kategori & Periode:</span>
                <span id="modalDeleteInfo" style="color:var(--text-main); font-weight:600;">-</span>
            </div>
            <div class="presensi-summary-row">
                <span style="color:var(--text-muted);">Status Saat Ini:</span>
                <span id="modalDeleteStatus" class="badge">-</span>
            </div>
        </div>

        <form id="formDeleteIzin" method="POST" action="<?= Config::baseUrl('ketidakhadiran/hapus') ?>" data-turbo="false" style="text-align: left;">
            <input type="hidden" name="id" id="modalDeleteId" value="">

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" id="btnCancelDelete">
                    Batal
                </button>
                <button type="submit" class="btn btn-danger" id="btnConfirmDelete">
                    <?= Icon::get('trash', 14) ?> <span id="btnConfirmDeleteText">Ya, Hapus Data</span>
                </button>
            </div>
        </form>
    </div>
</div>


<script>
(() => {
    function closeAllModals() {
        ['modalApproveIzin', 'modalRejectIzin', 'modalEditIzin', 'modalDeleteIzin'].forEach(id => {
            const m = document.getElementById(id);
            if (m) m.classList.remove('show');
        });
        document.body.style.overflow = '';
    }

    function initKetidakhadiranModals() {
        // Handle Tombol Export Excel (Bawa Filter Aktif)
        const btnExportExcel = document.getElementById('btnExportExcelIzin');
        if (btnExportExcel) {
            btnExportExcel.onclick = () => {
                const checkedKategori = Array.from(document.querySelectorAll('.looker-pill-wrapper[data-filter-col="kategori"] .looker-checklist input:checked')).map(cb => cb.value);
                const allKategoriCount = document.querySelectorAll('.looker-pill-wrapper[data-filter-col="kategori"] .looker-checklist input').length;
                const checkedStatus = Array.from(document.querySelectorAll('.looker-pill-wrapper[data-filter-col="status"] .looker-checklist input:checked')).map(cb => cb.value);
                const allStatusCount = document.querySelectorAll('.looker-pill-wrapper[data-filter-col="status"] .looker-checklist input').length;

                const baseHref = "<?= Config::baseUrl('ketidakhadiran/export-excel') ?>";
                const params = new URLSearchParams();
                if (checkedKategori.length > 0 && checkedKategori.length < allKategoriCount) {
                    params.set('kategori', checkedKategori.join(','));
                }
                if (checkedStatus.length > 0 && checkedStatus.length < allStatusCount) {
                    params.set('status', checkedStatus.join(','));
                }
                const queryStr = params.toString();
                btnExportExcel.href = baseHref + (queryStr ? '?' + queryStr : '');
            };
        }

        // Form Submit Handlers (Validasi & Anti Double-Submission Feedback)
        const formApprove = document.getElementById('formApproveIzin');
        const formReject = document.getElementById('formRejectIzin');
        const formEdit = document.getElementById('formEditIzin');
        const formDelete = document.getElementById('formDeleteIzin');
        const btnConfirmApprove = document.getElementById('btnConfirmApprove');
        const btnConfirmApproveText = document.getElementById('btnConfirmApproveText');
        const btnConfirmReject = document.getElementById('btnConfirmReject');
        const btnConfirmRejectText = document.getElementById('btnConfirmRejectText');
        const btnConfirmEdit = document.getElementById('btnConfirmEdit');
        const btnConfirmEditText = document.getElementById('btnConfirmEditText');
        const btnConfirmDelete = document.getElementById('btnConfirmDelete');
        const btnConfirmDeleteText = document.getElementById('btnConfirmDeleteText');
        const editTglMulai = document.getElementById('editIzinTanggalMulai');
        const editTglSelesai = document.getElementById('editIzinTanggalSelesai');

        if (formApprove) {
            formApprove.onsubmit = () => {
                if (btnConfirmApprove) btnConfirmApprove.disabled = true;
                if (btnConfirmApproveText) btnConfirmApproveText.textContent = 'Memproses...';
            };
        }

        if (formReject) {
            formReject.onsubmit = () => {
                if (btnConfirmReject) btnConfirmReject.disabled = true;
                if (btnConfirmRejectText) btnConfirmRejectText.textContent = 'Memproses...';
            };
        }

        if (formEdit) {
            formEdit.onsubmit = (e) => {
                if (editTglSelesai && editTglMulai && editTglSelesai.value < editTglMulai.value) {
                    if (window.SGMDialog && typeof window.SGMDialog.toast === 'function') {
                        window.SGMDialog.toast('Tanggal selesai tidak boleh lebih awal dari tanggal mulai!', 'warning');
                    } else {
                        alert('Tanggal selesai tidak boleh lebih awal dari tanggal mulai!');
                    }
                    editTglSelesai.focus();
                    e.preventDefault();
                    return false;
                }
                if (btnConfirmEdit) btnConfirmEdit.disabled = true;
                if (btnConfirmEditText) btnConfirmEditText.textContent = 'Menyimpan...';
            };
        }

        if (formDelete) {
            formDelete.onsubmit = () => {
                if (btnConfirmDelete) btnConfirmDelete.disabled = true;
                if (btnConfirmDeleteText) btnConfirmDeleteText.textContent = 'Menghapus...';
            };
        }
    }

    // Delegated Listener for Click Events (Guarded against multi-attachment)
    if (!window._hasKetidakhadiranDelegatedClickAttached) {
        window._hasKetidakhadiranDelegatedClickAttached = true;
        document.addEventListener('click', (e) => {
            const modalApprove = document.getElementById('modalApproveIzin');
            const modalReject = document.getElementById('modalRejectIzin');
            const modalEdit = document.getElementById('modalEditIzin');
            const modalDelete = document.getElementById('modalDeleteIzin');

            // Guard: Halaman aktif harus memiliki setidaknya salah satu modal izin di DOM
            if ((!modalEdit || !modalEdit.isConnected) && (!modalApprove || !modalApprove.isConnected)) return;

            // Close buttons or click on backdrop
            if (e.target.closest('#btnCancelApprove') || 
                e.target.closest('#btnCancelReject') || 
                e.target.closest('#btnCancelEdit') || 
                e.target.closest('#btnHeaderCloseEdit') || 
                e.target.closest('#btnCancelDelete') || 
                e.target.classList.contains('modal-backdrop')) {
                closeAllModals();
                return;
            }

            // 1. Tombol Setujui
            const btnApprove = e.target.closest('.btn-action-approve');
            if (btnApprove && modalApprove) {
                e.preventDefault();
                const inputApproveId = document.getElementById('modalApproveId');
                const spanApproveNama = document.getElementById('modalApproveNama');
                const spanApproveKategori = document.getElementById('modalApproveKategori');
                const spanApprovePeriode = document.getElementById('modalApprovePeriode');
                const spanApproveKeterangan = document.getElementById('modalApproveKeterangan');
                const noticeApproveBentrok = document.getElementById('modalApproveBentrokNotice');
                const textApproveBentrok = document.getElementById('modalApproveBentrokText');
                const inputApproveCatatan = document.getElementById('modalApproveCatatan');
                const btnConfirmApprove = document.getElementById('btnConfirmApprove');
                const btnConfirmApproveText = document.getElementById('btnConfirmApproveText');

                if (inputApproveId) inputApproveId.value = btnApprove.getAttribute('data-id') || '';
                if (spanApproveNama) spanApproveNama.textContent = btnApprove.getAttribute('data-nama') || 'Pegawai';
                const kat = btnApprove.getAttribute('data-kategori') || '';
                if (spanApproveKategori) {
                    spanApproveKategori.textContent = kat;
                    spanApproveKategori.className = 'badge ' + (kat === 'SAKIT' ? 'badge-danger' : (kat === 'CUTI' ? 'badge-warning' : 'badge-info'));
                }
                if (spanApprovePeriode) spanApprovePeriode.textContent = btnApprove.getAttribute('data-periode') || '-';
                if (spanApproveKeterangan) spanApproveKeterangan.textContent = btnApprove.getAttribute('data-keterangan') || '-';

                const bentrok = btnApprove.getAttribute('data-bentrok');
                if (noticeApproveBentrok) {
                    if (bentrok && bentrok.trim() !== '') {
                        if (textApproveBentrok) textApproveBentrok.textContent = bentrok;
                        noticeApproveBentrok.style.display = 'block';
                    } else {
                        noticeApproveBentrok.style.display = 'none';
                    }
                }

                if (inputApproveCatatan) inputApproveCatatan.value = '';
                if (btnConfirmApprove) btnConfirmApprove.disabled = false;
                if (btnConfirmApproveText) btnConfirmApproveText.textContent = 'Ya, Setujui Pengajuan';

                modalApprove.classList.add('show');
                document.body.style.overflow = 'hidden';
                return;
            }

            // 2. Tombol Tolak
            const btnReject = e.target.closest('.btn-action-reject');
            if (btnReject && modalReject) {
                e.preventDefault();
                const inputRejectId = document.getElementById('modalRejectId');
                const spanRejectNama = document.getElementById('modalRejectNama');
                const spanRejectInfo = document.getElementById('modalRejectInfo');
                const textareaRejectCatatan = document.getElementById('modalRejectCatatan');
                const btnConfirmReject = document.getElementById('btnConfirmReject');
                const btnConfirmRejectText = document.getElementById('btnConfirmRejectText');

                if (inputRejectId) inputRejectId.value = btnReject.getAttribute('data-id') || '';
                if (spanRejectNama) spanRejectNama.textContent = btnReject.getAttribute('data-nama') || 'Pegawai';
                const kat = btnReject.getAttribute('data-kategori') || '';
                const per = btnReject.getAttribute('data-periode') || '';
                if (spanRejectInfo) spanRejectInfo.textContent = `${kat} (${per})`;
                if (textareaRejectCatatan) textareaRejectCatatan.value = '';

                if (btnConfirmReject) btnConfirmReject.disabled = false;
                if (btnConfirmRejectText) btnConfirmRejectText.textContent = 'Ya, Tolak Pengajuan';

                modalReject.classList.add('show');
                document.body.style.overflow = 'hidden';
                setTimeout(() => {
                    const ta = document.getElementById('modalRejectCatatan');
                    if (ta) ta.focus();
                }, 150);
                return;
            }

            // 3. Tombol Edit
            const btnEdit = e.target.closest('.btn-action-edit');
            if (btnEdit && modalEdit) {
                e.preventDefault();
                const editId = document.getElementById('editIzinId');
                const editNamaPegawai = document.getElementById('editIzinNamaPegawai');
                const editKategori = document.getElementById('editIzinKategori');
                const editStatus = document.getElementById('editIzinStatus');
                const editTglMulai = document.getElementById('editIzinTanggalMulai');
                const editTglSelesai = document.getElementById('editIzinTanggalSelesai');
                const editKeterangan = document.getElementById('editIzinKeterangan');
                const editCatatanAdmin = document.getElementById('editIzinCatatanAdmin');
                const btnConfirmEdit = document.getElementById('btnConfirmEdit');
                const btnConfirmEditText = document.getElementById('btnConfirmEditText');

                if (editId) editId.value = btnEdit.getAttribute('data-id') || '';
                const nama = btnEdit.getAttribute('data-nama') || 'Pegawai';
                const nik = btnEdit.getAttribute('data-nik') || '-';
                const jabatan = btnEdit.getAttribute('data-jabatan') || '-';
                if (editNamaPegawai) editNamaPegawai.value = `${nama} (${nik}) - ${jabatan}`;

                if (editKategori) editKategori.value = btnEdit.getAttribute('data-kategori') || 'izin';
                if (editStatus) editStatus.value = btnEdit.getAttribute('data-status') || 'pending';
                if (editTglMulai) editTglMulai.value = btnEdit.getAttribute('data-tanggal-mulai') || '';
                if (editTglSelesai) editTglSelesai.value = btnEdit.getAttribute('data-tanggal-selesai') || '';
                if (editKeterangan) editKeterangan.value = btnEdit.getAttribute('data-keterangan') || '';
                if (editCatatanAdmin) editCatatanAdmin.value = btnEdit.getAttribute('data-catatan') || '';

                if (btnConfirmEdit) btnConfirmEdit.disabled = false;
                if (btnConfirmEditText) btnConfirmEditText.textContent = 'Simpan Perubahan';

                modalEdit.classList.add('show');
                document.body.style.overflow = 'hidden';
                return;
            }

            // 4. Tombol Hapus
            const btnDelete = e.target.closest('.btn-action-delete');
            if (btnDelete && modalDelete) {
                e.preventDefault();
                const deleteId = document.getElementById('modalDeleteId');
                const deleteNama = document.getElementById('modalDeleteNama');
                const deleteInfo = document.getElementById('modalDeleteInfo');
                const deleteStatus = document.getElementById('modalDeleteStatus');
                const deleteWarningDisetujui = document.getElementById('modalDeleteWarningDisetujui');
                const btnConfirmDelete = document.getElementById('btnConfirmDelete');
                const btnConfirmDeleteText = document.getElementById('btnConfirmDeleteText');

                if (deleteId) deleteId.value = btnDelete.getAttribute('data-id') || '';
                if (deleteNama) deleteNama.textContent = btnDelete.getAttribute('data-nama') || 'Pegawai';
                const kat = btnDelete.getAttribute('data-kategori') || '';
                const per = btnDelete.getAttribute('data-periode') || '';
                const st = btnDelete.getAttribute('data-status') || '';
                if (deleteInfo) deleteInfo.textContent = `${kat} (${per})`;
                if (deleteStatus) {
                    deleteStatus.textContent = st.toUpperCase();
                    deleteStatus.className = 'badge ' + (st === 'disetujui' ? 'badge-success' : (st === 'ditolak' ? 'badge-danger' : 'badge-warning'));
                }

                if (deleteWarningDisetujui) {
                    deleteWarningDisetujui.style.display = (st === 'disetujui') ? 'block' : 'none';
                }

                if (btnConfirmDelete) btnConfirmDelete.disabled = false;
                if (btnConfirmDeleteText) btnConfirmDeleteText.textContent = 'Ya, Hapus Data';

                modalDelete.classList.add('show');
                document.body.style.overflow = 'hidden';
                return;
            }
        });
    }

    if (!window._hasKetidakhadiranEscapeAttached) {
        window._hasKetidakhadiranEscapeAttached = true;
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const anyOpen = document.querySelector('.modal-backdrop.show');
                if (anyOpen) closeAllModals();
            }
        });
    }

    document.addEventListener('turbo:load', initKetidakhadiranModals);
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initKetidakhadiranModals);
    } else {
        initKetidakhadiranModals();
    }
})();
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
