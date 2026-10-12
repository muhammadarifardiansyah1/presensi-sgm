<?php
/**
 * Cetak Laporan Gaji Resmi Format RSUD RA Basoeni / SGM
 * A4 Landscape Print View
 */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Gaji RSUD RA Basoeni - <?= htmlspecialchars($periodeText) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --text-dark: #1e293b;
            --border-dark: #334155;
            --border-light: #cbd5e1;
            --bg-header: #f1f5f9;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 11pt;
            color: var(--text-dark);
            background-color: #f8fafc;
            padding: 24px;
        }

        /* Non-printable action toolbar */
        .no-print-toolbar {
            max-width: 1100px;
            margin: 0 auto 20px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            font-size: 0.9rem;
            font-weight: 600;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.15s ease;
        }

        .btn-print {
            background-color: #2563eb;
            color: #ffffff;
        }
        .btn-print:hover {
            background-color: #1d4ed8;
        }

        .btn-back {
            background-color: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
        }
        .btn-back:hover {
            background-color: #e2e8f0;
        }

        /* Printable Paper Container */
        .paper-sheet {
            max-width: 1100px;
            margin: 0 auto;
            background: #ffffff;
            padding: 32px 36px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -2px rgba(0,0,0,0.05);
            border-radius: 4px;
        }

        /* Header Document */
        .header-doc {
            text-align: center;
            margin-bottom: 24px;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 14px;
        }

        .header-doc h1 {
            font-size: 16pt;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 4px;
            color: #0f172a;
        }

        .header-doc h2 {
            font-size: 12pt;
            font-weight: 600;
            color: #334155;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .header-doc .periode-badge {
            display: inline-block;
            font-size: 10pt;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        /* Table */
        table.report-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5pt;
            margin-bottom: 28px;
        }

        table.report-table th,
        table.report-table td {
            border: 1px solid #64748b;
            padding: 6px 8px;
            vertical-align: middle;
        }

        table.report-table th {
            background-color: var(--bg-header);
            font-weight: 700;
            text-align: center;
            text-transform: uppercase;
            font-size: 9pt;
            letter-spacing: 0.3px;
        }

        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .font-bold { font-weight: 700; }
        .tabular-nums { font-variant-numeric: tabular-nums; }

        tr.row-total td {
            background-color: #f8fafc;
            font-weight: 700;
            font-size: 10pt;
            border-top: 2px solid #0f172a;
        }

        /* Signatures Section */
        .signature-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            text-align: center;
            margin-top: 36px;
            page-break-inside: avoid;
        }

        .signature-box {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .signature-box .role-title {
            font-size: 9.5pt;
            color: #475569;
            margin-bottom: 2px;
        }

        .signature-box .position-title {
            font-size: 10pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 60px; /* Space for physical signature */
        }

        .signature-box .signer-name {
            font-size: 10pt;
            font-weight: 700;
            text-decoration: underline;
            text-underline-offset: 4px;
        }

        /* Print Media Styles */
        @media print {
            @page {
                size: A4 landscape;
                margin: 12mm 10mm;
            }

            body {
                background: #ffffff;
                padding: 0;
                font-size: 9.5pt;
            }

            .no-print-toolbar {
                display: none !important;
            }

            .paper-sheet {
                box-shadow: none;
                padding: 0;
                margin: 0;
                max-width: 100%;
            }

            table.report-table {
                font-size: 8.5pt;
            }

            table.report-table th,
            table.report-table td {
                border-color: #000000;
                padding: 5px 6px;
            }

            .signature-box .position-title {
                margin-bottom: 50px;
            }
        }
    </style>
</head>
<body>

    <!-- Non-Printable Action Toolbar -->
    <div class="no-print-toolbar">
        <div>
            <a href="<?= Config::baseUrl("gaji?bulan={$bulan}&tahun={$tahun}") ?>" class="btn-action btn-back">
                &larr; Kembali ke Rekap Payroll
            </a>
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <span style="font-size: 0.85rem; color: #64748b;">Format Cetak A4 Landscape</span>
            <button onclick="window.print()" class="btn-action btn-print">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                    <rect x="6" y="14" width="12" height="8"></rect>
                </svg>
                <span>Cetak / Simpan PDF</span>
            </button>
        </div>
    </div>

    <!-- Printable Paper Sheet -->
    <div class="paper-sheet">
        <!-- Header -->
        <div class="header-doc">
            <h1>LAPORAN GAJI KARYAWAN</h1>
            <h2>RSUD RA BASOENI &bull; SURYA GEMILANG MOJOPAHIT</h2>
            <div class="periode-badge">BULAN: <?= htmlspecialchars($periodeText) ?></div>
        </div>

        <?php
        $totalPokok = 0;
        $totalPotAbsen = 0;
        $totalPenggantiLibur = 0;
        $totalLemburRp = 0;
        $totalLainLain = 0;
        $totalDiterima = 0;

        foreach ($rekapPayroll as $p) {
            $totalPokok += $p['gaji_pokok'];
            $totalPotAbsen += $p['potongan_alpa'];
            $totalPenggantiLibur += $p['uang_pengganti_libur'] ?? 0;
            $totalLemburRp += $p['uang_lembur'];
            $totalLainLain += $p['lain_lain'] ?? 0;
            $totalDiterima += $p['take_home_pay'];
        }
        ?>

        <!-- Table -->
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 35px;">NO</th>
                    <th style="text-align: left;">NAMA</th>
                    <th style="width: 130px;">JABATAN</th>
                    <th style="width: 55px;">STATUS</th>
                    <th style="width: 105px; text-align: right;">GAJI POKOK</th>
                    <th style="width: 85px; text-align: center;">ABSEN<br><small style="font-weight: normal; font-size: 7.5pt;">(Rp 52.000)</small></th>
                    <th style="width: 95px; text-align: center;">PENGGANTI LIBUR<br><small style="font-weight: normal; font-size: 7.5pt;">(Rp 52.000)</small></th>
                    <th style="width: 90px; text-align: center;">LEMBUR<br><small style="font-weight: normal; font-size: 7.5pt;">(Rp 20.000)</small></th>
                    <th style="width: 95px; text-align: right;">LAIN-LAIN</th>
                    <th style="width: 120px; text-align: right; background: #e2e8f0;">TOTAL GAJI DITERIMA</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rekapPayroll)): ?>
                    <tr>
                        <td colspan="10" class="text-center" style="padding: 24px; color: #64748b;">
                            Tidak ada data karyawan untuk periode <?= htmlspecialchars($periodeText) ?>.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($rekapPayroll as $row): ?>
                        <tr>
                            <td class="text-center tabular-nums"><?= $no++ ?></td>
                            <td class="text-left font-bold"><?= htmlspecialchars(strtoupper($row['nama'])) ?></td>
                            <td class="text-left"><?= htmlspecialchars(strtoupper($row['jabatan'])) ?></td>
                            <td class="text-center font-bold"><?= htmlspecialchars($row['status_gender'] ?? 'L') ?></td>
                            <td class="text-right tabular-nums">Rp <?= number_format($row['gaji_pokok'], 0, ',', '.') ?></td>
                            <td class="text-center tabular-nums">
                                <?= (int)($row['alpa'] ?? 0) ?>
                                <?php if (($row['potongan_alpa'] ?? 0) > 0): ?>
                                    <div style="font-size: 7.5pt; color: #dc2626;">(-Rp <?= number_format($row['potongan_alpa'], 0, ',', '.') ?>)</div>
                                <?php endif; ?>
                            </td>
                            <td class="text-center tabular-nums">
                                <?= (int)($row['pengganti_libur_hari'] ?? 0) ?>
                                <?php if (($row['uang_pengganti_libur'] ?? 0) > 0): ?>
                                    <div style="font-size: 7.5pt; color: #16a34a;">(+Rp <?= number_format($row['uang_pengganti_libur'], 0, ',', '.') ?>)</div>
                                <?php endif; ?>
                            </td>
                            <td class="text-center tabular-nums">
                                <?= (float)($row['jam_lembur'] ?? 0) ?> jam
                                <?php if (($row['uang_lembur'] ?? 0) > 0): ?>
                                    <div style="font-size: 7.5pt; color: #2563eb;">(+Rp <?= number_format($row['uang_lembur'], 0, ',', '.') ?>)</div>
                                <?php endif; ?>
                            </td>
                            <td class="text-right tabular-nums">
                                <?php if (($row['lain_lain'] ?? 0) > 0): ?>
                                    Rp <?= number_format($row['lain_lain'], 0, ',', '.') ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td class="text-right font-bold tabular-nums" style="background: rgba(16, 185, 129, 0.05);">
                                Rp <?= number_format($row['take_home_pay'], 0, ',', '.') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="row-total">
                    <td colspan="4" class="text-center font-bold">TOTAL PENGELUARAN GAJI</td>
                    <td class="text-right tabular-nums">Rp <?= number_format($totalPokok, 0, ',', '.') ?></td>
                    <td class="text-center tabular-nums" style="color: #dc2626;">-Rp <?= number_format($totalPotAbsen, 0, ',', '.') ?></td>
                    <td class="text-center tabular-nums" style="color: #16a34a;">+Rp <?= number_format($totalPenggantiLibur, 0, ',', '.') ?></td>
                    <td class="text-center tabular-nums" style="color: #2563eb;">+Rp <?= number_format($totalLemburRp, 0, ',', '.') ?></td>
                    <td class="text-right tabular-nums"><?= $totalLainLain > 0 ? 'Rp ' . number_format($totalLainLain, 0, ',', '.') : '-' ?></td>
                    <td class="text-right font-bold tabular-nums" style="background: #e2e8f0; font-size: 10.5pt;">
                        Rp <?= number_format($totalDiterima, 0, ',', '.') ?>
                    </td>
                </tr>
            </tfoot>
        </table>

        <!-- Signatures (3 Kolom Pengesahan) -->
        <div class="signature-grid">
            <div class="signature-box">
                <div class="role-title">Dibuat oleh,</div>
                <div class="position-title">KOORDINATOR</div>
                <div class="signer-name">( <?= htmlspecialchars(strtoupper($penandatanganKoordinator)) ?> )</div>
            </div>
            <div class="signature-box">
                <div class="role-title">Diperiksa oleh,</div>
                <div class="position-title">SUPERVISOR</div>
                <div class="signer-name">( <?= htmlspecialchars(strtoupper($penandatanganSupervisor)) ?> )</div>
            </div>
            <div class="signature-box">
                <div class="role-title">Diketahui oleh,</div>
                <div class="position-title">MANAJER OPERASIONAL</div>
                <div class="signer-name">( <?= htmlspecialchars(strtoupper($penandatanganManajer)) ?> )</div>
            </div>
        </div>
    </div>

</body>
</html>
