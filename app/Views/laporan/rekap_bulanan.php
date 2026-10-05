<?php
$pageTitle = 'Rekap Presensi Bulanan - ' . Config::APP_NAME;
$activePage = 'laporan_bulanan';
require_once __DIR__ . '/../layouts/header.php';

$namaBulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
$bulanSelected = (int)($bulan ?? date('m'));
$tahunSelected = (int)($tahun ?? date('Y'));
$spreadsheetUrl = Config::getSpreadsheetUrl();
$spreadsheetWebhookUrl = Config::getSpreadsheetWebhookUrl();
$periodeText = ($namaBulan[$bulanSelected] ?? $bulanSelected) . ' ' . $tahunSelected;
$targetSheetName = "Rekap Absensi " . $periodeText;
?>

<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
    <div>
        <h2 style="font-size: 1.4rem; font-weight: 700; margin: 0 0 4px 0;">Rekap Presensi Bulanan</h2>
        <p style="color: var(--text-muted); margin: 0; font-size: 0.9rem;">
            Catatan kehadiran, jam kerja, keterlambatan bulanan, dan integrasi spreadsheet.
        </p>
    </div>

    <?php if (($currentUser['role'] ?? '') === 'admin'): ?>
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <!-- Buka Spreadsheet -->
        <a href="<?= htmlspecialchars($spreadsheetUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn" style="background: #0F9D58; color: #fff; display: inline-flex; align-items: center; gap: 6px;">
            <?= Icon::get('external-link', 16) ?>
            <span>Buka Spreadsheet</span>
        </a>

        <!-- Sinkronkan ke Spreadsheet -->
        <button type="button" class="btn btn-blue" onclick="bukaModalSyncBulanan()" style="display: inline-flex; align-items: center; gap: 6px;">
            <?= Icon::get('refresh', 16) ?>
            <span>Sinkronkan ke Spreadsheet</span>
        </button>
    </div>
    <?php endif; ?>
</div>

<!-- Alert Notifikasi -->
<?php if (isset($_GET['msg']) && $_GET['msg'] === 'synced'): ?>
    <div class="alert alert-success" style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 8px;">
            <?= Icon::get('check-circle', 18) ?>
            <span><strong>Berhasil!</strong> Rekap presensi periode <strong><?= htmlspecialchars($periodeText) ?></strong> berhasil disinkronkan ke sheet <code><?= htmlspecialchars($targetSheetName) ?></code> di Google Spreadsheet.</span>
        </div>
        <a href="<?= htmlspecialchars($spreadsheetUrl) ?>" target="_blank" style="color: inherit; text-decoration: underline; font-weight: 600;">Buka Hasil →</a>
    </div>
<?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'presensi_updated'): ?>
    <div class="alert alert-success" style="margin-bottom: 20px;">
        <?= Icon::get('check-circle', 18) ?> Data presensi berhasil diperbarui.
    </div>
<?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'presensi_deleted'): ?>
    <div class="alert alert-success" style="margin-bottom: 20px;">
        <?= Icon::get('check-circle', 18) ?> Data presensi berhasil dihapus.
    </div>
<?php elseif (isset($_GET['err']) && $_GET['err'] === 'sync_failed'): ?>
    <div class="alert alert-danger" style="margin-bottom: 20px;">
        <?= Icon::get('alert-circle', 18) ?>
        <strong>Gagal Sinkronisasi:</strong> <?= htmlspecialchars($_SESSION['sync_error'] ?? 'Periksa URL Webhook Google Apps Script Anda.') ?>
        <br><small style="margin-top: 4px; display: block;">Pastikan Anda telah memasang Webhook Google Apps Script dengan izin akses 'Anyone'. Klik tombol <strong>Panduan Webhook</strong> untuk bantuan.</small>
    </div>
    <?php unset($_SESSION['sync_error']); ?>
<?php endif; ?>

<!-- Filter Bar & Export Actions (Screenshot 5) -->
<div class="form-card" style="padding: 16px 20px; margin-bottom: 24px;">
    <form method="GET" action="<?= Config::baseUrl('laporan/bulanan') ?>" style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
        <?php if ($currentUser['role'] === 'admin'): ?>
            <a href="<?= Config::baseUrl("laporan/export-excel?bulan={$bulanSelected}&tahun={$tahunSelected}") ?>" class="btn btn-blue">
                Export Excel
            </a>
        <?php endif; ?>

        <div style="min-width: 170px;">
            <select name="bulan" class="form-select">
                <option value="">-- Pilih Bulan --</option>
                <?php foreach ($namaBulan as $num => $nama): ?>
                    <option value="<?= $num ?>" <?= ($bulanSelected === $num) ? 'selected' : '' ?>>
                        <?= $nama ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="min-width: 140px;">
            <select name="tahun" class="form-select">
                <option value="">-- Pilih Tahun --</option>
                <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                    <option value="<?= $y ?>" <?= ($tahunSelected === $y) ? 'selected' : '' ?>>
                        <?= $y ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-blue">Tampilkan</button>
        <a href="<?= Config::baseUrl('laporan/bulanan') ?>" class="btn btn-success">Refresh</a>

        <?php if (($currentUser['role'] ?? '') === 'admin'): ?>
            <div style="margin-left: auto; display: flex; align-items: center; gap: 8px;">
                <button type="button" class="btn btn-sm btn-outline-info" onclick="bukaModalPetunjukBulanan()" style="border: 1px dashed var(--accent-color); color: var(--accent-color);">
                    💡 Panduan Webhook Spreadsheet
                </button>
            </div>
        <?php endif; ?>
    </form>
</div>

<!-- Table Header -->
<div style="margin-bottom: 12px; font-weight: 700; font-size: 1.05rem; color: var(--text-main);">
    Rekap Presensi Bulan <?= $namaBulan[$bulanSelected] ?? $bulanSelected ?> <?= $tahunSelected ?>
</div>

<div class="table-card">
    <div class="table-toolbar">
        <div class="table-toolbar-left">
            <div class="table-toolbar-group">
                <label for="sortBulanan" style="font-weight:600;">Urutkan:</label>
                <select id="sortBulanan" class="table-sort-select" data-table="#rekapBulananTable">
                    <option value="default">Default (Urutan Asli)</option>
                    <option value="tanggal-desc">Tanggal (Terbaru)</option>
                    <option value="tanggal-asc">Tanggal (Terlama)</option>
                    <option value="nama-asc">Nama Pegawai (A - Z)</option>
                    <option value="nama-desc">Nama Pegawai (Z - A)</option>
                    <option value="masuk-desc">Jam Masuk (Paling Akhir)</option>
                    <option value="masuk-asc">Jam Masuk (Paling Awal)</option>
                    <option value="durasi-desc">Total Jam Kerja (Terlama)</option>
                    <option value="terlambat-desc">Keterlambatan (Paling Lama)</option>
                </select>
            </div>
            <div class="table-toolbar-group">
                <label for="filterKetBulanan" style="font-weight:600;">Kategori:</label>
                <select id="filterKetBulanan" class="table-filter-select" data-table="#rekapBulananTable" data-filter-col="terlambat">
                    <option value="">Semua Kategori</option>
                    <option value="tepat">Tepat Waktu</option>
                    <option value="terlambat">Terlambat</option>
                </select>
            </div>
        </div>
        <div style="font-size:0.78rem; color:var(--text-muted);">
            💡 Klik header kolom untuk urutkan instan
        </div>
    </div>
    <div class="table-responsive">
        <table class="table-custom" id="rekapBulananTable">
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
                    <th class="sortable-th" data-col="terlambat" data-sort-type="duration">Total Terlambat</th>
                    <?php if (($currentUser['role'] ?? '') === 'admin'): ?>
                        <th style="width: 80px; text-align: center;">Aksi</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($dataPresensi)): ?>
                    <tr>
                        <td colspan="<?= (($currentUser['role'] ?? '') === 'admin') ? 10 : 9 ?>" style="text-align: center; color: var(--text-muted); padding: 36px;">
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
                            </td>
                            <td data-sort-value="<?= htmlspecialchars($row['tanggal']) ?>">
                                <?= date('d F Y', strtotime($row['tanggal'])) ?>
                            </td>
                            <td data-sort-value="<?= htmlspecialchars($row['jam_masuk'] ?? '-') ?>" style="font-family:'JetBrains Mono', monospace; font-weight:600;">
                                <?= htmlspecialchars($row['jam_masuk'] ?? '-') ?>
                            </td>
                            <td style="text-align: center;">
                                <?php if (!empty($row['foto_masuk'])): ?>
                                    <a href="<?= Config::baseUrl('assets/images/uploads/presensi/' . $row['foto_masuk']) ?>" target="_blank" title="Lihat Foto Masuk">
                                        <img src="<?= Config::baseUrl('assets/images/uploads/presensi/' . $row['foto_masuk']) ?>" class="img-thumb-table" alt="Foto Masuk">
                                    </a>
                                <?php else: ?>
                                    <span style="color:var(--text-subtle);">-</span>
                                <?php endif; ?>
                            </td>
                            <td data-sort-value="<?= htmlspecialchars($row['jam_pulang'] ?? '-') ?>" style="font-family:'JetBrains Mono', monospace; font-weight:600;">
                                <?= htmlspecialchars($row['jam_pulang'] ?? '-') ?>
                            </td>
                            <td style="text-align: center;">
                                <?php if (!empty($row['foto_keluar'])): ?>
                                    <a href="<?= Config::baseUrl('assets/images/uploads/presensi/' . $row['foto_keluar']) ?>" target="_blank" title="Lihat Foto Keluar">
                                        <img src="<?= Config::baseUrl('assets/images/uploads/presensi/' . $row['foto_keluar']) ?>" class="img-thumb-table" alt="Foto Keluar">
                                    </a>
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
                            <?php if (($currentUser['role'] ?? '') === 'admin'): ?>
                                <td style="text-align: center;">
                                    <div style="display:flex; gap:4px; justify-content:center;">
                                        <button type="button" 
                                                class="btn btn-secondary btn-sm" 
                                                onclick="bukaModalEditPresensi(<?= (int)$row['id'] ?>)" 
                                                title="Edit Informasi Presensi">
                                            <?= Icon::get('edit', 14) ?>
                                        </button>
                                        <a href="<?= Config::baseUrl('laporan/hapus-presensi?id=' . (int)$row['id']) ?>" 
                                           class="btn btn-danger btn-sm" 
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus data presensi ini? Pegawai akan dapat melakukan presensi ulang.');"
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
<!-- MODAL: Sinkronisasi Presensi Bulanan ke Google Spreadsheet -->
<div id="modalSyncBulanan" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="modalSyncBulananTitle">
    <div class="modal-card modal-card-md">
        <div class="modal-header">
            <div class="modal-header-left">
                <div class="modal-header-icon" style="background: rgba(37, 99, 235, 0.12); color: #2563eb;">
                    <?= Icon::get('refresh', 20) ?>
                </div>
                <div>
                    <h3 id="modalSyncBulananTitle" class="modal-title">Sinkronkan ke Google Spreadsheet</h3>
                    <div class="modal-subtitle">Kirim rekap presensi bulanan ke sheet '<?= htmlspecialchars($targetSheetName) ?>'</div>
                </div>
            </div>
            <button type="button" class="modal-close-btn" onclick="tutupModalSyncBulanan()" aria-label="Tutup">
                <?= Icon::get('x', 18) ?>
            </button>
        </div>

        <form id="formSyncBulanan" method="POST" action="<?= Config::baseUrl('laporan/sync-spreadsheet') ?>" style="display:flex; flex-direction:column; flex:1; overflow:hidden;">
            <input type="hidden" name="bulan" value="<?= $bulanSelected ?>">
            <input type="hidden" name="tahun" value="<?= $tahunSelected ?>">

            <div class="modal-body">
                <div id="syncBulananAlert" style="display:none; margin-bottom:14px; padding:10px 14px; border-radius:6px; font-size:0.85rem;"></div>

                <div style="background:var(--bg-subtle); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:14px 16px; margin-bottom:18px;">
                    <div style="font-weight:600; font-size:0.88rem; color:var(--text-main); margin-bottom:4px;">
                        Periode: <?= htmlspecialchars($periodeText) ?> (<?= count($dataPresensi) ?> Baris Data)
                    </div>
                    <p style="font-size:0.82rem; color:var(--text-muted); margin:0;">
                        Sistem akan mengisi sheet <code><?= htmlspecialchars($targetSheetName) ?></code> pada Google Spreadsheet Anda tanpa mengubah sheet <code>Rekap Gaji Karyawan</code>.
                    </p>
                </div>

                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label" style="font-weight:600;">URL Webhook Google Apps Script:</label>
                    <input type="url" name="webhook_url" id="syncBulananWebhookUrl" class="form-input" value="<?= htmlspecialchars($spreadsheetWebhookUrl) ?>" placeholder="https://script.google.com/macros/s/.../exec" oninput="periksaWebhookBulanan(this, 'syncBulananWebhookWarn')" required>
                    <div id="syncBulananWebhookWarn" style="display:none; color:#ef4444; font-size:0.8rem; margin-top:8px; background:rgba(239,68,68,0.1); padding:8px 12px; border-radius:6px; border:1px solid rgba(239,68,68,0.3);">
                        ⚠️ <strong>Perhatian:</strong> Anda memasukkan link halaman spreadsheet (docs.google.com). Webhook memerlukan Web App URL (script.google.com/macros/s/.../exec). Silakan lihat panduan.
                    </div>
                    <small style="color:var(--text-muted); display:block; margin-top:6px; font-size:0.78rem;">
                        Belum punya atau butuh memperbarui Webhook? <a href="javascript:void(0)" onclick="tutupModalSyncBulanan(); bukaModalPetunjukBulanan();" style="color:var(--accent-blue); font-weight:600;">Buka panduan script Webhook &rarr;</a>
                    </small>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="tutupModalSyncBulanan()" id="btnSyncBulananCancel">Batal</button>
                <button type="submit" class="btn btn-blue" id="btnSyncBulananSubmit" style="display:inline-flex; align-items:center; gap:6px;">
                    <?= Icon::get('refresh', 16) ?> <span>Kirim Data Presensi</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: Petunjuk Script Google Apps Script Presensi & Gaji -->
<div id="modalAppsScriptBulanan" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="modalAppsScriptBulananTitle">
    <div class="modal-card modal-card-lg">
        <div class="modal-header">
            <div class="modal-header-left">
                <div class="modal-header-icon" style="background: rgba(245, 158, 11, 0.12); color: #d97706;">
                    💡
                </div>
                <div>
                    <h3 id="modalAppsScriptBulananTitle" class="modal-title">Panduan Webhook Google Apps Script (Presensi + Gaji)</h3>
                    <div class="modal-subtitle">1 Link Spreadsheet menangani sheet 'Rekap Gaji Karyawan' dan 'Rekap Absensi [Bulan Tahun]'</div>
                </div>
            </div>
            <button type="button" class="modal-close-btn" onclick="tutupModalPetunjukBulanan()" aria-label="Tutup">
                <?= Icon::get('x', 18) ?>
            </button>
        </div>

        <div class="modal-body" style="display:flex; flex-direction:column; gap:20px;">
            <div style="background:rgba(37,99,235,0.06); border:1px solid rgba(37,99,235,0.2); border-radius:var(--radius-md); padding:14px 16px;">
                <h4 style="font-size:0.92rem; font-weight:700; color:var(--primary); margin:0 0 6px 0;">⚡ 1 Dokumen Spreadsheet, 2 Lembar Otomatis Rapi</h4>
                <p style="font-size:0.83rem; color:var(--text-main); margin:0; line-height:1.5;">
                    Script ini otomatis membagi data menjadi dua sheet utama:
                    <br>&bull; Presensi Bulanan masuk ke sheet: <strong><?= htmlspecialchars($targetSheetName) ?></strong>
                    <br>&bull; Gaji Karyawan masuk ke sheet: <strong>Rekap Gaji Karyawan</strong>
                </p>
            </div>

            <div style="display:flex; flex-direction:column; gap:16px;">
                <div style="display:flex; gap:12px; align-items:flex-start;">
                    <div style="width:26px; height:26px; border-radius:50%; background:var(--primary); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.8rem; flex-shrink:0;">1</div>
                    <div style="font-size:0.88rem; color:var(--text-main); line-height:1.5;">
                        Buka dokumen <a href="<?= htmlspecialchars($spreadsheetUrl) ?>" target="_blank" style="color:var(--primary); font-weight:600; text-decoration:underline;">Google Spreadsheet Anda</a>.
                    </div>
                </div>

                <div style="display:flex; gap:12px; align-items:flex-start;">
                    <div style="width:26px; height:26px; border-radius:50%; background:var(--primary); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.8rem; flex-shrink:0;">2</div>
                    <div style="font-size:0.88rem; color:var(--text-main); line-height:1.5;">
                        Di menu atas spreadsheet, klik menu <strong>Ekstensi (Extensions)</strong> &gt; <strong>Apps Script</strong>.
                    </div>
                </div>

                <div style="display:flex; gap:12px; align-items:flex-start;">
                    <div style="width:26px; height:26px; border-radius:50%; background:var(--primary); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.8rem; flex-shrink:0;">3</div>
                    <div style="flex:1; min-width:0;">
                        <div style="font-size:0.88rem; color:var(--text-main); line-height:1.5; margin-bottom:8px;">
                            Ganti seluruh isi file <code>Kode.gs</code> dengan kode di bawah ini:
                        </div>
                        <div style="position:relative;">
                            <textarea id="appsScriptBulananCode" readonly style="width:100%; height:230px; font-family:'JetBrains Mono', Consolas, monospace; font-size:0.78rem; background:#0d1117; color:#7ee787; border:1px solid #30363d; border-radius:var(--radius-sm); padding:14px; line-height:1.45; resize:none;">function doPost(e) {
  try {
    var data = JSON.parse(e.postData.contents);
    var ss = SpreadsheetApp.getActiveSpreadsheet();
    var action = data.action || "sync_payroll";

    // 1. SINKRONISASI REKAP ABSENSI BULANAN
    if (action === "sync_presensi") {
      var sheetName = data.sheet_name || ("Rekap Absensi " + (data.periode || "Bulanan"));
      var sheet = ss.getSheetByName(sheetName);
      if (!sheet) {
        sheet = ss.insertSheet(sheetName);
      }
      sheet.clear();
      sheet.clearFormats();
      sheet.setTabColor("#1E40AF");

      var list = data.data || data.rows || [];
      var headers = [
        "No.", "NIK", "Nama Pegawai", "Jabatan", "Lokasi", 
        "Tanggal", "Jam Masuk", "Status Masuk", "Terlambat", 
        "Jam Pulang", "Total Jam Kerja", "Lembur (Jam)", "Keterangan"
      ];
      var totalCols = headers.length;

      sheet.getRange(1, 1, 1, totalCols).merge();
      sheet.getRange(1, 1).setValue("REKAP PRESENSI KARYAWAN - CV SURYA GEMILANG MOJOPAHIT")
           .setBackground("#1E3A8A").setFontColor("#FFFFFF").setFontWeight("bold").setFontSize(12)
           .setHorizontalAlignment("center").setVerticalAlignment("middle");
      sheet.setRowHeight(1, 36);

      sheet.getRange(2, 1, 1, totalCols).merge();
      var metaText = "Periode: " + (data.periode || "-") + "   |   Total Data: " + list.length + " Catatan   |   Disinkronkan: " + (data.timestamp || new Date().toLocaleString());
      sheet.getRange(2, 1).setValue(metaText)
           .setBackground("#F1F5F9").setFontColor("#334155").setFontSize(9)
           .setHorizontalAlignment("center").setVerticalAlignment("middle");
      sheet.setRowHeight(2, 24);
      sheet.setRowHeight(3, 10);

      var headerRange = sheet.getRange(4, 1, 1, totalCols);
      headerRange.setValues([headers])
                 .setBackground("#1E40AF").setFontColor("#FFFFFF").setFontWeight("bold").setFontSize(10)
                 .setHorizontalAlignment("center").setVerticalAlignment("middle");
      sheet.setRowHeight(4, 28);

      if (list.length > 0) {
        var rowsData = [];
        var rowBgs = [];
        for (var i = 0; i < list.length; i++) {
          var r = list[i];
          rowsData.push([
            i + 1,
            r.nik || "-",
            r.nama || "-",
            r.jabatan || "-",
            r.lokasi || "-",
            r.tanggal || "-",
            r.jam_masuk || "-",
            r.status_masuk || "-",
            r.terlambat || "-",
            r.jam_pulang || "-",
            r.total_jam_kerja || "-",
            parseFloat(r.jam_lembur) || 0,
            r.keterangan || "-"
          ]);
          var bg = (i % 2 === 0) ? "#FFFFFF" : "#F8FAFC";
          var rowBg = [];
          for (var c = 0; c < totalCols; c++) rowBg.push(bg);
          rowBgs.push(rowBg);
        }

        var dataRange = sheet.getRange(5, 1, list.length, totalCols);
        dataRange.setValues(rowsData);
        dataRange.setBackgrounds(rowBgs);
        dataRange.setFontSize(9).setVerticalAlignment("middle");
        dataRange.setBorder(true, true, true, true, true, true, "#E2E8F0", SpreadsheetApp.BorderStyle.SOLID);

        sheet.getRange(5, 1, list.length, 2).setHorizontalAlignment("center");
        sheet.getRange(5, 3, list.length, 3).setHorizontalAlignment("left");
        sheet.getRange(5, 6, list.length, 6).setHorizontalAlignment("center");
        sheet.getRange(5, 12, list.length, 1).setHorizontalAlignment("right").setNumberFormat("0.00 \"jam\"");
        sheet.getRange(5, 13, list.length, 1).setHorizontalAlignment("left");

        for (var r = 5; r < 5 + list.length; r++) {
          sheet.setRowHeight(r, 22);
        }
      }

      sheet.setFrozenRows(4);

      // Atur lebar kolom presisi (proporsional pas di layar tanpa membuat kolom No melebar)
      var widthsPresensi = [45, 90, 160, 150, 115, 90, 80, 90, 105, 80, 105, 85, 95];
      for (var k = 0; k < widthsPresensi.length; k++) {
        sheet.setColumnWidth(k + 1, widthsPresensi[k]);
      }

      return ContentService.createTextOutput(JSON.stringify({
        status: "success", 
        message: "Data presensi (" + list.length + " baris) berhasil disinkronkan ke sheet '" + sheetName + "'!"
      })).setMimeType(ContentService.MimeType.JSON);
    }

    // 2. SINKRONISASI REKAP GAJI KARYAWAN
    var sheetName = data.sheet_name || "Rekap Gaji Karyawan";
    var sheet = ss.getSheetByName(sheetName);
    if (!sheet) {
      sheet = ss.insertSheet(sheetName);
    }
    sheet.clear();
    sheet.clearFormats();
    sheet.setTabColor("#0F9D58");

    var list = data.data || data.rows || [];
    var headers = [
      "No.", "NIK", "Nama Pegawai", "Jabatan", "Lokasi", "Hari Kerja", 
      "Hadir", "Terlambat", "Sakit", "Izin", "Cuti", "Alpa", 
      "Jam Lembur", "Gaji Pokok", "Uang Makan", "Uang Lembur", 
      "Potongan Alpa", "Take Home Pay"
    ];
    var totalCols = headers.length;

    sheet.getRange(1, 1, 1, totalCols).merge();
    sheet.getRange(1, 1).setValue("REKAP PENGGAJIAN KARYAWAN - CV SURYA GEMILANG MOJOPAHIT")
         .setBackground("#065F46").setFontColor("#FFFFFF").setFontWeight("bold").setFontSize(12)
         .setHorizontalAlignment("center").setVerticalAlignment("middle");
    sheet.setRowHeight(1, 36);

    sheet.getRange(2, 1, 1, totalCols).merge();
    var metaText = "Periode: " + (data.periode || "-") + "   |   Total Pegawai: " + list.length + " Orang   |   Disinkronkan: " + (data.timestamp || new Date().toLocaleString());
    sheet.getRange(2, 1).setValue(metaText)
         .setBackground("#F0FDF4").setFontColor("#166534").setFontSize(9)
         .setHorizontalAlignment("center").setVerticalAlignment("middle");
    sheet.setRowHeight(2, 24);
    sheet.setRowHeight(3, 10);

    var headerRange = sheet.getRange(4, 1, 1, totalCols);
    headerRange.setValues([headers])
               .setBackground("#0F9D58").setFontColor("#FFFFFF").setFontWeight("bold").setFontSize(10)
               .setHorizontalAlignment("center").setVerticalAlignment("middle");
    sheet.setRowHeight(4, 28);

    if (list.length > 0) {
      var rowsData = [];
      var rowBgs = [];
      var totHadir = 0;
      var totLembur = 0;
      var totGajiPokok = 0;
      var totUangMakan = 0;
      var totUangLembur = 0;
      var totPotonganAlpa = 0;
      var totTakeHomePay = 0;

      for (var i = 0; i < list.length; i++) {
        var r = list[i];
        var gPokok = parseFloat(r.gaji_pokok) || 0;
        var uMakan = parseFloat(r.uang_makan) || 0;
        var uLembur = parseFloat(r.uang_lembur) || 0;
        var pAlpa = parseFloat(r.potongan_alpa) || 0;
        var thp = parseFloat(r.take_home_pay || r.gaji_bersih) || 0;
        var jLembur = parseFloat(r.jam_lembur) || 0;
        var hadir = parseInt(r.hadir) || 0;

        totHadir += hadir;
        totLembur += jLembur;
        totGajiPokok += gPokok;
        totUangMakan += uMakan;
        totUangLembur += uLembur;
        totPotonganAlpa += pAlpa;
        totTakeHomePay += thp;

        rowsData.push([
          i + 1,
          r.nik || "-",
          r.nama || "-",
          r.jabatan || "-",
          r.lokasi || "-",
          parseInt(r.hari_kerja_sebulan || r.hari_kerja) || 0,
          hadir,
          parseInt(r.terlambat) || 0,
          parseInt(r.sakit) || 0,
          parseInt(r.izin) || 0,
          parseInt(r.cuti) || 0,
          parseInt(r.alpa) || 0,
          jLembur,
          gPokok,
          uMakan,
          uLembur,
          pAlpa,
          thp
        ]);

        var bg = (i % 2 === 0) ? "#FFFFFF" : "#F8FAFC";
        var rowBg = [];
        for (var c = 0; c < totalCols; c++) rowBg.push(bg);
        rowBgs.push(rowBg);
      }

      var dataRange = sheet.getRange(5, 1, list.length, totalCols);
      dataRange.setValues(rowsData);
      dataRange.setBackgrounds(rowBgs);
      dataRange.setFontSize(9).setVerticalAlignment("middle");
      dataRange.setBorder(true, true, true, true, true, true, "#E2E8F0", SpreadsheetApp.BorderStyle.SOLID);

      sheet.getRange(5, 1, list.length, 2).setHorizontalAlignment("center");
      sheet.getRange(5, 3, list.length, 3).setHorizontalAlignment("left");
      sheet.getRange(5, 6, list.length, 7).setHorizontalAlignment("center");
      sheet.getRange(5, 13, list.length, 1).setHorizontalAlignment("right").setNumberFormat("0.00 \"jam\"");
      sheet.getRange(5, 14, list.length, 5).setHorizontalAlignment("right").setNumberFormat("\"Rp \"#,##0");

      for (var r = 5; r < 5 + list.length; r++) {
        sheet.setRowHeight(r, 22);
      }

      var totalRowIdx = 5 + list.length;
      sheet.setRowHeight(totalRowIdx, 26);
      sheet.getRange(totalRowIdx, 1, 1, 6).merge();
      sheet.getRange(totalRowIdx, 1).setValue("TOTAL KESELURUHAN:").setHorizontalAlignment("right").setFontWeight("bold");
      sheet.getRange(totalRowIdx, 7).setValue(totHadir).setHorizontalAlignment("center").setFontWeight("bold");
      sheet.getRange(totalRowIdx, 8, 1, 5).setValue("-").setHorizontalAlignment("center");
      sheet.getRange(totalRowIdx, 13).setValue(totLembur).setHorizontalAlignment("right").setFontWeight("bold").setNumberFormat("0.00 \"jam\"");
      sheet.getRange(totalRowIdx, 14).setValue(totGajiPokok).setHorizontalAlignment("right").setFontWeight("bold").setNumberFormat("\"Rp \"#,##0");
      sheet.getRange(totalRowIdx, 15).setValue(totUangMakan).setHorizontalAlignment("right").setFontWeight("bold").setNumberFormat("\"Rp \"#,##0");
      sheet.getRange(totalRowIdx, 16).setValue(totUangLembur).setHorizontalAlignment("right").setFontWeight("bold").setNumberFormat("\"Rp \"#,##0");
      sheet.getRange(totalRowIdx, 17).setValue(totPotonganAlpa).setHorizontalAlignment("right").setFontWeight("bold").setNumberFormat("\"Rp \"#,##0");
      sheet.getRange(totalRowIdx, 18).setValue(totTakeHomePay).setHorizontalAlignment("right").setFontWeight("bold").setNumberFormat("\"Rp \"#,##0");

      var totalRange = sheet.getRange(totalRowIdx, 1, 1, totalCols);
      totalRange.setBackground("#DCFCE7").setFontSize(9).setFontWeight("bold").setVerticalAlignment("middle");
      totalRange.setBorder(true, true, true, true, null, null, "#166534", SpreadsheetApp.BorderStyle.DOUBLE);
    }

    sheet.setFrozenRows(4);

    // Atur lebar kolom presisi (proporsional pas di layar tanpa membuat kolom No melebar)
    var widthsPayroll = [45, 90, 165, 140, 115, 75, 65, 75, 60, 60, 60, 60, 85, 110, 100, 100, 100, 120];
    for (var k = 0; k < widthsPayroll.length; k++) {
      sheet.setColumnWidth(k + 1, widthsPayroll[k]);
    }

    return ContentService.createTextOutput(JSON.stringify({
      status: "success", 
      message: "Data gaji (" + list.length + " pegawai) berhasil disinkronkan ke sheet '" + sheetName + "'!"
    })).setMimeType(ContentService.MimeType.JSON);

  } catch (err) {
    return ContentService.createTextOutput(JSON.stringify({
      status: "error", 
      message: err.toString()
    })).setMimeType(ContentService.MimeType.JSON);
  }
}

function doGet(e) {
  return ContentService.createTextOutput(JSON.stringify({
    status: "ok", 
    message: "Webhook Web App Absensi SGM aktif siap menerima data presensi dan penggajian!"
  })).setMimeType(ContentService.MimeType.JSON);
}</textarea>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="salinKodeScriptBulanan()" style="position:absolute; top:10px; right:10px; display:inline-flex; align-items:center; gap:6px; font-size:0.75rem; background:rgba(30,41,59,0.92); border:1px solid rgba(255,255,255,0.18); color:#f1f5f9; backdrop-filter:blur(4px); box-shadow:0 4px 10px rgba(0,0,0,0.3);">
                                <?= Icon::get('clipboard', 13) ?> <span id="salinBulananBtnText">Salin Kode</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div style="display:flex; gap:12px; align-items:flex-start;">
                    <div style="width:26px; height:26px; border-radius:50%; background:var(--primary); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.8rem; flex-shrink:0;">4</div>
                    <div style="font-size:0.88rem; color:var(--text-main); line-height:1.5;">
                        Simpan file (Ctrl+S). Jika sudah pernah deploy, klik <strong>Deploy (Terapkan)</strong> &gt; <strong>Manage deployments (Kelola penerapan)</strong> &gt; Edit (ikon pensil) &gt; Pilih <strong>New version (Versi baru)</strong> &gt; Klik <strong>Deploy</strong>.
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-primary" onclick="tutupModalPetunjukBulanan()">Saya Mengerti</button>
        </div>
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
function bukaModalSyncBulanan() {
    const modal = document.getElementById('modalSyncBulanan');
    if (modal) {
        modal.classList.add('show');
    }
}
function tutupModalSyncBulanan() {
    const modal = document.getElementById('modalSyncBulanan');
    if (modal) {
        modal.classList.remove('show');
    }
}

function bukaModalPetunjukBulanan() {
    const modal = document.getElementById('modalAppsScriptBulanan');
    if (modal) {
        modal.classList.add('show');
    }
}
function tutupModalPetunjukBulanan() {
    const modal = document.getElementById('modalAppsScriptBulanan');
    if (modal) {
        modal.classList.remove('show');
    }
}

function periksaWebhookBulanan(input, warnId) {
    const warnEl = document.getElementById(warnId);
    if (!warnEl) return;
    const val = (input.value || '').trim();
    if (val.includes('docs.google.com/spreadsheets')) {
        warnEl.style.display = 'block';
    } else {
        warnEl.style.display = 'none';
    }
}

function salinKodeScriptBulanan() {
    const textarea = document.getElementById('appsScriptBulananCode');
    if (!textarea) return;
    textarea.select();
    navigator.clipboard.writeText(textarea.value).then(function() {
        const btnText = document.getElementById('salinBulananBtnText');
        if (btnText) {
            btnText.innerText = 'Tersalin!';
            setTimeout(function() {
                btnText.innerText = 'Salin Kode';
            }, 2500);
        }
    }).catch(function() {
        document.execCommand('copy');
        alert('Kode Apps Script berhasil disalin ke clipboard!');
    });
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
                alert('Gagal mengambil data presensi');
                tutupModalEditPresensi();
            }
        })
        .catch(err => {
            console.error(err);
            alert('Terjadi kesalahan koneksi');
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
    const mSync = document.getElementById('modalSyncBulanan');
    const mScript = document.getElementById('modalAppsScriptBulanan');
    const mEdit = document.getElementById('modalEditPresensi');
    if (e.target === mSync) tutupModalSyncBulanan();
    if (e.target === mScript) tutupModalPetunjukBulanan();
    if (e.target === mEdit) tutupModalEditPresensi();
});

const formSyncBulanan = document.getElementById('formSyncBulanan');
if (formSyncBulanan) {
    formSyncBulanan.addEventListener('submit', function(e) {
        e.preventDefault();
        const btnSubmit = document.getElementById('btnSyncBulananSubmit');
        const btnCancel = document.getElementById('btnSyncBulananCancel');
        const alertBox = document.getElementById('syncBulananAlert');

        if (btnSubmit.disabled) return;

        btnSubmit.disabled = true;
        if (btnCancel) btnCancel.disabled = true;
        const originalContent = btnSubmit.innerHTML;
        btnSubmit.innerHTML = '<span class="spinner"></span> Menyinkronkan...';

        if (alertBox) {
            alertBox.style.display = 'block';
            alertBox.style.background = 'rgba(59, 130, 246, 0.12)';
            alertBox.style.color = '#3b82f6';
            alertBox.style.border = '1px solid rgba(59, 130, 246, 0.3)';
            alertBox.innerHTML = '⏳ Sedang mengirim data ke Google Spreadsheet, mohon tunggu beberapa saat...';
        }

        const formData = new FormData(formSyncBulanan);
        fetch(formSyncBulanan.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (alertBox) {
                    alertBox.style.background = 'rgba(16, 185, 129, 0.12)';
                    alertBox.style.color = '#10b981';
                    alertBox.style.border = '1px solid rgba(16, 185, 129, 0.3)';
                    alertBox.innerHTML = '✅ ' + (data.message || 'Sinkronisasi berhasil!');
                }
                setTimeout(() => {
                    const bulan = formData.get('bulan');
                    const tahun = formData.get('tahun');
                    window.location.href = '<?= Config::baseUrl("laporan/bulanan") ?>?bulan=' + encodeURIComponent(bulan) + '&tahun=' + encodeURIComponent(tahun) + '&msg=synced';
                }, 800);
            } else {
                btnSubmit.disabled = false;
                if (btnCancel) btnCancel.disabled = false;
                btnSubmit.innerHTML = originalContent;
                if (alertBox) {
                    alertBox.style.background = 'rgba(239, 68, 68, 0.12)';
                    alertBox.style.color = '#ef4444';
                    alertBox.style.border = '1px solid rgba(239, 68, 68, 0.3)';
                    alertBox.innerHTML = '❌ ' + (data.message || 'Gagal menyinkronkan data.');
                }
            }
        })
        .catch(err => {
            btnSubmit.disabled = false;
            if (btnCancel) btnCancel.disabled = false;
            btnSubmit.innerHTML = originalContent;
            if (alertBox) {
                alertBox.style.background = 'rgba(239, 68, 68, 0.12)';
                alertBox.style.color = '#ef4444';
                alertBox.style.border = '1px solid rgba(239, 68, 68, 0.3)';
                alertBox.innerHTML = '❌ Terjadi kendala koneksi atau server: ' + err.message;
            }
        });
    });
}
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
