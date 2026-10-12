<?php
/**
 * Halaman Mandiri: Pusat Notifikasi
 * Absensi Surya Gemilang Mojopahit (SGM)
 */

$pageTitle = 'Pusat Notifikasi - ' . Config::APP_NAME;
$activePage = 'notifikasi';
require_once __DIR__ . '/../layouts/header.php';

$totalNotif = count($notifications);
$totalIzinNotif = 0;
$totalPresensiNotif = 0;
$totalLemburNotif = 0;

foreach ($notifications as $n) {
    if (($n['kategori'] ?? '') === 'ketidakhadiran') {
        $totalIzinNotif++;
    } elseif (($n['kategori'] ?? '') === 'presensi') {
        $totalPresensiNotif++;
    } elseif (($n['kategori'] ?? '') === 'lembur') {
        $totalLemburNotif++;
    }
}
?>

<style>
/* Notifikasi Page Styling */
.notif-header-banner {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: var(--shadow-sm);
    flex-wrap: wrap;
    gap: 16px;
}

.notif-title-group h2 {
    font-size: 1.4rem;
    font-weight: 800;
    color: var(--text-main);
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0 0 6px 0;
}

.notif-title-group p {
    font-size: 0.88rem;
    color: var(--text-muted);
    margin: 0;
}

.notif-filter-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    padding: 16px 20px;
    margin-bottom: 24px;
    box-shadow: var(--shadow-sm);
    flex-wrap: wrap;
    gap: 14px;
}

.notif-tabs {
    display: inline-flex;
    background: var(--bg-subtle);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-full);
    padding: 4px;
    gap: 4px;
}

.notif-tab-btn {
    padding: 7px 16px;
    border-radius: var(--radius-full);
    font-size: 0.84rem;
    font-weight: 600;
    color: var(--text-muted);
    background: transparent;
    border: none;
    cursor: pointer;
    transition: all var(--transition-fast);
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.notif-tab-btn:hover {
    color: var(--text-main);
}

.notif-tab-btn.active {
    background: var(--accent-blue);
    color: #ffffff;
    box-shadow: var(--shadow-sm);
}

.notif-search-input {
    padding: 8px 14px 8px 36px;
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-sm);
    font-size: 0.86rem;
    color: var(--text-main);
    outline: none;
    width: 240px;
    transition: border-color var(--transition-fast);
}

.notif-search-input:focus {
    border-color: var(--accent-blue);
}

.notif-card-container {
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: 24px;
    box-shadow: var(--shadow-sm);
}

.notif-list {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.notif-item-card {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    background: var(--bg-subtle);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    padding: 18px 20px;
    gap: 16px;
    transition: all var(--transition-fast);
}

.notif-item-card:hover {
    background: var(--bg-surface-elevated, var(--bg-surface));
    border-color: var(--border-hover);
    box-shadow: var(--shadow-sm);
}

.notif-item-main {
    display: flex;
    gap: 16px;
    align-items: flex-start;
    flex: 1;
    min-width: 0;
}

.notif-item-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.notif-icon-success {
    background: rgba(16, 185, 129, 0.12);
    color: #10b981;
}

.notif-icon-warning {
    background: rgba(245, 158, 11, 0.12);
    color: #f59e0b;
}

.notif-icon-danger {
    background: rgba(239, 68, 68, 0.12);
    color: #ef4444;
}

.notif-icon-info {
    background: rgba(37, 99, 235, 0.12);
    color: #2563eb;
}

.notif-item-body {
    flex: 1;
    min-width: 0;
}

.notif-item-header {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 6px;
}

.notif-item-title {
    font-size: 0.96rem;
    font-weight: 700;
    color: var(--text-main);
    margin: 0;
}

.notif-item-desc {
    font-size: 0.86rem;
    color: var(--text-muted);
    line-height: 1.55;
    margin: 0;
}

.notif-item-side {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 8px;
    flex-shrink: 0;
}

.notif-time-badge {
    font-size: 0.78rem;
    color: var(--text-subtle);
    font-weight: 600;
    background: var(--bg-surface);
    padding: 3px 8px;
    border-radius: var(--radius-sm);
    border: 1px solid var(--border-color);
}

.notif-action-btn {
    font-size: 0.82rem;
    font-weight: 700;
    color: var(--accent-blue);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    border-radius: var(--radius-sm);
    transition: background var(--transition-fast);
}

.notif-action-btn:hover {
    background: var(--accent-blue-light);
}

.notif-empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--text-muted);
}

@media (max-width: 768px) {
    .notif-header-banner {
        flex-direction: column;
        align-items: flex-start;
    }
    .notif-filter-bar {
        flex-direction: column;
        align-items: flex-start;
    }
    .notif-search-input {
        width: 100%;
    }
    .notif-item-card {
        flex-direction: column;
        align-items: flex-start;
    }
    .notif-item-side {
        align-items: flex-start;
        width: 100%;
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px dashed var(--border-color);
    }
}
</style>

<!-- Banner Header -->
<div class="notif-header-banner">
    <div class="notif-title-group">
        <h2><?= Icon::get('bell', 24) ?> Pusat Notifikasi</h2>
        <p>Seluruh pemberitahuan sistem absensi, status peninjauan izin, cuti, serta catatan aktivitas kehadiran Anda.</p>
    </div>
    <div>
        <a href="<?= Config::baseUrl('dashboard') ?>" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px;">
            <?= Icon::get('arrow-left', 16) ?> Ke Dashboard
        </a>
    </div>
</div>

<!-- Toolbar Filter & Search -->
<div class="notif-filter-bar">
    <div class="notif-tabs">
        <button type="button" class="notif-tab-btn active" data-filter="all">
            <?= Icon::get('bell', 15) ?> Semua (<?= $totalNotif ?>)
        </button>
        <button type="button" class="notif-tab-btn" data-filter="ketidakhadiran">
            <?= Icon::get('clipboard', 15) ?> Izin & Cuti (<?= $totalIzinNotif ?>)
        </button>
        <button type="button" class="notif-tab-btn" data-filter="presensi">
            <?= Icon::get('camera', 15) ?> Presensi (<?= $totalPresensiNotif ?>)
        </button>
        <?php if ($totalLemburNotif > 0): ?>
            <button type="button" class="notif-tab-btn" data-filter="lembur">
                <?= Icon::get('clock', 15) ?> Lembur (<?= $totalLemburNotif ?>)
            </button>
        <?php endif; ?>
    </div>

    <div style="position: relative; display: flex; align-items: center;">
        <span style="position: absolute; left: 10px; color: var(--text-muted); pointer-events: none;">
            <?= Icon::get('search', 16) ?>
        </span>
        <input type="text" id="notifSearch" class="notif-search-input" placeholder="Cari notifikasi...">
    </div>
</div>

<!-- Container Notifikasi List -->
<div class="notif-card-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid var(--border-color);">
        <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-main); margin: 0; display: flex; align-items: center; gap: 8px;">
            <?= Icon::get('inbox', 18) ?> Daftar Notifikasi Terbaru
        </h3>
        <span style="font-size: 0.8rem; color: var(--text-muted);" id="visibleNotifCount">
            Menampilkan <?= $totalNotif ?> notifikasi
        </span>
    </div>

    <?php if (empty($notifications)): ?>
        <div class="notif-empty-state">
            <?= Icon::get('bell-off', 48) ?>
            <h4 style="font-size: 1.1rem; font-weight: 700; color: var(--text-main); margin: 12px 0 6px 0;">Tidak Ada Notifikasi</h4>
            <p style="font-size: 0.88rem; max-width: 400px; margin: 0 auto 16px auto;">Saat ini belum ada pemberitahuan atau pesan baru untuk akun Anda.</p>
            <a href="<?= Config::baseUrl('dashboard') ?>" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 6px;">
                <?= Icon::get('arrow-left', 14) ?> Kembali ke Dashboard
            </a>
        </div>
    <?php else: ?>
        <div class="notif-list" id="notifListContainer">
            <?php foreach ($notifications as $item): 
                $type = $item['type'] ?? 'info';
                $badgeCls = ($type === 'success') ? 'badge-success' : (($type === 'danger') ? 'badge-danger' : (($type === 'warning') ? 'badge-warning' : 'badge-info'));
                $iconCls  = 'notif-icon-' . $type;
                $kategori = $item['kategori'] ?? 'lainnya';
            ?>
                <div class="notif-item-card" data-category="<?= htmlspecialchars($kategori) ?>">
                    <div class="notif-item-main">
                        <div class="notif-item-icon <?= $iconCls ?>">
                            <?= $item['icon'] ?? Icon::get('bell', 20) ?>
                        </div>
                        <div class="notif-item-body">
                            <div class="notif-item-header">
                                <h4 class="notif-item-title"><?= htmlspecialchars($item['title'] ?? 'Notifikasi') ?></h4>
                                <span class="badge <?= $badgeCls ?>"><?= htmlspecialchars($item['badge'] ?? 'Info') ?></span>
                            </div>
                            <p class="notif-item-desc"><?= htmlspecialchars($item['message'] ?? '') ?></p>
                        </div>
                    </div>
                    <div class="notif-item-side">
                        <div class="notif-time-badge" title="<?= htmlspecialchars($item['date_detail'] ?? '') ?>">
                            <?= htmlspecialchars($item['time'] ?? '') ?>
                        </div>
                        <?php if (!empty($item['link'])): ?>
                            <a href="<?= $item['link'] ?>" class="notif-action-btn">
                                Buka Detail <?= Icon::get('arrow-right', 13) ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
// Filter Tab & Pencarian Notifikasi Cepat (Hotwire Turbo & Native Lifecycle Compatible)
(function() {
    function initNotifFilter() {
        const tabBtns = document.querySelectorAll('.notif-tab-btn');
        const searchInput = document.getElementById('notifSearch');
        const notifCards = document.querySelectorAll('.notif-item-card');
        const countDisplay = document.getElementById('visibleNotifCount');

        if (!notifCards.length) return;

        let currentFilter = 'all';

        function applyFilter() {
            const query = (searchInput ? searchInput.value : '').toLowerCase().trim();
            let visibleCount = 0;

            notifCards.forEach(card => {
                const cat = card.getAttribute('data-category');
                const text = card.textContent.toLowerCase();

                const matchesCategory = (currentFilter === 'all' || cat === currentFilter);
                const matchesQuery = (!query || text.includes(query));

                if (matchesCategory && matchesQuery) {
                    card.style.display = 'flex';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            if (countDisplay) {
                countDisplay.textContent = `Menampilkan ${visibleCount} notifikasi`;
            }
        }

        tabBtns.forEach(btn => {
            btn.onclick = function(e) {
                e.preventDefault();
                tabBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                currentFilter = this.getAttribute('data-filter') || 'all';
                applyFilter();
            };
        });

        if (searchInput) {
            searchInput.oninput = applyFilter;
        }
    }

    document.addEventListener('turbo:load', initNotifFilter);
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initNotifFilter);
    } else {
        initNotifFilter();
    }
})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
