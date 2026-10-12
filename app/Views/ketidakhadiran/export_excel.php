<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rekap Data Ketidakhadiran - CV. Surya Gemilang Mojopahit</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            font-family: Arial, sans-serif;
            font-size: 10pt;
        }
        th, td {
            border: 1px solid #000000;
            padding: 7px 10px;
            text-align: left;
            vertical-align: middle;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .title-header { 
            font-size: 13pt; 
            font-weight: bold; 
            text-align: center;
        }
        .badge-sakit { color: #dc2626; font-weight: bold; }
        .badge-cuti { color: #d97706; font-weight: bold; }
        .badge-izin { color: #2563eb; font-weight: bold; }
        .status-disetujui { color: #16a34a; font-weight: bold; }
        .status-ditolak { color: #dc2626; font-weight: bold; }
        .status-pending { color: #d97706; font-weight: bold; }
    </style>
</head>
<body>

<table>
    <tr>
        <td colspan="13" class="title-header">REKAP DATA KETIDAK HADIRAN (IZIN / SAKIT / CUTI)</td>
    </tr>
    <tr>
        <td colspan="13" style="text-align:center; font-weight:bold; font-size:11pt;">CV. SURYA GEMILANG MOJOPAHIT</td>
    </tr>
    <tr>
        <td colspan="13" style="font-size:9pt; color:#555555;">
            <strong>Waktu Ekspor:</strong> <?= date('d M Y, H:i') ?> WIB |
            <strong>Filter Kategori:</strong> <?= !empty($kategoriFilter) ? strtoupper(htmlspecialchars($kategoriFilter)) : 'Semua Kategori' ?> |
            <strong>Filter Status:</strong> <?= !empty($statusFilter) ? strtoupper(htmlspecialchars($statusFilter)) : 'Semua Status' ?>
        </td>
    </tr>
    <tr>
        <td colspan="13">&nbsp;</td>
    </tr>
    <thead>
        <tr>
            <th style="width: 40px;">NO.</th>
            <th>NIK</th>
            <th>NAMA PEGAWAI</th>
            <th>JABATAN</th>
            <th>KATEGORI</th>
            <th>TANGGAL MULAI</th>
            <th>TANGGAL SELESAI</th>
            <th style="width: 70px;">DURASI</th>
            <th>KETERANGAN / ALASAN</th>
            <th>STATUS VERIFIKASI</th>
            <th>CATATAN ADMIN</th>
            <th>DOKUMEN LAMPIRAN</th>
            <th>WAKTU PENGAJUAN</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($dataIzin)): ?>
            <tr>
                <td colspan="13" class="text-center" style="padding: 20px; font-style: italic;">
                    Tidak ada data ketidakhadiran yang sesuai dengan filter.
                </td>
            </tr>
        <?php else: ?>
            <?php $no = 1; foreach ($dataIzin as $row): 
                $d1 = new DateTime($row['tanggal_mulai']);
                $d2 = new DateTime($row['tanggal_selesai']);
                $durasi = $d1->diff($d2)->days + 1;
                $kategoriUpper = strtoupper($row['kategori']);
                $kategoriClass = ($row['kategori'] === 'sakit') ? 'badge-sakit' : (($row['kategori'] === 'cuti') ? 'badge-cuti' : 'badge-izin');
            ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td style="mso-number-format:'\@';"><?= htmlspecialchars($row['nik'] ?? '-') ?></td>
                    <td><strong><?= htmlspecialchars($row['nama_pegawai'] ?? '-') ?></strong></td>
                    <td><?= htmlspecialchars($row['jabatan'] ?? '-') ?></td>
                    <td class="text-center <?= $kategoriClass ?>"><?= $kategoriUpper ?></td>
                    <td class="text-center"><?= date('d/m/Y', strtotime($row['tanggal_mulai'])) ?></td>
                    <td class="text-center"><?= date('d/m/Y', strtotime($row['tanggal_selesai'])) ?></td>
                    <td class="text-center"><strong><?= $durasi ?></strong> Hari</td>
                    <td><?= htmlspecialchars($row['keterangan']) ?></td>
                    <td class="text-center">
                        <?php if ($row['status_approval'] === 'disetujui'): ?>
                            <span class="status-disetujui">DISETUJUI</span>
                        <?php elseif ($row['status_approval'] === 'ditolak'): ?>
                            <span class="status-ditolak">DITOLAK</span>
                        <?php else: ?>
                            <span class="status-pending">MENUNGGU</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($row['catatan_admin'] ?? '-') ?></td>
                    <td class="text-center">
                        <?= !empty($row['dokumen_bukti']) ? 'Ada Lampiran' : '-' ?>
                    </td>
                    <td class="text-center">
                        <?= !empty($row['created_at']) ? date('d/m/Y H:i', strtotime($row['created_at'])) : '-' ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>
