<?php
$isEdit = !empty($jabatan['id']);
$pageTitle = ($isEdit ? 'Edit Jabatan' : 'Tambah Jabatan') . ' - ' . Config::APP_NAME;
$activePage = 'master_jabatan';
require_once __DIR__ . '/../layouts/header.php';
?>

<div style="margin-bottom: 20px; display:flex; justify-content:space-between; align-items:center;">
    <h2 style="font-size: 1.4rem; font-weight: 700;"><?= $isEdit ? 'Edit Jabatan' : 'Tambah Jabatan Baru' ?></h2>
    <a href="<?= Config::baseUrl('master/jabatan') ?>" class="btn btn-secondary btn-sm">← Kembali</a>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger">
        ⚠️ <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="form-card" style="max-width: 600px; margin: 0 auto;">
    <form method="POST">
        <div class="form-group">
            <label class="form-label" for="nama_jabatan">Nama Jabatan *</label>
            <input type="text" id="nama_jabatan" name="nama_jabatan" class="form-input" 
                   value="<?= htmlspecialchars($jabatan['nama_jabatan'] ?? '') ?>" 
                   placeholder="Contoh: HRD, Staff Operasional, IT Support" required autofocus>
            <small style="color:var(--text-muted); font-size:0.8rem; margin-top:4px; display:block;">
                Nama jabatan pegawai yang akan ditampilkan di profil dan form presensi.
            </small>
        </div>

        <div style="margin-top: 24px; display:flex; justify-content:flex-end; gap: 10px;">
            <a href="<?= Config::baseUrl('master/jabatan') ?>" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-blue">
                <?= $isEdit ? 'Perbarui Data' : 'Simpan Data' ?>
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
