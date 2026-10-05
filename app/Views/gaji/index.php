<?php
$pageTitle = 'Rekap Penggajian & Integrasi Spreadsheet - ' . Config::APP_NAME;
$activePage = 'gaji';
require_once __DIR__ . '/../layouts/header.php';

// Format mata uang rupiah
function formatRupiah(float $angka): string {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Menghitung ringkasan KPI
$totalPengeluaranGaji = 0;
$totalJamLemburSemua = 0;
$totalUangLemburSemua = 0;
$totalPotonganAlpaSemua = 0;
$totalHadirSemua = 0;

foreach ($rekapPayroll as $p) {
    $totalPengeluaranGaji += $p['take_home_pay'];
    $totalJamLemburSemua += $p['jam_lembur'];
    $totalUangLemburSemua += $p['uang_lembur'];
    $totalPotonganAlpaSemua += $p['potongan_alpa'];
    $totalHadirSemua += $p['hadir'];
}

$periodeText = ($namaBulan[$bulan] ?? $bulan) . ' ' . $tahun;
?>

<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
    <div>
        <h2 style="font-size: 1.4rem; font-weight: 700; margin: 0 0 4px 0;">Penggajian & Integrasi Spreadsheet</h2>
        <p style="color: var(--text-muted); margin: 0; font-size: 0.9rem;">
            Perhitungan gaji berbasis kehadiran, absensi (alpa/izin/sakit/cuti), dan lembur per jam.
        </p>
    </div>

    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <!-- Buka Spreadsheet -->
        <a href="<?= htmlspecialchars($spreadsheetUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn" style="background: #0F9D58; color: #fff; display: inline-flex; align-items: center; gap: 6px;">
            <?= Icon::get('external-link', 16) ?>
            <span>Buka Spreadsheet</span>
        </a>

        <!-- Sinkronkan ke Spreadsheet -->
        <button type="button" class="btn btn-blue" onclick="bukaModalSync()" style="display: inline-flex; align-items: center; gap: 6px;">
            <?= Icon::get('refresh', 16) ?>
            <span>Sinkronkan ke Spreadsheet</span>
        </button>

        <!-- Download CSV Rekap -->
        <a href="<?= Config::baseUrl("gaji/export-csv?bulan={$bulan}&tahun={$tahun}") ?>" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 6px;">
            <?= Icon::get('download', 16) ?>
            <span>Export CSV</span>
        </a>

        <!-- Cetak Seluruh Slip Gaji 1 Bulan -->
        <a href="<?= Config::baseUrl("gaji/slip-bulk?bulan={$bulan}&tahun={$tahun}") ?>" target="_blank" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 6px;">
            <?= Icon::get('printer', 16) ?>
            <span>Cetak Seluruh Slip Gaji</span>
        </a>

        <!-- Pengaturan Tarif & Integrasi -->
        <button type="button" class="btn btn-secondary" onclick="bukaModalPengaturan()" title="Pengaturan Gaji & Link Spreadsheet" style="display: inline-flex; align-items: center; gap: 6px;">
            <?= Icon::get('settings', 16) ?>
            <span>Pengaturan</span>
        </button>
    </div>
</div>

<!-- Alert Notifikasi -->
<?php if (isset($_GET['msg']) && $_GET['msg'] === 'synced'): ?>
    <div class="alert alert-success" style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 8px;">
            <?= Icon::get('check-circle', 18) ?>
            <span><strong>Berhasil!</strong> Data rekap payroll periode <?= $periodeText ?> berhasil disinkronkan ke Google Spreadsheet.</span>
        </div>
        <a href="<?= htmlspecialchars($spreadsheetUrl) ?>" target="_blank" style="color: inherit; text-decoration: underline; font-weight: 600;">Buka Hasil →</a>
    </div>
<?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'settings_saved'): ?>
    <div class="alert alert-success" style="margin-bottom: 20px;">
        <?= Icon::get('check-circle', 18) ?> Pengaturan link spreadsheet, webhook, dan tarif gaji berhasil diperbarui.
    </div>
<?php elseif (isset($_GET['err']) && $_GET['err'] === 'sync_failed'): ?>
    <div class="alert alert-danger" style="margin-bottom: 20px;">
        <?= Icon::get('alert-circle', 18) ?>
        <strong>Gagal Sinkronisasi:</strong> <?= htmlspecialchars($_SESSION['sync_error'] ?? 'Periksa URL Webhook Google Apps Script Anda.') ?>
        <br><small style="margin-top: 4px; display: block;">Pastikan Anda telah memasang Webhook Google Apps Script dengan izin akses 'Anyone'. Klik tombol <strong>Petunjuk Script</strong> untuk panduan.</small>
    </div>
    <?php unset($_SESSION['sync_error']); ?>
<?php elseif (isset($_GET['err']) && $_GET['err'] === 'setting_invalid'): ?>
    <div class="alert alert-danger" style="margin-bottom: 20px;">
        <?= Icon::get('alert-circle', 18) ?>
        <strong>Pengaturan Tidak Tersimpan:</strong>
        <ul style="margin: 6px 0 0 18px; font-size: 0.88rem;">
            <?php 
            $setErrors = $_SESSION['setting_errors'] ?? ['Format pengaturan yang dimasukkan tidak valid.'];
            foreach ($setErrors as $errItem): ?>
                <li><?= htmlspecialchars($errItem) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php unset($_SESSION['setting_errors']); ?>
<?php endif; ?>

<!-- Filter Periode Bulan & Tahun -->
<div class="form-card" style="padding: 16px 20px; margin-bottom: 24px;">
    <form method="GET" action="<?= Config::baseUrl('gaji') ?>" style="display: flex; gap: 14px; align-items: center; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 8px;">
            <label for="pilihBulan" style="font-weight: 600; font-size: 0.9rem;"><?= Icon::get('calendar', 16) ?> Bulan:</label>
            <select name="bulan" id="pilihBulan" class="form-input" style="min-width: 140px;">
                <?php foreach ($namaBulan as $mNum => $mName): ?>
                    <option value="<?= $mNum ?>" <?= $bulan === $mNum ? 'selected' : '' ?>><?= $mName ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="display: flex; align-items: center; gap: 8px;">
            <label for="pilihTahun" style="font-weight: 600; font-size: 0.9rem;">Tahun:</label>
            <select name="tahun" id="pilihTahun" class="form-input" style="min-width: 110px;">
                <?php 
                $curYear = (int)date('Y');
                for ($y = $curYear - 2; $y <= $curYear + 1; $y++): ?>
                    <option value="<?= $y ?>" <?= $tahun === $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-blue">Tampilkan Rekap</button>
        <a href="<?= Config::baseUrl('gaji') ?>" class="btn btn-secondary">Bulan Ini</a>

        <div style="margin-left: auto; display: flex; align-items: center; gap: 8px;">
            <button type="button" class="btn btn-sm btn-outline-info" onclick="bukaModalPetunjukScript()" style="border: 1px dashed var(--accent-color); color: var(--accent-color);">
                💡 Panduan Webhook Spreadsheet
            </button>
        </div>
    </form>
</div>

<!-- KPI Cards -->
<div class="kpi-grid" style="margin-bottom: 24px;">
    <!-- Card 1: Total Take Home Pay -->
    <div class="kpi-card">
        <div class="kpi-icon" style="background: rgba(16, 185, 129, 0.1); color: #10B981;">
            <?= Icon::get('wallet', 24) ?>
        </div>
        <div class="kpi-info">
            <h4>Total Payroll Periode Ini</h4>
            <div class="kpi-value" style="font-size: 1.25rem; color: #10B981;"><?= formatRupiah($totalPengeluaranGaji) ?></div>
        </div>
    </div>

    <!-- Card 2: Total Lembur -->
    <div class="kpi-card">
        <div class="kpi-icon" style="background: rgba(245, 158, 11, 0.1); color: #F59E0B;">
            <?= Icon::get('clock', 24) ?>
        </div>
        <div class="kpi-info">
            <h4>Total Jam Lembur</h4>
            <div class="kpi-value"><?= number_format($totalJamLemburSemua, 2) ?> <span class="kpi-label">jam (<?= formatRupiah($totalUangLemburSemua) ?>)</span></div>
        </div>
    </div>

    <!-- Card 3: Total Kehadiran -->
    <div class="kpi-card">
        <div class="kpi-icon" style="background: rgba(59, 130, 246, 0.1); color: #3B82F6;">
            <?= Icon::get('user-check', 24) ?>
        </div>
        <div class="kpi-info">
            <h4>Total Hari Masuk</h4>
            <div class="kpi-value"><?= $totalHadirSemua ?> <span class="kpi-label">kehadiran</span></div>
        </div>
    </div>

    <!-- Card 4: Total Potongan Alpa -->
    <div class="kpi-card">
        <div class="kpi-icon" style="background: rgba(239, 68, 68, 0.1); color: #EF4444;">
            <?= Icon::get('clipboard-x', 24) ?>
        </div>
        <div class="kpi-info">
            <h4>Total Potongan Alpa</h4>
            <div class="kpi-value" style="color: #EF4444;"><?= formatRupiah($totalPotonganAlpaSemua) ?></div>
        </div>
    </div>
</div>

<!-- Tabel Rekap Penggajian -->
<div class="table-card">
    <div class="table-toolbar">
        <div class="table-toolbar-left">
            <span style="font-weight: 700; font-size: 1rem; color: var(--text-main);">
                Daftar Gaji Karyawan - Periode <?= $periodeText ?>
            </span>
            <span class="badge badge-info" style="font-size: 0.8rem;">
                <?= count($rekapPayroll) ?> Karyawan Terdaftar
            </span>
        </div>
        <div style="font-size: 0.8rem; color: var(--text-muted);">
            Formula: Gaji Pokok + Uang Makan (Hadir × Tarif) + Lembur (Jam × Tarif) − Potongan Alpa (Alpa × Tarif)
        </div>
    </div>

    <div class="table-responsive">
        <table class="table-custom" id="payrollTable">
            <thead>
                <tr>
                    <th style="width: 35px; text-align: center;">No.</th>
                    <th>Pegawai</th>
                    <th style="text-align: center;" title="Hari Kerja Efektif">HK</th>
                    <th style="text-align: center;" title="Hadir Tepat Waktu / Terlambat">Hadir</th>
                    <th style="text-align: center;" title="Terlambat">Telat</th>
                    <th style="text-align: center;" title="Sakit, Izin & Cuti">S/I/C</th>
                    <th style="text-align: center;" title="Alpa / Tanpa Keterangan">Alpa</th>
                    <th style="text-align: right;">Lembur (Jam)</th>
                    <th style="text-align: right;">Gaji Pokok</th>
                    <th style="text-align: right;">Uang Makan</th>
                    <th style="text-align: right;">Uang Lembur</th>
                    <th style="text-align: right; color: #EF4444;">Pot. Alpa</th>
                    <th style="text-align: right; font-weight: 700; background: rgba(16, 185, 129, 0.05);">Take Home Pay</th>
                    <th style="width: 100px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rekapPayroll)): ?>
                    <tr>
                        <td colspan="14" style="text-align: center; color: var(--text-muted); padding: 36px;">
                            Belum ada data pegawai atau absensi pada periode ini.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($rekapPayroll as $row): ?>
                        <tr>
                            <td style="text-align: center;"><?= $no++ ?></td>
                            <td>
                                <strong><?= htmlspecialchars($row['nama']) ?></strong><br>
                                <small style="color: var(--text-muted);"><?= htmlspecialchars($row['nik']) ?> &bull; <?= htmlspecialchars($row['jabatan']) ?></small>
                            </td>
                            <td style="text-align: center; font-weight: 600;"><?= $row['hari_kerja_sebulan'] ?? $row['hari_kerja_bulan'] ?? 0 ?></td>
                            <td style="text-align: center;">
                                <span class="badge badge-success"><?= $row['hadir'] ?? 0 ?></span>
                            </td>
                            <td style="text-align: center;">
                                <?php if (($row['terlambat'] ?? 0) > 0): ?>
                                    <span class="badge badge-warning"><?= $row['terlambat'] ?></span>
                                <?php else: ?>
                                    <span style="color: var(--text-subtle);">-</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <span title="Sakit: <?= $row['sakit'] ?? 0 ?>, Izin: <?= $row['izin'] ?? 0 ?>, Cuti: <?= $row['cuti'] ?? 0 ?>">
                                    <?= (($row['sakit'] ?? 0) + ($row['izin'] ?? 0) + ($row['cuti'] ?? 0)) ?>
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <?php if (($row['alpa'] ?? 0) > 0): ?>
                                    <span class="badge badge-danger"><?= $row['alpa'] ?></span>
                                <?php else: ?>
                                    <span style="color: var(--text-subtle);">0</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right; font-family: 'JetBrains Mono', monospace;">
                                <?php if (($row['jam_lembur'] ?? 0) > 0): ?>
                                    <strong><?= number_format($row['jam_lembur'], 2) ?> jam</strong>
                                <?php else: ?>
                                    <span style="color: var(--text-subtle);">0 jam</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right; font-family: 'JetBrains Mono', monospace;">
                                <?= formatRupiah($row['gaji_pokok'] ?? 0) ?>
                            </td>
                            <td style="text-align: right; font-family: 'JetBrains Mono', monospace;">
                                <?= formatRupiah($row['uang_makan'] ?? 0) ?>
                            </td>
                            <td style="text-align: right; font-family: 'JetBrains Mono', monospace; color: #10B981;">
                                +<?= formatRupiah($row['uang_lembur'] ?? 0) ?>
                            </td>
                            <td style="text-align: right; font-family: 'JetBrains Mono', monospace; color: #EF4444;">
                                <?= ($row['potongan_alpa'] ?? 0) > 0 ? '-' . formatRupiah($row['potongan_alpa']) : 'Rp 0' ?>
                            </td>
                            <td style="text-align: right; font-family: 'JetBrains Mono', monospace; font-weight: 700; color: #10B981; background: rgba(16, 185, 129, 0.05); font-size: 0.95rem;">
                                <?= formatRupiah($row['take_home_pay'] ?? 0) ?>
                            </td>
                            <td style="text-align: center;">
                                <a href="<?= Config::baseUrl("gaji/slip?id=" . ($row['id'] ?? $row['user_id'] ?? 0) . "&bulan={$bulan}&tahun={$tahun}") ?>" target="_blank" class="btn btn-secondary btn-sm" title="Pratinjau / Cetak Slip">
                                    <?= Icon::get('printer', 14) ?> Slip
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <?php if (!empty($rekapPayroll)): ?>
                <tfoot>
                    <tr style="background: var(--bg-hover); font-weight: 700;">
                        <td colspan="3" style="text-align: right;">TOTAL:</td>
                        <td style="text-align: center;"><?= $totalHadirSemua ?></td>
                        <td style="text-align: center;">-</td>
                        <td style="text-align: center;">-</td>
                        <td style="text-align: center;">-</td>
                        <td style="text-align: right; font-family: 'JetBrains Mono', monospace;"><?= number_format($totalJamLemburSemua, 2) ?> jam</td>
                        <td colspan="2" style="text-align: right;">-</td>
                        <td style="text-align: right; font-family: 'JetBrains Mono', monospace; color: #10B981;"><?= formatRupiah($totalUangLemburSemua) ?></td>
                        <td style="text-align: right; font-family: 'JetBrains Mono', monospace; color: #EF4444;">-<?= formatRupiah($totalPotonganAlpaSemua) ?></td>
                        <td style="text-align: right; font-family: 'JetBrains Mono', monospace; color: #10B981; font-size: 1rem;"><?= formatRupiah($totalPengeluaranGaji) ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<!-- MODAL: Sinkronisasi ke Google Spreadsheet (Opsi A) -->
<!-- MODAL: Sinkronisasi ke Google Spreadsheet (Opsi A) -->
<div id="modalSync" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="modalSyncTitle">
    <div class="modal-card modal-card-md">
        <div class="modal-header">
            <div class="modal-header-left">
                <div class="modal-header-icon" style="background: rgba(37, 99, 235, 0.12); color: #2563eb;">
                    <?= Icon::get('refresh', 20) ?>
                </div>
                <div>
                    <h3 id="modalSyncTitle" class="modal-title">Sinkronkan ke Google Spreadsheet</h3>
                    <div class="modal-subtitle">Kirim data penggajian langsung ke spreadsheet via Webhook</div>
                </div>
            </div>
            <button type="button" class="modal-close-btn" onclick="tutupModalSync()" aria-label="Tutup">
                <?= Icon::get('x', 18) ?>
            </button>
        </div>

        <form id="formSyncGaji" method="POST" action="<?= Config::baseUrl('gaji/sync-spreadsheet') ?>" style="display:flex; flex-direction:column; flex:1; overflow:hidden;">
            <input type="hidden" name="bulan" value="<?= $bulan ?>">
            <input type="hidden" name="tahun" value="<?= $tahun ?>">

            <div class="modal-body">
                <div id="syncGajiAlert" style="display:none; margin-bottom:14px; padding:10px 14px; border-radius:6px; font-size:0.85rem;"></div>

                <div style="background:var(--bg-subtle); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:14px 16px; margin-bottom:18px;">
                    <div style="font-weight:600; font-size:0.88rem; color:var(--text-main); margin-bottom:4px;">
                        Periode: <?= $periodeText ?> (<?= count($rekapPayroll) ?> Karyawan)
                    </div>
                    <p style="font-size:0.82rem; color:var(--text-muted); margin:0;">
                        Sistem akan mengisi sheet bernama <code>Rekap Gaji Karyawan</code> di Google Spreadsheet Anda dan mengisinya dengan rekap absensi serta rincian nominal gaji.
                    </p>
                </div>

                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label" style="font-weight:600;">URL Webhook Google Apps Script:</label>
                    <input type="url" name="webhook_url" id="syncWebhookUrl" class="form-input" value="<?= htmlspecialchars($spreadsheetWebhookUrl) ?>" placeholder="https://script.google.com/macros/s/.../exec" oninput="periksaWebhookInput(this, 'syncWebhookWarn')" required>
                    <div id="syncWebhookWarn" style="display:none; color:#ef4444; font-size:0.8rem; margin-top:8px; background:rgba(239,68,68,0.1); padding:8px 12px; border-radius:6px; border:1px solid rgba(239,68,68,0.3);">
                        ⚠️ <strong>Perhatian:</strong> Anda memasukkan link halaman spreadsheet (docs.google.com). Webhook memerlukan Web App URL (script.google.com/macros/s/.../exec). Silakan lihat panduan.
                    </div>
                    <small style="color:var(--text-muted); display:block; margin-top:6px; font-size:0.78rem;">
                        Belum punya Webhook URL? <a href="javascript:void(0)" onclick="tutupModalSync(); bukaModalPetunjukScript();" style="color:var(--accent-blue); font-weight:600;">Buka panduan 1 menit pembuatan Webhook &rarr;</a>
                    </small>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="tutupModalSync()" id="btnSyncCancel">Batal</button>
                <button type="submit" class="btn btn-blue" id="btnSyncSubmit" style="display:inline-flex; align-items:center; gap:6px;">
                    <?= Icon::get('refresh', 16) ?> <span>Kirim Data Sekarang</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: Pengaturan Tarif & Integrasi -->
<div id="modalPengaturan" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="modalPengaturanTitle">
    <div class="modal-card modal-card-lg">
        <div class="modal-header">
            <div class="modal-header-left">
                <div class="modal-header-icon" style="background:var(--primary-light); color:var(--primary);">
                    <?= Icon::get('settings', 20) ?>
                </div>
                <div>
                    <h3 id="modalPengaturanTitle" class="modal-title">Pengaturan Link Spreadsheet & Tarif Gaji</h3>
                    <div class="modal-subtitle">Konfigurasi integrasi dokumen dan tarif komponen penggajian per jabatan</div>
                </div>
            </div>
            <button type="button" class="modal-close-btn" onclick="tutupModalPengaturan()" aria-label="Tutup">
                <?= Icon::get('x', 18) ?>
            </button>
        </div>

        <form method="POST" action="<?= Config::baseUrl('gaji/simpan-pengaturan') ?>" style="display:flex; flex-direction:column; flex:1; overflow:hidden;">
            <div class="modal-body">
                <!-- Link Google Spreadsheet -->
                <div class="form-group" style="margin-bottom:16px;">
                    <label class="form-label" style="font-weight:600;">Link Dokumen Google Spreadsheet Client:</label>
                    <input type="url" name="spreadsheet_url" class="form-input" value="<?= htmlspecialchars($spreadsheetUrl) ?>" required>
                    <small style="color:var(--text-muted); display:block; margin-top:4px; font-size:0.78rem;">Tautan dokumen Google Sheets yang dibuka saat tombol "Buka Spreadsheet" diklik.</small>
                </div>

                <!-- Webhook Google Apps Script -->
                <div class="form-group" style="margin-bottom:24px;">
                    <label class="form-label" style="font-weight:600;">Webhook Web App Google Apps Script (Opsional untuk Sinkronisasi Otomatis):</label>
                    <input type="url" name="spreadsheet_webhook_url" id="settingWebhookUrl" class="form-input" value="<?= htmlspecialchars($spreadsheetWebhookUrl) ?>" placeholder="https://script.google.com/macros/s/.../exec" oninput="periksaWebhookInput(this, 'settingWebhookWarn')">
                    <div id="settingWebhookWarn" style="display:none; color:#ef4444; font-size:0.8rem; margin-top:8px; background:rgba(239,68,68,0.1); padding:8px 12px; border-radius:6px; border:1px solid rgba(239,68,68,0.3);">
                        ⚠️ <strong>Perhatian:</strong> Kolom ini khusus URL Web App (script.google.com/macros/s/.../exec), bukan link spreadsheet biasa.
                    </div>
                </div>

                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-wrap:wrap; gap:8px;">
                    <div>
                        <h4 style="font-size:0.95rem; font-weight:700; color:var(--text-main); margin:0;">
                            Tarif Gaji, Lembur, & Potongan per Jabatan
                        </h4>
                        <div style="font-size:0.78rem; color:var(--text-muted); margin-top:2px;">Diperbarui secara real-time untuk perhitungan payroll otomatis</div>
                    </div>
                </div>

                <div class="table-responsive" style="border:1px solid var(--border-color); border-radius:var(--radius-md); overflow-x:auto; background:var(--bg-surface);">
                    <table class="table-custom" style="width:100%; min-width:680px; font-size:0.85rem; margin-bottom:0;">
                        <thead style="background:var(--bg-subtle);">
                            <tr>
                                <th style="padding:10px 14px; white-space:nowrap;">Jabatan</th>
                                <th style="padding:10px 14px; white-space:nowrap; text-align:right;">Gaji Pokok (Rp)</th>
                                <th style="padding:10px 14px; white-space:nowrap; text-align:right;">Uang Makan/Hari (Rp)</th>
                                <th style="padding:10px 14px; white-space:nowrap; text-align:right;">Lembur/Jam (Rp)</th>
                                <th style="padding:10px 14px; white-space:nowrap; text-align:right;">Potongan Alpa/Hari (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pengaturanGajiList as $p): ?>
                                <tr>
                                    <td style="font-weight:600; padding:8px 14px; background:var(--bg-surface); white-space:nowrap;"><?= htmlspecialchars($p['jabatan']) ?></td>
                                    <td style="padding:8px 14px; background:var(--bg-surface); text-align:right;">
                                        <input type="number" step="1000" name="tarif[<?= htmlspecialchars($p['jabatan']) ?>][gaji_pokok]" class="form-input" style="width:100%; min-width:115px; font-family:'JetBrains Mono', monospace; text-align:right; padding:6px 10px;" value="<?= (float)$p['gaji_pokok'] ?>" required>
                                    </td>
                                    <td style="padding:8px 14px; background:var(--bg-surface); text-align:right;">
                                        <input type="number" step="1000" name="tarif[<?= htmlspecialchars($p['jabatan']) ?>][uang_makan_harian]" class="form-input" style="width:100%; min-width:100px; font-family:'JetBrains Mono', monospace; text-align:right; padding:6px 10px;" value="<?= (float)$p['uang_makan_harian'] ?>" required>
                                    </td>
                                    <td style="padding:8px 14px; background:var(--bg-surface); text-align:right;">
                                        <input type="number" step="1000" name="tarif[<?= htmlspecialchars($p['jabatan']) ?>][tarif_lembur_per_jam]" class="form-input" style="width:100%; min-width:100px; font-family:'JetBrains Mono', monospace; text-align:right; padding:6px 10px;" value="<?= (float)$p['tarif_lembur_per_jam'] ?>" required>
                                    </td>
                                    <td style="padding:8px 14px; background:var(--bg-surface); text-align:right;">
                                        <input type="number" step="1000" name="tarif[<?= htmlspecialchars($p['jabatan']) ?>][potongan_alpa_per_hari]" class="form-input" style="width:100%; min-width:105px; font-family:'JetBrains Mono', monospace; text-align:right; padding:6px 10px;" value="<?= (float)$p['potongan_alpa_per_hari'] ?>" required>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="tutupModalPengaturan()">Batal</button>
                <button type="submit" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:6px;">
                    <?= Icon::get('save', 16) ?> Simpan Pengaturan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: Petunjuk Script Google Sheets (Opsi A) -->
<div id="modalAppsScript" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="modalAppsScriptTitle">
    <div class="modal-card modal-card-lg">
        <div class="modal-header">
            <div class="modal-header-left">
                <div class="modal-header-icon" style="background: rgba(245, 158, 11, 0.12); color: #d97706;">
                    💡
                </div>
                <div>
                    <h3 id="modalAppsScriptTitle" class="modal-title">Panduan Webhook Google Apps Script</h3>
                    <div class="modal-subtitle">Cara mudah mengaktifkan sinkronisasi otomatis ke spreadsheet client</div>
                </div>
            </div>
            <button type="button" class="modal-close-btn" onclick="tutupModalPetunjukScript()" aria-label="Tutup">
                <?= Icon::get('x', 18) ?>
            </button>
        </div>

        <div class="modal-body">
            <div style="display:flex; flex-direction:column; gap:16px;">
                <div style="display:flex; gap:12px; align-items:flex-start;">
                    <div style="width:26px; height:26px; border-radius:50%; background:var(--primary); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.8rem; flex-shrink:0;">1</div>
                    <div style="font-size:0.88rem; color:var(--text-main); line-height:1.5;">
                        Buka dokumen spreadsheet Anda: 
                        <a href="<?= htmlspecialchars($spreadsheetUrl) ?>" target="_blank" rel="noopener noreferrer" style="color:var(--accent-blue); font-weight:600; text-decoration:underline;">Buka Google Sheets &rarr;</a>
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
                            Hapus kode bawaan di editor, lalu salin dan tempel kode berikut:
                        </div>
                        <div style="position:relative;">
                            <textarea id="appsScriptCode" readonly style="width:100%; height:230px; font-family:'JetBrains Mono', Consolas, monospace; font-size:0.78rem; background:#0d1117; color:#7ee787; border:1px solid #30363d; border-radius:var(--radius-sm); padding:14px; line-height:1.45; resize:none;">function doPost(e) {
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
      sheet.getRange(1, 1).setValue("REKAP PRESENSI KARYAWAN - PT SURYA GADING MANDIRI")
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
    sheet.getRange(1, 1).setValue("REKAP PENGGAJIAN KARYAWAN - PT SURYA GADING MANDIRI")
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
                            <button type="button" class="btn btn-secondary btn-sm" onclick="salinKodeScript()" style="position:absolute; top:10px; right:10px; display:inline-flex; align-items:center; gap:6px; font-size:0.75rem; background:rgba(30,41,59,0.92); border:1px solid rgba(255,255,255,0.18); color:#f1f5f9; backdrop-filter:blur(4px); box-shadow:0 4px 10px rgba(0,0,0,0.3);">
                                <?= Icon::get('clipboard', 13) ?> <span id="salinBtnText">Salin Kode</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div style="display:flex; gap:12px; align-items:flex-start;">
                    <div style="width:26px; height:26px; border-radius:50%; background:var(--primary); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.8rem; flex-shrink:0;">4</div>
                    <div style="font-size:0.88rem; color:var(--text-main); line-height:1.5;">
                        Klik tombol biru <strong>Deploy (Terapkan)</strong> di pojok kanan atas &gt; pilih <strong>New deployment (Penerapan baru)</strong>.
                    </div>
                </div>

                <div style="display:flex; gap:12px; align-items:flex-start;">
                    <div style="width:26px; height:26px; border-radius:50%; background:var(--primary); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.8rem; flex-shrink:0;">5</div>
                    <div style="font-size:0.88rem; color:var(--text-main); line-height:1.5;">
                        Klik ikon gerigi di sebelah 'Select type', pilih <strong>Web app</strong>.
                    </div>
                </div>

                <div style="display:flex; gap:12px; align-items:flex-start;">
                    <div style="width:26px; height:26px; border-radius:50%; background:var(--primary); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.8rem; flex-shrink:0;">6</div>
                    <div style="font-size:0.88rem; color:var(--text-main); line-height:1.5;">
                        Pada kolom <strong>Who has access (Siapa yang memiliki akses)</strong>, ubah menjadi <strong>Anyone (Siapa saja)</strong>, lalu klik <strong>Deploy</strong>.
                    </div>
                </div>

                <div style="display:flex; gap:12px; align-items:flex-start;">
                    <div style="width:26px; height:26px; border-radius:50%; background:var(--primary); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.8rem; flex-shrink:0;">7</div>
                    <div style="font-size:0.88rem; color:var(--text-main); line-height:1.5;">
                        Salin <strong>Web App URL</strong> yang dihasilkan (berawalan <code>https://script.google.com/macros/s/.../exec</code>), lalu buka menu <strong>Pengaturan</strong> pada halaman ini dan tempelkan ke kolom <em>Webhook Web App</em>.
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-primary" onclick="tutupModalPetunjukScript()">Mengerti &amp; Tutup</button>
        </div>
    </div>
</div>

<script>
function bukaModalSync() {
    const modal = document.getElementById('modalSync');
    if (modal) modal.classList.add('show');
}
function tutupModalSync() {
    const modal = document.getElementById('modalSync');
    if (modal) modal.classList.remove('show');
}

function bukaModalPengaturan() {
    const modal = document.getElementById('modalPengaturan');
    if (modal) modal.classList.add('show');
}
function tutupModalPengaturan() {
    const modal = document.getElementById('modalPengaturan');
    if (modal) modal.classList.remove('show');
}

function bukaModalPetunjukScript() {
    const modal = document.getElementById('modalAppsScript');
    if (modal) modal.classList.add('show');
}
function tutupModalPetunjukScript() {
    const modal = document.getElementById('modalAppsScript');
    if (modal) modal.classList.remove('show');
}

function salinKodeScript() {
    const copyText = document.getElementById("appsScriptCode");
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(copyText.value).then(function() {
        const btnText = document.getElementById("salinBtnText");
        if (btnText) {
            btnText.textContent = "Tersalin!";
            setTimeout(() => { btnText.textContent = "Salin Kode"; }, 2500);
        }
    });
}

function periksaWebhookInput(input, warnId) {
    const val = (input.value || '').trim();
    const warn = document.getElementById(warnId);
    if (!warn) return;
    if (val.indexOf('docs.google.com/spreadsheets') !== -1) {
        warn.style.display = 'block';
    } else {
        warn.style.display = 'none';
    }
}

// Tutup modal jika klik di luar box
window.addEventListener('click', function(e) {
    const mSync = document.getElementById('modalSync');
    const mPengaturan = document.getElementById('modalPengaturan');
    const mApps = document.getElementById('modalAppsScript');
    if (e.target === mSync) tutupModalSync();
    if (e.target === mPengaturan) tutupModalPengaturan();
    if (e.target === mApps) tutupModalPetunjukScript();
});

const formSyncGaji = document.getElementById('formSyncGaji');
if (formSyncGaji) {
    formSyncGaji.addEventListener('submit', function(e) {
        e.preventDefault();
        const btnSubmit = document.getElementById('btnSyncSubmit');
        const btnCancel = document.getElementById('btnSyncCancel');
        const alertBox = document.getElementById('syncGajiAlert');

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

        const formData = new FormData(formSyncGaji);
        fetch(formSyncGaji.action, {
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
                    window.location.href = '<?= Config::baseUrl("gaji") ?>?bulan=' + encodeURIComponent(bulan) + '&tahun=' + encodeURIComponent(tahun) + '&msg=synced';
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

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
