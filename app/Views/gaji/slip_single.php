<?php
/**
 * Single Employee Payslip View (Pratinjau & Cetak Slip Gaji Pegawai)
 * Absensi Surya Gemilang Mojopahit (SGM)
 */

function formatRupiahSingle(float $angka): string {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function penyebutSingle(float $nilai): string {
    $nilai = abs((int)$nilai);
    $huruf = ["", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"];
    $temp = "";
    if ($nilai < 12) {
        $temp = " " . $huruf[$nilai];
    } else if ($nilai < 20) {
        $temp = penyebutSingle($nilai - 10) . " Belas";
    } else if ($nilai < 100) {
        $temp = penyebutSingle(floor($nilai / 10)) . " Puluh" . penyebutSingle($nilai % 10);
    } else if ($nilai < 200) {
        $temp = " Seratus" . penyebutSingle($nilai - 100);
    } else if ($nilai < 1000) {
        $temp = penyebutSingle(floor($nilai / 100)) . " Ratus" . penyebutSingle($nilai % 100);
    } else if ($nilai < 2000) {
        $temp = " Seribu" . penyebutSingle($nilai - 1000);
    } else if ($nilai < 1000000) {
        $temp = penyebutSingle(floor($nilai / 1000)) . " Ribu" . penyebutSingle($nilai % 1000);
    } else if ($nilai < 1000000000) {
        $temp = penyebutSingle(floor($nilai / 1000000)) . " Juta" . penyebutSingle($nilai % 1000000);
    } else if ($nilai < 1000000000000) {
        $temp = penyebutSingle(floor($nilai / 1000000000)) . " Milyar" . penyebutSingle(fmod($nilai, 1000000000));
    }
    return $temp;
}

function terbilangRupiahSingle(float $nilai): string {
    if ($nilai < 0) {
        return "Minus " . trim(penyebutSingle($nilai)) . " Rupiah";
    }
    return trim(penyebutSingle($nilai)) . " Rupiah";
}

$p = $pegawaiGaji;
$subtotalPenerimaan = $p['gaji_pokok'] + $p['uang_makan'] + $p['uang_lembur'];
$subtotalPotongan   = $p['potongan_alpa'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slip Gaji - <?= htmlspecialchars($p['nama']) ?> - Periode <?= htmlspecialchars($periodeText) ?></title>
    
    <!-- Favicon & App Icons -->
    <link rel="icon" type="image/png" sizes="32x32" href="<?= Config::baseUrl('assets/images/favicon-32x32.png') ?>?v=<?= filemtime(__DIR__ . '/../../../assets/images/favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= Config::baseUrl('assets/images/favicon-16x16.png') ?>?v=<?= filemtime(__DIR__ . '/../../../assets/images/favicon-16x16.png') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= Config::baseUrl('assets/images/apple-touch-icon.png') ?>?v=<?= filemtime(__DIR__ . '/../../../assets/images/apple-touch-icon.png') ?>">
    <link rel="shortcut icon" href="<?= Config::baseUrl('favicon.ico') ?>?v=<?= filemtime(__DIR__ . '/../../../favicon.ico') ?>">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
            font-size: 13px;
            line-height: 1.4;
        }

        .no-print-bar {
            background: #1e293b;
            color: #ffffff;
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .btn-print {
            background: #0ea5e9;
            color: #ffffff;
            border: none;
            padding: 8px 18px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
        }
        .btn-back {
            background: #475569;
            color: #ffffff;
            border: none;
            padding: 8px 14px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            margin-right: 8px;
        }

        .slip-container {
            max-width: 780px;
            margin: 24px auto;
            padding: 0 10px;
        }

        .slip-card {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 28px 32px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .slip-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .slip-header .company {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
        }
        .slip-header .tagline {
            font-size: 11px;
            color: #64748b;
        }
        .slip-header .doc-title {
            text-align: right;
        }
        .slip-header .doc-title h2 {
            font-size: 16px;
            font-weight: 700;
            color: #0369a1;
            text-transform: uppercase;
        }
        .slip-header .doc-title .period {
            font-size: 12px;
            font-weight: 600;
            color: #475569;
        }

        .pegawai-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px 24px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 14px;
            margin-bottom: 16px;
            font-size: 12px;
        }
        .meta-row { display: flex; }
        .meta-label { width: 110px; color: #64748b; font-weight: 500; }
        .meta-value { font-weight: 600; color: #0f172a; }

        .attendance-summary {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 6px;
            margin-bottom: 18px;
            text-align: center;
        }
        .att-box {
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 6px 4px;
            background: #ffffff;
        }
        .att-box .num { font-size: 14px; font-weight: 700; color: #0f172a; }
        .att-box .lbl { font-size: 10px; color: #64748b; text-transform: uppercase; }

        .financial-grid {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 16px;
            margin-bottom: 18px;
        }
        .fin-section {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            overflow: hidden;
        }
        .fin-section-title {
            background: #f1f5f9;
            padding: 6px 12px;
            font-weight: 700;
            font-size: 12px;
            border-bottom: 1px solid #e2e8f0;
            text-transform: uppercase;
        }
        .fin-section-title.penerimaan { color: #0369a1; border-left: 3px solid #0284c7; }
        .fin-section-title.potongan { color: #b91c1c; border-left: 3px solid #ef4444; }
        .fin-table { width: 100%; border-collapse: collapse; }
        .fin-table td { padding: 6px 12px; font-size: 12px; border-bottom: 1px solid #f1f5f9; }
        .fin-table .amount { text-align: right; font-family: 'JetBrains Mono', Consolas, monospace; font-weight: 600; }
        .fin-table .subtotal-row { background: #f8fafc; font-weight: 700; border-top: 1px solid #cbd5e1; }

        .thp-box {
            background: #f0fdf4;
            border: 2px solid #86efac;
            border-radius: 6px;
            padding: 12px 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        .thp-box .lbl { font-size: 13px; font-weight: 700; color: #166534; text-transform: uppercase; }
        .thp-box .amount { font-size: 18px; font-weight: 800; color: #15803d; font-family: 'JetBrains Mono', Consolas, monospace; }

        .terbilang-text {
            font-size: 11px;
            font-style: italic;
            color: #475569;
            background: #f8fafc;
            padding: 6px 12px;
            border-radius: 4px;
            border-left: 2px solid #cbd5e1;
            margin-bottom: 20px;
        }

        .signature-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 24px;
            text-align: center;
        }
        .sig-box { font-size: 12px; }
        .sig-box .date { margin-bottom: 8px; color: #475569; }
        .sig-box .space { height: 55px; }
        .sig-box .name { font-weight: 700; text-decoration: underline; color: #0f172a; }
        .sig-box .role { font-size: 11px; color: #64748b; }

        @media print {
            body { background: #ffffff; }
            .no-print-bar { display: none !important; }
            .slip-container { max-width: 100%; margin: 0; padding: 0; }
            .slip-card { border: 1px solid #94a3b8; box-shadow: none; padding: 24px 28px; }
        }
    </style>
</head>
<body>

    <div class="no-print-bar">
        <div>
            <strong>Slip Gaji:</strong> <?= htmlspecialchars($p['nama']) ?> &bull; Periode <?= htmlspecialchars($periodeText) ?>
        </div>
        <div>
            <?php if (($currentUser['role'] ?? '') === 'admin'): ?>
                <a href="<?= Config::baseUrl("gaji?bulan={$bulan}&tahun={$tahun}") ?>" class="btn-back">&larr; Kembali ke Rekap Penggajian</a>
            <?php else: ?>
                <a href="<?= Config::baseUrl('dashboard') ?>" class="btn-back">&larr; Kembali ke Dashboard</a>
            <?php endif; ?>
            <button class="btn-print" onclick="window.print()">🖨️ Cetak / Simpan PDF</button>
        </div>
    </div>

    <div class="slip-container">
        <div class="slip-card">
            <!-- Header -->
            <div class="slip-header">
                <div>
                    <div class="company"><?= Config::APP_NAME ?></div>
                    <div class="tagline">Automated Payroll & Attendance System</div>
                </div>
                <div class="doc-title">
                    <h2>Slip Gaji</h2>
                    <div class="period">Periode: <?= htmlspecialchars($periodeText) ?></div>
                </div>
            </div>

            <!-- Identitas Pegawai -->
            <div class="pegawai-meta">
                <div class="meta-row">
                    <span class="meta-label">NIK</span>
                    <span class="meta-value">: <?= htmlspecialchars($p['nik']) ?></span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Jabatan</span>
                    <span class="meta-value">: <?= htmlspecialchars($p['jabatan']) ?></span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Nama Pegawai</span>
                    <span class="meta-value">: <?= htmlspecialchars($p['nama']) ?></span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Dicetak Pada</span>
                    <span class="meta-value">: <?= date('d/m/Y H:i') ?></span>
                </div>
            </div>

            <!-- Ringkasan Absensi -->
            <div class="attendance-summary">
                <div class="att-box">
                    <div class="num"><?= $p['hari_kerja_sebulan'] ?? $p['hari_kerja_bulan'] ?? 0 ?></div>
                    <div class="lbl">Hari Kerja</div>
                </div>
                <div class="att-box">
                    <div class="num" style="color:#16a34a;"><?= $p['hadir'] ?></div>
                    <div class="lbl">Hadir</div>
                </div>
                <div class="att-box">
                    <div class="num" style="color:#ca8a04;"><?= $p['terlambat'] ?></div>
                    <div class="lbl">Terlambat</div>
                </div>
                <div class="att-box">
                    <div class="num"><?= $p['sakit'] ?></div>
                    <div class="lbl">Sakit</div>
                </div>
                <div class="att-box">
                    <div class="num"><?= $p['izin'] + $p['cuti'] ?></div>
                    <div class="lbl">Izin/Cuti</div>
                </div>
                <div class="att-box">
                    <div class="num" style="color:#dc2626;"><?= $p['alpa'] ?></div>
                    <div class="lbl">Alpa</div>
                </div>
                <div class="att-box">
                    <div class="num" style="color:#0284c7;"><?= number_format($p['jam_lembur'], 1) ?> jam</div>
                    <div class="lbl">Lembur</div>
                </div>
            </div>

            <!-- Rincian Keuangan -->
            <div class="financial-grid">
                <!-- Penerimaan -->
                <div class="fin-section">
                    <div class="fin-section-title penerimaan">Rincian Penerimaan</div>
                    <table class="fin-table">
                        <tr>
                            <td>Gaji Pokok</td>
                            <td class="amount"><?= formatRupiahSingle($p['gaji_pokok']) ?></td>
                        </tr>
                        <tr>
                            <td>
                                Uang Makan
                                <br><small style="color:#64748b;"><?= $p['hadir'] ?> hr × <?= formatRupiahSingle($p['tarif']['uang_makan_harian'] ?? 0) ?></small>
                            </td>
                            <td class="amount"><?= formatRupiahSingle($p['uang_makan']) ?></td>
                        </tr>
                        <tr>
                            <td>
                                Uang Lembur
                                <br><small style="color:#64748b;"><?= number_format($p['jam_lembur'], 2) ?> jam × <?= formatRupiahSingle($p['tarif']['tarif_lembur_per_jam'] ?? 0) ?></small>
                            </td>
                            <td class="amount"><?= formatRupiahSingle($p['uang_lembur']) ?></td>
                        </tr>
                        <tr class="subtotal-row">
                            <td>Subtotal Penerimaan</td>
                            <td class="amount"><?= formatRupiahSingle($subtotalPenerimaan) ?></td>
                        </tr>
                    </table>
                </div>

                <!-- Potongan -->
                <div class="fin-section">
                    <div class="fin-section-title potongan">Rincian Potongan</div>
                    <table class="fin-table">
                        <tr>
                            <td>
                                Potongan Alpa
                                <br><small style="color:#64748b;"><?= $p['alpa'] ?> hr × <?= formatRupiahSingle($p['tarif']['potongan_alpa_per_hari'] ?? 0) ?></small>
                            </td>
                            <td class="amount" style="color:#dc2626;">-<?= formatRupiahSingle($p['potongan_alpa']) ?></td>
                        </tr>
                        <tr>
                            <td>Potongan Lain-lain</td>
                            <td class="amount">Rp 0</td>
                        </tr>
                        <tr>
                            <td style="color:transparent;">-</td>
                            <td class="amount" style="color:transparent;">-</td>
                        </tr>
                        <tr class="subtotal-row">
                            <td>Subtotal Potongan</td>
                            <td class="amount" style="color:#dc2626;">-<?= formatRupiahSingle($subtotalPotongan) ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Take Home Pay Box -->
            <div class="thp-box">
                <div class="lbl">Total Gaji Diterima (Take Home Pay)</div>
                <div class="amount"><?= formatRupiahSingle($p['take_home_pay']) ?></div>
            </div>

            <div class="terbilang-text">
                <strong>Terbilang:</strong> <?= terbilangRupiahSingle($p['take_home_pay']) ?>
            </div>

            <!-- Tanda Tangan -->
            <div class="signature-grid">
                <div class="sig-box">
                    <div class="date">&nbsp;</div>
                    <div>Diterima Oleh,</div>
                    <div class="space"></div>
                    <div class="name"><?= htmlspecialchars($p['nama']) ?></div>
                    <div class="role">Karyawan</div>
                </div>
                <div class="sig-box">
                    <div class="date">Pekanbaru, <?= date('d') . ' ' . $periodeText ?></div>
                    <div>Diserahkan Oleh,</div>
                    <div class="space"></div>
                    <div class="name">HRD / Finance</div>
                    <div class="role">CV. Surya Gemilang Mojopahit</div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
