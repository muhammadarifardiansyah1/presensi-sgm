<?php
/**
 * Presensi Controller
 * Absensi Surya Gemilang Mojopahit (SGM)
 */

require_once __DIR__ . '/../Config/Config.php';
require_once __DIR__ . '/AuthController.php';
require_once __DIR__ . '/../Models/Presensi.php';
require_once __DIR__ . '/../Models/Lokasi.php';
require_once __DIR__ . '/../Helpers/ImageHelper.php';

class PresensiController {
    private Presensi $presensiModel;
    private Lokasi $lokasiModel;

    public function __construct() {
        $this->presensiModel = new Presensi();
        $this->lokasiModel = new Lokasi();
    }

    /**
     * Hitung jarak dua titik koordinat GPS menggunakan formula Haversine (meter)
     */
    public static function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float {
        $earthRadius = 6371000; // Radius bumi dalam meter
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return round($earthRadius * $c, 2);
    }

    private function saveBase64Image(string $base64Data, string $prefix): ?string {
        $uploadDir = __DIR__ . '/../../assets/images/uploads/presensi/';
        return ImageHelper::saveBase64Optimized($base64Data, $uploadDir, $prefix, 640, 75);
    }

    private function calculateLateness(string $jamMasukAktual, string $jamMasukJadwal): string {
        $tAktual = strtotime($jamMasukAktual);
        $tJadwal = strtotime($jamMasukJadwal);
        if ($tAktual > $tJadwal) {
            $diff = $tAktual - $tJadwal;
            $hours = floor($diff / 3600);
            $minutes = floor(($diff % 3600) / 60);
            return "{$hours} jam {$minutes} menit";
        }
        return 'Tepat Waktu';
    }

    private function calculateWorkDuration(string $jamMasuk, string $jamPulang): string {
        $tMasuk = strtotime($jamMasuk);
        $tPulang = strtotime($jamPulang);
        if ($tPulang > $tMasuk) {
            $diff = $tPulang - $tMasuk;
            $hours = floor($diff / 3600);
            $minutes = floor(($diff % 3600) / 60);
            return "{$hours} jam {$minutes} menit";
        }
        return '0 jam 0 menit';
    }

    public function masuk(): void {
        $currentUser = AuthController::checkAuth();
        $today = date('Y-m-d');
        $presensiHariIni = $this->presensiModel->getHariIni($currentUser['id'], $today);

        // Jika sudah absen masuk, arahkan ke dashboard
        if ($presensiHariIni && !empty($presensiHariIni['jam_masuk'])) {
            header("Location: " . Config::baseUrl('dashboard'));
            exit;
        }

        $lokasiList = $this->lokasiModel->getAll();
        $defaultLokasi = null;
        if (!empty($currentUser['lokasi_id'])) {
            $defaultLokasi = $this->lokasiModel->findById((int)$currentUser['lokasi_id']);
        }
        if (!$defaultLokasi) {
            $defaultLokasi = $this->lokasiModel->getDefault() ?: [
                'id' => 1,
                'nama_lokasi' => 'Kantor Utama SGM',
                'latitude' => -4.01771108,
                'longitude' => 103.23932119,
                'radius' => 500,
                'jam_masuk' => '08:00:00'
            ];
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');

            $fotoBase64 = $_POST['image'] ?? '';
            $lat        = (float) ($_POST['latitude'] ?? 0);
            $long       = (float) ($_POST['longitude'] ?? 0);
            $lokasiId   = (int) ($_POST['lokasi_id'] ?? $defaultLokasi['id']);

            if (empty($fotoBase64) || $lat == 0 || $long == 0) {
                echo json_encode(['status' => 'error', 'message' => 'Foto dan koordinat GPS wajib diisi!']);
                exit;
            }

            $lokasi = $this->lokasiModel->findById($lokasiId) ?: $defaultLokasi;

            // Hitung jarak ke kantor dengan rumus Haversine
            $jarak = self::calculateDistance($lat, $long, (float)$lokasi['latitude'], (float)$lokasi['longitude']);

            // Validasi Geofence Radius
            if ($jarak > (float)$lokasi['radius']) {
                $selisih = round($jarak - (float)$lokasi['radius']);
                echo json_encode([
                    'status' => 'error',
                    'message' => "Anda berada di luar radius kantor! Jarak Anda: {$jarak}m (Maksimal: {$lokasi['radius']}m, selisih {$selisih}m)."
                ]);
                exit;
            }

            // Double-check proteksi race condition / double-click
            $checkAgain = $this->presensiModel->getHariIni($currentUser['id'], $today);
            if ($checkAgain && !empty($checkAgain['jam_masuk'])) {
                echo json_encode([
                    'status' => 'error', 
                    'message' => 'Presensi masuk untuk hari ini sudah pernah dicatat sebelumnya.'
                ]);
                exit;
            }

            // Simpan foto
            $filename = $this->saveBase64Image($fotoBase64, 'masuk_' . $currentUser['id']);
            if (!$filename) {
                echo json_encode(['status' => 'error', 'message' => 'Gagal memproses foto presensi!']);
                exit;
            }

            $jamMasuk = date('H:i:s');
            $terlambat = $this->calculateLateness($jamMasuk, $lokasi['jam_masuk'] ?? '08:00:00');
            $status = ($terlambat === 'Tepat Waktu') ? 'hadir' : 'terlambat';

            $sukses = $this->presensiModel->absenMasuk(
                $currentUser['id'],
                $lokasi['id'],
                $today,
                $jamMasuk,
                $filename,
                $lat,
                $long,
                $jarak,
                $status,
                $terlambat
            );

            if ($sukses) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Presensi Masuk berhasil tercatat! Selamat bekerja.',
                    'redirect' => Config::baseUrl('dashboard')
                ]);
            } else {
                $check = $this->presensiModel->getHariIni($currentUser['id'], $today);
                if ($check && !empty($check['jam_masuk'])) {
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Presensi Masuk Anda sudah tercatat sebelumnya.',
                        'redirect' => Config::baseUrl('dashboard')
                    ]);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan sistem saat menyimpan presensi.']);
                }
            }
            exit;
        }

        require_once __DIR__ . '/../Views/presensi/masuk.php';
    }

    public function keluar(): void {
        $currentUser = AuthController::checkAuth();
        $today = date('Y-m-d');
        $presensiHariIni = $this->presensiModel->getHariIni($currentUser['id'], $today);

        // Harus sudah absen masuk terlebih dahulu
        if (!$presensiHariIni || empty($presensiHariIni['jam_masuk'])) {
            header("Location: " . Config::baseUrl('dashboard'));
            exit;
        }

        // Jika sudah absen keluar, redirect
        if (!empty($presensiHariIni['jam_pulang'])) {
            header("Location: " . Config::baseUrl('dashboard'));
            exit;
        }

        $lokasiId = $presensiHariIni['lokasi_id'] ?: 1;
        $lokasi = $this->lokasiModel->findById($lokasiId) ?: $this->lokasiModel->getDefault();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');

            $fotoBase64 = $_POST['image'] ?? '';
            $lat        = (float) ($_POST['latitude'] ?? 0);
            $long       = (float) ($_POST['longitude'] ?? 0);

            if (empty($fotoBase64) || $lat == 0 || $long == 0) {
                echo json_encode(['status' => 'error', 'message' => 'Foto dan koordinat GPS wajib diisi!']);
                exit;
            }

            // Hitung jarak ke kantor
            $jarak = self::calculateDistance($lat, $long, (float)$lokasi['latitude'], (float)$lokasi['longitude']);

            // Validasi Geofence Radius
            if ($jarak > (float)$lokasi['radius']) {
                $selisih = round($jarak - (float)$lokasi['radius']);
                echo json_encode([
                    'status' => 'error',
                    'message' => "Anda berada di luar radius kantor! Jarak Anda: {$jarak}m (Maksimal: {$lokasi['radius']}m, selisih {$selisih}m)."
                ]);
                exit;
            }

            // Double-check proteksi race condition / double-click
            $checkAgain = $this->presensiModel->getHariIni($currentUser['id'], $today);
            if (!$checkAgain || empty($checkAgain['jam_masuk'])) {
                echo json_encode([
                    'status' => 'error', 
                    'message' => 'Data presensi masuk tidak ditemukan. Silakan presensi masuk terlebih dahulu.'
                ]);
                exit;
            }
            if (!empty($checkAgain['jam_pulang'])) {
                echo json_encode([
                    'status' => 'error', 
                    'message' => 'Presensi pulang untuk hari ini sudah pernah dicatat sebelumnya.'
                ]);
                exit;
            }

            $filename = $this->saveBase64Image($fotoBase64, 'keluar_' . $currentUser['id']);
            if (!$filename) {
                echo json_encode(['status' => 'error', 'message' => 'Gagal memproses foto presensi!']);
                exit;
            }

            $jamPulang = date('H:i:s');
            $totalJam = $this->calculateWorkDuration($presensiHariIni['jam_masuk'], $jamPulang);

            $sukses = $this->presensiModel->absenKeluar(
                $presensiHariIni['id'],
                $jamPulang,
                $filename,
                $lat,
                $long,
                $jarak,
                $totalJam
            );

            if ($sukses) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Presensi Pulang berhasil! Terima kasih atas dedikasi Anda hari ini.',
                    'redirect' => Config::baseUrl('dashboard')
                ]);
            } else {
                $check = $this->presensiModel->getHariIni($currentUser['id'], $today);
                if ($check && !empty($check['jam_pulang'])) {
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Presensi Pulang Anda sudah tercatat sebelumnya.',
                        'redirect' => Config::baseUrl('dashboard')
                    ]);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui presensi pulang.']);
                }
            }
            exit;
        }

        require_once __DIR__ . '/../Views/presensi/keluar.php';
    }
}
