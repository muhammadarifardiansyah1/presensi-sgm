<?php
/**
 * Lokasi Controller
 * Absensi Surya Gemilang Mojopahit (SGM)
 */

require_once __DIR__ . '/../Config/Config.php';
require_once __DIR__ . '/AuthController.php';
require_once __DIR__ . '/../Models/Lokasi.php';

class LokasiController {
    private Lokasi $lokasiModel;

    public function __construct() {
        $this->lokasiModel = new Lokasi();
    }

    public function index(): void {
        $currentUser = AuthController::checkAdmin();
        $lokasiList = $this->lokasiModel->getAll();
        require_once __DIR__ . '/../Views/master/lokasi_index.php';
    }

    public function detail(int $id): void {
        $currentUser = AuthController::checkAdmin();
        $lokasi = $this->lokasiModel->findById($id);
        if (!$lokasi) {
            header("Location: " . Config::baseUrl('master/lokasi'));
            exit;
        }
        require_once __DIR__ . '/../Views/master/lokasi_detail.php';
    }

    public function tambah(): void {
        $currentUser = AuthController::checkAdmin();
        $error = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            [$error, $data] = $this->validateInput($_POST);
            if (!$error) {
                if ($this->lokasiModel->create($data)) {
                    header("Location: " . Config::baseUrl('master/lokasi?msg=added'));
                    exit;
                } else {
                    $error = 'Gagal menyimpan data lokasi.';
                }
            }
        }

        require_once __DIR__ . '/../Views/master/lokasi_form.php';
    }

    public function edit(int $id): void {
        $currentUser = AuthController::checkAdmin();
        $lokasi = $this->lokasiModel->findById($id);
        if (!$lokasi) {
            header("Location: " . Config::baseUrl('master/lokasi'));
            exit;
        }

        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            [$error, $data] = $this->validateInput($_POST);
            if (!$error) {
                if ($this->lokasiModel->update($id, $data)) {
                    header("Location: " . Config::baseUrl('master/lokasi?msg=updated'));
                    exit;
                } else {
                    $error = 'Gagal memperbarui data lokasi.';
                }
            } else {
                // Populate submitted data back to form so user does not lose input
                $lokasi = array_merge($lokasi, $data);
            }
        }

        require_once __DIR__ . '/../Views/master/lokasi_form.php';
    }

    public function hapus(int $id): void {
        AuthController::checkAdmin();
        $usage = $this->lokasiModel->isUsed($id);
        if ($usage['is_used']) {
            $details = [];
            if ($usage['user_count'] > 0) $details[] = $usage['user_count'] . ' pegawai aktif';
            if ($usage['presensi_count'] > 0) $details[] = $usage['presensi_count'] . ' catatan presensi';
            header("Location: " . Config::baseUrl('master/lokasi?err=in_use&detail=' . urlencode(implode(' dan ', $details))));
            exit;
        }

        $this->lokasiModel->delete($id);
        header("Location: " . Config::baseUrl('master/lokasi?msg=deleted'));
        exit;
    }

    private function validateInput(array $post): array {
        $namaLokasi   = trim($post['nama_lokasi'] ?? '');
        $alamatLokasi = trim($post['alamat_lokasi'] ?? '');
        $tipeLokasi   = in_array($post['tipe_lokasi'] ?? '', ['Pusat', 'Cabang']) ? $post['tipe_lokasi'] : 'Cabang';
        $latitude     = (float) ($post['latitude'] ?? 0);
        $longitude    = (float) ($post['longitude'] ?? 0);
        $radius       = (int) ($post['radius'] ?? 500);
        $zonaWaktu    = in_array($post['zona_waktu'] ?? '', ['WIB', 'WITA', 'WIT']) ? $post['zona_waktu'] : 'WIB';
        $jamMasuk     = trim($post['jam_masuk'] ?? '08:00:00');
        $jamPulang    = trim($post['jam_pulang'] ?? '17:00:00');

        if (strlen($jamMasuk) === 5) $jamMasuk .= ':00';
        if (strlen($jamPulang) === 5) $jamPulang .= ':00';

        $error = null;
        if ($namaLokasi === '') {
            $error = 'Nama lokasi wajib diisi!';
        } elseif ($latitude < -90.0 || $latitude > 90.0 || abs($latitude) < 0.00001) {
            $error = 'Koordinat Latitude tidak valid! Nilai wajib berada di antara -90.0 s/d 90.0 (tidak boleh 0).';
        } elseif ($longitude < -180.0 || $longitude > 180.0 || abs($longitude) < 0.00001) {
            $error = 'Koordinat Longitude tidak valid! Nilai wajib berada di antara -180.0 s/d 180.0 (tidak boleh 0).';
        } elseif ($radius < 25) {
            $error = 'Radius kantor minimal 25 meter agar GPS smartphone karyawan dapat mendeteksi lokasi dengan stabil.';
        } elseif ($radius > 50000) {
            $error = 'Radius kantor maksimal 50.000 meter (50 km).';
        } elseif ($jamMasuk === $jamPulang) {
            $error = 'Jam masuk dan jam pulang kantor tidak boleh sama.';
        }

        $data = [
            'nama_lokasi'   => $namaLokasi,
            'alamat_lokasi' => $alamatLokasi,
            'tipe_lokasi'   => $tipeLokasi,
            'latitude'      => $latitude,
            'longitude'     => $longitude,
            'radius'        => $radius,
            'zona_waktu'    => $zonaWaktu,
            'jam_masuk'     => $jamMasuk,
            'jam_pulang'    => $jamPulang,
        ];

        return [$error, $data];
    }
}
