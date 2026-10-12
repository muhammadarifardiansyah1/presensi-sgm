<?php
/**
 * Penugasan Lembur - Form Tambah & Edit View
 * Absensi Surya Gemilang Mojopahit (SGM)
 */

$pageTitle = ($isEdit ? 'Edit Penugasan Lembur' : 'Buat Penugasan Lembur Baru') . ' - ' . Config::APP_NAME;
$activePage = 'lembur';
require_once __DIR__ . '/../layouts/header.php';
?>

<div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
    <div>
        <h2 style="font-size: 1.4rem; font-weight: 800; color: var(--text-main); display: flex; align-items: center; gap: 10px; margin: 0 0 6px 0;">
            <?= Icon::get('clock', 24) ?> <?= $isEdit ? 'Edit Surat Perintah Lembur' : 'Buat Surat Perintah Lembur Baru' ?>
        </h2>
        <p style="font-size: 0.88rem; color: var(--text-muted); margin: 0;">
            Tentukan tanggal, lokasi, jadwal waktu, serta checklist pegawai yang berhak mendapatkan uang lembur.
        </p>
    </div>
    <a href="<?= Config::baseUrl('lembur') ?>" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 8px;">
        <?= Icon::get('arrow-left', 16) ?> Kembali
    </a>
</div>

<?php if (isset($_GET['err'])): ?>
    <div class="alert alert-danger">
        <?php
            if ($_GET['err'] === 'empty_fields') echo '⚠️ Semua field (tanggal, lokasi, jam, dan uraian tugas) wajib diisi!';
            if ($_GET['err'] === 'invalid_time') echo '⚠️ Jam selesai lembur harus lebih besar dari jam mulai!';
            if ($_GET['err'] === 'no_employee_selected') echo '⚠️ Anda belum memilih pegawai! Silakan checklist minimal 1 pegawai.';
            if ($_GET['err'] === 'save_failed') echo '⚠️ Terjadi kesalahan saat menyimpan data ke database!';
        ?>
    </div>
<?php endif; ?>

<form method="POST" action="<?= $isEdit ? Config::baseUrl('lembur/edit?id=' . $penugasan['id']) : Config::baseUrl('lembur/tambah') ?>" id="lemburForm">
    <div style="display: grid; grid-template-columns: 1fr 1.6fr; gap: 24px;" class="lembur-form-layout">
        
        <!-- Kolom Kiri: Detail Pengaturan Lembur -->
        <div class="card" style="padding: 24px; height: fit-content;">
            <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-main); margin-bottom: 18px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                <?= Icon::get('clipboard', 18) ?> Parameter Lembur
            </h3>

            <div class="form-group" style="margin-bottom: 16px;">
                <label for="tanggal" style="font-weight: 600; font-size: 0.86rem; color: var(--text-main); margin-bottom: 6px; display: block;">
                    Tanggal Lembur <span style="color: var(--danger);">*</span>
                </label>
                <input type="date" id="tanggal" name="tanggal" class="form-input" value="<?= htmlspecialchars($penugasan['tanggal']) ?>" required>
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label for="lokasi_id" style="font-weight: 600; font-size: 0.86rem; color: var(--text-main); margin-bottom: 6px; display: block;">
                    Lokasi Penugasan <span style="color: var(--danger);">*</span>
                </label>
                <select id="lokasi_id" name="lokasi_id" class="form-input" required onchange="onLokasiChange(this.value)">
                    <option value="">-- Pilih Lokasi Penugasan --</option>
                    <?php foreach ($lokasiList as $lok): ?>
                        <option value="<?= $lok['id'] ?>" <?= (int)$penugasan['lokasi_id'] === (int)$lok['id'] ? 'selected' : '' ?> data-pulang="<?= substr($lok['jam_pulang'] ?? '17:00', 0, 5) ?>">
                            <?= htmlspecialchars($lok['nama_lokasi']) ?> (Pulang: <?= substr($lok['jam_pulang'] ?? '17:00', 0, 5) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <div style="font-size: 0.76rem; color: var(--text-muted); margin-top: 4px;">
                    Daftar pegawai di sebelah kanan akan otomatis disaring sesuai lokasi ini.
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                <div class="form-group">
                    <label for="jam_mulai" style="font-weight: 600; font-size: 0.86rem; color: var(--text-main); margin-bottom: 6px; display: block;">
                        Jam Mulai <span style="color: var(--danger);">*</span>
                    </label>
                    <input type="time" id="jam_mulai" name="jam_mulai" class="form-input" value="<?= substr($penugasan['jam_mulai'], 0, 5) ?>" required onchange="hitungMaksimalJam()">
                </div>
                <div class="form-group">
                    <label for="jam_selesai" style="font-weight: 600; font-size: 0.86rem; color: var(--text-main); margin-bottom: 6px; display: block;">
                        Jam Selesai <span style="color: var(--danger);">*</span>
                    </label>
                    <input type="time" id="jam_selesai" name="jam_selesai" class="form-input" value="<?= substr($penugasan['jam_selesai'], 0, 5) ?>" required onchange="hitungMaksimalJam()">
                </div>
            </div>

            <!-- Kalkulasi Live Durasi Lembur -->
            <div style="background: var(--bg-subtle); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 12px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <div style="font-size: 0.78rem; color: var(--text-muted); font-weight: 600;">Maksimal Jam Lembur:</div>
                    <div id="liveDurasiText" style="font-size: 1.1rem; font-weight: 800; color: var(--accent-blue); font-family: 'JetBrains Mono', monospace;">
                        0.00 Jam
                    </div>
                </div>
                <div style="font-size: 0.74rem; color: var(--text-muted); text-align: right; max-width: 140px;">
                    Uang lembur di-cap maksimal sesuai durasi ini.
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label for="keterangan" style="font-weight: 600; font-size: 0.86rem; color: var(--text-main); margin-bottom: 6px; display: block;">
                    Uraian Tugas / Keterangan Lembur <span style="color: var(--danger);">*</span>
                </label>
                <textarea id="keterangan" name="keterangan" class="form-input" rows="3" placeholder="Contoh: Pekerjaan Stock Opname Akhir Bulan, Maintenance Jaringan..." required><?= htmlspecialchars($penugasan['keterangan']) ?></textarea>
            </div>

            <?php if ($isEdit): ?>
                <div class="form-group" style="margin-bottom: 16px;">
                    <label for="status" style="font-weight: 600; font-size: 0.86rem; color: var(--text-main); margin-bottom: 6px; display: block;">
                        Status Penugasan
                    </label>
                    <select id="status" name="status" class="form-input">
                        <option value="aktif" <?= $penugasan['status'] === 'aktif' ? 'selected' : '' ?>>Aktif (Berlaku)</option>
                        <option value="selesai" <?= $penugasan['status'] === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                        <option value="dibatalkan" <?= $penugasan['status'] === 'dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
                    </select>
                </div>
            <?php endif; ?>

            <div style="margin-top: 24px; display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary" style="flex: 1; padding: 12px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                    <?= Icon::get('check', 18) ?> <?= $isEdit ? 'Simpan Perubahan' : 'Terbitkan Penugasan Lembur' ?>
                </button>
            </div>
        </div>

        <!-- Kolom Kanan: Checklist Pegawai Interaktif -->
        <div class="card" style="padding: 24px; display: flex; flex-direction: column;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; border-bottom: 1px solid var(--border-color); padding-bottom: 14px; margin-bottom: 16px;">
                <div>
                    <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-main); margin: 0; display: flex; align-items: center; gap: 8px;">
                        <?= Icon::get('users', 18) ?> Pilih Pegawai yang Ditugaskan
                    </h3>
                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">
                        Hanya pegawai yang dicentang yang berhak mendapatkan uang lembur saat presensi keluar.
                    </div>
                </div>

                <!-- Live Selection Badge Counter -->
                <div id="selectionCounterBadge" style="background: var(--primary-light); color: var(--primary); font-weight: 700; font-size: 0.82rem; padding: 6px 14px; border-radius: var(--radius-full); border: 1px solid rgba(16, 185, 129, 0.2);">
                    <span id="selectedCount">0</span> Pegawai Dipilih
                </div>
            </div>

            <!-- Toolbar Checklist All & Live Search Filter -->
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 14px; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.88rem; font-weight: 700; color: var(--text-main); user-select: none;">
                        <input type="checkbox" id="checkAllMaster" onchange="toggleCheckAll(this.checked)" style="width: 18px; height: 18px; cursor: pointer; accent-color: var(--primary);">
                        <span>Checklist All (Pilih Semua)</span>
                    </label>
                    <span style="color: var(--border-color);">|</span>
                    <button type="button" class="btn btn-secondary" onclick="toggleAllLocationEmployees()" style="padding: 4px 10px; font-size: 0.78rem; color: var(--text-muted);" id="btnToggleAllLoc">
                        <?= Icon::get('globe', 13) ?> Tampilkan Seluruh Lokasi
                    </button>
                </div>

                <!-- Instant Search Input -->
                <div style="position: relative; min-width: 200px;">
                    <input type="text" id="employeeSearchInput" class="form-input" placeholder="Cari nama / NIK..." style="padding: 6px 12px 6px 32px; font-size: 0.84rem;" oninput="filterEmployeeList(this.value)">
                    <span style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none;">
                        <?= Icon::get('search', 14) ?>
                    </span>
                </div>
            </div>

            <!-- Container Table / List Pegawai -->
            <div style="flex: 1; max-height: 480px; overflow-y: auto; border: 1px solid var(--border-color); border-radius: var(--radius-md);">
                <table class="table-custom" style="margin: 0;" id="tablePegawaiLembur">
                    <thead style="position: sticky; top: 0; background: var(--bg-surface); z-index: 2;">
                        <tr>
                            <th style="width: 44px; text-align: center;">Pilih</th>
                            <th>Pegawai</th>
                            <th>NIK & Jabatan</th>
                            <th>Penugasan Lokasi</th>
                        </tr>
                    </thead>
                    <tbody id="pegawaiListTbody">
                        <!-- Dimuat secara dinamis atau server-side -->
                        <?php if (empty($pegawaiList)): ?>
                            <tr id="emptyPegawaiRow">
                                <td colspan="4" style="text-align: center; padding: 32px; color: var(--text-muted);">
                                    Belum ada pegawai pada lokasi ini.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php 
                            $avatarGradients = [
                                'linear-gradient(135deg, #3b82f6, #1d4ed8)',
                                'linear-gradient(135deg, #10b981, #047857)',
                                'linear-gradient(135deg, #8b5cf6, #6d28d9)',
                                'linear-gradient(135deg, #f59e0b, #b45309)',
                                'linear-gradient(135deg, #ec4899, #be185d)',
                                'linear-gradient(135deg, #06b6d4, #0e7490)',
                                'linear-gradient(135deg, #6366f1, #4338ca)',
                            ];
                            foreach ($pegawaiList as $p): 
                                $isChecked = in_array((int)$p['id'], array_map('intval', $assignedUserIds));
                                $userPhoto = $p['foto_profil'] ?? null;
                                $photoPath = __DIR__ . '/../../../assets/images/uploads/profil/' . $userPhoto;
                                $hasPhoto = !empty($userPhoto) && file_exists($photoPath);
                                $inisial = strtoupper(substr($p['nama'], 0, 1));
                                $colorIdx = abs(crc32($p['nama'] ?? '')) % count($avatarGradients);
                                $avatarBg = $avatarGradients[$colorIdx];
                            ?>
                                <tr class="pegawai-item-row" data-id="<?= $p['id'] ?>" data-name="<?= strtolower($p['nama']) ?>" data-nik="<?= strtolower($p['nik']) ?>" onclick="toggleRowCheckbox(event, <?= $p['id'] ?>)" style="cursor: pointer;">
                                    <td style="text-align: center;">
                                        <input type="checkbox" name="user_ids[]" value="<?= $p['id'] ?>" class="user-checkbox" <?= $isChecked ? 'checked' : '' ?> onchange="updateCounter()" style="width: 17px; height: 17px; cursor: pointer; accent-color: var(--primary);">
                                    </td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <div style="width: 34px; height: 34px; border-radius: 50%; background: <?= $avatarBg ?>; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.82rem; overflow: hidden; flex-shrink: 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 2px solid var(--border-color);">
                                                <?php if ($hasPhoto): ?>
                                                    <img src="<?= Config::baseUrl('assets/images/uploads/profil/' . $userPhoto) ?>" alt="Foto" style="width:100%; height:100%; object-fit:cover; display:block;" onerror="this.style.display='none'; this.parentElement.innerText='<?= $inisial ?>';">
                                                <?php else: ?>
                                                    <?= $inisial ?>
                                                <?php endif; ?>
                                            </div>
                                            <div style="font-weight: 700; color: var(--text-main); font-size: 0.88rem;">
                                                <?= htmlspecialchars($p['nama']) ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-family: 'JetBrains Mono', monospace; font-size: 0.78rem; color: var(--text-muted);">
                                            <?= htmlspecialchars($p['nik']) ?>
                                        </div>
                                        <div style="font-size: 0.8rem; color: var(--text-main); font-weight: 500;">
                                            <?= htmlspecialchars($p['jabatan']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span style="font-size: 0.78rem; color: var(--text-muted);">
                                            <?= htmlspecialchars($p['nama_lokasi'] ?? 'Kantor SGM') ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 10px; font-size: 0.76rem; color: var(--text-muted); display: flex; justify-content: space-between;">
                <span>* Klik pada baris untuk mencentang/membatalkan pilihan.</span>
                <span id="renderedCountInfo">Menampilkan <?= count($pegawaiList) ?> Pegawai</span>
            </div>
        </div>

    </div>
</form>

<script>
// State ID Pegawai Terpilih (Window-scoped agar aman terhadap navigasi Turbo)
window.preselectedUserIds = <?= json_encode(array_map('intval', $assignedUserIds)) ?>;
window.isShowingAllLocations = false;
var preselectedUserIds = window.preselectedUserIds;
var isShowingAllLocations = window.isShowingAllLocations;

function hitungMaksimalJam() {
    const tMulai = document.getElementById('jam_mulai').value;
    const tSelesai = document.getElementById('jam_selesai').value;
    const liveText = document.getElementById('liveDurasiText');

    if (tMulai && tSelesai) {
        const d1 = new Date('1970-01-01T' + tMulai + ':00');
        const d2 = new Date('1970-01-01T' + tSelesai + ':00');
        let diffMs = d2 - d1;
        if (diffMs > 0) {
            let hours = (diffMs / (1000 * 60 * 60)).toFixed(2);
            liveText.textContent = hours + ' Jam';
            liveText.style.color = 'var(--accent-blue)';
            return;
        }
    }
    liveText.textContent = '0.00 Jam (Tidak Valid)';
    liveText.style.color = 'var(--danger)';
}

function updateCounter() {
    const checkboxes = document.querySelectorAll('.user-checkbox:checked');
    const totalVisible = document.querySelectorAll('.user-checkbox');
    const count = checkboxes.length;
    
    document.getElementById('selectedCount').textContent = count;
    
    // Sinkronisasi status Check All master
    const checkAll = document.getElementById('checkAllMaster');
    if (checkAll && totalVisible.length > 0) {
        checkAll.checked = (count === totalVisible.length);
        checkAll.indeterminate = (count > 0 && count < totalVisible.length);
    }
}

function toggleCheckAll(checked) {
    const checkboxes = document.querySelectorAll('.user-checkbox');
    checkboxes.forEach(cb => {
        // Hanya checklist baris yang tidak disembunyikan oleh filter search
        const row = cb.closest('tr');
        if (row && row.style.display !== 'none') {
            cb.checked = checked;
        }
    });
    updateCounter();
}

function toggleRowCheckbox(event, id) {
    // Jika klik langsung pada elemen input checkbox, biarkan browser menangani secara native
    if (event.target.tagName.toLowerCase() === 'input') return;

    const row = document.querySelector(`.pegawai-item-row[data-id="${id}"]`);
    if (row) {
        const cb = row.querySelector('.user-checkbox');
        if (cb) {
            cb.checked = !cb.checked;
            updateCounter();
        }
    }
}

function filterEmployeeList(query) {
    query = query.toLowerCase().trim();
    const rows = document.querySelectorAll('.pegawai-item-row');
    let visibleCount = 0;

    rows.forEach(row => {
        const name = row.getAttribute('data-name') || '';
        const nik = row.getAttribute('data-nik') || '';
        if (!query || name.includes(query) || nik.includes(query)) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    const info = document.getElementById('renderedCountInfo');
    if (info) {
        info.textContent = `Menampilkan ${visibleCount} Pegawai`;
    }
    updateCounter();
}

// Fetch Pegawai saat Lokasi Diubah
function onLokasiChange(lokasiId) {
    isShowingAllLocations = false;
    document.getElementById('btnToggleAllLoc').innerHTML = '<?= Icon::get('globe', 13) ?> Tampilkan Seluruh Lokasi';
    
    // Set jam mulai default sesuai jam pulang lokasi jika dipilih
    const selectLokasi = document.getElementById('lokasi_id');
    const selectedOption = selectLokasi.options[selectLokasi.selectedIndex];
    if (selectedOption && selectedOption.dataset.pulang) {
        document.getElementById('jam_mulai').value = selectedOption.dataset.pulang;
        hitungMaksimalJam();
    }

    fetchEmployees(lokasiId);
}

function toggleAllLocationEmployees() {
    isShowingAllLocations = !isShowingAllLocations;
    const btn = document.getElementById('btnToggleAllLoc');
    
    if (isShowingAllLocations) {
        btn.innerHTML = '<?= Icon::get('filter', 13) ?> Saring Sesuai Lokasi';
        fetchEmployees(0); // 0 = all
    } else {
        btn.innerHTML = '<?= Icon::get('globe', 13) ?> Tampilkan Seluruh Lokasi';
        const lokasiId = document.getElementById('lokasi_id').value;
        fetchEmployees(lokasiId);
    }
}

function fetchEmployees(lokasiId) {
    const tbody = document.getElementById('pegawaiListTbody');
    tbody.innerHTML = `<tr><td colspan="4" style="text-align:center; padding:24px; color:var(--text-muted);"><?= Icon::get('refresh-cw', 18) ?> Memuat daftar pegawai...</td></tr>`;

    // Simpan dulu ID pegawai yang saat ini sedang diceklis agar tidak hilang saat reload
    const currentlyChecked = Array.from(document.querySelectorAll('.user-checkbox:checked')).map(cb => parseInt(cb.value));
    preselectedUserIds = Array.from(new Set([...preselectedUserIds, ...currentlyChecked]));

    fetch('<?= Config::baseUrl('lembur/pegawai-lokasi') ?>?lokasi_id=' + (lokasiId || 0))
        .then(res => res.json())
        .then(response => {
            if (response.status === 'success') {
                renderEmployeeTable(response.data);
            } else {
                tbody.innerHTML = `<tr><td colspan="4" style="text-align:center; padding:20px; color:var(--danger);">Gagal memuat data pegawai.</td></tr>`;
            }
        })
        .catch(err => {
            tbody.innerHTML = `<tr><td colspan="4" style="text-align:center; padding:20px; color:var(--danger);">Koneksi terganggu.</td></tr>`;
        });
}

function renderEmployeeTable(employees) {
    const tbody = document.getElementById('pegawaiListTbody');
    if (!employees || employees.length === 0) {
        tbody.innerHTML = `<tr><td colspan="4" style="text-align:center; padding:32px; color:var(--text-muted);">Tidak ada pegawai aktif yang terdaftar di lokasi ini.</td></tr>`;
        document.getElementById('renderedCountInfo').textContent = 'Menampilkan 0 Pegawai';
        updateCounter();
        return;
    }

    const gradients = [
        'linear-gradient(135deg, #3b82f6, #1d4ed8)',
        'linear-gradient(135deg, #10b981, #047857)',
        'linear-gradient(135deg, #8b5cf6, #6d28d9)',
        'linear-gradient(135deg, #f59e0b, #b45309)',
        'linear-gradient(135deg, #ec4899, #be185d)',
        'linear-gradient(135deg, #06b6d4, #0e7490)',
        'linear-gradient(135deg, #6366f1, #4338ca)'
    ];

    let html = '';
    employees.forEach(p => {
        const isChecked = preselectedUserIds.includes(parseInt(p.id));
        const initial = p.nama ? p.nama.charAt(0).toUpperCase() : 'U';
        let hash = 0;
        for (let i = 0; i < (p.nama || '').length; i++) hash = (hash << 5) - hash + p.nama.charCodeAt(i);
        const bgGrad = gradients[Math.abs(hash) % gradients.length];

        const photoHtml = p.foto_profil 
            ? `<img src="<?= Config::baseUrl('assets/images/uploads/profil/') ?>${p.foto_profil}" alt="Foto" style="width:100%; height:100%; object-fit:cover; display:block;" onerror="this.remove(); this.parentElement.innerText='${initial}';">`
            : initial;

        html += `
            <tr class="pegawai-item-row" data-id="${p.id}" data-name="${p.nama.toLowerCase()}" data-nik="${p.nik.toLowerCase()}" onclick="toggleRowCheckbox(event, ${p.id})" style="cursor: pointer;">
                <td style="text-align: center;">
                    <input type="checkbox" name="user_ids[]" value="${p.id}" class="user-checkbox" ${isChecked ? 'checked' : ''} onchange="updateCounter()" style="width: 17px; height: 17px; cursor: pointer; accent-color: var(--primary);">
                </td>
                <td>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 34px; height: 34px; border-radius: 50%; background: ${bgGrad}; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.82rem; overflow: hidden; flex-shrink: 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 2px solid var(--border-color);">
                            ${photoHtml}
                        </div>
                        <div style="font-weight: 700; color: var(--text-main); font-size: 0.88rem;">
                            ${p.nama}
                        </div>
                    </div>
                </td>
                <td>
                    <div style="font-family: 'JetBrains Mono', monospace; font-size: 0.78rem; color: var(--text-muted);">
                        ${p.nik}
                    </div>
                    <div style="font-size: 0.8rem; color: var(--text-main); font-weight: 500;">
                        ${p.jabatan}
                    </div>
                </td>
                <td>
                    <span style="font-size: 0.78rem; color: var(--text-muted);">
                        ${p.nama_lokasi || 'Kantor SGM'}
                    </span>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
    document.getElementById('renderedCountInfo').textContent = `Menampilkan ${employees.length} Pegawai`;
    updateCounter();
}

function initLemburForm() {
    hitungMaksimalJam();
    updateCounter();
}

if (document.readyState !== 'loading') {
    initLemburForm();
} else {
    document.addEventListener('DOMContentLoaded', initLemburForm);
}
document.addEventListener('turbo:load', initLemburForm);
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
