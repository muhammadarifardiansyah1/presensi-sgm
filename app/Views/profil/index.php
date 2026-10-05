<?php
$pageTitle = 'Profil Pengguna - ' . Config::APP_NAME;
$activePage = 'profil';
require_once __DIR__ . '/../layouts/header.php';
?>

<div style="margin-bottom: 24px;">
    <h2 style="font-size: 1.5rem; font-weight: 700; color: var(--text-main); margin-bottom: 6px;">Profil Pengguna</h2>
    <p style="color: var(--text-muted); font-size: 0.9rem;">Kelola informasi data diri, foto profil, dan keamanan akun Anda.</p>
</div>

<?php if (!empty($success)): ?>
    <div class="alert alert-success" style="margin-bottom: 24px; animation: fadeIn 0.3s ease;">
        ✅ <?= htmlspecialchars($success) ?>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger" style="margin-bottom: 24px; animation: fadeIn 0.3s ease;">
        ⚠️ <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="profile-layout-grid" style="display: grid; grid-template-columns: 320px 1fr; gap: 28px; align-items: start;">
    
    <!-- Kolom Kiri: Kartu Ringkasan Akun -->
    <div class="form-card profile-summary-card" style="padding: 28px; text-align: center;">
        <div class="profile-avatar-large-wrapper" style="position: relative; width: 120px; height: 120px; margin: 0 auto 16px;">
            <?php if (!empty($user['foto_profil']) && file_exists(__DIR__ . '/../../../assets/images/uploads/profil/' . $user['foto_profil'])): ?>
                <img src="<?= Config::baseUrl('assets/images/uploads/profil/' . $user['foto_profil']) ?>" 
                     alt="Foto Profil" 
                     id="avatar-preview-img"
                     style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid var(--border-color); box-shadow: var(--shadow-md);">
            <?php else: ?>
                <div id="avatar-preview-initials" style="width: 120px; height: 120px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), var(--primary-hover)); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 2.8rem; font-weight: 800; border: 4px solid var(--border-color); box-shadow: var(--shadow-md);">
                    <?= strtoupper(substr($user['nama'] ?? 'U', 0, 1)) ?>
                </div>
                <img id="avatar-preview-img" src="" alt="Preview" style="display:none; width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid var(--border-color); box-shadow: var(--shadow-md);">
            <?php endif; ?>
            <span class="status-indicator-badge" style="position: absolute; bottom: 4px; right: 8px; width: 18px; height: 18px; background: var(--success); border: 3px solid var(--bg-surface); border-radius: 50%;" title="Status: Aktif"></span>
        </div>

        <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--text-main); margin-bottom: 4px;">
            <?= htmlspecialchars($user['nama'] ?? 'User') ?>
        </h3>
        <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 12px;">
            @<?= htmlspecialchars($user['username'] ?? '') ?>
        </p>

        <div style="display: flex; justify-content: center; gap: 8px; margin-bottom: 20px;">
            <span class="badge <?= ($user['role'] === 'admin') ? 'badge-primary' : 'badge-info' ?>" style="font-size: 0.8rem; padding: 4px 12px; border-radius: 20px;">
                <?= ($user['role'] === 'admin') ? '👑 Super Admin' : '👤 Pegawai' ?>
            </span>
            <span class="badge badge-success" style="font-size: 0.8rem; padding: 4px 12px; border-radius: 20px;">
                <?= ucfirst($user['status'] ?? 'aktif') ?>
            </span>
        </div>

        <div style="border-top: 1px solid var(--border-color); padding-top: 16px; text-align: left; font-size: 0.875rem;">
            <div style="margin-bottom: 12px; display: flex; justify-content: space-between;">
                <span style="color: var(--text-muted);">NIK:</span>
                <strong style="font-family: 'JetBrains Mono', monospace;"><?= htmlspecialchars($user['nik'] ?? '-') ?></strong>
            </div>
            <div style="margin-bottom: 12px; display: flex; justify-content: space-between;">
                <span style="color: var(--text-muted);">Jabatan:</span>
                <strong><?= htmlspecialchars($user['jabatan'] ?? '-') ?></strong>
            </div>
            <div style="margin-bottom: 12px; display: flex; justify-content: space-between;">
                <span style="color: var(--text-muted);">Lokasi Kerja:</span>
                <span style="font-weight: 600; text-align: right; max-width: 150px;"><?= htmlspecialchars($user['nama_lokasi'] ?? ($user['role'] === 'admin' ? 'Semua Lokasi' : 'Belum Diatur')) ?></span>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span style="color: var(--text-muted);">Bergabung:</span>
                <span><?= !empty($user['created_at']) ? date('d M Y', strtotime($user['created_at'])) : '-' ?></span>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Form Edit Data & Keamanan -->
    <div style="display: flex; flex-direction: column; gap: 24px;">

        <!-- Kartu 1: Edit Data Diri -->
        <div class="form-card" style="padding: 28px;">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                <span style="display:flex; align-items:center; color:var(--text-main);"><?= Icon::get('user', 22) ?></span>
                <div>
                    <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--text-main);">Informasi Data Diri</h3>
                    <p style="color: var(--text-muted); font-size: 0.82rem;">Perbarui data identitas dan kontak pribadi Anda</p>
                </div>
            </div>

            <form method="POST" action="<?= Config::baseUrl('profil/update') ?>" enctype="multipart/form-data">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 18px; margin-bottom: 18px;">
                    <div class="form-group">
                        <label class="form-label" for="nik">Nomor Induk Karyawan (NIK)</label>
                        <input type="text" id="nik" class="form-input" value="<?= htmlspecialchars($user['nik'] ?? '') ?>" disabled style="background: var(--bg-subtle); cursor: not-allowed; font-family: 'JetBrains Mono', monospace;">
                        <small style="color: var(--text-muted); font-size: 0.75rem;">NIK diatur oleh sistem</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="username">Username</label>
                        <input type="text" id="username" class="form-input" value="<?= htmlspecialchars($user['username'] ?? '') ?>" disabled style="background: var(--bg-subtle); cursor: not-allowed;">
                        <small style="color: var(--text-muted); font-size: 0.75rem;">Username digunakan untuk login</small>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 18px; margin-bottom: 18px;">
                    <div class="form-group">
                        <label class="form-label" for="nama">Nama Lengkap <span style="color: var(--danger);">*</span></label>
                        <input type="text" id="nama" name="nama" class="form-input" value="<?= htmlspecialchars($user['nama'] ?? '') ?>" required placeholder="Masukkan nama lengkap">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="jenis_kelamin">Jenis Kelamin</label>
                        <select id="jenis_kelamin" name="jenis_kelamin" class="form-select">
                            <option value="Laki-laki" <?= (($user['jenis_kelamin'] ?? 'Laki-laki') === 'Laki-laki') ? 'selected' : '' ?>>Laki-laki</option>
                            <option value="Perempuan" <?= (($user['jenis_kelamin'] ?? '') === 'Perempuan') ? 'selected' : '' ?>>Perempuan</option>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 18px; margin-bottom: 18px;">
                    <div class="form-group">
                        <label class="form-label" for="no_handphone">Nomor Handphone / WhatsApp</label>
                        <input type="tel" id="no_handphone" name="no_handphone" class="form-input" value="<?= htmlspecialchars($user['no_handphone'] ?? '') ?>" placeholder="08xxxxxxxxxx">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="foto">Foto Profil Baru</label>
                        <input type="file" id="foto" name="foto" class="form-input" accept="image/png, image/jpeg, image/jpg, image/webp" onchange="previewProfilePhoto(this)">
                        <small style="color: var(--text-muted); font-size: 0.75rem;">Maksimal 2MB (JPG, JPEG, PNG, WEBP)</small>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 24px;">
                    <label class="form-label" for="alamat">Alamat Lengkap</label>
                    <textarea id="alamat" name="alamat" class="form-textarea" rows="3" placeholder="Masukkan alamat tempat tinggal"><?= htmlspecialchars($user['alamat'] ?? '') ?></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end;">
                    <button type="submit" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px;">
                        <?= Icon::get('save', 16) ?> <span>Simpan Perubahan Profil</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Kartu 2: Keamanan & Ganti Password -->
        <div class="form-card" id="ganti-password" style="padding: 28px;">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                <span style="display:flex; align-items:center; color:var(--text-main);"><?= Icon::get('lock', 22) ?></span>
                <div>
                    <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--text-main);">Keamanan & Ganti Password</h3>
                    <p style="color: var(--text-muted); font-size: 0.82rem;">Perbarui kata sandi untuk melindungi akun Anda</p>
                </div>
            </div>

            <form method="POST" action="<?= Config::baseUrl('profil/ganti-password') ?>">
                <div class="form-group" style="margin-bottom: 18px;">
                    <label class="form-label" for="current_password">Password Saat Ini <span style="color: var(--danger);">*</span></label>
                    <div class="password-wrapper" style="position: relative;">
                        <input type="password" id="current_password" name="current_password" class="form-input" placeholder="Masukkan password saat ini" required autocomplete="current-password">
                        <button type="button" class="toggle-pw-btn toggle-password-btn" data-target="current_password" title="Lihat password" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--text-muted); display:flex; align-items:center;"><?= Icon::get('eye', 16) ?></button>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 18px; margin-bottom: 24px;">
                    <div class="form-group">
                        <label class="form-label" for="new_password">Password Baru <span style="color: var(--danger);">*</span></label>
                        <div class="password-wrapper" style="position: relative;">
                            <input type="password" id="new_password" name="new_password" class="form-input" placeholder="Minimal 6 karakter" required autocomplete="new-password">
                            <button type="button" class="toggle-pw-btn toggle-password-btn" data-target="new_password" title="Lihat password" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--text-muted); display:flex; align-items:center;"><?= Icon::get('eye', 16) ?></button>
                        </div>
                        <small style="color: var(--text-muted); font-size: 0.75rem;">Gunakan kombinasi huruf dan angka</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="confirm_password">Konfirmasi Password Baru <span style="color: var(--danger);">*</span></label>
                        <div class="password-wrapper" style="position: relative;">
                            <input type="password" id="confirm_password" name="confirm_password" class="form-input" placeholder="Ulangi password baru" required autocomplete="new-password">
                            <button type="button" class="toggle-pw-btn toggle-password-btn" data-target="confirm_password" title="Lihat password" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--text-muted); display:flex; align-items:center;"><?= Icon::get('eye', 16) ?></button>
                        </div>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end;">
                    <button type="submit" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 8px; background: var(--bg-subtle); border-color: var(--border-color); color: var(--text-main);">
                        <?= Icon::get('key', 16) ?> <span>Perbarui Password</span>
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>

<script>
function previewProfilePhoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const previewImg = document.getElementById('avatar-preview-img');
            const previewInitials = document.getElementById('avatar-preview-initials');
            if (previewImg) {
                previewImg.src = e.target.result;
                previewImg.style.display = 'block';
            }
            if (previewInitials) {
                previewInitials.style.display = 'none';
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
