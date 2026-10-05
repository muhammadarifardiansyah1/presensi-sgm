<?php
$isEdit = !empty($pegawai['id']);
$pageTitle = ($isEdit ? 'Edit Pegawai' : 'Tambah Pegawai') . ' - ' . Config::APP_NAME;
$activePage = 'pegawai';
require_once __DIR__ . '/../layouts/header.php';
?>

<div style="margin-bottom: 20px;">
    <h2 style="font-size: 1.4rem; font-weight: 700;"><?= $isEdit ? 'Edit Pegawai' : 'Tambah Pegawai' ?></h2>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger" style="margin-bottom: 20px;">
        ⚠️ <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="form-card" style="padding: 32px 36px; margin-bottom: 30px;">
    <form method="POST" enctype="multipart/form-data">
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 36px;">
            <!-- Kolom Kiri -->
            <div>
                <div class="form-group" style="margin-bottom: 20px;">
                    <label class="form-label" for="nama" style="font-weight: 600; margin-bottom: 8px;">Nama</label>
                    <input type="text" id="nama" name="nama" class="form-input" 
                           value="<?= htmlspecialchars($pegawai['nama'] ?? '') ?>" 
                           placeholder="Nama Pegawai" required autofocus>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label class="form-label" for="jenis_kelamin" style="font-weight: 600; margin-bottom: 8px;">Jenis Kelamin</label>
                    <select id="jenis_kelamin" name="jenis_kelamin" class="form-select" required>
                        <option value="">-- Pilih Jenis Kelamin --</option>
                        <option value="Laki-laki" <?= (($pegawai['jenis_kelamin'] ?? 'Laki-laki') === 'Laki-laki') ? 'selected' : '' ?>>Laki-laki</option>
                        <option value="Perempuan" <?= (($pegawai['jenis_kelamin'] ?? '') === 'Perempuan') ? 'selected' : '' ?>>Perempuan</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label class="form-label" for="alamat" style="font-weight: 600; margin-bottom: 8px;">Alamat</label>
                    <textarea id="alamat" name="alamat" class="form-textarea" rows="3" 
                              placeholder="Alamat tempat tinggal"><?= htmlspecialchars($pegawai['alamat'] ?? '') ?></textarea>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label class="form-label" for="no_handphone" style="font-weight: 600; margin-bottom: 8px;">No Handphone</label>
                    <input type="text" id="no_handphone" name="no_handphone" class="form-input" 
                           value="<?= htmlspecialchars($pegawai['no_handphone'] ?? '') ?>" 
                           placeholder="08xxxxxxxxxx">
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label class="form-label" for="jabatan" style="font-weight: 600; margin-bottom: 8px;">Jabatan</label>
                    <select id="jabatan" name="jabatan" class="form-select" required>
                        <option value="">-- Pilih Jabatan --</option>
                        <?php if (!empty($jabatanList)): ?>
                            <?php foreach ($jabatanList as $j): ?>
                                <option value="<?= htmlspecialchars($j['nama_jabatan']) ?>" <?= (($pegawai['jabatan'] ?? '') === $j['nama_jabatan']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($j['nama_jabatan']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label class="form-label" for="status" style="font-weight: 600; margin-bottom: 8px;">Status</label>
                    <select id="status" name="status" class="form-select" required>
                        <option value="">-- Pilih Status --</option>
                        <option value="aktif" <?= (($pegawai['status'] ?? 'aktif') === 'aktif') ? 'selected' : '' ?>>Aktif</option>
                        <option value="nonaktif" <?= (($pegawai['status'] ?? '') === 'nonaktif') ? 'selected' : '' ?>>Nonaktif</option>
                    </select>
                </div>
            </div>

            <!-- Kolom Kanan -->
            <div>
                <div class="form-group" style="margin-bottom: 20px;">
                    <label class="form-label" for="username" style="font-weight: 600; margin-bottom: 8px;">Username</label>
                    <input type="text" id="username" name="username" class="form-input" 
                           value="<?= htmlspecialchars($pegawai['username'] ?? '') ?>" 
                           placeholder="Username login" required autocomplete="off">
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label class="form-label" for="password" style="font-weight: 600; margin-bottom: 8px;">
                        Password <?= $isEdit ? '<small style="color:var(--text-muted);font-weight:normal;">(Kosongkan bila tidak diubah)</small>' : '' ?>
                    </label>
                    <input type="password" id="password" name="password" class="form-input" 
                           placeholder="Password" <?= $isEdit ? '' : 'required' ?> autocomplete="new-password">
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label class="form-label" for="ulangi_password" style="font-weight: 600; margin-bottom: 8px;">
                        Ulangi Password <?= $isEdit ? '<small style="color:var(--text-muted);font-weight:normal;">(Kosongkan bila tidak diubah)</small>' : '' ?>
                    </label>
                    <input type="password" id="ulangi_password" name="ulangi_password" class="form-input" 
                           placeholder="Ulangi Password" <?= $isEdit ? '' : 'required' ?> autocomplete="new-password">
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label class="form-label" for="role" style="font-weight: 600; margin-bottom: 8px;">Role</label>
                    <select id="role" name="role" class="form-select" required>
                        <option value="">-- Pilih Role --</option>
                        <option value="admin" <?= (($pegawai['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Admin</option>
                        <option value="pegawai" <?= (($pegawai['role'] ?? 'pegawai') === 'pegawai') ? 'selected' : '' ?>>Pegawai</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label class="form-label" for="lokasi_id" style="font-weight: 600; margin-bottom: 8px;">Lokasi Presensi</label>
                    <select id="lokasi_id" name="lokasi_id" class="form-select">
                        <option value="">-- Pilih Lokasi Presensi --</option>
                        <?php if (!empty($lokasiList)): ?>
                            <?php foreach ($lokasiList as $lok): ?>
                                <option value="<?= $lok['id'] ?>" <?= (($pegawai['lokasi_id'] ?? '') == $lok['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($lok['nama_lokasi']) ?> (<?= htmlspecialchars($lok['tipe_lokasi']) ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 24px;">
                    <label class="form-label" for="foto" style="font-weight: 600; margin-bottom: 8px;">Foto</label>
                    <input type="file" id="foto" name="foto" class="form-input" accept="image/*">
                    <?php if (!empty($pegawai['foto_profil'])): ?>
                        <div style="margin-top: 10px; display:flex; align-items:center; gap:12px;">
                            <img src="<?= Config::baseUrl('assets/images/uploads/profil/' . $pegawai['foto_profil']) ?>" 
                                 alt="Foto Saat Ini" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px; border:1px solid var(--border-color);">
                            <small style="color:var(--text-muted);">Foto profil saat ini</small>
                        </div>
                    <?php endif; ?>
                </div>

                <div style="display:flex; gap: 12px; align-items: center; margin-top: 10px;">
                    <button type="submit" class="btn btn-blue" style="min-width: 120px; font-weight: 600; padding: 10px 24px;">
                        Simpan
                    </button>
                    <a href="<?= Config::baseUrl('pegawai') ?>" class="btn btn-secondary">
                        Kembali
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
