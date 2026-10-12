<?php
$pageTitle = 'Detail Lokasi Presensi - ' . Config::APP_NAME;
$activePage = 'master_lokasi';
require_once __DIR__ . '/../layouts/header.php';
?>

<div style="margin-bottom: 20px; display:flex; justify-content:space-between; align-items:center;">
    <h2 style="font-size: 1.4rem; font-weight: 700;">Detail Lokasi Presensi</h2>
    <div style="display:flex; gap:10px;">
        <a href="<?= Config::baseUrl('master/lokasi/edit?id=' . $lokasi['id']) ?>" class="btn btn-primary btn-sm">
            <?= Icon::get('edit', 14) ?> Edit Lokasi
        </a>
        <a href="<?= Config::baseUrl('master/lokasi') ?>" class="btn btn-secondary btn-sm">← Kembali</a>
    </div>
</div>

<div class="split-presensi-layout">
    <!-- Kolom Kiri: Tabel Informasi Detail -->
    <div class="table-card">
        <table class="table-custom">
            <tbody>
                <tr>
                    <th style="width: 35%;">Nama Lokasi</th>
                    <td><strong><?= htmlspecialchars($lokasi['nama_lokasi']) ?></strong></td>
                </tr>
                <tr>
                    <th>Alamat Lokasi</th>
                    <td><?= nl2br(htmlspecialchars($lokasi['alamat_lokasi'])) ?></td>
                </tr>
                <tr>
                    <th>Tipe Lokasi</th>
                    <td>
                        <span class="badge <?= ($lokasi['tipe_lokasi'] === 'Pusat') ? 'badge-success' : 'badge-info' ?>">
                            <?= htmlspecialchars($lokasi['tipe_lokasi']) ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>Latitude</th>
                    <td style="font-family:'JetBrains Mono', monospace;"><?= htmlspecialchars($lokasi['latitude']) ?></td>
                </tr>
                <tr>
                    <th>Longitude</th>
                    <td style="font-family:'JetBrains Mono', monospace;"><?= htmlspecialchars($lokasi['longitude']) ?></td>
                </tr>
                <tr>
                    <th>Radius</th>
                    <td><?= (int)$lokasi['radius'] ?> meter</td>
                </tr>
                <tr>
                    <th>Zona Waktu</th>
                    <td><?= htmlspecialchars($lokasi['zona_waktu']) ?></td>
                </tr>
                <tr>
                    <th>Jam Masuk Standar</th>
                    <td><span class="badge badge-success"><?= htmlspecialchars($lokasi['jam_masuk']) ?> WIB</span></td>
                </tr>
                <tr>
                    <th>Jam Pulang Standar</th>
                    <td><span class="badge badge-danger"><?= htmlspecialchars($lokasi['jam_pulang']) ?> WIB</span></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Kolom Kanan: Peta Interaktif -->
    <div class="presensi-pane">
        <div class="pane-title" style="display:flex; align-items:center; gap:8px;">
            <?= Icon::get('map', 18) ?> <span>Pratinjau Peta & Radius Geofence</span>
        </div>
        <div id="detail-map" style="width:100%; height:380px; border-radius:var(--radius-md); border:1px solid var(--border-color);"></div>
    </div>
</div>

<script>
(() => {
    let map = null;

    function cleanupMap() {
        if (map) {
            try { map.remove(); } catch(e) {}
            map = null;
        }
        const container = document.getElementById('detail-map');
        if (container && container._leaflet_id) {
            delete container._leaflet_id;
            container.innerHTML = '';
        }
    }

    function initDetailMap() {
        const container = document.getElementById('detail-map');
        if (!container) return;

        cleanupMap();

        const lat = <?= (float)$lokasi['latitude'] ?>;
        const lng = <?= (float)$lokasi['longitude'] ?>;
        const radius = <?= (float)$lokasi['radius'] ?>;

        try {
            map = L.map('detail-map').setView([lat, lng], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);
        } catch(e) {
            console.warn('Map initialization:', e);
            return;
        }

        L.marker([lat, lng]).addTo(map)
            .bindPopup("<b><?= htmlspecialchars($lokasi['nama_lokasi']) ?></b><br>Radius: " + radius + "m").openPopup();

        L.circle([lat, lng], {
            color: '#ef4444',
            fillColor: '#f87171',
            fillOpacity: 0.25,
            radius: radius
        }).addTo(map);
    }

    document.addEventListener('turbo:before-cache', cleanupMap);
    document.addEventListener('turbo:before-visit', cleanupMap);
    window.addEventListener('beforeunload', cleanupMap);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDetailMap);
    } else {
        initDetailMap();
    }
})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
