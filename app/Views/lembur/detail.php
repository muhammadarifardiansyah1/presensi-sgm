<?php
/**
 * Penugasan Lembur - Detail & Audit Pegawai View
 * Absensi Surya Gemilang Mojopahit (SGM)
 */

$pageTitle = 'Detail SPL ' . htmlspecialchars($penugasan['nomor_sp_lembur']) . ' - ' . Config::APP_NAME;
$activePage = 'lembur';
require_once __DIR__ . '/../layouts/header.php';

$tglFmt = date('d F Y', strtotime($penugasan['tanggal']));
$jamMulaiFmt = substr($penugasan['jam_mulai'], 0, 5);
$jamSelesaiFmt = substr($penugasan['jam_selesai'], 0, 5);
$badgeCls = ($penugasan['status'] === 'aktif') ? 'badge-success' : (($penugasan['status'] === 'selesai') ? 'badge-info' : 'badge-danger');

// Kalkulasi statistik pegawai penugasan
$totalDitugaskan = count($pegawaiList);
$totalSelesai = 0;
$totalJamAktual = 0.0;

foreach ($pegawaiList as $p) {
    if (!empty($p['jam_lembur_presensi']) || !empty($p['jam_lembur_aktual'])) {
        $totalSelesai++;
    }
    $totalJamAktual += (float)($p['jam_lembur_presensi'] ?? $p['jam_lembur_aktual'] ?? 0);
}
?>

<div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
    <div>
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px; font-size: 0.85rem; color: var(--text-muted);">
            <a href="<?= Config::baseUrl('lembur') ?>" style="color: var(--accent-blue); text-decoration: none; display: inline-flex; align-items: center; gap: 4px; font-weight: 600;">
                <?= Icon::get('arrow-left', 14) ?> Penugasan Lembur
            </a>
            <span>/</span>
            <span style="font-family: 'JetBrains Mono', monospace; font-weight: 600; color: var(--text-main);"><?= htmlspecialchars($penugasan['nomor_sp_lembur']) ?></span>
            <span class="badge <?= $badgeCls ?>" style="font-size: 0.74rem; text-transform: uppercase; margin-left: 6px;">
                <?= htmlspecialchars($penugasan['status']) ?>
            </span>
        </div>
        <h2 style="font-size: 1.4rem; font-weight: 800; color: var(--text-main); margin: 0; display: flex; align-items: center; gap: 8px;">
            <?= Icon::get('file-text', 22) ?> <?= htmlspecialchars($penugasan['nomor_sp_lembur']) ?>
        </h2>
    </div>
    
    <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
        <a href="<?= Config::baseUrl('lembur') ?>" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 8px; font-weight: 600;">
            <?= Icon::get('arrow-left', 16) ?> <span>Kembali</span>
        </a>
        <a href="<?= Config::baseUrl('lembur/edit?id=' . $penugasan['id']) ?>" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 6px;">
            <?= Icon::get('edit', 16) ?> <span>Edit Penugasan</span>
        </a>
        <a href="<?= Config::baseUrl('lembur/hapus?id=' . $penugasan['id']) ?>" 
           class="btn btn-danger" 
           style="display: inline-flex; align-items: center; gap: 6px;"
           data-turbo="false"
           data-confirm="Apakah Anda yakin ingin menghapus Surat Perintah Lembur <?= htmlspecialchars($penugasan['nomor_sp_lembur']) ?>?"
           data-confirm-title="Hapus Penugasan Lembur"
           data-confirm-type="delete"
           data-confirm-btn="Ya, Hapus">
            <?= Icon::get('trash', 16) ?> <span>Hapus</span>
        </a>
    </div>
</div>

<!-- Info Alert: Sistem Proteksi Anti-Curang Lembur -->
<div class="alert alert-info" style="margin-bottom: 24px; display: flex; align-items: flex-start; gap: 12px;">
    <div style="font-size: 1.25rem; line-height: 1;"><?= Icon::get('shield-check', 20) ?></div>
    <div style="font-size: 0.86rem; line-height: 1.5;">
        <strong>Sistem Proteksi Jam Lembur Aktif:</strong> Uang lembur hanya akan diakui dan dicatat ke dalam slip gaji untuk pegawai yang terdaftar pada SPL ini. Jam lembur presensi keluar dibatasi maksimal <strong><?= (float)$penugasan['maksimal_jam'] ?> jam</strong>, sehingga pegawai yang sengaja menunda kepulangan tidak akan mendapatkan uang lembur berlebih.
    </div>
</div>

<!-- Ringkasan Informasi SPL -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 18px; margin-bottom: 24px;">
    <!-- Box 1: Jadwal & Waktu -->
    <div class="card" style="padding: 20px;">
        <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
            <?= Icon::get('calendar', 14) ?> Jadwal & Waktu Lembur
        </div>
        <div style="font-size: 1.05rem; font-weight: 700; color: var(--text-main); margin-bottom: 4px;">
            <?= $tglFmt ?>
        </div>
        <div style="font-size: 0.95rem; font-weight: 700; color: var(--accent-blue); font-family: 'JetBrains Mono', monospace; margin-bottom: 6px;">
            <?= $jamMulaiFmt ?> - <?= $jamSelesaiFmt ?> WIB
        </div>
        <div style="font-size: 0.82rem; color: var(--text-muted);">
            Batas Maksimal Pengakuan: <strong style="color: var(--text-main);"><?= (float)$penugasan['maksimal_jam'] ?> Jam</strong>
        </div>
    </div>

    <!-- Box 2: Lokasi Penugasan -->
    <div class="card" style="padding: 20px;">
        <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
            <?= Icon::get('map-pin', 14) ?> Lokasi Kerja
        </div>
        <div style="font-size: 1.05rem; font-weight: 700; color: var(--text-main); margin-bottom: 4px;">
            <?= htmlspecialchars($penugasan['nama_lokasi'] ?? 'Kantor Utama') ?>
        </div>
        <div style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 6px;">
            <?= htmlspecialchars($penugasan['alamat_lokasi'] ?? '-') ?>
        </div>
        <div style="font-size: 0.82rem; color: var(--text-muted);">
            Jam Pulang Reguler: <strong style="color: var(--text-main);"><?= !empty($penugasan['jam_pulang_lokasi']) ? substr($penugasan['jam_pulang_lokasi'], 0, 5) . ' WIB' : '17:00 WIB' ?></strong>
        </div>
    </div>

    <!-- Box 3: Ringkasan Pegawai & Dibuat Oleh -->
    <div class="card" style="padding: 20px;">
        <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
            <?= Icon::get('user-check', 14) ?> Realisasi Presensi
        </div>
        <div style="display: flex; align-items: baseline; gap: 8px; margin-bottom: 4px;">
            <span style="font-size: 1.5rem; font-weight: 800; color: var(--text-main); font-family: 'JetBrains Mono', monospace;">
                <?= $totalSelesai ?> / <?= $totalDitugaskan ?>
            </span>
            <span style="font-size: 0.84rem; color: var(--text-muted);">Pegawai Selesai Lembur</span>
        </div>
        <div style="font-size: 0.84rem; color: var(--text-muted); margin-bottom: 4px;">
            Total Jam Aktual: <strong style="color: var(--success); font-family: 'JetBrains Mono', monospace;"><?= number_format($totalJamAktual, 2) ?> Jam</strong>
        </div>
        <div style="font-size: 0.78rem; color: var(--text-muted); border-top: 1px solid var(--border-color); padding-top: 6px; margin-top: 6px;">
            Dibuat oleh: <strong><?= htmlspecialchars($penugasan['nama_creator'] ?? 'Admin') ?></strong>
            <span style="margin-left: 6px;">(<?= date('d/m/Y H:i', strtotime($penugasan['created_at'])) ?>)</span>
        </div>
    </div>
</div>

<!-- Uraian Tugas -->
<div class="card" style="padding: 20px; margin-bottom: 24px;">
    <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px; display: flex; align-items: center; gap: 6px;">
        <?= Icon::get('align-left', 14) ?> Uraian Pekerjaan / Alasan Lembur
    </div>
    <div style="font-size: 0.95rem; color: var(--text-main); line-height: 1.6; white-space: pre-line; background: var(--bg-subtle); padding: 14px 18px; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
        <?= htmlspecialchars($penugasan['keterangan']) ?>
    </div>
</div>

<!-- Tabel Audit Pegawai Ditugaskan -->
<div class="table-card">
    <div class="table-toolbar" style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div>
            <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-main); margin: 0 0 4px 0; display: flex; align-items: center; gap: 8px;">
                <?= Icon::get('users', 18) ?> Daftar Pegawai Ditugaskan (<?= count($pegawaiList) ?> Orang)
            </h3>
            <p style="font-size: 0.82rem; color: var(--text-muted); margin: 0;">
                Audit jam presensi masuk, jam pulang, dan jam lembur yang sah diakui oleh sistem.
            </p>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table-custom">
            <thead>
                <tr>
                    <th style="width: 45px; text-align: center;">No</th>
                    <th>Pegawai</th>
                    <th>Jabatan</th>
                    <th style="text-align: center;">Status SPL</th>
                    <th style="text-align: center;">Presensi Masuk</th>
                    <th style="text-align: center;">Presensi Pulang</th>
                    <th style="text-align: right;">Total Jam Kerja</th>
                    <th style="text-align: right;">Jam Lembur Diakui</th>
                    <th style="text-align: right;">Estimasi Upah</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pegawaiList)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 36px 16px; color: var(--text-muted);">
                            Tidak ada pegawai yang ditugaskan pada SPL ini.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $no = 1; 
                    $maksimalJam = (float)$penugasan['maksimal_jam'];
                    $avatarGradients = [
                        'linear-gradient(135deg, #3b82f6, #1d4ed8)', // Biru
                        'linear-gradient(135deg, #10b981, #047857)', // Hijau zamrud
                        'linear-gradient(135deg, #8b5cf6, #6d28d9)', // Ungu
                        'linear-gradient(135deg, #f59e0b, #b45309)', // Amber emas
                        'linear-gradient(135deg, #ec4899, #be185d)', // Rose
                        'linear-gradient(135deg, #06b6d4, #0e7490)', // Cyan
                        'linear-gradient(135deg, #6366f1, #4338ca)', // Indigo
                    ];

                    foreach ($pegawaiList as $peg): 
                        $userPhoto = $peg['foto_profil'] ?? null;
                        $photoPath = __DIR__ . '/../../../assets/images/uploads/profil/' . $userPhoto;
                        $hasPhoto = !empty($userPhoto) && file_exists($photoPath);
                        $inisial = strtoupper(substr($peg['nama_pegawai'] ?? 'U', 0, 1));
                        
                        $colorIdx = abs(crc32($peg['nama_pegawai'] ?? '')) % count($avatarGradients);
                        $avatarBg = $avatarGradients[$colorIdx];
                        
                        $jamMasukFmt = !empty($peg['jam_masuk']) ? substr($peg['jam_masuk'], 0, 5) . ' WIB' : '-';
                        $jamPulangFmt = !empty($peg['jam_pulang']) ? substr($peg['jam_pulang'], 0, 5) . ' WIB' : '-';
                        
                        $jamLemburPresensi = (float)($peg['jam_lembur_presensi'] ?? $peg['jam_lembur_aktual'] ?? 0);
                        $isCapped = ($jamLemburPresensi >= $maksimalJam && $jamLemburPresensi > 0);
                        $estimasiUpah = $jamLemburPresensi * 25000; // Standar 25.000/jam
                    ?>
                        <tr>
                            <td style="text-align: center; font-weight: 600; color: var(--text-muted);"><?= $no++ ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 36px; height: 36px; border-radius: 50%; background: <?= $avatarBg ?>; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem; overflow: hidden; flex-shrink: 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 2px solid var(--border-color);">
                                        <?php if ($hasPhoto): ?>
                                            <img src="<?= Config::baseUrl('assets/images/uploads/profil/' . $userPhoto) ?>" 
                                                 alt="Foto" 
                                                 style="width: 100%; height: 100%; object-fit: cover; display: block;" 
                                                 onerror="this.style.display='none'; this.parentElement.innerText='<?= $inisial ?>';">
                                        <?php else: ?>
                                            <?= $inisial ?>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div style="font-weight: 700; color: var(--text-main); font-size: 0.88rem;">
                                            <?= htmlspecialchars($peg['nama_pegawai']) ?>
                                        </div>
                                        <div style="font-size: 0.76rem; color: var(--text-muted); font-family: 'JetBrains Mono', monospace;">
                                            NIK: <?= htmlspecialchars($peg['nik']) ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-secondary" style="font-size: 0.78rem;">
                                    <?= htmlspecialchars($peg['jabatan'] ?? 'Staff') ?>
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <?php if (!empty($peg['jam_pulang'])): ?>
                                    <span class="badge badge-success" style="font-size: 0.78rem;">Selesai Lembur</span>
                                <?php elseif (!empty($peg['jam_masuk'])): ?>
                                    <span class="badge badge-info" style="font-size: 0.78rem;">Sedang Bekerja</span>
                                <?php else: ?>
                                    <span class="badge badge-warning" style="font-size: 0.78rem;">Belum Presensi</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center; font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; color: var(--text-main);">
                                <?= $jamMasukFmt ?>
                            </td>
                            <td style="text-align: center; font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; color: var(--text-main);">
                                <?= $jamPulangFmt ?>
                            </td>
                            <td style="text-align: right; font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; color: var(--text-muted);">
                                <?= !empty($peg['total_jam_kerja']) ? htmlspecialchars($peg['total_jam_kerja']) : '-' ?>
                            </td>
                            <td style="text-align: right;">
                                <div style="font-weight: 700; font-family: 'JetBrains Mono', monospace; font-size: 0.9rem; color: <?= $jamLemburPresensi > 0 ? 'var(--accent-blue)' : 'var(--text-muted)' ?>;">
                                    <?= number_format($jamLemburPresensi, 2) ?> Jam
                                </div>
                                <?php if ($isCapped): ?>
                                    <span style="font-size: 0.7rem; color: var(--warning); display: block;" title="Dibatasi sesuai maksimal penugasan SPL">
                                        (Maks SPL)
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right; font-weight: 700; font-family: 'JetBrains Mono', monospace; font-size: 0.88rem; color: <?= $estimasiUpah > 0 ? 'var(--success)' : 'var(--text-muted)' ?>;">
                                Rp <?= number_format($estimasiUpah, 0, ',', '.') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Navigasi Bawah: Tombol Kembali & Aksi Cepat -->
<div style="margin-top: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
    <a href="<?= Config::baseUrl('lembur') ?>" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 8px; font-weight: 600;">
        <?= Icon::get('arrow-left', 16) ?> <span>Kembali ke Daftar Penugasan Lembur</span>
    </a>
    <div style="display: flex; gap: 10px;">
        <a href="<?= Config::baseUrl('lembur/edit?id=' . $penugasan['id']) ?>" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 6px;">
            <?= Icon::get('edit', 16) ?> <span>Edit Penugasan</span>
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
