<?php
$pageTitle = 'Presensi Masuk - ' . Config::APP_NAME;
$activePage = 'dashboard';
require_once __DIR__ . '/../layouts/header.php';
?>

<div style="margin-bottom: 12px; display:flex; justify-content:space-between; align-items:center;">
    <h2 style="font-size: 1.4rem; font-weight: 700;">Presensi Masuk</h2>
    <a href="<?= Config::baseUrl('dashboard') ?>" class="btn btn-secondary btn-sm">← Kembali</a>
</div>

<div id="alert-box" style="display:none;" class="alert"></div>

<div class="split-presensi-layout">
    <!-- Kolom Kiri: Peta Geofencing GPS -->
    <div class="presensi-pane">
        <div class="pane-title" style="display:flex; align-items:center; gap:8px;">
            <?= Icon::get('map-pin', 18) ?> <span>Lokasi GPS & Radius Presensi</span>
        </div>

        <div style="margin-bottom: 12px;">
            <label class="form-label" for="select-lokasi" style="font-size:0.8rem;">Titik Lokasi Kantor:</label>
            <select id="select-lokasi" class="form-select" style="padding:6px 10px; font-size:0.85rem;">
                <?php foreach ($lokasiList as $lok): ?>
                    <option value="<?= $lok['id'] ?>" 
                            data-lat="<?= $lok['latitude'] ?>" 
                            data-lng="<?= $lok['longitude'] ?>" 
                            data-radius="<?= $lok['radius'] ?>"
                            data-jam="<?= $lok['jam_masuk'] ?>"
                            <?= ($lok['id'] == ($defaultLokasi['id'] ?? 0)) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($lok['nama_lokasi']) ?> (Radius <?= $lok['radius'] ?>m)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="map-container"></div>

        <div id="distance-badge" class="distance-info-badge out-of-range" style="margin-top: 14px;">
            <span>Mengambil koordinat GPS...</span>
            <span id="distance-meter">-</span>
        </div>
    </div>

    <!-- Kolom Kanan: Kamera Selfie Webcam -->
    <div class="presensi-pane">
        <div class="pane-title" style="display:flex; align-items:center; gap:8px;">
            <?= Icon::get('camera', 18) ?> <span>Kamera Selfie Verifikasi</span>
        </div>

        <div class="camera-preview-wrapper">
            <span id="camera-mode-badge" class="camera-mode-badge">📷 Depan</span>
            <button type="button" id="btn-switch-camera" class="camera-switch-btn" title="Ganti ke Kamera Belakang">
                <span class="switch-icon"><?= Icon::get('refresh', 14) ?></span>
                <span id="camera-switch-label">Kamera Belakang</span>
            </button>
            <video id="webcam-video" autoplay playsinline></video>
            <canvas id="snapshot-canvas" style="display:none;"></canvas>
            <div class="camera-overlay-timestamp" id="camera-time-overlay">
                <?= date('d F Y - H:i:s') ?>
            </div>
        </div>

        <div style="margin-top: 18px; text-align: center;">
            <button id="btn-submit-masuk" class="btn btn-blue" style="width: 100%; padding: 12px; font-size: 1rem;" disabled>
                ⏳ Menyiapkan GPS & Kamera...
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const video = document.getElementById('webcam-video');
    const canvas = document.getElementById('snapshot-canvas');
    const submitBtn = document.getElementById('btn-submit-masuk');
    const distanceBadge = document.getElementById('distance-badge');
    const distanceMeter = document.getElementById('distance-meter');
    const selectLokasi = document.getElementById('select-lokasi');
    const alertBox = document.getElementById('alert-box');
    const btnSwitchCamera = document.getElementById('btn-switch-camera');
    const cameraModeBadge = document.getElementById('camera-mode-badge');
    const cameraSwitchLabel = document.getElementById('camera-switch-label');

    let map = null;
    let userMarker = null;
    let officeCircle = null;
    let officeMarker = null;
    let cameraStream = null;
    let currentFacingMode = 'user'; // 'user' (depan/selfie) atau 'environment' (belakang)

    // Device States Coordinator (Single Source of Truth)
    const gpsState = {
        status: 'pending', // 'pending' | 'ready' | 'error'
        errCode: null,
        lat: null,
        lng: null
    };

    const cameraState = {
        status: 'pending', // 'pending' | 'ready' | 'error'
        errName: null
    };

    function getCameraErrorDesc(name) {
        if (name === 'NotAllowedError' || name === 'PermissionDeniedError') {
            return 'Izin akses kamera diblokir oleh browser. Sistem memerlukan foto selfie untuk verifikasi.';
        }
        if (name === 'NotReadableError' || name === 'TrackStartError') {
            return 'Kamera sedang dipakai aplikasi lain (WhatsApp, Zoom, atau tab browser lain).';
        }
        if (name === 'NotFoundError' || name === 'DevicesNotFoundError') {
            return 'Perangkat keras kamera / webcam tidak terdeteksi di perangkat Anda.';
        }
        return 'Kamera tidak dapat diakses (' + (name || 'error') + ').';
    }

    function getGpsErrorDesc(code) {
        if (code === 1) { // PERMISSION_DENIED
            return 'Izin lokasi (GPS) diblokir oleh browser. Sistem memerlukan GPS untuk verifikasi radius kantor.';
        }
        if (code === 2) { // POSITION_UNAVAILABLE
            return 'Sinyal GPS tidak terdeteksi. Pastikan GPS/Lokasi perangkat aktif & bukan mode pesawat.';
        }
        if (code === 3) { // TIMEOUT
            return 'Waktu pencarian GPS habis karena sinyal satelit lemah.';
        }
        return 'Lokasi GPS belum terdeteksi.';
    }

    // 1. Koordinator Utama Notifikasi & Status Tombol
    function updateCoordinator() {
        renderAlerts();
        updateDistanceAndButton();
    }

    function renderAlerts() {
        // Kasus 1: KEDUA-DUANYA ERROR (Kamera & GPS belum aktif/ditolak)
        if (cameraState.status === 'error' && gpsState.status === 'error') {
            const html = `
                <div style="text-align: left; line-height: 1.5;">
                    <div style="font-weight: 700; font-size: 0.95rem; margin-bottom: 8px; display:flex; align-items:center; gap:8px;">
                        🚫 <span>Izin Kamera & Lokasi (GPS) Belum Aktif</span>
                    </div>
                    <p style="margin: 0 0 10px 0; font-size: 0.85rem;">
                        Sistem presensi membutuhkan akses <b>Kamera</b> (untuk verifikasi wajah) dan <b>Lokasi GPS</b> (untuk verifikasi radius kantor). Keduanya saat ini belum aktif / belum diizinkan.
                    </p>
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 10px; margin-bottom: 12px;">
                        <div style="background: rgba(0,0,0,0.04); padding: 10px 12px; border-radius: 6px; border: 1px solid var(--border-color); font-size: 0.82rem;">
                            <div style="font-weight: 700; margin-bottom: 4px; color: var(--danger); display:flex; align-items:center; gap:6px;">
                                📷 <span>Kamera Belum Terhubung</span>
                            </div>
                            <div style="margin-bottom:8px;">${getCameraErrorDesc(cameraState.errName)}</div>
                            <button type="button" class="btn btn-secondary btn-sm" id="btn-alert-retry-cam" style="font-size:0.75rem; padding:3px 10px;">
                                🔄 Coba Kamera Saja
                            </button>
                        </div>
                        <div style="background: rgba(0,0,0,0.04); padding: 10px 12px; border-radius: 6px; border: 1px solid var(--border-color); font-size: 0.82rem;">
                            <div style="font-weight: 700; margin-bottom: 4px; color: var(--danger); display:flex; align-items:center; gap:6px;">
                                📍 <span>Lokasi GPS Belum Terdeteksi</span>
                            </div>
                            <div style="margin-bottom:8px;">${getGpsErrorDesc(gpsState.errCode)}</div>
                            <button type="button" class="btn btn-secondary btn-sm" id="btn-alert-retry-gps" style="font-size:0.75rem; padding:3px 10px;">
                                🔄 Coba GPS Saja
                            </button>
                        </div>
                    </div>
                    <div style="background: rgba(0,0,0,0.04); padding: 8px 12px; border-radius: 6px; font-size: 0.82rem; margin-bottom: 12px; border: 1px solid var(--border-color);">
                        <b>Cara Mengaktifkan Keduanya di Browser:</b>
                        <div style="margin-top: 4px;">• <b>HP Android (Chrome):</b> Ketuk ikon <b>Setelan/Gembok 🔒</b> di kiri kolom alamat &rarr; <b>Izin Situs</b> &rarr; aktifkan toggle <b>Kamera</b> dan <b>Lokasi</b> &rarr; pilih <b>Izinkan</b>.</div>
                        <div style="margin-top: 2px;">• <b>iPhone (Safari):</b> Buka <b>Pengaturan HP &rarr; Safari &rarr;</b> pastikan <b>Kamera</b> dan <b>Lokasi</b> disetel ke <b>Izinkan</b>.</div>
                        <div style="margin-top: 2px;">• <b>Laptop / PC:</b> Ketuk ikon <b>Gembok 🔒</b> di kiri alamat web &rarr; aktifkan toggle <b>Kamera</b> dan <b>Lokasi</b> &rarr; muat ulang halaman.</div>
                    </div>
                    <div style="display:flex; flex-wrap:wrap; gap:8px;">
                        <button type="button" id="btn-retry-both" class="btn btn-blue btn-sm" style="display:inline-flex; align-items:center; gap:6px;">
                            🔄 Coba Deteksi Ulang Keduanya (GPS & Kamera)
                        </button>
                    </div>
                </div>
            `;
            showAlert('danger', html, true);

            const bBoth = document.getElementById('btn-retry-both');
            if (bBoth) bBoth.onclick = () => { initCamera(currentFacingMode, true); initGeolocation(true); };
            const bCam = document.getElementById('btn-alert-retry-cam');
            if (bCam) bCam.onclick = () => initCamera(currentFacingMode, true);
            const bGps = document.getElementById('btn-alert-retry-gps');
            if (bGps) bGps.onclick = () => initGeolocation(true);
            return;
        }

        // Kasus 2: Hanya KAMERA Error (GPS aman / pending)
        if (cameraState.status === 'error' && gpsState.status !== 'error') {
            const html = `
                <div style="text-align: left; line-height: 1.5;">
                    <div style="font-weight: 700; font-size: 0.95rem; margin-bottom: 6px;">
                        🚫 Izin Akses Kamera Belum Terhubung
                    </div>
                    <p style="margin: 0 0 8px 0; font-size: 0.85rem;">
                        ${getCameraErrorDesc(cameraState.errName)}
                    </p>
                    <div style="background: rgba(0,0,0,0.05); padding: 8px 12px; border-radius: 6px; font-size: 0.82rem; margin-bottom: 12px; border: 1px solid var(--border-color);">
                        <b>Cara Mengaktifkan Izin Kamera:</b>
                        <div style="margin-top: 4px;">• <b>HP Android (Chrome):</b> Ketuk ikon <b>Setelan/Gembok 🔒</b> di kiri kolom alamat &rarr; <b>Izin Situs</b> &rarr; <b>Kamera</b> &rarr; pilih <b>Izinkan</b>.</div>
                        <div style="margin-top: 2px;">• <b>iPhone (Safari):</b> Buka <b>Pengaturan HP &rarr; Safari &rarr; Kamera &rarr; Izinkan</b>.</div>
                        <div style="margin-top: 2px;">• <b>Laptop / PC:</b> Ketuk ikon <b>Gembok 🔒</b> di kiri alamat web &rarr; aktifkan toggle <b>Kamera</b> &rarr; muat ulang halaman.</div>
                    </div>
                    <button type="button" id="btn-retry-camera" class="btn btn-blue btn-sm" style="display:inline-flex; align-items:center; gap:6px;">
                        🔄 Coba Deteksi Ulang Kamera
                    </button>
                </div>
            `;
            showAlert('danger', html, true);
            const retryCameraBtn = document.getElementById('btn-retry-camera');
            if (retryCameraBtn) retryCameraBtn.onclick = () => initCamera(currentFacingMode, true);
            return;
        }

        // Kasus 3: Hanya GPS Error (Kamera aman / pending)
        if (gpsState.status === 'error' && cameraState.status !== 'error') {
            const html = `
                <div style="text-align: left; line-height: 1.5;">
                    <div style="font-weight: 700; font-size: 0.95rem; margin-bottom: 6px;">
                        🚫 Izin Lokasi (GPS) Belum Terdeteksi
                    </div>
                    <p style="margin: 0 0 8px 0; font-size: 0.85rem;">
                        ${getGpsErrorDesc(gpsState.errCode)}
                    </p>
                    <div style="background: rgba(0,0,0,0.05); padding: 8px 12px; border-radius: 6px; font-size: 0.82rem; margin-bottom: 12px; border: 1px solid var(--border-color);">
                        <b>Cara Mengaktifkan Izin Lokasi:</b>
                        <div style="margin-top: 4px;">• <b>HP Android (Chrome):</b> Ketuk ikon <b>Setelan/Gembok 🔒</b> di kiri kolom alamat &rarr; <b>Izin Situs</b> &rarr; <b>Lokasi</b> &rarr; pilih <b>Izinkan</b>.</div>
                        <div style="margin-top: 2px;">• <b>iPhone (Safari):</b> Buka <b>Pengaturan HP &rarr; Safari &rarr; Lokasi &rarr; Izinkan</b>.</div>
                        <div style="margin-top: 2px;">• <b>Laptop / PC:</b> Ketuk ikon <b>Gembok 🔒</b> di kiri alamat web &rarr; aktifkan toggle <b>Lokasi</b> &rarr; refresh halaman.</div>
                    </div>
                    <button type="button" id="btn-retry-gps" class="btn btn-blue btn-sm" style="display:inline-flex; align-items:center; gap:6px;">
                        🔄 Coba Deteksi Ulang GPS
                    </button>
                </div>
            `;
            showAlert('danger', html, true);
            const retryGpsBtn = document.getElementById('btn-retry-gps');
            if (retryGpsBtn) retryGpsBtn.onclick = () => initGeolocation(true);
            return;
        }

        // Kasus 4: Keduanya aman/ready
        alertBox.style.display = 'none';
        alertBox.innerHTML = '';
    }

    function updateDistanceAndButton() {
        const opt = selectLokasi.options[selectLokasi.selectedIndex];
        const officeLat = parseFloat(opt.getAttribute('data-lat'));
        const officeLng = parseFloat(opt.getAttribute('data-lng'));
        const radius = parseFloat(opt.getAttribute('data-radius'));

        let dist = null;
        let inRange = false;

        // Update Distance Badge & Peta
        if (gpsState.status === 'ready' && gpsState.lat !== null && gpsState.lng !== null) {
            dist = calcDistance(gpsState.lat, gpsState.lng, officeLat, officeLng);
            inRange = (dist <= radius);

            if (userMarker) map.removeLayer(userMarker);
            userMarker = L.marker([gpsState.lat, gpsState.lng]).addTo(map)
                .bindPopup("<b>Lokasi Anda Saat Ini</b>").openPopup();

            if (inRange) {
                distanceBadge.className = 'distance-info-badge in-range';
                distanceBadge.innerHTML = `<span>✅ Dalam Radius Kantor (${dist}m)</span><span>Maks: ${radius}m</span>`;
            } else {
                const over = dist - radius;
                distanceBadge.className = 'distance-info-badge out-of-range';
                distanceBadge.innerHTML = `<span>⚠️ Di Luar Radius Kantor (${dist}m)</span><span>Lebih ${over}m</span>`;
            }
        } else if (gpsState.status === 'error') {
            distanceBadge.className = 'distance-info-badge out-of-range';
            distanceBadge.innerHTML = `<span>⚠️ Lokasi GPS Belum Terdeteksi</span><button type="button" class="btn btn-secondary btn-sm" id="btn-quick-retry-gps" style="padding:2px 8px; font-size:0.75rem;">🔄 Coba Lagi</button>`;
            const quickRetry = document.getElementById('btn-quick-retry-gps');
            if (quickRetry) quickRetry.onclick = () => initGeolocation(true);
        } else {
            distanceBadge.className = 'distance-info-badge out-of-range';
            distanceBadge.innerHTML = `<span>⏳ Mendeteksi sinyal GPS...</span><span>Mohon tunggu</span>`;
        }

        // Update Status Tombol Presensi
        if (gpsState.status === 'ready' && cameraState.status === 'ready') {
            if (inRange) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Masuk Sekarang';
            } else {
                submitBtn.disabled = true;
                submitBtn.textContent = `Di Luar Radius (${dist}m > ${radius}m)`;
            }
        } else if (cameraState.status === 'ready' && gpsState.status !== 'ready') {
            submitBtn.disabled = true;
            submitBtn.textContent = (gpsState.status === 'error') ? 'Lokasi GPS Belum Terdeteksi' : 'Menunggu Lokasi GPS...';
        } else if (gpsState.status === 'ready' && cameraState.status !== 'ready') {
            submitBtn.disabled = true;
            submitBtn.textContent = (cameraState.status === 'error') ? 'Kamera Belum Terhubung' : 'Menyiapkan Kamera...';
        } else {
            submitBtn.disabled = true;
            if (gpsState.status === 'error' && cameraState.status === 'error') {
                submitBtn.textContent = 'GPS & Kamera Belum Siap';
            } else {
                submitBtn.textContent = 'Menyiapkan GPS & Kamera...';
            }
        }
    }

    // 2. Inisialisasi Kamera dengan Dukungan Kamera Depan & Belakang
    async function initCamera(facingMode = currentFacingMode, isRetry = false) {
        currentFacingMode = facingMode;
        cameraState.status = 'pending';
        cameraState.errName = null;
        updateCoordinator();

        if (currentFacingMode === 'environment') {
            video.classList.add('unmirrored');
            cameraModeBadge.textContent = '📷 Belakang';
            cameraSwitchLabel.textContent = 'Kamera Depan';
            btnSwitchCamera.title = 'Ganti ke Kamera Depan (Selfie)';
        } else {
            video.classList.remove('unmirrored');
            cameraModeBadge.textContent = '📷 Depan';
            cameraSwitchLabel.textContent = 'Kamera Belakang';
            btnSwitchCamera.title = 'Ganti ke Kamera Belakang';
        }

        stopCamera();

        try {
            const constraints = {
                video: {
                    facingMode: { ideal: currentFacingMode },
                    width: { ideal: 640 },
                    height: { ideal: 480 }
                },
                audio: false
            };

            cameraStream = await navigator.mediaDevices.getUserMedia(constraints);
            video.srcObject = cameraStream;
            cameraState.status = 'ready';
            cameraState.errName = null;

            await video.play().catch(() => {});
            updateCoordinator();
        } catch (err) {
            console.warn("Camera init error for " + currentFacingMode + ":", err);

            if (err.name === 'OverconstrainedError' && !isRetry) {
                try {
                    cameraStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
                    video.srcObject = cameraStream;
                    cameraState.status = 'ready';
                    cameraState.errName = null;
                    await video.play().catch(() => {});
                    updateCoordinator();
                    return;
                } catch (fallbackErr) {
                    cameraState.status = 'error';
                    cameraState.errName = fallbackErr.name || 'Error';
                    updateCoordinator();
                    return;
                }
            }

            cameraState.status = 'error';
            cameraState.errName = err.name || 'Error';
            updateCoordinator();
        }
    }

    function stopCamera() {
        if (cameraStream) {
            cameraStream.getTracks().forEach(track => {
                track.stop();
            });
            cameraStream = null;
        }
    }

    btnSwitchCamera.addEventListener('click', async () => {
        btnSwitchCamera.classList.add('switching');
        const nextMode = (currentFacingMode === 'user') ? 'environment' : 'user';
        await initCamera(nextMode);
        setTimeout(() => btnSwitchCamera.classList.remove('switching'), 400);
    });

    window.addEventListener('beforeunload', stopCamera);

    // 3. Inisialisasi Peta Leaflet
    function initMap(officeLat, officeLng, radiusMeters) {
        if (!map) {
            map = L.map('map-container').setView([officeLat, officeLng], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);
        } else {
            map.setView([officeLat, officeLng], 15);
        }

        if (officeMarker) map.removeLayer(officeMarker);
        if (officeCircle) map.removeLayer(officeCircle);

        officeMarker = L.marker([officeLat, officeLng]).addTo(map)
            .bindPopup("<b>Titik Kantor SGM</b>").openPopup();

        officeCircle = L.circle([officeLat, officeLng], {
            color: '#ef4444',
            fillColor: '#f87171',
            fillOpacity: 0.3,
            radius: radiusMeters
        }).addTo(map);
    }

    // 4. Hitung Jarak Haversine di Frontend
    function calcDistance(lat1, lon1, lat2, lon2) {
        const R = 6371000;
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                  Math.sin(dLon/2) * Math.sin(dLon/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return Math.round(R * c);
    }

    // 5. Inisialisasi Lokasi GPS dengan Fallback & Pemulihan Interaktif
    function initGeolocation(isRetry = false) {
        if (!navigator.geolocation) {
            gpsState.status = 'error';
            gpsState.errCode = 2;
            updateCoordinator();
            return;
        }

        gpsState.status = 'pending';
        gpsState.errCode = null;
        updateCoordinator();

        const highAccuracyOptions = { enableHighAccuracy: true, timeout: 8000, maximumAge: 0 };
        const lowAccuracyOptions = { enableHighAccuracy: false, timeout: 12000, maximumAge: 60000 };

        function onGpsSuccess(pos) {
            gpsState.status = 'ready';
            gpsState.errCode = null;
            gpsState.lat = pos.coords.latitude;
            gpsState.lng = pos.coords.longitude;

            const opt = selectLokasi.options[selectLokasi.selectedIndex];
            const officeLat = parseFloat(opt.getAttribute('data-lat'));
            const officeLng = parseFloat(opt.getAttribute('data-lng'));
            const radius = parseFloat(opt.getAttribute('data-radius'));

            initMap(officeLat, officeLng, radius);
            updateCoordinator();
        }

        function onGpsError(err) {
            console.warn("GPS High Accuracy Error:", err.code, err.message);

            if ((err.code === err.TIMEOUT || err.code === err.POSITION_UNAVAILABLE) && !isRetry) {
                console.info("Mencoba fallback geolokasi dengan low accuracy...");
                navigator.geolocation.getCurrentPosition(onGpsSuccess, (fallbackErr) => handleFinalGpsError(fallbackErr), lowAccuracyOptions);
                return;
            }

            handleFinalGpsError(err);
        }

        function handleFinalGpsError(err) {
            console.error("GPS Final Error:", err);
            gpsState.status = 'error';
            gpsState.errCode = err.code || 2;
            updateCoordinator();
        }

        navigator.geolocation.getCurrentPosition(onGpsSuccess, onGpsError, highAccuracyOptions);
    }

    selectLokasi.addEventListener('change', () => {
        const opt = selectLokasi.options[selectLokasi.selectedIndex];
        const officeLat = parseFloat(opt.getAttribute('data-lat'));
        const officeLng = parseFloat(opt.getAttribute('data-lng'));
        const radius = parseFloat(opt.getAttribute('data-radius'));
        initMap(officeLat, officeLng, radius);
        updateCoordinator();
    });

    let isSubmitting = false;

    // 6. Handle Submit Absen Masuk (Ringan & Cepat Terkompresi)
    submitBtn.addEventListener('click', async () => {
        if (isSubmitting || submitBtn.disabled) return;
        if (!video.videoWidth) {
            showAlert('danger', 'Kamera belum siap, tunggu beberapa detik.');
            return;
        }

        if (gpsState.lat === null || gpsState.lng === null) {
            showAlert('danger', 'Lokasi GPS belum terdeteksi. Silakan aktifkan GPS.');
            return;
        }

        isSubmitting = true;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Memproses Presensi...';

        // Downscale canvas secara proporsional ke maksimal 640px agar encoding instan & hemat bandwidth
        const maxDimension = 640;
        let targetWidth = video.videoWidth;
        let targetHeight = video.videoHeight;

        if (targetWidth > maxDimension || targetHeight > maxDimension) {
            if (targetWidth >= targetHeight) {
                targetHeight = Math.round((targetHeight / targetWidth) * maxDimension);
                targetWidth = maxDimension;
            } else {
                targetWidth = Math.round((targetWidth / targetHeight) * maxDimension);
                targetHeight = maxDimension;
            }
        }

        canvas.width = targetWidth;
        canvas.height = targetHeight;
        const ctx = canvas.getContext('2d');
        if (currentFacingMode === 'user') {
            // Mirror back agar orientasi selfie alami
            ctx.translate(canvas.width, 0);
            ctx.scale(-1, 1);
        } else {
            // Kamera belakang: render normal tanpa mirror
            ctx.setTransform(1, 0, 0, 1, 0, 0);
        }
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

        // Kompresi ke JPEG dengan kualitas 0.70 (~35KB - 50KB, hemat 95% bandwidth)
        const photoBase64 = canvas.toDataURL('image/jpeg', 0.70);

        const formData = new FormData();
        formData.append('image', photoBase64);
        formData.append('latitude', gpsState.lat);
        formData.append('longitude', gpsState.lng);
        formData.append('lokasi_id', selectLokasi.value);

        try {
            const resp = await fetch('<?= Config::baseUrl('presensi/masuk') ?>', {
                method: 'POST',
                body: formData
            });
            const result = await resp.json();

            if (result.status === 'success') {
                stopCamera(); // Segera matikan stream kamera ponsel
                showAlert('success', result.message);
                setTimeout(() => {
                    window.location.href = result.redirect;
                }, 1200);
            } else {
                if (result.session_expired) {
                    stopCamera();
                    showAlert('danger', result.message);
                    setTimeout(() => {
                        window.location.href = result.redirect || '<?= Config::baseUrl("login") ?>';
                    }, 2000);
                    return;
                }
                showAlert('danger', result.message);
                isSubmitting = false;
                submitBtn.disabled = false;
                submitBtn.textContent = 'Masuk Sekarang';
            }
        } catch (e) {
            console.error(e);
            showAlert('danger', 'Terjadi kesalahan komunikasi dengan server.');
            isSubmitting = false;
            submitBtn.disabled = false;
            submitBtn.textContent = 'Masuk Sekarang';
        }
    });

    function showAlert(type, msg, isBlock = false) {
        alertBox.className = `alert alert-${type}` + (isBlock ? ' alert-block' : '');
        alertBox.innerHTML = msg;
        alertBox.style.display = 'block';
        alertBox.scrollIntoView({ behavior: 'smooth' });
    }

    initCamera();
    initGeolocation();
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
