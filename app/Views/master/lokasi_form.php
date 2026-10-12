<?php
$isEdit = !empty($lokasi['id']);
$pageTitle = ($isEdit ? 'Edit Lokasi' : 'Tambah Lokasi Presensi') . ' - ' . Config::APP_NAME;
$activePage = 'master_lokasi';
require_once __DIR__ . '/../layouts/header.php';
?>

<div style="margin-bottom: 20px;">
    <h2 style="font-size: 1.4rem; font-weight: 700;"><?= $isEdit ? 'Edit Lokasi Presensi' : 'Tambah Lokasi Presensi' ?></h2>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger" style="margin-bottom: 20px;">
        ⚠️ <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="form-card" style="max-width: 800px; margin: 0 auto; padding: 32px 36px;">
    <form method="POST">
        <div class="form-group" style="margin-bottom: 20px;">
            <label class="form-label" for="nama_lokasi" style="font-weight: 600; margin-bottom: 8px;">Nama Lokasi</label>
            <input type="text" id="nama_lokasi" name="nama_lokasi" class="form-input" 
                   value="<?= htmlspecialchars($lokasi['nama_lokasi'] ?? '') ?>" 
                   placeholder="Masukkan nama lokasi" required autofocus>
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label class="form-label" for="alamat_lokasi" style="font-weight: 600; margin-bottom: 8px;">Alamat Lokasi</label>
            <input type="text" id="alamat_lokasi" name="alamat_lokasi" class="form-input" 
                   value="<?= htmlspecialchars($lokasi['alamat_lokasi'] ?? '') ?>" 
                   placeholder="Masukkan alamat lokasi">
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label class="form-label" for="tipe_lokasi" style="font-weight: 600; margin-bottom: 8px;">Tipe Lokasi</label>
            <select id="tipe_lokasi" name="tipe_lokasi" class="form-select" required>
                <option value="">-- Pilih Tipe Lokasi --</option>
                <option value="Pusat" <?= (($lokasi['tipe_lokasi'] ?? '') === 'Pusat') ? 'selected' : '' ?>>Pusat</option>
                <option value="Cabang" <?= (($lokasi['tipe_lokasi'] ?? '') === 'Cabang') ? 'selected' : '' ?>>Cabang</option>
            </select>
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label class="form-label" for="latitude" style="font-weight: 600; margin-bottom: 8px;">Latitude</label>
            <input type="number" step="any" min="-90" max="90" id="latitude" name="latitude" class="form-input" 
                   value="<?= htmlspecialchars((string)($lokasi['latitude'] ?? '-6.20876340')) ?>" required
                   placeholder="-6.20876340">
            <small style="color:var(--text-muted); font-size:0.75rem; margin-top:4px; display:block;">Rentang valid: -90.0 s/d 90.0</small>
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label class="form-label" for="longitude" style="font-weight: 600; margin-bottom: 8px;">Longitude</label>
            <input type="number" step="any" min="-180" max="180" id="longitude" name="longitude" class="form-input" 
                   value="<?= htmlspecialchars((string)($lokasi['longitude'] ?? '106.84559900')) ?>" required
                   placeholder="106.84559900">
            <small style="color:var(--text-muted); font-size:0.75rem; margin-top:4px; display:block;">Rentang valid: -180.0 s/d 180.0</small>
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label class="form-label" for="radius" style="font-weight: 600; margin-bottom: 8px;">Radius Presensi (Meter)</label>
            <input type="number" id="radius" name="radius" class="form-input" 
                   value="<?= htmlspecialchars((string)($lokasi['radius'] ?? '500')) ?>" placeholder="500" required min="25" max="50000">
            <small style="color:var(--text-muted); font-size:0.75rem; margin-top:4px; display:block;">Batas minimal 25 meter. Rekomendasi: 50 – 500 meter untuk mengantisipasi deviasi sinyal GPS smartphone karyawan.</small>
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label class="form-label" for="zona_waktu" style="font-weight: 600; margin-bottom: 8px;">Zona Waktu</label>
            <select id="zona_waktu" name="zona_waktu" class="form-select" required>
                <option value="">-- Pilih Zona Waktu --</option>
                <option value="WIB" <?= (($lokasi['zona_waktu'] ?? '') === 'WIB') ? 'selected' : '' ?>>WIB (Waktu Indonesia Barat)</option>
                <option value="WITA" <?= (($lokasi['zona_waktu'] ?? '') === 'WITA') ? 'selected' : '' ?>>WITA (Waktu Indonesia Tengah)</option>
                <option value="WIT" <?= (($lokasi['zona_waktu'] ?? '') === 'WIT') ? 'selected' : '' ?>>WIT (Waktu Indonesia Timur)</option>
            </select>
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label class="form-label" for="jam_masuk" style="font-weight: 600; margin-bottom: 8px;">Jam Masuk Kantor</label>
            <input type="time" id="jam_masuk" name="jam_masuk" class="form-input" 
                   value="<?= htmlspecialchars(substr($lokasi['jam_masuk'] ?? '08:00', 0, 5)) ?>" required>
        </div>

        <div class="form-group" style="margin-bottom: 28px;">
            <label class="form-label" for="jam_pulang" style="font-weight: 600; margin-bottom: 8px;">Jam Pulang Kantor</label>
            <input type="time" id="jam_pulang" name="jam_pulang" class="form-input" 
                   value="<?= htmlspecialchars(substr($lokasi['jam_pulang'] ?? '17:00', 0, 5)) ?>" required>
        </div>

        <!-- Interactive map & radius preview -->
        <div style="margin-bottom: 24px;">
            <label class="form-label" style="font-weight: 600; margin-bottom: 6px; font-size: 0.85rem; color:var(--text-main); display:flex; justify-content:space-between; align-items:center;">
                <span>📍 Pratinjau Titik Kantor & Lingkaran Radius Presensi</span>
                <span style="font-size:0.75rem; color:var(--text-muted); font-weight:normal;">Klik atau seret pin untuk atur titik</span>
            </label>
            <div id="picker-map" style="width:100%; height:250px; border-radius:var(--radius-sm); border:1px solid var(--border-color);"></div>
        </div>

        <div style="display:flex; gap: 12px; align-items:center;">
            <button type="submit" class="btn btn-blue" style="min-width: 110px; padding: 10px 24px; font-weight: 600;">
                Simpan
            </button>
            <a href="<?= Config::baseUrl('master/lokasi') ?>" class="btn btn-secondary">
                Kembali
            </a>
        </div>
    </form>
</div>

<script>
(() => {
    let map = null;

    function cleanupMap() {
        if (map) {
            try { map.remove(); } catch(e) {}
            map = null;
        }
        const container = document.getElementById('picker-map');
        if (container && container._leaflet_id) {
            delete container._leaflet_id;
            container.innerHTML = '';
        }
    }

    function initLokasiPicker() {
        const container = document.getElementById('picker-map');
        if (!container) return;

        cleanupMap();

        const latInput = document.getElementById('latitude');
        const lngInput = document.getElementById('longitude');
        const radiusInput = document.getElementById('radius');
        if (!latInput || !lngInput || !radiusInput) return;

        let initialLat = parseFloat(latInput.value) || -6.2087634;
        let initialLng = parseFloat(lngInput.value) || 106.845599;
        let initialRadius = parseInt(radiusInput.value) || 500;

        try {
            map = L.map('picker-map').setView([initialLat, initialLng], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap'
            }).addTo(map);
        } catch(e) {
            console.warn('Map initialization:', e);
            return;
        }

        let marker = L.marker([initialLat, initialLng], { draggable: true }).addTo(map);

        let circle = L.circle([initialLat, initialLng], {
            radius: Math.max(25, initialRadius),
            color: '#2563eb',
            fillColor: '#3b82f6',
            fillOpacity: 0.18,
            weight: 2
        }).addTo(map);

        const updateCoords = (lat, lng) => {
            latInput.value = lat.toFixed(8);
            lngInput.value = lng.toFixed(8);
            circle.setLatLng([lat, lng]);
        };

        map.on('click', (e) => {
            marker.setLatLng(e.latlng);
            updateCoords(e.latlng.lat, e.latlng.lng);
        });

        marker.on('dragend', (e) => {
            const pos = e.target.getLatLng();
            updateCoords(pos.lat, pos.lng);
        });

        radiusInput.addEventListener('input', () => {
            const r = parseInt(radiusInput.value) || 25;
            circle.setRadius(Math.max(25, r));
        });

        const syncMapFromInputs = () => {
            const lat = parseFloat(latInput.value);
            const lng = parseFloat(lngInput.value);
            if (!isNaN(lat) && !isNaN(lng) && lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180) {
                marker.setLatLng([lat, lng]);
                circle.setLatLng([lat, lng]);
                map.panTo([lat, lng]);
            }
        };

        latInput.addEventListener('change', syncMapFromInputs);
        lngInput.addEventListener('change', syncMapFromInputs);
    }

    document.addEventListener('turbo:before-cache', cleanupMap);
    document.addEventListener('turbo:before-visit', cleanupMap);
    window.addEventListener('beforeunload', cleanupMap);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLokasiPicker);
    } else {
        initLokasiPicker();
    }
})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
