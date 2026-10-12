<?php
$pageTitle = 'Data Lokasi Presensi - ' . Config::APP_NAME;
$activePage = 'master_lokasi';
require_once __DIR__ . '/../layouts/header.php';
?>

<div style="margin-bottom: 20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
    <div>
        <h2 style="font-size: 1.4rem; font-weight: 700; margin: 0 0 4px 0;">Data Lokasi Presensi</h2>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0;">Kelola titik radius presensi kantor pusat dan kantor cabang</p>
    </div>
    <a href="<?= Config::baseUrl('master/lokasi/tambah') ?>" class="btn btn-blue">
        <?= Icon::get('plus', 16) ?> <span>Tambah Data</span>
    </a>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success" style="margin-bottom: 20px;">
        <?php 
            if ($_GET['msg'] === 'added') echo '✅ Lokasi presensi berhasil ditambahkan!';
            if ($_GET['msg'] === 'updated') echo '✅ Data lokasi berhasil diperbarui!';
            if ($_GET['msg'] === 'deleted') echo '✅ Lokasi presensi berhasil dihapus!';
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['err'])): ?>
    <div class="alert alert-danger" style="margin-bottom: 20px;">
        <?php if ($_GET['err'] === 'in_use'): ?>
            <?= Icon::get('alert-circle', 18) ?> <strong>Lokasi Terkunci:</strong> Lokasi ini tidak dapat dihapus karena masih terhubung dengan <strong><?= htmlspecialchars($_GET['detail'] ?? 'data pegawai/presensi') ?></strong>. Demi menjaga integritas data riwayat absensi, pindahkan penugasan pegawai terlebih dahulu.
        <?php elseif ($_GET['err'] === 'not_found'): ?>
            <?= Icon::get('alert-circle', 18) ?> <strong>Gagal:</strong> Data lokasi tidak ditemukan di sistem.
        <?php elseif ($_GET['err'] === 'invalid_id'): ?>
            <?= Icon::get('alert-circle', 18) ?> <strong>Gagal:</strong> ID lokasi tidak valid.
        <?php else: ?>
            <?= Icon::get('alert-circle', 18) ?> <strong>Gagal:</strong> Terjadi kesalahan saat menghapus lokasi. Silakan coba kembali.
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="table-card">
    <div class="looker-filter-bar" data-table="#lokasiTable">
        <div class="looker-filter-bar-left">
            <!-- Quick Search Input -->
            <div class="looker-search-box">
                <span class="looker-search-icon"><?= Icon::get('search', 14) ?></span>
                <input type="text" class="looker-search-input" data-table="#lokasiTable" placeholder="Cari Lokasi...">
            </div>

            <!-- Sort Select -->
            <div class="looker-sort-box">
                <select class="table-sort-select" data-table="#lokasiTable">
                    <option value="default">Urutkan: Default</option>
                    <option value="nama-asc">Nama Lokasi (A - Z)</option>
                    <option value="nama-desc">Nama Lokasi (Z - A)</option>
                    <option value="tipe-asc">Tipe Lokasi (A - Z)</option>
                    <option value="radius-asc">Radius (Terkecil)</option>
                    <option value="radius-desc">Radius (Terbesar)</option>
                </select>
            </div>

            <!-- Filter Pill: Tipe Lokasi -->
            <div class="looker-pill-wrapper" data-table="#lokasiTable" data-filter-col="tipe" data-label="Tipe Lokasi">
                <button type="button" class="looker-filter-pill">
                    <span class="looker-pill-text">Tipe Lokasi</span>
                    <span class="looker-pill-badge" style="display:none;"></span>
                    <span class="looker-pill-caret">▼</span>
                </button>
                <div class="looker-popover">
                    <div class="looker-popover-header">
                        <label class="looker-select-all-label">
                            <input type="checkbox" class="looker-select-all-input" checked> Pilih Semua
                        </label>
                    </div>
                    <div class="looker-popover-search">
                        <span class="looker-popover-search-icon"><?= Icon::get('search', 12) ?></span>
                        <input type="text" class="looker-popover-input" placeholder="Cari tipe...">
                    </div>
                    <div class="looker-checklist">
                        <label class="looker-check-item">
                            <span class="looker-check-left">
                                <input type="checkbox" value="pusat" checked>
                                <span class="looker-check-label">Pusat</span>
                            </span>
                            <span class="looker-check-count"></span>
                        </label>
                        <label class="looker-check-item">
                            <span class="looker-check-left">
                                <input type="checkbox" value="cabang" checked>
                                <span class="looker-check-label">Cabang</span>
                            </span>
                            <span class="looker-check-count"></span>
                        </label>
                    </div>
                    <div class="looker-popover-footer">
                        <span class="looker-footer-status">Semua dipilih</span>
                        <button type="button" class="looker-clear-btn">Reset</button>
                    </div>
                </div>
            </div>

            <!-- Reset All Button -->
            <button type="button" class="looker-reset-all-btn" data-table="#lokasiTable" title="Reset semua filter">
                <?= Icon::get('refresh-cw', 13) ?> Reset
            </button>
        </div>
        <div class="looker-filter-bar-right">
            <span class="looker-row-count"></span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table-custom" id="lokasiTable">
            <thead>
                <tr>
                    <th style="width: 50px; text-align: center;">No.</th>
                    <th class="sortable-th" data-col="nama" data-sort-type="text">Nama Lokasi</th>
                    <th class="sortable-th" data-col="tipe" data-sort-type="text">Tipe Lokasi</th>
                    <th>Latitude / Longitude</th>
                    <th class="sortable-th" data-col="radius" data-sort-type="number">Radius</th>
                    <th>Keterikatan Data</th>
                    <th style="width: 220px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($lokasiList)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 30px;">
                            Data kosong, silahkan tambahkan data baru
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($lokasiList as $lok): 
                        $userCount = (int)($lok['user_count'] ?? 0);
                        $presensiCount = (int)($lok['presensi_count'] ?? 0);
                        $isUsed = ($userCount > 0 || $presensiCount > 0);
                    ?>
                        <tr>
                            <td style="text-align: center;"><?= $no++ ?></td>
                            <td data-sort-value="<?= htmlspecialchars($lok['nama_lokasi']) ?>">
                                <strong><?= htmlspecialchars($lok['nama_lokasi']) ?></strong>
                            </td>
                            <td data-sort-value="<?= htmlspecialchars($lok['tipe_lokasi']) ?>">
                                <span class="badge <?= ($lok['tipe_lokasi'] === 'Pusat') ? 'badge-success' : 'badge-info' ?>">
                                    <?= htmlspecialchars($lok['tipe_lokasi']) ?>
                                </span>
                            </td>
                            <td style="font-family:'JetBrains Mono', monospace; font-size:0.8rem;">
                                <?= htmlspecialchars($lok['latitude']) ?> / <?= htmlspecialchars($lok['longitude']) ?>
                            </td>
                            <td data-sort-value="<?= (int)$lok['radius'] ?>"><?= (int)$lok['radius'] ?> meter</td>
                            <td>
                                <?php if ($isUsed): ?>
                                    <div style="display:flex; flex-wrap:wrap; gap:5px; align-items:center;">
                                        <?php if ($userCount > 0): ?>
                                            <span class="badge badge-warning" style="font-size:0.73rem; padding: 2px 8px; display:inline-flex; align-items:center; gap:4px;">
                                                <?= Icon::get('users', 12) ?> <span><?= $userCount ?> Pegawai</span>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($presensiCount > 0): ?>
                                            <span class="badge badge-info" style="font-size:0.73rem; padding: 2px 8px; display:inline-flex; align-items:center; gap:4px;">
                                                <?= Icon::get('calendar', 12) ?> <span><?= $presensiCount ?> Presensi</span>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="badge" style="background:var(--bg-subtle); color:var(--text-muted); border:1px solid var(--border-color); font-size:0.72rem; font-weight:500;">
                                        0 Keterikatan (Siap Dihapus)
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <div style="display:inline-flex; gap:6px; align-items:center;">
                                    <a href="<?= Config::baseUrl('master/lokasi/detail?id=' . $lok['id']) ?>" class="btn btn-blue btn-sm" title="Lihat Detail Lokasi">
                                        Detail
                                    </a>
                                    <a href="<?= Config::baseUrl('master/lokasi/edit?id=' . $lok['id']) ?>" class="btn btn-primary btn-sm" title="Edit Data Lokasi">
                                        Edit
                                    </a>
                                    <?php if ($isUsed): ?>
                                        <span class="tooltip-wrapper" data-tooltip="Terkunci: Masih digunakan oleh <?= $userCount ?> pegawai dan <?= $presensiCount ?> catatan presensi. Pindahkan pegawai terlebih dahulu untuk menghapus.">
                                            <button type="button" class="btn btn-secondary btn-sm" disabled style="opacity: 0.52; cursor: not-allowed; display:inline-flex; align-items:center; gap:4px;">
                                                <?= Icon::get('lock', 13) ?> <span>Terkunci</span>
                                            </button>
                                        </span>
                                    <?php else: ?>
                                        <button type="button" 
                                                class="btn btn-danger btn-sm btn-delete-lokasi" 
                                                data-id="<?= (int)$lok['id'] ?>" 
                                                data-name="<?= htmlspecialchars($lok['nama_lokasi'], ENT_QUOTES) ?>"
                                                title="Hapus Lokasi Ini">
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

<!-- Modal Konfirmasi Hapus Lokasi (In-App Dialog - Bebas Pop-up Blocker) -->
<div id="deleteLokasiModal" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="modalDeleteTitle">
    <div class="modal-card modal-confirm">
        <div class="modal-icon-danger">
            <?= Icon::get('trash', 28) ?>
        </div>
        <h3 id="modalDeleteTitle" class="modal-title">Hapus Lokasi Presensi?</h3>
        <p class="modal-desc">
            Apakah Anda yakin ingin menghapus lokasi <strong id="deleteModalLocationName" style="color:var(--text-main);"></strong>? 
            <br><span style="font-size:0.8rem; color:var(--text-muted); display:inline-block; margin-top:6px;">Lokasi ini tidak memiliki keterikatan data pegawai dan akan dihapus secara permanen.</span>
        </p>
        <form id="deleteLokasiForm" method="POST" action="<?= Config::baseUrl('master/lokasi/hapus') ?>">
            <input type="hidden" name="id" id="deleteModalLocationId" value="">
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" id="btnCancelDelete">
                    Batal
                </button>
                <button type="submit" class="btn btn-danger" id="btnConfirmDelete">
                    <?= Icon::get('trash', 14) ?> <span id="btnConfirmDeleteText">Ya, Hapus Lokasi</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(() => {
function initDeleteLokasiModal() {
    const modal = document.getElementById('deleteLokasiModal');
    const form = document.getElementById('deleteLokasiForm');
    const inputId = document.getElementById('deleteModalLocationId');
    const nameSpan = document.getElementById('deleteModalLocationName');
    const btnCancel = document.getElementById('btnCancelDelete');
    const btnConfirm = document.getElementById('btnConfirmDelete');
    const btnConfirmText = document.getElementById('btnConfirmDeleteText');

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
    document.querySelectorAll('.btn-delete-lokasi').forEach(function(btn) {
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
    if (!window._hasLokasiEscapeAttached) {
        window._hasLokasiEscapeAttached = true;
        document.addEventListener('keydown', function(e) {
            const m = document.getElementById('deleteLokasiModal');
            if (e.key === 'Escape' && m && m.classList.contains('show')) {
                m.classList.remove('show');
                document.body.style.overflow = '';
            }
        });
    }

    // Prevent double submission
    if (form) {
        form.addEventListener('submit', function() {
            btnConfirm.disabled = true;
            btnConfirmText.textContent = 'Menghapus...';
        });
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDeleteLokasiModal);
} else {
    initDeleteLokasiModal();
}
})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
