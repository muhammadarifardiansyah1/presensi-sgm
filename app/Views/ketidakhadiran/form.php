<?php
$pageTitle = 'Ajukan Ketidakhadiran - ' . Config::APP_NAME;
$activePage = 'ketidakhadiran';
require_once __DIR__ . '/../layouts/header.php';
?>

<div style="margin-bottom: 20px; display:flex; justify-content:space-between; align-items:center;">
    <h2 style="font-size: 1.4rem; font-weight: 700;">Form Pengajuan Izin / Sakit / Cuti</h2>
    <a href="<?= Config::baseUrl('ketidakhadiran') ?>" class="btn btn-secondary btn-sm">← Kembali</a>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger">
        ⚠️ <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="form-card" style="max-width: 650px; margin: 0 auto;">
    <form method="POST" enctype="multipart/form-data" data-turbo="false">
        <div class="form-group">
            <label class="form-label" for="kategori">Kategori Pengajuan *</label>
            <select id="kategori" name="kategori" class="form-select" required>
                <option value="izin">Izin Tidak Masuk</option>
                <option value="sakit">Sakit (Wajib Lampirkan Surat Dokter)</option>
                <option value="cuti">Cuti Tahunan / Khusus</option>
            </select>
        </div>

        <div class="form-row-2col">
            <div class="form-group">
                <label class="form-label" for="tanggal_mulai">Tanggal Mulai *</label>
                <input type="date" id="tanggal_mulai" name="tanggal_mulai" class="form-input" 
                       value="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="tanggal_selesai">Tanggal Selesai *</label>
                <input type="date" id="tanggal_selesai" name="tanggal_selesai" class="form-input" 
                       value="<?= date('Y-m-d') ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="keterangan">Alasan & Keterangan Lengkap *</label>
            <textarea id="keterangan" name="keterangan" class="form-textarea" rows="4" 
                      placeholder="Tuliskan keterangan detail pengajuan..." required></textarea>
        </div>

        <div class="form-group">
            <label class="form-label" for="dokumen">Lampiran Dokumen Bukti (Opsional / Surat Dokter)</label>
            <input type="file" id="dokumen" name="dokumen" class="form-input" accept=".jpg,.jpeg,.png,.webp,.pdf">
            <small style="color:var(--text-muted); font-size:0.8rem; display:block; margin-top:4px;">
                Format yang didukung: Foto JPG, PNG, WEBP (Otomatis dioptimasi) atau Dokumen PDF (Maksimal 2MB).
            </small>
            <div id="file-feedback" style="display:none; font-size:0.82rem; margin-top:6px; padding:8px 12px; border-radius:6px;"></div>
            <div id="file-error" style="color: #ef4444; font-size: 0.8rem; margin-top: 4px; display: none;"></div>
        </div>

        <div style="display:flex; justify-content:flex-end; gap: 12px; margin-top: 24px;">
            <a href="<?= Config::baseUrl('ketidakhadiran') ?>" class="btn btn-secondary">Batal</a>
            <button type="submit" id="btn-submit" class="btn btn-blue">Kirim Pengajuan</button>
        </div>
    </form>
</div>

<script>
(() => {
function initKetidakhadiranForm() {
    const fileInput = document.getElementById('dokumen');
    if (!fileInput) return;
    const fileError = document.getElementById('file-error');
    const fileFeedback = document.getElementById('file-feedback');
    const submitBtn = document.getElementById('btn-submit');
    const form = fileInput.closest('form');

    let isCompressing = false;
    let isSubmitting = false;

    function formatBytes(bytes) {
        if (!bytes || bytes === 0) return '0 B';
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
    }

    // Kompresi gambar sisi klien menggunakan HTML5 Canvas native
    function compressImageFile(file, maxDimension = 1280, quality = 0.78) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onerror = reject;
            reader.onload = () => {
                const img = new Image();
                img.onerror = reject;
                img.onload = () => {
                    let width = img.width;
                    let height = img.height;

                    // Downscale proporsional
                    if (width > maxDimension || height > maxDimension) {
                        if (width >= height) {
                            height = Math.round((height / width) * maxDimension);
                            width = maxDimension;
                        } else {
                            width = Math.round((width / height) * maxDimension);
                            height = maxDimension;
                        }
                    }

                    const canvas = document.createElement('canvas');
                    canvas.width = width;
                    canvas.height = height;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, width, height);

                    canvas.toBlob((blob) => {
                        if (!blob) {
                            reject(new Error("Gagal mengompresi gambar"));
                            return;
                        }
                        const newName = file.name.replace(/\.[^.]+$/, '') + '.jpg';
                        const newFile = new File([blob], newName, { type: 'image/jpeg', lastModified: Date.now() });
                        resolve(newFile);
                    }, 'image/jpeg', quality);
                };
                img.src = reader.result;
            };
            reader.readAsDataURL(file);
        });
    }

    fileInput.addEventListener('change', async () => {
        fileError.style.display = 'none';
        fileError.textContent = '';
        fileFeedback.style.display = 'none';
        fileFeedback.textContent = '';

        if (!fileInput.files || !fileInput.files[0]) return;

        const file = fileInput.files[0];
        const ext = file.name.split('.').pop().toLowerCase();
        const validExts = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];

        if (!validExts.includes(ext)) {
            fileError.textContent = '⚠️ Format berkas tidak didukung. Harap pilih berkas foto (JPG, PNG, WEBP) atau dokumen PDF.';
            fileError.style.display = 'block';
            fileInput.value = '';
            return;
        }

        // Penanganan Dokumen PDF
        if (ext === 'pdf') {
            const maxPdfBytes = 2 * 1024 * 1024; // 2MB
            if (file.size > maxPdfBytes) {
                fileError.textContent = `⚠️ Ukuran dokumen PDF terlalu besar (${formatBytes(file.size)})! Batas maksimal adalah 2MB.`;
                fileError.style.display = 'block';
                fileInput.value = '';
                return;
            }
            fileFeedback.className = 'alert-info';
            fileFeedback.style.display = 'block';
            fileFeedback.innerHTML = `📄 Dokumen PDF siap diunggah (Ukuran: <b>${formatBytes(file.size)}</b>).`;
            return;
        }

        // Penanganan Berkas Foto (Auto-Resize jika foto kamera resolusi tinggi / ukuran besar)
        isCompressing = true;
        submitBtn.disabled = true;
        fileFeedback.className = 'alert-info';
        fileFeedback.style.display = 'block';
        fileFeedback.innerHTML = `⏳ Mengoptimasi ukuran foto dokumen (asli: <b>${formatBytes(file.size)}</b>)...`;

        try {
            const compressedFile = await compressImageFile(file, 1280, 0.78);
            
            // Gantikan file input dengan file hasil kompresi jika DataTransfer didukung browser
            if (window.DataTransfer) {
                const dt = new DataTransfer();
                dt.items.add(compressedFile);
                fileInput.files = dt.files;
            }

            const hematPersen = Math.max(0, Math.round((1 - (compressedFile.size / file.size)) * 100));
            fileFeedback.className = 'alert-success';
            fileFeedback.style.display = 'block';
            fileFeedback.innerHTML = `✅ Foto otomatis dioptimasi dari <b>${formatBytes(file.size)}</b> menjadi <b>${formatBytes(compressedFile.size)}</b> (${hematPersen}% lebih hemat, instan terkirim).`;
        } catch (err) {
            console.warn("Auto-compression fallback:", err);
            // Fallback: Jika ukuran asli di bawah 5MB, izinkan lanjut
            if (file.size > 5 * 1024 * 1024) {
                fileError.textContent = `⚠️ Ukuran berkas foto terlalu besar (${formatBytes(file.size)}) dan gagal dioptimasi. Harap pilih foto berukuran lebih kecil.`;
                fileError.style.display = 'block';
                fileInput.value = '';
                fileFeedback.style.display = 'none';
            } else {
                fileFeedback.className = 'alert-info';
                fileFeedback.style.display = 'block';
                fileFeedback.innerHTML = `📷 Foto siap diunggah (Ukuran: <b>${formatBytes(file.size)}</b>).`;
            }
        } finally {
            isCompressing = false;
            submitBtn.disabled = false;
        }
    });

    // Proteksi Double-Submission / Double-Click Form Lock
    form.addEventListener('submit', (e) => {
        if (isSubmitting) {
            e.preventDefault();
            return false;
        }
        if (isCompressing) {
            e.preventDefault();
            if (window.SGMDialog && typeof window.SGMDialog.toast === 'function') {
                window.SGMDialog.toast('Mohon tunggu sebentar, foto lampiran sedang dioptimasi...', 'info');
            } else {
                alert('Mohon tunggu sebentar, foto lampiran sedang dioptimasi...');
            }
            return false;
        }

        isSubmitting = true;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span>⏳</span> Mengirim Pengajuan...';
        submitBtn.style.opacity = '0.75';
        submitBtn.style.cursor = 'not-allowed';
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initKetidakhadiranForm);
} else {
    initKetidakhadiranForm();
}
})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
