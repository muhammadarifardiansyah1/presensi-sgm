<?php
/**
 * Dashboard Controller
 * Absensi Surya Gemilang Mojopahit (SGM)
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
            require_once __DIR__ . '/../Models/Lokasi.php';
            $lokasiModel = new Lokasi();

            // 1. Metrik Utama Presensi Admin Hari Ini
            $totalPegawai = $this->userModel->countPegawaiAktif();
            $statHariIni  = $this->presensiModel->getStatistikHarianAdmin($today);
            $jumlahHadir  = $statHariIni['total_hadir'];
            $tepatWaktu   = $statHariIni['tepat_waktu'];
            $terlambat    = $statHariIni['terlambat'];
            $jumlahSakitIzinCuti = $this->ketidakhadiranModel->countSakitIzinCutiHariIni($today);
            $jumlahPendingIzin   = $this->ketidakhadiranModel->countPending();

            // Pegawai yang belum absen atau berpotensi alpa hari ini
            $jumlahBelumAbsen = max(0, $totalPegawai - ($jumlahHadir + $jumlahSakitIzinCuti));
            $persenHadir = $totalPegawai > 0 ? round(($jumlahHadir / $totalPegawai) * 100) : 0;

            // 2. Visualisasi Data: Tren Kehadiran 7 Hari Terakhir
            $trenTujuhHari = $this->presensiModel->getTrenKehadiranAdmin($totalPegawai);

            // 3. Visualisasi Data: Sebaran Kehadiran per Lokasi Penugasan
            $lokasiStatistik = $lokasiModel->getStatistikHadirHariIni($today);

            // 4. Live Unified Activity Stream: Presensi, Izin/Cuti, dan SPL Terbaru
            require_once __DIR__ . '/../Helpers/NotificationHelper.php';
            $aktivitasTerbaru = NotificationHelper::getAdminActivityStream(6);

            require_once __DIR__ . '/../Views/dashboard/admin.php';
        } else {
            // Stats & Data Lengkap untuk Dashboard Pegawai
            $userId = (int)$currentUser['id'];
            $bulan = (int)date('m');
            $tahun = (int)date('Y');

            // 1. Data lengkap profil pegawai & lokasi penugasan
            $pegawaiProfile = $this->userModel->findById($userId) ?? $currentUser;

            // 2. Presensi hari ini
            $presensiHariIni = $this->presensiModel->getHariIni($userId, $today);

            // 3. Statistik bulanan (Jam kerja, tingkat kehadiran, tepat waktu, terlambat, lembur)
            $statBulanan = $this->presensiModel->getStatistikBulananPegawai($userId, $bulan, $tahun);

            // 4. Statistik izin, sakit, dan cuti
            $statIzin = $this->ketidakhadiranModel->countStatistikPegawai($userId, $bulan, $tahun);

            // 5. Tren jam kerja 7 hari terakhir
            $trenTujuhHari = $this->presensiModel->getTrenTujuhHari($userId);

            // 6. 5 Riwayat presensi terakhir
            $riwayatPresensi = $this->presensiModel->getRiwayatTerakhirPegawai($userId, 5);

            // 7. Penugasan lembur aktif hari ini (jika ada)
            require_once __DIR__ . '/../Models/PenugasanLembur.php';
            $lemburModel = new PenugasanLembur();
            $penugasanHariIni = $lemburModel->getPenugasanAktifUser($userId, $today);

            require_once __DIR__ . '/../Views/dashboard/pegawai.php';
        }
    }

    /**
     * Halaman Mandiri: Pusat Notifikasi
     */
    public function notifikasi(): void {
        $currentUser = AuthController::checkAuth();

        // Pegawai menggunakan halaman awal terpadu: Riwayat Presensi & Aktivitas
        if (($currentUser['role'] ?? '') === 'pegawai') {
            header("Location: " . Config::baseUrl('riwayat'));
            exit;
        }

        require_once __DIR__ . '/../Helpers/NotificationHelper.php';

        $notifData = NotificationHelper::getAllNotifications($currentUser, 50);
        $notifications = $notifData['items'] ?? [];
        $unreadCount = $notifData['unread_count'] ?? 0;

        require_once __DIR__ . '/../Views/notifikasi/index.php';
    }
}

