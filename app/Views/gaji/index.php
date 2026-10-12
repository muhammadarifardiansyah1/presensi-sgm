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
$totalPenggantiLiburSemua = 0;
$totalUangPLSemua = 0;
$totalPotonganAlpaSemua = 0;
$totalLainLainSemua = 0;
$totalHadirSemua = 0;

foreach ($rekapPayroll as $p) {
    $totalPengeluaranGaji += $p['take_home_pay'];
    $totalJamLemburSemua += $p['jam_lembur'];
    $totalUangLemburSemua += $p['uang_lembur'];
    $totalPenggantiLiburSemua += ($p['pengganti_libur_hari'] ?? 0);
    $totalUangPLSemua += ($p['uang_pengganti_libur'] ?? 0);
    $totalPotonganAlpaSemua += $p['potongan_alpa'];
    $totalLainLainSemua += ($p['lain_lain'] ?? 0);
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

    <!-- Action Toolbar Groups -->
    <div class="payroll-actions-toolbar">
        <!-- Group 1: Spreadsheet Split Action Group -->
        <div class="action-btn-group split-btn-group">
            <button type="button" class="btn btn-blue" onclick="bukaModalSync()" style="display: inline-flex; align-items: center; gap: 6px;">
                <?= Icon::get('refresh', 16) ?>
                <span>Sinkronkan</span>
            </button>
            <div class="dropdown">
                <button type="button" class="btn btn-blue dropdown-toggle-btn" id="btnSpreadsheetDropdown" aria-haspopup="true" aria-expanded="false" title="Opsi Spreadsheet Lainnya">
                    <?= Icon::get('chevron-down', 14) ?>
                </button>
                <div class="dropdown-menu dropdown-menu-right" style="min-width: 220px; padding: 6px;">
                    <a href="<?= htmlspecialchars($spreadsheetUrl) ?>" target="_blank" rel="noopener noreferrer" class="dropdown-item" style="display: flex; align-items: center; gap: 10px;">
                        <span style="color: #10b981; display: inline-flex;"><?= Icon::get('external-link', 16) ?></span>
                        <div>
                            <div style="font-weight: 600; font-size: 0.88rem;">Buka Spreadsheet</div>
                            <div style="font-size: 0.74rem; color: var(--text-muted);">Lihat sheet hasil sinkronisasi</div>
                        </div>
                    </a>
                    <div class="dropdown-divider" style="margin: 4px 0;"></div>
                    <a href="javascript:void(0)" onclick="bukaModalPetunjukScript()" class="dropdown-item" style="display: flex; align-items: center; gap: 10px;">
                        <span style="color: var(--warning, #f59e0b); display: inline-flex;"><?= Icon::get('file-text', 16) ?></span>
                        <div>
                            <div style="font-weight: 600; font-size: 0.88rem;">Panduan Webhook</div>
                            <div style="font-size: 0.74rem; color: var(--text-muted);">Setup Google Apps Script</div>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <!-- Group 2: Dokumen & Cetak Resmi -->
        <a href="<?= Config::baseUrl("gaji/cetak-laporan?bulan={$bulan}&tahun={$tahun}") ?>" data-turbo="false" target="_blank" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 6px;" title="Cetak Laporan Gaji Resmi Format RSUD RA Basoeni">
            <?= Icon::get('printer', 16) ?>
            <span>Cetak Format Basoeni</span>
        </a>

        <a href="<?= Config::baseUrl("gaji/slip-bulk?bulan={$bulan}&tahun={$tahun}") ?>" data-turbo="false" target="_blank" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 6px;" title="Cetak Seluruh Slip Gaji 1 Bulan">
            <?= Icon::get('file-text', 16) ?>
            <span>Slip Gaji Bulk</span>
        </a>

        <a href="<?= Config::baseUrl("gaji/export-csv?bulan={$bulan}&tahun={$tahun}") ?>" data-turbo="false" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 6px;" title="Download CSV Rekap Payroll">
            <?= Icon::get('download', 16) ?>
            <span>Export CSV</span>
        </a>

        <!-- Group 3: Pengaturan -->
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
<?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'penyesuaian_saved'): ?>
    <div class="alert alert-success" style="margin-bottom: 20px;">
        <?= Icon::get('check-circle', 18) ?> <strong>Berhasil!</strong> Data penyesuaian payroll (Pengganti Libur, Lembur, dan Lain-lain) periode <?= $periodeText ?> berhasil disimpan.
    </div>
<?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'settings_saved'): ?>
    <div class="alert alert-success" style="margin-bottom: 20px;">
        <?= Icon::get('check-circle', 18) ?> Pengaturan link spreadsheet, penandatangan laporan, dan tarif gaji berhasil diperbarui.
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
<div class="form-card" style="padding: 14px 20px; margin-bottom: 24px;">
    <form method="GET" action="<?= Config::baseUrl('gaji') ?>" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 8px;">
            <label for="pilihBulan" style="font-weight: 600; font-size: 0.88rem; color: var(--text-main); display: inline-flex; align-items: center; gap: 6px;">
                <?= Icon::get('calendar', 15) ?> Bulan:
            </label>
            <select name="bulan" id="pilihBulan" class="form-input" style="min-width: 140px; padding: 7px 12px;">
                <?php foreach ($namaBulan as $mNum => $mName): ?>
                    <option value="<?= $mNum ?>" <?= $bulan === $mNum ? 'selected' : '' ?>><?= $mName ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="display: flex; align-items: center; gap: 8px;">
            <label for="pilihTahun" style="font-weight: 600; font-size: 0.88rem; color: var(--text-main);">Tahun:</label>
            <select name="tahun" id="pilihTahun" class="form-input" style="min-width: 105px; padding: 7px 12px;">
                <?php 
                $curYear = (int)date('Y');
                for ($y = $curYear - 2; $y <= $curYear + 1; $y++): ?>
                    <option value="<?= $y ?>" <?= $tahun === $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-blue" style="padding: 7px 18px; font-weight: 600; font-size: 0.88rem; display: inline-flex; align-items: center; gap: 6px;">
            <?= Icon::get('search', 14) ?> <span>Tampilkan Rekap</span>
        </button>

        <a href="<?= Config::baseUrl('gaji') ?>" class="btn btn-secondary" style="padding: 7px 14px; font-size: 0.88rem;">
            Bulan Ini
        </a>
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
            <div class="kpi-value" id="kpiTotalTHP" style="font-size: 1.25rem; color: #10B981;"><?= formatRupiah($totalPengeluaranGaji) ?></div>
        </div>
    </div>

    <!-- Card 2: Total Lembur -->
    <div class="kpi-card">
        <div class="kpi-icon" style="background: rgba(245, 158, 11, 0.1); color: #F59E0B;">
            <?= Icon::get('clock', 24) ?>
        </div>
        <div class="kpi-info">
            <h4>Total Jam Lembur</h4>
            <div class="kpi-value" id="kpiTotalLembur"><?= number_format($totalJamLemburSemua, 2) ?> <span class="kpi-label">jam (<?= formatRupiah($totalUangLemburSemua) ?>)</span></div>
        </div>
    </div>

    <!-- Card 3: Total Pengganti Libur (PL) -->
    <div class="kpi-card">
        <div class="kpi-icon" style="background: rgba(59, 130, 246, 0.1); color: #3B82F6;">
            <?= Icon::get('calendar', 24) ?>
        </div>
        <div class="kpi-info">
            <h4>Total Pengganti Libur (PL)</h4>
            <div class="kpi-value" id="kpiTotalPL"><?= $totalPenggantiLiburSemua ?> <span class="kpi-label">hari (<?= formatRupiah($totalUangPLSemua) ?>)</span></div>
        </div>
    </div>

    <!-- Card 4: Total Potongan Absen -->
    <div class="kpi-card">
        <div class="kpi-icon" style="background: rgba(239, 68, 68, 0.1); color: #EF4444;">
            <?= Icon::get('clipboard-x', 24) ?>
        </div>
        <div class="kpi-info">
            <h4>Total Potongan Absen</h4>
            <div class="kpi-value" id="kpiTotalAbsen" style="color: #EF4444;"><?= formatRupiah($totalPotonganAlpaSemua) ?></div>
        </div>
    </div>
</div>

<!-- Form Pembungkus Penyesuaian Payroll In-Line -->
<form id="formPenyesuaianPayroll" method="POST" action="<?= Config::baseUrl('gaji/simpan-penyesuaian') ?>" novalidate data-turbo="false" onsubmit="return handleSimpanPenyesuaian(event)">
    <input type="hidden" name="bulan" value="<?= $bulan ?>">
    <input type="hidden" name="tahun" value="<?= $tahun ?>">

    <div class="table-card">
        <div class="looker-filter-bar" data-table="#payrollTable">
            <div class="looker-filter-bar-left">
                <!-- Quick Search Input -->
                <div class="looker-search-box">
                    <span class="looker-search-icon"><?= Icon::get('search', 14) ?></span>
                    <input type="text" class="looker-search-input" data-table="#payrollTable" placeholder="Cari Pegawai / Jabatan...">
                </div>

                <!-- Sort Select -->
                <div class="looker-sort-box">
                    <select class="table-sort-select" data-table="#payrollTable">
                        <option value="default">Urutkan: Default</option>
                        <option value="nama-asc">Nama Pegawai (A - Z)</option>
                        <option value="nama-desc">Nama Pegawai (Z - A)</option>
                        <option value="pokok-desc">Gaji Pokok (Terbesar)</option>
                        <option value="pokok-asc">Gaji Pokok (Terkecil)</option>
                        <option value="total-desc">Total Diterima (Terbesar)</option>
                        <option value="total-asc">Total Diterima (Terkecil)</option>
                    </select>
                </div>

                <!-- Filter Pill: Status / Gender -->
                <div class="looker-pill-wrapper" data-table="#payrollTable" data-filter-col="status" data-label="Gender">
                    <button type="button" class="looker-filter-pill">
                        <span class="looker-pill-text">Gender</span>
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
                            <input type="text" class="looker-popover-input" placeholder="Cari gender...">
                        </div>
                        <div class="looker-checklist">
                            <label class="looker-check-item">
                                <span class="looker-check-left">
                                    <input type="checkbox" value="L" checked>
                                    <span class="looker-check-label">Laki-Laki (L)</span>
                                </span>
                                <span class="looker-check-count"></span>
                            </label>
                            <label class="looker-check-item">
                                <span class="looker-check-left">
                                    <input type="checkbox" value="P" checked>
                                    <span class="looker-check-label">Perempuan (P)</span>
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
                <button type="button" class="looker-reset-all-btn" data-table="#payrollTable" title="Reset semua filter">
                    <?= Icon::get('refresh-cw', 13) ?> Reset
                </button>
            </div>
            <div class="looker-filter-bar-right">
                <span class="looker-row-count"></span>
                <span style="font-size:0.75rem; color:var(--text-muted); display:inline-flex; align-items:center; gap:4px;">
                    <?= Icon::get('edit-2', 12) ?> In-Line Fast Entry
                </span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table-custom" id="payrollTable">
                <thead>
                    <tr>
                        <th style="width: 44px; min-width: 44px; text-align: center;">No.</th>
                        <th class="sortable-th" data-col="nama" data-sort-type="text" style="min-width: 220px;">Pegawai</th>
                        <th class="sortable-th" data-col="status" data-sort-type="text" style="width: 75px; min-width: 75px; text-align: center;" title="Jenis Kelamin (L/P)">Gender</th>
                        <th class="sortable-th" data-col="pokok" data-sort-type="number" style="text-align: right; min-width: 130px;">Gaji Pokok</th>
                        <th style="text-align: center; min-width: 120px;" title="Jumlah hari absen (Potongan Rp 52.000/hari)">Absen (Hari)</th>
                        <th style="text-align: center; min-width: 125px;" title="Pengganti Libur / PL (+Rp 52.000/hari)">Pengganti Libur</th>
                        <th style="text-align: center; min-width: 120px;" title="Total jam lembur (+Rp 20.000/jam)">Lembur (Jam)</th>
                        <th style="text-align: right; min-width: 125px;" title="Nominal penyesuaian khusus (Rp)">Lain-Lain (Rp)</th>
                        <th style="min-width: 140px;">Keterangan</th>
                        <th class="sortable-th" data-col="total" data-sort-type="number" style="text-align: right; min-width: 165px; background: rgba(16, 185, 129, 0.08); color: #059669; font-weight: 700;">Total Diterima</th>
                        <th style="width: 65px; min-width: 65px; text-align: center;">Slip</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rekapPayroll)): ?>
                        <tr>
                            <td colspan="11" style="text-align: center; color: var(--text-muted); padding: 36px;">
                                Belum ada data pegawai atau absensi pada periode ini.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $avatarGradients = [
                            'linear-gradient(135deg, #3b82f6, #1d4ed8)',
                            'linear-gradient(135deg, #10b981, #047857)',
                            'linear-gradient(135deg, #8b5cf6, #6d28d9)',
                            'linear-gradient(135deg, #f59e0b, #b45309)',
                            'linear-gradient(135deg, #ec4899, #be185d)',
                            'linear-gradient(135deg, #06b6d4, #0e7490)',
                            'linear-gradient(135deg, #6366f1, #4338ca)'
                        ];
                        $no = 1; 
                        foreach ($rekapPayroll as $row): 
                            $uId = $row['id'] ?? $row['user_id'];
                            $pokok = (float)($row['gaji_pokok'] ?? 0);
                            $hadir = (int)($row['hadir'] ?? 0);
                            $uMakanHarian = (float)($row['tarif']['uang_makan_harian'] ?? 0);
                            $tarifAbsen = (float)($row['tarif']['potongan_alpa_per_hari'] ?? 52000);
                            $tarifPL = (float)($row['tarif']['tarif_pengganti_libur_per_hari'] ?? 52000);
                            $tarifLembur = (float)($row['tarif']['tarif_lembur_per_jam'] ?? 20000);
                            $alpaVal = $row['absen_hari_override'] !== null ? (int)$row['absen_hari_override'] : (int)($row['alpa'] ?? 0);
                            $plVal = (int)($row['pengganti_libur_hari'] ?? 0);
                            $lemburVal = $row['lembur_jam_override'] !== null ? (float)$row['lembur_jam_override'] : (float)($row['jam_lembur'] ?? 0);
                            $lainVal = (float)($row['lain_lain'] ?? 0);

                            $inisial = strtoupper(substr($row['nama'] ?? 'U', 0, 1));
                            $colorIdx = abs(crc32($row['nama'] ?? '')) % count($avatarGradients);
                            $avatarBg = $avatarGradients[$colorIdx];

                            $potAbsenNominal = (float)($row['potongan_alpa'] ?? 0);
                            $uangPLNominal = (float)($row['uang_pengganti_libur'] ?? 0);
                            $uangLemburNominal = (float)($row['uang_lembur'] ?? 0);
                        ?>
                            <tr class="payroll-row" 
                                data-user-id="<?= $uId ?>"
                                data-pokok="<?= $pokok ?>"
                                data-hadir="<?= $hadir ?>"
                                data-uang-makan="<?= $uMakanHarian ?>"
                                data-tarif-absen="<?= $tarifAbsen ?>"
                                data-tarif-pl="<?= $tarifPL ?>"
                                data-tarif-lembur="<?= $tarifLembur ?>">
                                <td style="text-align: center; color: var(--text-muted); font-weight: 600; font-size: 0.82rem;"><?= $no++ ?></td>
                                <td data-sort-value="<?= htmlspecialchars($row['nama']) ?>">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div class="payroll-avatar" style="background: <?= $avatarBg ?>;">
                                            <?= $inisial ?>
                                        </div>
                                        <div style="min-width: 0;">
                                            <div style="font-weight: 700; color: var(--text-main); font-size: 0.88rem; line-height: 1.25; white-space: nowrap;">
                                                <?= htmlspecialchars($row['nama']) ?>
                                            </div>
                                            <div style="font-size: 0.76rem; color: var(--text-muted); margin-top: 3px; display: flex; align-items: center; gap: 6px; white-space: nowrap;">
                                                <span style="font-family: 'JetBrains Mono', monospace;"><?= htmlspecialchars($row['nik']) ?></span>
                                                <span style="opacity: 0.5;">&bull;</span>
                                                <span style="font-weight: 500; color: var(--text-subtle);"><?= htmlspecialchars($row['jabatan']) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td data-sort-value="<?= $row['status_gender'] ?? 'L' ?>" style="text-align: center;">
                                    <?php if (($row['status_gender'] ?? 'L') === 'P'): ?>
                                        <span class="payroll-gender-badge gender-p" title="Perempuan (P)">P</span>
                                    <?php else: ?>
                                        <span class="payroll-gender-badge gender-l" title="Laki-Laki (L)">L</span>
                                    <?php endif; ?>
                                </td>
                                <td data-sort-value="<?= $pokok ?>" style="text-align: right; font-family: 'JetBrains Mono', monospace; font-weight: 600; font-size: 0.86rem; color: var(--text-main); white-space: nowrap;">
                                    <?= formatRupiah($pokok) ?>
                                </td>
                                <td style="text-align: center;">
                                    <div class="payroll-cell-container">
                                        <input type="number" min="0" step="1" 
                                            name="penyesuaian[<?= $uId ?>][absen_hari_override]" 
                                            class="payroll-cell-input input-absen" 
                                            value="<?= $alpaVal ?>" 
                                            placeholder="<?= $row['alpa_sistem'] ?? 0 ?>"
                                            title="Hari Absen (Sistem: <?= $row['alpa_sistem'] ?? 0 ?> hari, Tarif: <?= formatRupiah($tarifAbsen) ?>/hari)">
                                        <small class="cell-subhint text-danger hint-pot-absen <?= ($potAbsenNominal <= 0) ? 'is-zero' : '' ?>">-<?= formatRupiah($row['potongan_alpa']) ?></small>
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <div class="payroll-cell-container">
                                        <input type="number" min="0" step="1" 
                                            name="penyesuaian[<?= $uId ?>][pengganti_libur_hari]" 
                                            class="payroll-cell-input input-pl" 
                                            value="<?= $plVal ?>" 
                                            placeholder="0"
                                            title="Pengganti Libur / PL (Tarif Tambah: <?= formatRupiah($tarifPL) ?>/hari)">
                                        <small class="cell-subhint text-success hint-uang-pl <?= ($uangPLNominal <= 0) ? 'is-zero' : '' ?>">+<?= formatRupiah($row['uang_pengganti_libur'] ?? 0) ?></small>
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <div class="payroll-cell-container">
                                        <input type="number" min="0" step="any" 
                                            name="penyesuaian[<?= $uId ?>][lembur_jam_override]" 
                                            class="payroll-cell-input input-lembur" 
                                            value="<?= $lemburVal ?>" 
                                            placeholder="<?= (float)($row['jam_lembur_sistem'] ?? 0) ?>"
                                            title="Jam Lembur (SPL: <?= (float)($row['jam_lembur_sistem'] ?? 0) ?> jam, Tarif: <?= formatRupiah($tarifLembur) ?>/jam)">
                                        <small class="cell-subhint text-blue hint-uang-lembur <?= ($uangLemburNominal <= 0) ? 'is-zero' : '' ?>">+<?= formatRupiah($row['uang_lembur']) ?></small>
                                    </div>
                                </td>
                                <td style="text-align: right;">
                                    <div class="payroll-cell-container" style="align-items: flex-end;">
                                        <input type="number" step="any" 
                                            name="penyesuaian[<?= $uId ?>][lain_lain_nominal]" 
                                            class="payroll-cell-input input-lain" 
                                            value="<?= $lainVal ?>" 
                                            placeholder="0"
                                            title="Penyesuaian Lain-Lain (Rp)">
                                        <small class="cell-subhint is-zero" style="visibility: hidden;">-</small>
                                    </div>
                                </td>
                                <td>
                                    <div class="payroll-cell-container" style="align-items: stretch;">
                                        <input type="text" 
                                            name="penyesuaian[<?= $uId ?>][keterangan]" 
                                            class="payroll-cell-input input-ket" 
                                            value="<?= htmlspecialchars($row['keterangan_penyesuaian'] ?? '') ?>" 
                                            placeholder="Catatan...">
                                        <small class="cell-subhint is-zero" style="visibility: hidden;">-</small>
                                    </div>
                                </td>
                                <td data-sort-value="<?= (float)($row['take_home_pay'] ?? 0) ?>" style="text-align: right; white-space: nowrap;">
                                    <div class="payroll-thp-badge">
                                        <span class="cell-thp"><?= formatRupiah($row['take_home_pay']) ?></span>
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <a href="<?= Config::baseUrl("gaji/slip?id={$uId}&bulan={$bulan}&tahun={$tahun}") ?>" data-turbo="false" target="_blank" class="payroll-btn-slip" title="Pratinjau / Cetak Slip">
                                        <?= Icon::get('printer', 14) ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($rekapPayroll)): ?>
                    <tfoot>
                        <tr class="payroll-footer-row">
                            <td colspan="4" style="text-align: right; font-weight: 700; color: var(--text-main); font-size: 0.85rem; letter-spacing: 0.3px;">TOTAL:</td>
                            <td style="text-align: center; color: #EF4444; font-family: 'JetBrains Mono', monospace; font-weight: 700; font-size: 0.85rem;" id="footTotAbsen">-<?= formatRupiah($totalPotonganAlpaSemua) ?></td>
                            <td style="text-align: center; color: #10B981; font-family: 'JetBrains Mono', monospace; font-weight: 700; font-size: 0.85rem;" id="footTotPL">+<?= formatRupiah($totalUangPLSemua) ?></td>
                            <td style="text-align: center; color: #3B82F6; font-family: 'JetBrains Mono', monospace; font-weight: 700; font-size: 0.85rem;" id="footTotLembur">+<?= formatRupiah($totalUangLemburSemua) ?></td>
                            <td style="text-align: right; font-family: 'JetBrains Mono', monospace; font-weight: 700; font-size: 0.85rem;" id="footTotLain"><?= formatRupiah($totalLainLainSemua) ?></td>
                            <td></td>
                            <td style="text-align: right; white-space: nowrap;">
                                <div class="payroll-thp-badge footer-thp-badge">
                                    <span id="footTotTHP"><?= formatRupiah($totalPengeluaranGaji) ?></span>
                                </div>
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <!-- Sticky Floating Action Bar Saat Ada Perubahan Nilai -->
    <div class="payroll-floating-bar" id="payrollFloatingBar">
        <div class="floating-bar-info">
            <span class="pulse-indicator"></span>
            <span id="floatingBarMsg">Ada perubahan data penggajian (PL / Lembur / Lain-lain) yang belum disimpan.</span>
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <button type="button" class="btn btn-secondary btn-sm" onclick="konfirmasiResetPayroll()" style="background: rgba(255,255,255,0.15); border-color: transparent; color: #fff;">Reset</button>
            <button type="submit" id="btnSimpanFloating" class="btn btn-blue btn-sm" style="font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                <?= Icon::get('save', 15) ?>
                <span>Simpan Semua Penyesuaian</span>
            </button>
        </div>
    </div>
</form>

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
<!-- MODAL: Pengaturan Tarif & Integrasi (Enhanced Wide & Tabbed Layout) -->
<div id="modalPengaturan" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="modalPengaturanTitle">
    <div class="modal-card modal-card-xl">
        <div class="modal-header">
            <div class="modal-header-left">
                <div class="modal-header-icon" style="background:var(--primary-light); color:var(--primary);">
                    <?= Icon::get('settings', 20) ?>
                </div>
                <div>
                    <h3 id="modalPengaturanTitle" class="modal-title">Pengaturan Komponen Gaji & Integrasi Spreadsheet</h3>
                    <div class="modal-subtitle">Konfigurasi tarif payroll per jabatan dan integrasi Google Sheets</div>
                </div>
            </div>
            <button type="button" class="modal-close-btn" onclick="tutupModalPengaturan()" aria-label="Tutup">
                <?= Icon::get('x', 18) ?>
            </button>
        </div>

        <form method="POST" action="<?= Config::baseUrl('gaji/simpan-pengaturan') ?>" style="display:flex; flex-direction:column; flex:1; overflow:hidden;">
            <!-- Modal Tabs Bar -->
            <div class="modal-tabs">
                <button type="button" class="modal-tab-btn active" id="tabBtnTarif" onclick="gantiTabPengaturan('tarif')">
                    <?= Icon::get('wallet', 16) ?>
                    <span>Tarif Gaji per Jabatan</span>
                </button>
                <button type="button" class="modal-tab-btn" id="tabBtnIntegrasi" onclick="gantiTabPengaturan('integrasi')">
                    <?= Icon::get('external-link', 16) ?>
                    <span>Integrasi Spreadsheet & Webhook</span>
                </button>
                <button type="button" class="modal-tab-btn" id="tabBtnPenandatangan" onclick="gantiTabPengaturan('penandatangan')">
                    <?= Icon::get('file-text', 16) ?>
                    <span>Penandatangan Laporan (Basoeni)</span>
                </button>
            </div>

            <div class="modal-body" style="padding:0; flex:1; overflow-y:auto;">
                <!-- TAB 1: Tarif Komponen Gaji per Jabatan -->
                <div class="modal-tab-content active" id="tabContentTarif">
                    <div style="margin-bottom:16px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
                        <div>
                            <h4 style="font-size:0.95rem; font-weight:700; color:var(--text-main); margin:0;">
                                Matriks Tarif Komponen Gaji per Jabatan
                            </h4>
                            <div style="font-size:0.8rem; color:var(--text-muted); margin-top:2px;">
                                Nilai diperbarui secara real-time untuk perhitungan payroll dan slip gaji pegawai.
                            </div>
                        </div>
                        <span style="font-size:0.75rem; background:var(--primary-light); color:var(--primary); padding:4px 12px; border-radius:var(--radius-full); font-weight:600;">
                            <?= count($pengaturanGajiList) ?> Jabatan Terdaftar
                        </span>
                    </div>

                    <!-- Banner Info Kebijakan Lembur & SPL -->
                    <div style="background: rgba(59, 130, 246, 0.08); border: 1px solid rgba(59, 130, 246, 0.2); border-radius: var(--radius-sm); padding: 10px 14px; margin-bottom: 16px; font-size: 0.82rem; color: var(--text-main); display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="color: #3b82f6; display: inline-flex;"><?= Icon::get('shield-check', 16) ?></span>
                            <span>
                                <strong>Kebijakan Lembur:</strong> Uang lembur hanya diakui & dibayarkan jika pegawai memiliki <strong>Surat Perintah Lembur (SPL)</strong> aktif dan jamnya dibatasi maksimal (cap) sesuai SPL.
                            </span>
                        </div>
                        <a href="<?= Config::baseUrl('lembur') ?>" class="btn btn-secondary btn-sm" style="font-size: 0.76rem; padding: 4px 10px; display: inline-flex; align-items: center; gap: 4px;">
                            <?= Icon::get('clock', 12) ?> Atur Penugasan Lembur &rarr;
                        </a>
                    </div>

                    <!-- Responsive Matrix Table (Card-view otomatis di mobile) -->
                    <div class="salary-table-wrapper">
                        <table class="salary-matrix-table">
                            <thead>
                                <tr>
                                    <th style="min-width:210px; width:22%;">Jabatan</th>
                                    <th style="min-width:160px; width:15.6%; text-align:right;">Gaji Pokok (Rp)</th>
                                    <th style="min-width:160px; width:15.6%; text-align:right;">Uang Makan/Hari (Rp)</th>
                                    <th style="min-width:160px; width:15.6%; text-align:right;">Lembur/Jam (Rp)</th>
                                    <th style="min-width:160px; width:15.6%; text-align:right;">Potongan Alpa/Hari (Rp)</th>
                                    <th style="min-width:170px; width:15.6%; text-align:right;">Pengganti Libur/Hari (Rp)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pengaturanGajiList as $p): 
                                    $safeJab = htmlspecialchars($p['jabatan']);
                                ?>
                                    <tr class="salary-row">
                                        <td class="salary-col-role">
                                            <div class="salary-role-badge">
                                                <span class="role-dot"></span>
                                                <span><?= $safeJab ?></span>
                                            </div>
                                        </td>
                                        <td class="salary-col-field">
                                            <div class="field-label-mobile">Gaji Pokok (Rp)</div>
                                            <div class="salary-input-group">
                                                <input type="number" step="1000" min="0" 
                                                    name="tarif[<?= $safeJab ?>][gaji_pokok]" 
                                                    class="form-input currency-input" 
                                                    value="<?= (float)$p['gaji_pokok'] ?>" 
                                                    oninput="updateCurrencyHint(this)" 
                                                    required>
                                                <span class="currency-live-hint"><?= formatRupiah((float)$p['gaji_pokok']) ?></span>
                                            </div>
                                        </td>
                                        <td class="salary-col-field">
                                            <div class="field-label-mobile">Uang Makan/Hari (Rp)</div>
                                            <div class="salary-input-group">
                                                <input type="number" step="1000" min="0" 
                                                    name="tarif[<?= $safeJab ?>][uang_makan_harian]" 
                                                    class="form-input currency-input" 
                                                    value="<?= (float)$p['uang_makan_harian'] ?>" 
                                                    oninput="updateCurrencyHint(this)" 
                                                    required>
                                                <span class="currency-live-hint"><?= formatRupiah((float)$p['uang_makan_harian']) ?></span>
                                            </div>
                                        </td>
                                        <td class="salary-col-field">
                                            <div class="field-label-mobile">Lembur/Jam (Rp)</div>
                                            <div class="salary-input-group">
                                                <input type="number" step="1000" min="0" 
                                                    name="tarif[<?= $safeJab ?>][tarif_lembur_per_jam]" 
                                                    class="form-input currency-input" 
                                                    value="<?= (float)$p['tarif_lembur_per_jam'] ?>" 
                                                    oninput="updateCurrencyHint(this)" 
                                                    required>
                                                <span class="currency-live-hint"><?= formatRupiah((float)$p['tarif_lembur_per_jam']) ?></span>
                                            </div>
                                        </td>
                                        <td class="salary-col-field">
                                            <div class="field-label-mobile">Potongan Alpa/Hari (Rp)</div>
                                            <div class="salary-input-group">
                                                <input type="number" step="1000" min="0" 
                                                    name="tarif[<?= $safeJab ?>][potongan_alpa_per_hari]" 
                                                    class="form-input currency-input" 
                                                    value="<?= (float)$p['potongan_alpa_per_hari'] ?>" 
                                                    oninput="updateCurrencyHint(this)" 
                                                    required>
                                                <span class="currency-live-hint"><?= formatRupiah((float)$p['potongan_alpa_per_hari']) ?></span>
                                            </div>
                                        </td>
                                        <td class="salary-col-field">
                                            <div class="field-label-mobile">Pengganti Libur/Hari (Rp)</div>
                                            <div class="salary-input-group">
                                                <input type="number" step="1000" min="0" 
                                                    name="tarif[<?= $safeJab ?>][tarif_pengganti_libur_per_hari]" 
                                                    class="form-input currency-input" 
                                                    value="<?= (float)($p['tarif_pengganti_libur_per_hari'] ?? 52000) ?>" 
                                                    oninput="updateCurrencyHint(this)" 
                                                    required>
                                                <span class="currency-live-hint"><?= formatRupiah((float)($p['tarif_pengganti_libur_per_hari'] ?? 52000)) ?></span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 2: Integrasi Spreadsheet & Webhook -->
                <div class="modal-tab-content" id="tabContentIntegrasi">
                    <!-- Link Google Spreadsheet -->
                    <div class="form-group" style="margin-bottom:20px;">
                        <label class="form-label" style="font-weight:600; display:flex; align-items:center; gap:6px;">
                            <?= Icon::get('external-link', 16) ?>
                            <span>Link Dokumen Google Spreadsheet Client:</span>
                        </label>
                        <div style="display:flex; gap:8px;">
                            <input type="url" name="spreadsheet_url" id="settingSpreadsheetUrl" class="form-input" style="flex:1;" value="<?= htmlspecialchars($spreadsheetUrl) ?>" placeholder="https://docs.google.com/spreadsheets/d/..." required>
                            <a href="<?= htmlspecialchars($spreadsheetUrl) ?>" id="btnTestSpreadsheetLink" target="_blank" rel="noopener noreferrer" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:6px; flex-shrink:0;">
                                <?= Icon::get('external-link', 14) ?> Buka Link
                            </a>
                        </div>
                        <small style="color:var(--text-muted); display:block; margin-top:6px; font-size:0.8rem;">
                            Tautan dokumen Google Sheets yang dibuka saat tombol "Buka Spreadsheet" diklik.
                        </small>
                    </div>

                    <!-- Webhook Google Apps Script -->
                    <div class="form-group" style="margin-bottom:20px;">
                        <label class="form-label" style="font-weight:600; display:flex; align-items:center; gap:6px;">
                            <?= Icon::get('refresh', 16) ?>
                            <span>Webhook Web App Google Apps Script (Opsional untuk Sinkronisasi Otomatis):</span>
                        </label>
                        <input type="url" name="spreadsheet_webhook_url" id="settingWebhookUrl" class="form-input" value="<?= htmlspecialchars($spreadsheetWebhookUrl) ?>" placeholder="https://script.google.com/macros/s/.../exec" oninput="periksaWebhookInput(this, 'settingWebhookWarn')">
                        <div id="settingWebhookWarn" style="display:none; color:#ef4444; font-size:0.8rem; margin-top:8px; background:rgba(239,68,68,0.1); padding:10px 14px; border-radius:8px; border:1px solid rgba(239,68,68,0.3);">
                            ⚠️ <strong>Perhatian:</strong> Kolom ini khusus URL Web App (script.google.com/macros/s/.../exec), bukan link spreadsheet biasa.
                        </div>
                        <small style="color:var(--text-muted); display:block; margin-top:6px; font-size:0.8rem;">
                            Digunakan untuk mengirim data rekap kehadiran dan payroll langsung ke sheet via tombol "Sinkronkan ke Spreadsheet".
                        </small>
                    </div>

                    <!-- Mini Panduan Box -->
                    <div style="background:var(--bg-subtle); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:16px; margin-top:24px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
                            <div style="display:flex; align-items:center; gap:10px;">
                                <span style="font-size:1.3rem;">💡</span>
                                <div>
                                    <strong style="font-size:0.88rem; color:var(--text-main);">Butuh Bantuan Memasang Google Apps Script?</strong>
                                    <div style="font-size:0.8rem; color:var(--text-muted);">Lihat contoh kode script macro dan panduan deployment Web App step-by-step.</div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="tutupModalPengaturan(); bukaModalPetunjukScript();" style="display:inline-flex; align-items:center; gap:6px;">
                                Buka Panduan Skrip →
                            </button>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: Penandatangan Laporan Resmi (RSUD RA Basoeni) -->
                <div class="modal-tab-content" id="tabContentPenandatangan" style="padding: 20px;">
                    <div style="background: rgba(37, 99, 235, 0.06); border: 1px solid rgba(37, 99, 235, 0.2); border-radius: var(--radius-sm); padding: 12px 16px; margin-bottom: 20px;">
                        <strong style="color: var(--primary); font-size: 0.9rem;">Otorisasi Cetak Laporan Resmi</strong>
                        <p style="color: var(--text-muted); font-size: 0.82rem; margin: 4px 0 0 0;">
                            Nama yang tercantum pada 3 kolom pengesahan laporan fisik (Koordinator, Supervisor, Manajer Operasional) sesuai standar RSUD RA Basoeni.
                        </p>
                    </div>
                    <div class="form-group" style="margin-bottom: 18px;">
                        <label class="form-label" style="font-weight: 600;">1. Dibuat Oleh (Koordinator):</label>
                        <input type="text" name="payroll_penandatangan_koordinator" class="form-input" value="<?= htmlspecialchars($penandatanganKoordinator) ?>" placeholder="Nama Koordinator (Contoh: Rian)">
                    </div>
                    <div class="form-group" style="margin-bottom: 18px;">
                        <label class="form-label" style="font-weight: 600;">2. Diperiksa Oleh (Supervisor):</label>
                        <input type="text" name="payroll_penandatangan_supervisor" class="form-input" value="<?= htmlspecialchars($penandatanganSupervisor) ?>" placeholder="Nama Supervisor">
                    </div>
                    <div class="form-group" style="margin-bottom: 18px;">
                        <label class="form-label" style="font-weight: 600;">3. Diketahui Oleh (Manajer Operasional):</label>
                        <input type="text" name="payroll_penandatangan_manajer" class="form-input" value="<?= htmlspecialchars($penandatanganManajer) ?>" placeholder="Nama Manajer Operasional">
                    </div>
                </div>
            </div>

            <!-- Sticky Modal Footer -->
            <div class="modal-footer" style="justify-content:space-between;">
                <div style="font-size:0.8rem; color:var(--text-muted); display:flex; align-items:center; gap:6px;">
                    <?= Icon::get('check-circle', 14) ?>
                    <span>Tarif akan tersimpan aman ke database</span>
                </div>
                <div style="display:flex; gap:10px;">
                    <button type="button" class="btn btn-secondary" onclick="tutupModalPengaturan()">Batal</button>
                    <button type="submit" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:6px;">
                        <?= Icon::get('save', 16) ?> Simpan Pengaturan
                    </button>
                </div>
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
    if (modal) {
        modal.classList.add('show');
        // Default ke tab tarif saat modal dibuka
        gantiTabPengaturan('tarif');
    }
}
function tutupModalPengaturan() {
    const modal = document.getElementById('modalPengaturan');
    if (modal) modal.classList.remove('show');
}

function gantiTabPengaturan(tabName) {
    const tabTarif = document.getElementById('tabContentTarif');
    const tabIntegrasi = document.getElementById('tabContentIntegrasi');
    const tabPenandatangan = document.getElementById('tabContentPenandatangan');
    const btnTarif = document.getElementById('tabBtnTarif');
    const btnIntegrasi = document.getElementById('tabBtnIntegrasi');
    const btnPenandatangan = document.getElementById('tabBtnPenandatangan');

    [tabTarif, tabIntegrasi, tabPenandatangan].forEach(t => t && t.classList.remove('active'));
    [btnTarif, btnIntegrasi, btnPenandatangan].forEach(b => b && b.classList.remove('active'));

    if (tabName === 'tarif') {
        if (tabTarif) tabTarif.classList.add('active');
        if (btnTarif) btnTarif.classList.add('active');
    } else if (tabName === 'integrasi') {
        if (tabIntegrasi) tabIntegrasi.classList.add('active');
        if (btnIntegrasi) btnIntegrasi.classList.add('active');
    } else if (tabName === 'penandatangan') {
        if (tabPenandatangan) tabPenandatangan.classList.add('active');
        if (btnPenandatangan) btnPenandatangan.classList.add('active');
    }
}

function formatRupiahJs(number) {
    return 'Rp ' + Number(number || 0).toLocaleString('id-ID');
}

function updateCurrencyHint(input) {
    if (!input) return;
    const parent = input.closest('.salary-input-group');
    if (parent) {
        const hint = parent.querySelector('.currency-live-hint');
        if (hint) {
            const val = parseFloat(input.value) || 0;
            hint.textContent = formatRupiahJs(val);
        }
    }
}

// State tracking perubahan data penyesuaian (Window-scoped & var agar aman terhadap navigasi ulang Hotwire Turbo)
window.isPayrollDirty = false;
var isPayrollDirty = window.isPayrollDirty;

function markPayrollDirty() {
    window.isPayrollDirty = true;
    isPayrollDirty = true;
    const bar = document.getElementById('payrollFloatingBar');
    if (bar) bar.classList.add('active');
}

function resetPayrollDirty() {
    window.isPayrollDirty = false;
    isPayrollDirty = false;
    const bar = document.getElementById('payrollFloatingBar');
    if (bar) bar.classList.remove('active');
}

function hitungBaris(tr) {
    if (!tr) return null;
    const pokok = parseFloat(tr.dataset.pokok) || 0;
    const hadir = parseInt(tr.dataset.hadir) || 0;
    const uangMakanHarian = parseFloat(tr.dataset.uangMakan) || 0;
    const totalUangMakan = hadir * uangMakanHarian;
    const tarifAbsen = parseFloat(tr.dataset.tarifAbsen) || 52000;
    const tarifPL = parseFloat(tr.dataset.tarifPl) || 52000;
    const tarifLembur = parseFloat(tr.dataset.tarifLembur) || 20000;

    const inAbsen = tr.querySelector('.input-absen');
    const inPL = tr.querySelector('.input-pl');
    const inLembur = tr.querySelector('.input-lembur');
    const inLain = tr.querySelector('.input-lain');

    const absenDays = inAbsen ? (parseFloat(inAbsen.value) || 0) : 0;
    const plDays = inPL ? (parseFloat(inPL.value) || 0) : 0;
    const jamLembur = inLembur ? (parseFloat(inLembur.value) || 0) : 0;
    const lainLain = inLain ? (parseFloat(inLain.value) || 0) : 0;

    const potAbsen = absenDays * tarifAbsen;
    const uangPL = plDays * tarifPL;
    const uangLembur = Math.round(jamLembur * tarifLembur);
    const thp = Math.max(0, pokok + totalUangMakan + uangPL + uangLembur + lainLain - potAbsen);

    const hintAbsen = tr.querySelector('.hint-pot-absen');
    if (hintAbsen) {
        hintAbsen.textContent = '-' + formatRupiahJs(potAbsen);
        hintAbsen.classList.toggle('is-zero', potAbsen <= 0);
    }

    const hintPL = tr.querySelector('.hint-uang-pl');
    if (hintPL) {
        hintPL.textContent = '+' + formatRupiahJs(uangPL);
        hintPL.classList.toggle('is-zero', uangPL <= 0);
    }

    const hintLembur = tr.querySelector('.hint-uang-lembur');
    if (hintLembur) {
        hintLembur.textContent = '+' + formatRupiahJs(uangLembur);
        hintLembur.classList.toggle('is-zero', uangLembur <= 0);
    }

    const cellTHP = tr.querySelector('.cell-thp');
    if (cellTHP) cellTHP.textContent = formatRupiahJs(thp);

    return { potAbsen, uangPL, jamLembur, uangLembur, plDays, lainLain, thp };
}

function hitungUlangSemua() {
    const rows = document.querySelectorAll('#payrollTable .payroll-row');
    if (!rows.length) return;

    let sumAbsen = 0;
    let sumPL = 0;
    let sumUangPL = 0;
    let sumJamLembur = 0;
    let sumUangLembur = 0;
    let sumLain = 0;
    let sumTHP = 0;

    rows.forEach(tr => {
        const res = hitungBaris(tr);
        if (res) {
            sumAbsen += res.potAbsen;
            sumPL += res.plDays;
            sumUangPL += res.uangPL;
            sumJamLembur += res.jamLembur;
            sumUangLembur += res.uangLembur;
            sumLain += res.lainLain;
            sumTHP += res.thp;
        }
    });

    // Update Footers Tabel
    const footAbsen = document.getElementById('footTotAbsen');
    if (footAbsen) footAbsen.textContent = '-' + formatRupiahJs(sumAbsen);

    const footPL = document.getElementById('footTotPL');
    if (footPL) footPL.textContent = '+' + formatRupiahJs(sumUangPL);

    const footLembur = document.getElementById('footTotLembur');
    if (footLembur) footLembur.textContent = '+' + formatRupiahJs(sumUangLembur);

    const footLain = document.getElementById('footTotLain');
    if (footLain) footLain.textContent = formatRupiahJs(sumLain);

    const footTHP = document.getElementById('footTotTHP');
    if (footTHP) footTHP.textContent = formatRupiahJs(sumTHP);

    // Update KPI Cards Atas
    const kpiTHP = document.getElementById('kpiTotalTHP');
    if (kpiTHP) kpiTHP.textContent = formatRupiahJs(sumTHP);

    const kpiLembur = document.getElementById('kpiTotalLembur');
    if (kpiLembur) kpiLembur.innerHTML = sumJamLembur.toFixed(2) + ' <span class="kpi-label">jam (' + formatRupiahJs(sumUangLembur) + ')</span>';

    const kpiPL = document.getElementById('kpiTotalPL');
    if (kpiPL) kpiPL.innerHTML = sumPL + ' <span class="kpi-label">hari (' + formatRupiahJs(sumUangPL) + ')</span>';

    const kpiAbsen = document.getElementById('kpiTotalAbsen');
    if (kpiAbsen) kpiAbsen.textContent = formatRupiahJs(sumAbsen);
}

function konfirmasiResetPayroll() {
    if (!window.isPayrollDirty) {
        if (window.SGMDialog && typeof window.SGMDialog.toast === 'function') {
            window.SGMDialog.toast('Tidak ada perubahan penyesuaian yang perlu direset.', 'info');
        }
        return;
    }

    if (window.SGMDialog && typeof window.SGMDialog.confirm === 'function') {
        window.SGMDialog.confirm({
            title: 'Batalkan Semua Perubahan?',
            message: 'Seluruh nilai in-line penggajian (Pengganti Libur, Jam Lembur, dan Lain-lain) yang baru saja Anda edit akan dibatalkan dan dikembalikan ke kondisi awal.',
            type: 'warning',
            confirmText: 'Ya, Reset Nilai',
            cancelText: 'Lanjut Edit',
            onConfirm: () => {
                // Nonaktifkan dirty flag agar reload tidak dicegat oleh event beforeunload bawaan browser
                window.isPayrollDirty = false;
                const bar = document.getElementById('payrollFloatingBar');
                if (bar) bar.classList.remove('active');
                window.location.reload();
            }
        });
    } else {
        if (confirm('Batalkan seluruh perubahan penggajian yang belum disimpan?')) {
            window.isPayrollDirty = false;
            window.location.reload();
        }
    }
}

function handleSimpanPenyesuaian(e) {
    if (e) e.preventDefault();
    const form = document.getElementById('formPenyesuaianPayroll');
    if (!form) return false;

    const btnFloating = document.getElementById('btnSimpanFloating');
    const origFloatingHtml = btnFloating ? btnFloating.innerHTML : '';
    if (btnFloating) {
        btnFloating.disabled = true;
        btnFloating.innerHTML = '<span class="spinner" style="width:14px;height:14px;border:2px solid #fff;border-top-color:transparent;border-radius:50%;display:inline-block;animation:spin 0.6s linear infinite;margin-right:6px;"></span> Menyimpan...';
    }

    const formData = new FormData(form);

    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            resetPayrollDirty();
            if (window.SGMDialog && typeof window.SGMDialog.toast === 'function') {
                window.SGMDialog.toast(data.message || 'Penyesuaian payroll berhasil disimpan!', 'success');
            } else {
                alert('✅ ' + (data.message || 'Penyesuaian payroll berhasil disimpan!'));
            }
        } else {
            if (window.SGMDialog && typeof window.SGMDialog.toast === 'function') {
                window.SGMDialog.toast(data.message || 'Gagal menyimpan penyesuaian.', 'danger');
            } else {
                alert('❌ ' + (data.message || 'Gagal menyimpan penyesuaian.'));
            }
        }
    })
    .catch(err => {
        console.error('Error saving payroll adjustments:', err);
        if (window.SGMDialog && typeof window.SGMDialog.toast === 'function') {
            window.SGMDialog.toast('Terjadi kendala koneksi saat menyimpan penyesuaian: ' + err.message, 'danger');
        } else {
            alert('❌ Kendala koneksi: ' + err.message);
        }
    })
    .finally(() => {
        if (btnFloating) {
            btnFloating.disabled = false;
            btnFloating.innerHTML = origFloatingHtml;
        }
    });

    return false;
}

function initPayrollTableEvents() {
    const table = document.getElementById('payrollTable');
    if (!table) return;

    const inputs = table.querySelectorAll('.payroll-cell-input');
    const colClasses = ['input-absen', 'input-pl', 'input-lembur', 'input-lain', 'input-ket'];

    inputs.forEach(input => {
        // Event live math & dirty status
        input.addEventListener('input', function() {
            const tr = this.closest('.payroll-row');
            hitungBaris(tr);
            hitungUlangSemua();
            markPayrollDirty();
        });

        // Keyboard navigation ala Excel (Enter / Down / Up)
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                let currCol = null;
                for (const cls of colClasses) {
                    if (this.classList.contains(cls)) {
                        currCol = cls;
                        break;
                    }
                }
                if (!currCol) return;

                const tr = this.closest('.payroll-row');
                if (!tr) return;

                let targetTr = null;
                if (e.key === 'Enter' || e.key === 'ArrowDown') {
                    targetTr = tr.nextElementSibling;
                } else if (e.key === 'ArrowUp') {
                    targetTr = tr.previousElementSibling;
                }

                if (targetTr && targetTr.classList.contains('payroll-row')) {
                    const targetInput = targetTr.querySelector('.' + currCol);
                    if (targetInput) {
                        e.preventDefault();
                        targetInput.focus();
                        if (typeof targetInput.select === 'function') {
                            targetInput.select();
                        }
                    }
                }
            }
        });
    });
}

function initGajiSettingsEvents() {
    const settingSheetInput = document.getElementById('settingSpreadsheetUrl');
    const btnTestSheet = document.getElementById('btnTestSpreadsheetLink');
    if (settingSheetInput && btnTestSheet) {
        settingSheetInput.addEventListener('input', function() {
            btnTestSheet.href = this.value || '#';
        });
    }

    initFormSyncGaji();
    initPayrollTableEvents();
}

// Proteksi Tab/Browser Close (Standard HTTP)
window.addEventListener('beforeunload', function(e) {
    if (window.isPayrollDirty) {
        e.preventDefault();
        e.returnValue = '';
    }
});

// Proteksi SPA Navigasi Hotwire Turbo Drive (Mencegah human error kehilangan data editan)
if (!window._hasGajiTurboBeforeVisit) {
    window._hasGajiTurboBeforeVisit = true;
    document.addEventListener('turbo:before-visit', function(e) {
        if (window.isPayrollDirty) {
            const confirmed = window.confirm(
                '⚠️ PERINGATAN: PERUBAHAN BELUM DISIMPAN!\n\n' +
                'Anda memiliki data penyesuaian gaji yang sedang diedit dan belum disimpan ke server.\n\n' +
                'Jika Anda berpindah halaman sekarang, seluruh data editan tersebut akan HILANG.\n\n' +
                'Klik "Batal" (Cancel) untuk tetap di halaman ini dan menyimpan data.\n' +
                'Klik "OK" jika Anda memang ingin membuang perubahan ini.'
            );
            if (!confirmed) {
                e.preventDefault();
            } else {
                window.isPayrollDirty = false;
                var bar = document.getElementById('payrollFloatingBar');
                if (bar) bar.classList.remove('active');
            }
        }
    });
}

document.addEventListener('turbo:load', initGajiSettingsEvents);
if (document.readyState !== 'loading') {
    initGajiSettingsEvents();
} else {
    document.addEventListener('DOMContentLoaded', initGajiSettingsEvents);
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

// Tutup modal jika klik di luar box (Single Guarded Listener)
if (!window._hasGajiOutsideClickAttached) {
    window._hasGajiOutsideClickAttached = true;
    window.addEventListener('click', function(e) {
        const mSync = document.getElementById('modalSync');
        const mPengaturan = document.getElementById('modalPengaturan');
        const mApps = document.getElementById('modalAppsScript');
        if (e.target === mSync) tutupModalSync();
        if (e.target === mPengaturan) tutupModalPengaturan();
        if (e.target === mApps) tutupModalPetunjukScript();
    });
}

function initFormSyncGaji() {
    const formSync = document.getElementById('formSyncGaji');
    if (!formSync || formSync.dataset.boundTurbo) return;
    formSync.dataset.boundTurbo = 'true';

    formSync.addEventListener('submit', function(e) {
        e.preventDefault();
        const btnSubmit = document.getElementById('btnSyncSubmit');
        const btnCancel = document.getElementById('btnSyncCancel');
        const alertBox = document.getElementById('syncGajiAlert');

        if (btnSubmit && btnSubmit.disabled) return;

        if (btnSubmit) btnSubmit.disabled = true;
        if (btnCancel) btnCancel.disabled = true;
        const originalContent = btnSubmit ? btnSubmit.innerHTML : '';
        if (btnSubmit) btnSubmit.innerHTML = '<span class="spinner"></span> Menyinkronkan...';

        if (alertBox) {
            alertBox.style.display = 'block';
            alertBox.style.background = 'rgba(59, 130, 246, 0.12)';
            alertBox.style.color = '#3b82f6';
            alertBox.style.border = '1px solid rgba(59, 130, 246, 0.3)';
            alertBox.innerHTML = '⏳ Sedang mengirim data ke Google Spreadsheet, mohon tunggu beberapa saat...';
        }

        const formData = new FormData(formSync);
        fetch(formSync.action, {
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
                if (btnSubmit) {
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = originalContent;
                }
                if (btnCancel) btnCancel.disabled = false;
                if (alertBox) {
                    alertBox.style.background = 'rgba(239, 68, 68, 0.12)';
                    alertBox.style.color = '#ef4444';
                    alertBox.style.border = '1px solid rgba(239, 68, 68, 0.3)';
                    alertBox.innerHTML = '❌ ' + (data.message || 'Gagal menyinkronkan data.');
                }
            }
        })
        .catch(err => {
            if (btnSubmit) {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = originalContent;
            }
            if (btnCancel) btnCancel.disabled = false;
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
