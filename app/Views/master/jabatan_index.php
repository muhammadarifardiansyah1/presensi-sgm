<?php
$pageTitle = 'Data Jabatan - ' . Config::APP_NAME;
$activePage = 'master_jabatan';
require_once __DIR__ . '/../layouts/header.php';
?>

<div style="margin-bottom: 20px; display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:16px;">
    <div>
        <h2 style="font-size: 1.4rem; font-weight: 700; margin: 0 0 4px 0;">Data Jabatan</h2>
        <p style="color: var(--text-muted); margin: 0; font-size: 0.9rem;">
            Kelola master jabatan, peran struktural, dan keterikatan pegawai
        </p>
    </div>
    <a href="<?= Config::baseUrl('master/jabatan/tambah') ?>" class="btn btn-blue">
        <?= Icon::get('plus', 16) ?> <span>Tambah Data</span>
    </a>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success">
        <?php 
            if ($_GET['msg'] === 'added') echo '✅ Data jabatan berhasil ditambahkan!';
            if ($_GET['msg'] === 'updated') echo '✅ Data jabatan serta seluruh data pegawai & pengaturan gaji terkait berhasil disinkronkan!';
            if ($_GET['msg'] === 'deleted') echo '✅ Data jabatan berhasil dihapus!';
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['err']) && $_GET['err'] === 'in_use'): ?>
    <div class="alert alert-danger" style="margin-bottom: 20px;">
        <?= Icon::get('alert-circle', 18) ?> <strong>Jabatan Tidak Dapat Dihapus:</strong> Jabatan <strong><?= htmlspecialchars($_GET['name'] ?? '') ?></strong> masih aktif digunakan oleh <strong><?= (int)($_GET['count'] ?? 1) ?> orang pegawai</strong>. Silakan ubah jabatan pegawai terkait terlebih dahulu sebelum menghapus master jabatan ini.
    </div>
<?php endif; ?>

<div class="table-card">
    <div class="looker-filter-bar" data-table="#jabatanTable">
        <div class="looker-filter-bar-left">
            <!-- Quick Search Input -->
            <div class="looker-search-box">
                <span class="looker-search-icon"><?= Icon::get('search', 14) ?></span>
                <input type="text" class="looker-search-input" data-table="#jabatanTable" placeholder="Cari Jabatan...">
            </div>

            <!-- Sort Select -->
            <div class="looker-sort-box">
                <select class="table-sort-select" data-table="#jabatanTable">
                    <option value="default">Urutkan: Default</option>
                    <option value="nama-asc">Nama Jabatan (A - Z)</option>
                    <option value="nama-desc">Nama Jabatan (Z - A)</option>
                    <option value="pegawai-asc">Pegawai (Sedikit - Banyak)</option>
                    <option value="pegawai-desc">Pegawai (Banyak - Sedikit)</option>
                </select>
            </div>

            <!-- Reset All Button -->
            <button type="button" class="looker-reset-all-btn" data-table="#jabatanTable" title="Reset semua filter">
                <?= Icon::get('refresh-cw', 13) ?> Reset
            </button>
        </div>
        <div class="looker-filter-bar-right">
            <span class="looker-row-count"></span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table-custom" id="jabatanTable">
            <thead>
                <tr>
                    <th style="width: 60px; text-align: center;">No.</th>
                    <th class="sortable-th" data-col="nama" data-sort-type="text">Nama Jabatan</th>
                    <th class="sortable-th" data-col="pegawai" data-sort-type="number" style="width: 230px;">Keterikatan Data</th>
                    <th style="width: 170px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($jabatanList)): ?>
                    <tr>
                        <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 30px;">
                            Data kosong, silahkan tambahkan data baru
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($jabatanList as $j): 
                        $userCount = (int)($j['user_count'] ?? 0);
                        $isUsed = ($userCount > 0);
                    ?>
                        <tr>
                            <td style="text-align: center;"><?= $no++ ?></td>
                            <td data-sort-value="<?= htmlspecialchars($j['nama_jabatan']) ?>">
                                <strong><?= htmlspecialchars($j['nama_jabatan']) ?></strong>
                            </td>
                            <td data-sort-value="<?= $userCount ?>">
                                <?php if ($isUsed): ?>
                                    <div style="display:flex; flex-wrap:wrap; gap:5px; align-items:center;">
                                        <span class="badge badge-warning" style="font-size:0.73rem; padding: 2px 8px; display:inline-flex; align-items:center; gap:4px;">
                                            <?= Icon::get('users', 12) ?> <span><?= $userCount ?> Pegawai</span>
                                        </span>
                                    </div>
                                <?php else: ?>
                                    <span class="badge" style="background:var(--bg-subtle); color:var(--text-muted); border:1px solid var(--border-color); font-size:0.72rem; font-weight:500;">
                                        0 Keterikatan (Siap Dihapus)
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <div style="display:inline-flex; gap:6px; align-items:center;">
                                    <a href="<?= Config::baseUrl('master/jabatan/edit?id=' . $j['id']) ?>" class="btn btn-primary btn-sm" title="Edit Data Jabatan">
                                        Edit
                                    </a>
                                    <?php if ($isUsed): ?>
                                        <span class="tooltip-wrapper" data-tooltip="Terkunci: Masih digunakan oleh <?= $userCount ?> pegawai. Pindahkan jabatan pegawai terlebih dahulu untuk menghapus.">
                                            <button type="button" class="btn btn-secondary btn-sm" disabled style="opacity: 0.52; cursor: not-allowed; display:inline-flex; align-items:center; gap:4px;">
                                                <?= Icon::get('lock', 13) ?> <span>Terkunci</span>
                                            </button>
                                        </span>
                                    <?php else: ?>
                                        <button type="button" 
                                                class="btn btn-danger btn-sm btn-delete-jabatan" 
                                                data-id="<?= (int)$j['id'] ?>" 
                                                data-name="<?= htmlspecialchars($j['nama_jabatan'], ENT_QUOTES) ?>"
                                                title="Hapus Jabatan Ini">
                                            <?= Icon::get('trash', 13) ?> <span>Hapus</span>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Konfirmasi Hapus Jabatan (In-App Dialog - Bebas Pop-up Blocker) -->
<div id="deleteJabatanModal" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="modalDeleteJabatanTitle">
    <div class="modal-card modal-confirm">
        <div class="modal-icon-danger">
            <?= Icon::get('trash', 28) ?>
        </div>
        <h3 id="modalDeleteJabatanTitle" class="modal-title">Hapus Data Jabatan?</h3>
        <p class="modal-desc">
            Apakah Anda yakin ingin menghapus jabatan <strong id="deleteModalJabatanName" style="color:var(--text-main);"></strong>? 
            <br><span style="font-size:0.8rem; color:var(--text-muted); display:inline-block; margin-top:6px;">Jabatan ini tidak memiliki keterikatan data pegawai dan akan dihapus secara permanen.</span>
        </p>
        <form id="deleteJabatanForm" method="POST" action="<?= Config::baseUrl('master/jabatan/hapus') ?>">
            <input type="hidden" name="id" id="deleteModalJabatanId" value="">
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" id="btnCancelDeleteJabatan">
                    Batal
                </button>
                <button type="submit" class="btn btn-danger" id="btnConfirmDeleteJabatan">
                    <?= Icon::get('trash', 14) ?> <span id="btnConfirmDeleteJabatanText">Ya, Hapus Jabatan</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(() => {
function initDeleteJabatanModal() {
    const modal = document.getElementById('deleteJabatanModal');
    if (!modal) return;
    const form = document.getElementById('deleteJabatanForm');
    const inputId = document.getElementById('deleteModalJabatanId');
    const nameSpan = document.getElementById('deleteModalJabatanName');
    const btnCancel = document.getElementById('btnCancelDeleteJabatan');
    const btnConfirm = document.getElementById('btnConfirmDeleteJabatan');
    const btnConfirmText = document.getElementById('btnConfirmDeleteJabatanText');

    function openModal(id, name) {
        inputId.value = id;
        nameSpan.textContent = '"' + name + '"';
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        modal.classList.remove('show');
        document.body.style.overflow = '';
        inputId.value = '';
    }

    // Attach click event to all delete buttons
    document.querySelectorAll('.btn-delete-jabatan').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            openModal(id, name);
        });
    });

    if (btnCancel) {
        btnCancel.addEventListener('click', closeModal);
    }

    // Close on clicking backdrop
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeModal();
        }
    });

    // Close on Escape key (Single Guarded Listener)
    if (!window._hasJabatanEscapeAttached) {
        window._hasJabatanEscapeAttached = true;
        document.addEventListener('keydown', function(e) {
            const m = document.getElementById('deleteJabatanModal');
            if (e.key === 'Escape' && m && m.classList.contains('show')) {
                m.classList.remove('show');
                document.body.style.overflow = '';
            }
        });
    }

    // Prevent double submission
    if (form) {
        form.addEventListener('submit', function() {
            if (btnConfirm) {
                btnConfirm.disabled = true;
                if (btnConfirmText) btnConfirmText.textContent = 'Menghapus...';
            }
        });
    }
}

initDeleteJabatanModal();
})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
