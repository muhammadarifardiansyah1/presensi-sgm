<?php
/**
 * Penugasan Lembur Controller
 * Absensi Surya Gemilang Mojopahit (SGM)
 */

require_once __DIR__ . '/../Config/Config.php';
require_once __DIR__ . '/AuthController.php';
require_once __DIR__ . '/../Models/PenugasanLembur.php';
require_once __DIR__ . '/../Models/Lokasi.php';
require_once __DIR__ . '/../Models/User.php';

class LemburController {
    private PenugasanLembur $lemburModel;
    private Lokasi $lokasiModel;
    private User $userModel;

    public function __construct() {
        $this->lemburModel = new PenugasanLembur();
        $this->lokasiModel = new Lokasi();
        $this->userModel = new User();
    }

    /**
     * Halaman Utama: Daftar Penugasan Lembur Karyawan
     */
    public function index(): void {
        $currentUser = AuthController::checkAdmin();

        $bulan    = isset($_GET['bulan']) && $_GET['bulan'] !== '' ? (int)$_GET['bulan'] : (int)date('m');
        $tahun    = isset($_GET['tahun']) && $_GET['tahun'] !== '' ? (int)$_GET['tahun'] : (int)date('Y');
        $lokasiId = isset($_GET['lokasi_id']) && $_GET['lokasi_id'] !== '' ? (int)$_GET['lokasi_id'] : null;
        $status   = isset($_GET['status']) && $_GET['status'] !== '' ? trim($_GET['status']) : null;

        $penugasanList = $this->lemburModel->getAllPenugasan($bulan, $tahun, $lokasiId, $status);
        $lokasiList = $this->lokasiModel->getAll();

        require_once __DIR__ . '/../Views/lembur/index.php';
    }

    /**
     * Form Tambah Penugasan Lembur
     */
    public function tambah(): void {
        $currentUser = AuthController::checkAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $tanggal = trim($_POST['tanggal'] ?? '');
            $lokasiId = (int)($_POST['lokasi_id'] ?? 0);
            $jamMulai = trim($_POST['jam_mulai'] ?? '');
            $jamSelesai = trim($_POST['jam_selesai'] ?? '');
            $keterangan = trim($_POST['keterangan'] ?? '');
            $selectedUsers = $_POST['user_ids'] ?? [];

            if (empty($tanggal) || $lokasiId <= 0 || empty($jamMulai) || empty($jamSelesai) || empty($keterangan)) {
                header('Location: ' . Config::baseUrl('lembur/tambah?err=empty_fields'));
                exit;
            }

            if (strtotime($jamSelesai) <= strtotime($jamMulai)) {
                header('Location: ' . Config::baseUrl('lembur/tambah?err=invalid_time'));
                exit;
            }

            if (!is_array($selectedUsers) || empty($selectedUsers)) {
                header('Location: ' . Config::baseUrl('lembur/tambah?err=no_employee_selected'));
                exit;
            }

            try {
                $newId = $this->lemburModel->createPenugasan([
                    'tanggal' => $tanggal,
                    'lokasi_id' => $lokasiId,
                    'jam_mulai' => $jamMulai,
                    'jam_selesai' => $jamSelesai,
                    'keterangan' => $keterangan,
                    'status' => 'aktif'
                ], $selectedUsers, (int)$currentUser['id']);

                header('Location: ' . Config::baseUrl('lembur?msg=created'));
                exit;
            } catch (Exception $e) {
                header('Location: ' . Config::baseUrl('lembur/tambah?err=save_failed'));
                exit;
            }
        }

        $lokasiList = $this->lokasiModel->getAll();
        $isEdit = false;
        $penugasan = [
            'id' => 0,
            'tanggal' => date('Y-m-d'),
            'lokasi_id' => !empty($lokasiList[0]['id']) ? (int)$lokasiList[0]['id'] : 0,
            'jam_mulai' => '17:00',
            'jam_selesai' => '20:00',
            'keterangan' => '',
            'status' => 'aktif'
        ];
        $assignedUserIds = [];

        // Ambil default pegawai lokasi pertama
        $defaultLokasiId = $penugasan['lokasi_id'];
        $pegawaiList = $this->lemburModel->getPegawaiByLokasi($defaultLokasiId);

        require_once __DIR__ . '/../Views/lembur/form.php';
    }

    /**
     * Form Edit Penugasan Lembur
     */
    public function edit(int $id): void {
        $currentUser = AuthController::checkAdmin();

        $penugasan = $this->lemburModel->findById($id);
        if (!$penugasan) {
            header('Location: ' . Config::baseUrl('lembur?err=not_found'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $tanggal = trim($_POST['tanggal'] ?? '');
            $lokasiId = (int)($_POST['lokasi_id'] ?? 0);
            $jamMulai = trim($_POST['jam_mulai'] ?? '');
            $jamSelesai = trim($_POST['jam_selesai'] ?? '');
            $keterangan = trim($_POST['keterangan'] ?? '');
            $status = trim($_POST['status'] ?? 'aktif');
            $selectedUsers = $_POST['user_ids'] ?? [];

            if (empty($tanggal) || $lokasiId <= 0 || empty($jamMulai) || empty($jamSelesai) || empty($keterangan)) {
                header('Location: ' . Config::baseUrl("lembur/edit?id={$id}&err=empty_fields"));
                exit;
            }

            if (strtotime($jamSelesai) <= strtotime($jamMulai)) {
                header('Location: ' . Config::baseUrl("lembur/edit?id={$id}&err=invalid_time"));
                exit;
            }

            if (!is_array($selectedUsers) || empty($selectedUsers)) {
                header('Location: ' . Config::baseUrl("lembur/edit?id={$id}&err=no_employee_selected"));
                exit;
            }

            try {
                $this->lemburModel->updatePenugasan($id, [
                    'tanggal' => $tanggal,
                    'lokasi_id' => $lokasiId,
                    'jam_mulai' => $jamMulai,
                    'jam_selesai' => $jamSelesai,
                    'keterangan' => $keterangan,
                    'status' => $status
                ], $selectedUsers);

                header('Location: ' . Config::baseUrl('lembur?msg=updated'));
                exit;
            } catch (Exception $e) {
                header('Location: ' . Config::baseUrl("lembur/edit?id={$id}&err=save_failed"));
                exit;
            }
        }

        $isEdit = true;
        $lokasiList = $this->lokasiModel->getAll();
        $assignedUserIds = $this->lemburModel->getPegawaiIdsByPenugasan($id);
        $pegawaiList = $this->lemburModel->getPegawaiByLokasi((int)$penugasan['lokasi_id'], $assignedUserIds);

        require_once __DIR__ . '/../Views/lembur/form.php';
    }

    /**
     * Halaman Detail Penugasan Lembur & Audit Pegawai
     */
    public function detail(int $id): void {
        $currentUser = AuthController::checkAdmin();

        $penugasan = $this->lemburModel->findById($id);
        if (!$penugasan) {
            header('Location: ' . Config::baseUrl('lembur?err=not_found'));
            exit;
        }

        $pegawaiList = $this->lemburModel->getPegawaiByPenugasan($id);

        require_once __DIR__ . '/../Views/lembur/detail.php';
    }

    /**
     * Hapus Penugasan Lembur
     */
    public function hapus(int $id): void {
        AuthController::checkAdmin();

        $penugasan = $this->lemburModel->findById($id);
        if (!$penugasan) {
            header('Location: ' . Config::baseUrl('lembur?err=not_found'));
            exit;
        }

        $sukses = $this->lemburModel->deletePenugasan($id);
        if ($sukses) {
            header('Location: ' . Config::baseUrl('lembur?msg=deleted'));
        } else {
            header('Location: ' . Config::baseUrl('lembur?err=delete_failed'));
        }
        exit;
    }

    /**
     * AJAX Endpoint: Ambil daftar pegawai berdasarkan lokasi
     */
    public function getPegawaiByLokasiJson(): void {
        AuthController::checkAdmin();

        $lokasiId = isset($_GET['lokasi_id']) ? (int)$_GET['lokasi_id'] : 0;
        $pegawaiList = $this->lemburModel->getPegawaiByLokasi($lokasiId);

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'data' => $pegawaiList
        ]);
        exit;
    }
}
