<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rekap Presensi Harian - CV. Surya Gemilang Mojopahit</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            font-family: Arial, sans-serif;
            font-size: 11pt;
        }
        th, td {
            border: 1px solid #000000;
            padding: 8px;
            text-align: left;
            vertical-align: middle;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
        }
        .text-center { text-align: center; }
        .title-header { font-size: 14pt; font-weight: bold; }
    </style>
</head>
<body>

<table>
    <tr>
        <td colspan="9" class="title-header">REKAP PRESENSI HARIAN - CV. SURYA GEMILANG MOJOPAHIT</td>
    </tr>
    <tr>
        <td colspan="9"><strong>Periode:</strong> <?= $tanggalMulai ?><?= ($tanggalMulai !== $tanggalSelesai) ? ' s/d ' . $tanggalSelesai : '' ?></td>
    </tr>
    <tr>
        <td colspan="9">&nbsp;</td>
    </tr>
    <thead>
        <tr>
            <th style="width: 40px;">NO.</th>
            <th>NAMA</th>
            <th>TANGGAL</th>
            <th>JAM MASUK</th>
            <th>FOTO MASUK</th>
            <th>JAM PULANG</th>
            <th>FOTO KELUAR</th>
            <th>TOTAL JAM</th>
            <th>TOTAL TERLAMBAT</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($dataPresensi)): ?>
            <tr>
                <td colspan="9" class="text-center">Data rekap presensi masih kosong!</td>
            </tr>
        <?php else: ?>
            <?php $no = 1; foreach ($dataPresensi as $row): ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td><?= htmlspecialchars($row['nama_pegawai']) ?> (NIK: <?= htmlspecialchars($row['nik']) ?>)</td>
                    <td class="text-center"><?= $row['tanggal'] ?></td>
                    <td class="text-center"><?= $row['jam_masuk'] ?? '-' ?></td>
                    <td class="text-center"><?= !empty($row['foto_masuk']) ? 'Ada' : '-' ?></td>
                    <td class="text-center"><?= $row['jam_pulang'] ?? '-' ?></td>
                    <td class="text-center"><?= !empty($row['foto_keluar']) ? 'Ada' : '-' ?></td>
                    <td class="text-center"><?= $row['total_jam_kerja'] ?? '-' ?></td>
                    <td class="text-center"><?= $row['total_terlambat'] ?? 'Tepat Waktu' ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>
