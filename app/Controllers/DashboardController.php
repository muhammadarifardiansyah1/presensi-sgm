<?php
/**
 * Dashboard Controller
 * Absensi Surya Gading Mandiri (SGM)
 */

require_once __DIR__ . '/../Config/Config.php';
require_once __DIR__ . '/AuthController.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Models/Presensi.php';
require_once __DIR__ . '/../Models/Ketidakhadiran.php';

class DashboardController {
    private User $userModel;
    private Presensi $presensiModel;
    private Ketidakhadiran $ketidakhadiranModel;

    public function __construct() {
        $this->userModel = new User();
        $this->presensiModel = new Presensi();
        $this->ketidakhadiranModel = new Ketidakhadiran();
    }

    public function index(): void {
        $currentUser = AuthController::checkAuth();
        $today = date('Y-m-d');

        if ($currentUser['role'] === 'admin') {
            // Stats for Admin
            $totalPegawai = $this->userModel->countPegawaiAktif();
            $jumlahHadir = $this->presensiModel->countHadirHariIni($today);
            $jumlahAlpa  = $this->presensiModel->countAlpaHariIni($today);
            $jumlahSakitIzinCuti = $this->ketidakhadiranModel->countSakitIzinCutiHariIni($today);

            require_once __DIR__ . '/../Views/dashboard/admin.php';
        } else {
            // Stats for Pegawai
            $presensiHariIni = $this->presensiModel->getHariIni($currentUser['id'], $today);

            require_once __DIR__ . '/../Views/dashboard/pegawai.php';
        }
    }
}
