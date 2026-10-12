<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Presensi Bulanan - <?= $bulanText ?> <?= $tahun ?></title>
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
        .img-cell {
            width: 70px;
            height: 70px;
            object-fit: cover;
            display: block;
            margin: 0 auto;
        }
    </style>
</head>
<body>

<table>
    <tr>
        <td colspan="11" class="title-header">REKAP PRESENSI BULANAN - CV. SURYA GEMILANG MOJOPAHIT</td>
    </tr>
    <tr>
        <td colspan="11"><strong>Bulan:</strong> <?= $bulan ?> (<?= $bulanText ?>)</td>
    </tr>
    <tr>
        <td colspan="11"><strong>Tahun:</strong> <?= $tahun ?></td>
    </tr>
    <tr>
        <td colspan="11">&nbsp;</td>
    </tr>
    <thead>
        <tr>
            <th style="width: 40px;">NO</th>
            <th>NAMA</th>
            <th>NIP</th>
            <th>TANGGAL MASUK</th>
            <th>JAM MASUK</th>
            <th style="width: 80px;">FOTO MASUK</th>
            <th>TANGGAL KELUAR</th>
            <th>JAM KELUAR</th>
            <th style="width: 80px;">FOTO KELUAR</th>
            <th>TOTAL JAM KERJA</th>
            <th>TOTAL JAM TERLAMBAT</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($dataPresensi)): ?>
            <tr>
                <td colspan="11" class="text-center">Tidak ada data presensi pada periode ini.</td>
            </tr>
        <?php else: ?>
            <?php $no = 1; foreach ($dataPresensi as $row): ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td><?= htmlspecialchars($row['nama_pegawai']) ?></td>
                    <td><?= htmlspecialchars($row['nik']) ?></td>
                    <td><?= htmlspecialchars($row['tanggal']) ?></td>
                    <td class="text-center"><?= htmlspecialchars($row['jam_masuk'] ?? '-') ?></td>
                    <td class="text-center">
                        <?php if (!empty($row['foto_masuk'])): ?>
                            <a href="<?= ImageHelper::getPhotoUrl($row['foto_masuk'], 'presensi') ?>" target="_blank" style="color: #2563eb; text-decoration: underline;">Lihat Foto</a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td><?= !empty($row['jam_pulang']) ? htmlspecialchars($row['tanggal']) : '-' ?></td>
                    <td class="text-center"><?= htmlspecialchars($row['jam_pulang'] ?? '-') ?></td>
                    <td class="text-center">
                        <?php if (!empty($row['foto_keluar'])): ?>
                            <a href="<?= ImageHelper::getPhotoUrl($row['foto_keluar'], 'presensi') ?>" target="_blank" style="color: #2563eb; text-decoration: underline;">Lihat Foto</a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td class="text-center"><?= htmlspecialchars($row['total_jam_kerja'] ?? '-') ?></td>
                    <td class="text-center"><?= htmlspecialchars($row['total_terlambat'] ?? 'Tepat Waktu') ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>
