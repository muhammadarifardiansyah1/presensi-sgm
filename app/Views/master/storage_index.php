<?php
$pageTitle = 'Manajemen Penyimpanan & Retensi Foto - ' . Config::APP_NAME;
$activePage = 'master_storage';
require_once __DIR__ . '/../layouts/header.php';
?>

<div style="margin-bottom: 24px; display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px;">
    <div>
        <h2 style="font-size: 1.45rem; font-weight: 700; color: var(--text-main); margin-bottom: 4px; display:flex; align-items:center; gap:8px;">
            <?= Icon::get('database', 22) ?> Manajemen Penyimpanan Foto & Retensi Disk
        </h2>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0;">
            Pantau kapasitas ruang disk foto presensi dan jalankan kebijakan retensi berkas lampau secara aman.
        </p>
    </div>
    <div>
        <a href="<?= Config::baseUrl('master/storage') ?>" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:6px;">
            <?= Icon::get('rotate-ccw', 15) ?> Segarkan Statistik
        </a>
    </div>
</div>

<?php if (!empty($lastResult)): ?>
    <?php if (!empty($lastResult['success']) && !empty($lastResult['dry_run'])): ?>
        <div class="alert alert-info" style="border-left: 4px solid var(--primary); margin-bottom: 24px;">
            <div style="font-weight: 700; margin-bottom: 4px; font-size: 0.95rem;">
                ℹ️ Hasil Simulasi Pembersihan (Dry-Run)
            </div>
            <div style="font-size: 0.9rem; line-height: 1.5;">
                Ditemukan <strong><?= number_format($lastResult['files_count']) ?> berkas foto</strong> dari 
                <strong><?= number_format($lastResult['records_affected']) ?> baris presensi</strong> yang lebih tua dari 
                <strong><?= $lastResult['days_threshold'] ?> hari</strong> (sebelum tanggal <?= $lastResult['cutoff_date'] ?>).<br>
                Estimasi ruang disk yang dapat dihemat: <strong><?= $lastResult['mb_freed'] ?> MB</strong> (<?= number_format($lastResult['bytes_freed']) ?> bytes).
                <div style="margin-top: 6px; font-size: 0.82rem; color: var(--text-muted);">
                    *Ini hanya simulasi. Berkas fisik dan database belum diubah.
                </div>
            </div>
        </div>
    <?php elseif (!empty($lastResult['success']) && empty($lastResult['dry_run'])): ?>
        <div class="alert alert-success" style="border-left: 4px solid #10b981; margin-bottom: 24px;">
            <div style="font-weight: 700; margin-bottom: 4px; font-size: 0.95rem;">
                ✅ Pembersihan Berkas Fisik Berhasil Dijalankan!
            </div>
            <div style="font-size: 0.9rem; line-height: 1.5;">
                Berhasil menghapus <strong><?= number_format($lastResult['files_count']) ?> berkas foto</strong> lama 
                (sebelum tanggal <?= $lastResult['cutoff_date'] ?>) dan membebaskan 
                <strong><?= $lastResult['mb_freed'] ?> MB</strong> ruang disk server.<br>
                Subfolder kosong yang dirapikan: <strong><?= $lastResult['pruned_dirs'] ?> direktori</strong>.<br>
                <div style="margin-top: 6px; font-size: 0.82rem; color: #065f46;">
                    *Seluruh data teks kehadiran, jam kerja, lembur, dan status di database tetap utuh dan aman.
                </div>
            </div>
        </div>
    <?php elseif (!empty($lastResult['error'])): ?>
        <div class="alert alert-danger" style="margin-bottom: 24px;">
            <strong>Gagal:</strong> <?= htmlspecialchars($lastResult['error']) ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- KPI STATS CARDS -->
<div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <div class="stat-card" style="background: var(--bg-card); padding: 18px; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <span style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px;">Ukuran Disk Foto</span>
                <div style="font-size: 1.6rem; font-weight: 700; color: var(--text-main); margin-top: 6px;">
                    <?= $stats['disk_total_mb'] ?> <span style="font-size: 0.95rem; font-weight: 500;">MB</span>
                </div>
                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">
                    (≈ <?= $stats['disk_total_gb'] ?> GB)
                </div>
            </div>
            <div style="background: rgba(37, 99, 235, 0.1); color: var(--primary); padding: 10px; border-radius: 10px;">
                <?= Icon::get('hard-drive', 24) ?>
            </div>
        </div>
    </div>

    <div class="stat-card" style="background: var(--bg-card); padding: 18px; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <span style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px;">Total File Foto</span>
                <div style="font-size: 1.6rem; font-weight: 700; color: var(--text-main); margin-top: 6px;">
                    <?= number_format($stats['disk_total_files']) ?> <span style="font-size: 0.95rem; font-weight: 500;">berkas</span>
                </div>
                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">
                    Format JPG/JPEG terpartisi
                </div>
            </div>
            <div style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 10px; border-radius: 10px;">
                <?= Icon::get('camera', 24) ?>
            </div>
        </div>
    </div>

    <div class="stat-card" style="background: var(--bg-card); padding: 18px; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <span style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px;">Foto Masuk / Keluar (DB)</span>
                <div style="font-size: 1.6rem; font-weight: 700; color: var(--text-main); margin-top: 6px;">
                    <?= number_format($stats['db_foto_masuk']) ?> / <?= number_format($stats['db_foto_keluar']) ?>
                </div>
                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">
                    Dari <?= number_format($stats['db_total_presensi']) ?> total presensi
                </div>
            </div>
            <div style="background: rgba(245, 158, 11, 0.1); color: #f59e0b; padding: 10px; border-radius: 10px;">
                <?= Icon::get('clipboard-check', 24) ?>
            </div>
        </div>
    </div>

    <div class="stat-card" style="background: var(--bg-card); padding: 18px; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <span style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px;">Foto &gt; 6 Bulan</span>
                <div style="font-size: 1.6rem; font-weight: 700; color: var(--text-main); margin-top: 6px;">
                    <?= number_format($stats['breakdown_by_age']['180_hari']) ?> <span style="font-size: 0.95rem; font-weight: 500;">data</span>
                </div>
                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">
                    Siap diarsipkan/dibersihkan
                </div>
            </div>
            <div style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 10px; border-radius: 10px;">
                <?= Icon::get('clock', 24) ?>
            </div>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; margin-bottom: 24px;">
    <!-- CARD 1: FORM RETENSI & PEMBERSIHAN -->
    <div class="table-card" style="padding: 22px;">
        <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 12px; display:flex; align-items:center; gap:8px;">
            <?= Icon::get('trash-2', 18) ?> Kebijakan Retensi Berkas Foto
        </h3>
        <p style="font-size: 0.86rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 18px;">
            Hapus berkas fisik gambar selfie presensi yang telah melewati masa retensi untuk mengosongkan kapasitas SSD/Hosting server.
            <strong>Data teks riwayat kehadiran, jam masuk, jam pulang, dan koordinat GPS di database tetap utuh 100%.</strong>
        </p>

        <form action="<?= Config::baseUrl('master/storage/cleanup') ?>" method="POST" id="formCleanup">
            <div style="margin-bottom: 16px;">
                <label for="cleanupDays" style="font-weight: 600; font-size: 0.88rem; display:block; margin-bottom: 6px;">
                    Pilih Ambang Batas Umur Foto:
                </label>
                <select name="days" id="cleanupDays" class="form-select" style="width: 100%; padding: 10px 14px; font-weight: 500;">
                    <option value="90">Lebih Tua dari 3 Bulan (90 Hari) — <?= number_format($stats['breakdown_by_age']['90_hari']) ?> data</option>
                    <option value="180" selected>Lebih Tua dari 6 Bulan (180 Hari) [Direkomendasikan] — <?= number_format($stats['breakdown_by_age']['180_hari']) ?> data</option>
                    <option value="365">Lebih Tua dari 1 Tahun (365 Hari) — <?= number_format($stats['breakdown_by_age']['365_hari']) ?> data</option>
                    <option value="30">Lebih Tua dari 1 Bulan (30 Hari) — <?= number_format($stats['breakdown_by_age']['30_hari']) ?> data</option>
                </select>
            </div>

            <input type="hidden" name="mode" id="cleanupMode" value="simulate">

            <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 20px;">
                <button type="submit" class="btn btn-secondary" onclick="document.getElementById('cleanupMode').value='simulate';" style="flex: 1; display:flex; justify-content:center; align-items:center; gap:6px;">
                    <?= Icon::get('eye', 16) ?> <span>Simulasi (Dry-Run)</span>
                </button>
                <button type="button" class="btn btn-danger" onclick="confirmExecuteCleanup();" style="flex: 1; display:flex; justify-content:center; align-items:center; gap:6px;">
                    <?= Icon::get('trash-2', 16) ?> <span>Bersihkan Foto Fisik</span>
                </button>
            </div>
        </form>
    </div>

    <!-- CARD 2: PANDUAN OTOMASI CRON JOB -->
    <div class="table-card" style="padding: 22px;">
        <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 12px; display:flex; align-items:center; gap:8px;">
            <?= Icon::get('terminal', 18) ?> Otomasi Terjadwal (Cron Job)
        </h3>
        <p style="font-size: 0.86rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 14px;">
            Untuk server produksi, Anda dapat menjadwalkan pembersihan otomatis secara mingguan melalui cPanel Cron Jobs atau Linux Crontab tanpa perlu eksekusi manual:
        </p>

        <div style="background: #1e293b; color: #f8fafc; padding: 12px 14px; border-radius: 8px; font-family: monospace; font-size: 0.82rem; word-break: break-all; margin-bottom: 14px;">
            # Jalankan setiap Minggu jam 02.00 dini hari (retensi 180 hari)<br>
            0 2 * * 0 php <?= addslashes(realpath(__DIR__ . '/../../scripts/cleanup_photos.php') ?: 'scripts/cleanup_photos.php') ?> --days=180 --force
        </div>

        <div style="font-size: 0.82rem; color: var(--text-muted); line-height: 1.4;">
            💡 <strong>Info Keamanan:</strong> Script dilengkapi mekanisme <em>Path-Traversal Protection</em> dan <em>Empty Directory Pruning</em> otomatis sehingga aman dioperasikan secara berkala.
        </div>
    </div>
</div>

<script>
function confirmExecuteCleanup() {
    const sel = document.getElementById('cleanupDays');
    const selectedDays = sel ? sel.options[sel.selectedIndex].text : '';
    
    const message = `
        <div style="font-size: 0.95rem; margin-bottom: 8px;">
            Anda akan menghapus berkas fisik foto presensi dengan kriteria:<br>
            <strong style="color: var(--danger, #ef4444); font-size: 1.05rem;">${selectedDays}</strong>
        </div>
        <div style="background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 8px; padding: 12px; margin-top: 10px; font-size: 0.85rem; text-align: left; line-height: 1.5;">
            <strong style="color: var(--danger, #ef4444);">Catatan Penting:</strong>
            <ul style="margin: 6px 0 0 16px; padding: 0;">
                <li>Berkas fisik foto di disk server akan dihapus <strong>permanen</strong>.</li>
                <li>Seluruh riwayat presensi di database (tanggal, jam, koordinat, status) <strong>TETAP UTUH</strong>.</li>
            </ul>
        </div>
    `;

    if (window.SGMDialog && typeof window.SGMDialog.confirm === 'function') {
        window.SGMDialog.confirm({
            title: 'Pembersihan Berkas Foto Fisik',
            message: message,
            type: 'danger',
            confirmText: 'Ya, Bersihkan Permanen',
            cancelText: 'Batalkan',
            onConfirm: function() {
                document.getElementById('cleanupMode').value = 'execute';
                document.getElementById('formCleanup').submit();
            }
        });
    } else {
        if (confirm("⚠️ PERINGATAN KONFIRMASI PEMBERSIHAN FOTO:\n\nAnda akan menghapus berkas fisik foto presensi dengan kriteria:\n" + selectedDays + "\n\nCatatan Penting:\n- Berkas fisik foto di disk server akan dihapus permanen.\n- Seluruh riwayat presensi di database (tanggal, jam, koordinat, status) TETAP UTUH.\n\nApakah Anda yakin ingin melanjutkan eksekusi nyata?")) {
            document.getElementById('cleanupMode').value = 'execute';
            document.getElementById('formCleanup').submit();
        }
    }
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
