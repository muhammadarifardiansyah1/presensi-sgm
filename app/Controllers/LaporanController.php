<?php
/**
 * Laporan & Rekap Controller
 * Absensi Surya Gemilang Mojopahit (SGM)
 */

require_once __DIR__ . '/../Config/Config.php';
require_once __DIR__ . '/AuthController.php';
require_once __DIR__ . '/../Models/Presensi.php';

class LaporanController {
    private Presensi $presensiModel;

    public function __construct() {
        $this->presensiModel = new Presensi();
    }

    public function rekapHarian(): void {
        $currentUser = AuthController::checkAuth();
        $tanggalMulai = $_GET['tanggal_mulai'] ?? $_GET['tanggal'] ?? date('Y-m-d');
        $tanggalSelesai = $_GET['tanggal_selesai'] ?? $tanggalMulai;
        
        $userId = ($currentUser['role'] === 'admin') ? null : $currentUser['id'];
        $dataPresensi = $this->presensiModel->getRekapHarian($tanggalMulai, $tanggalSelesai, $userId);

        require_once __DIR__ . '/../Models/User.php';
        require_once __DIR__ . '/../Models/Lokasi.php';
        $pegawaiList = ($currentUser['role'] === 'admin') ? (new User())->getAllPegawai() : [];
        $lokasiList = ($currentUser['role'] === 'admin') ? (new Lokasi())->getAll() : [];

        require_once __DIR__ . '/../Views/laporan/rekap_harian.php';
    }

    public function rekapBulanan(): void {
        $currentUser = AuthController::checkAuth();
        $bulan = (int) ($_GET['bulan'] ?? date('m'));
        $tahun = (int) ($_GET['tahun'] ?? date('Y'));

        $userId = ($currentUser['role'] === 'admin') ? null : $currentUser['id'];
        $dataPresensi = $this->presensiModel->getRekapBulanan($bulan, $tahun, $userId);

        require_once __DIR__ . '/../Models/User.php';
        require_once __DIR__ . '/../Models/Lokasi.php';
        $pegawaiList = ($currentUser['role'] === 'admin') ? (new User())->getAllPegawai() : [];
        $lokasiList = ($currentUser['role'] === 'admin') ? (new Lokasi())->getAll() : [];

        require_once __DIR__ . '/../Views/laporan/rekap_bulanan.php';
    }

    public function exportExcel(): void {
        AuthController::checkAdmin();
        $bulan = (int) ($_GET['bulan'] ?? date('m'));
        $tahun = (int) ($_GET['tahun'] ?? date('Y'));

        $dataPresensi = $this->presensiModel->getRekapBulanan($bulan, $tahun, null);

        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $bulanText = $namaBulan[$bulan] ?? $bulan;

        // Set Headers for Excel Download
        $filename = "Laporan_Presensi_Bulanan_{$bulan}_{$tahun}.xls";
        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Cache-Control: max-age=0");

        require_once __DIR__ . '/../Views/laporan/export_excel.php';
        exit;
    }

    public function exportExcelHarian(): void {
        AuthController::checkAdmin();
        $tanggalMulai = $_GET['tanggal_mulai'] ?? $_GET['tanggal'] ?? date('Y-m-d');
        $tanggalSelesai = $_GET['tanggal_selesai'] ?? $tanggalMulai;

        $d1 = DateTime::createFromFormat('Y-m-d', $tanggalMulai);
        $d2 = DateTime::createFromFormat('Y-m-d', $tanggalSelesai);
        if (!$d1 || !$d2 || $tanggalMulai > $tanggalSelesai) {
            header("Location: " . Config::baseUrl('laporan/harian?err=invalid_date'));
            exit;
        }

        // Batasi rentang ekspor maksimal 31 hari per unduhan untuk mencegah memory exhaustion server hosting
        $diff = (int)$d1->diff($d2)->format('%a');
        if ($diff > 31) {
            header("Location: " . Config::baseUrl("laporan/harian?tanggal_mulai={$tanggalMulai}&tanggal_selesai={$tanggalSelesai}&err=export_range_too_large"));
            exit;
        }

        $dataPresensi = $this->presensiModel->getRekapHarian($tanggalMulai, $tanggalSelesai, null);

        $filename = "Rekap_Presensi_Harian_{$tanggalMulai}.xls";
        if ($tanggalMulai !== $tanggalSelesai) {
            $filename = "Rekap_Presensi_{$tanggalMulai}_sd_{$tanggalSelesai}.xls";
        }
        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Cache-Control: max-age=0");

        require_once __DIR__ . '/../Views/laporan/export_excel_harian.php';
        exit;
    }

    public function hapusPresensi(int $id): void {
        AuthController::checkAdmin();
        if ($id > 0) {
            $this->presensiModel->delete($id);
        }
        $referer = $_SERVER['HTTP_REFERER'] ?? Config::baseUrl('laporan/harian');
        
        $parsed = parse_url($referer);
        $queryParams = [];
        if (!empty($parsed['query'])) {
            parse_str($parsed['query'], $queryParams);
        }
        $queryParams['msg'] = 'presensi_deleted';
        
        $scheme = $parsed['scheme'] ?? 'http';
        $host = $parsed['host'] ?? '';
        $port = !empty($parsed['port']) ? ':' . $parsed['port'] : '';
        $path = $parsed['path'] ?? '/';
        
        $redirectUrl = ($host !== '' ? ($scheme . '://' . $host . $port) : '') . $path . '?' . http_build_query($queryParams);

        header("Location: " . $redirectUrl);
        exit;
    }

    public function tambahPresensi(): void {
        AuthController::checkAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = (int)($_POST['user_id'] ?? 0);
            $tanggal = trim($_POST['tanggal'] ?? '');
            $referer = $_POST['redirect_url'] ?? $_SERVER['HTTP_REFERER'] ?? Config::baseUrl('laporan/harian');

            $parsed = parse_url($referer);
            $queryParams = [];
            if (!empty($parsed['query'])) {
                parse_str($parsed['query'], $queryParams);
            }
            unset($queryParams['msg'], $queryParams['err']);

            if ($userId <= 0 || empty($tanggal)) {
                $queryParams['err'] = 'presensi_invalid';
            } elseif ($this->presensiModel->findByUserAndTanggal($userId, $tanggal)) {
                // Tangkal error duplikat SQL 1062 dan tampilkan peringatan ramah ke Admin
                $queryParams['err'] = 'duplicate_presensi';
            } else {
                $sukses = $this->presensiModel->createManual($_POST);
                if ($sukses) {
                    $queryParams['msg'] = 'presensi_added';
                } else {
                    $queryParams['err'] = 'presensi_failed';
                }
            }

            $scheme = $parsed['scheme'] ?? 'http';
            $host = $parsed['host'] ?? '';
            $port = !empty($parsed['port']) ? ':' . $parsed['port'] : '';
            $path = $parsed['path'] ?? '/';
            $redirectUrl = ($host !== '' ? ($scheme . '://' . $host . $port) : '') . $path . (!empty($queryParams) ? '?' . http_build_query($queryParams) : '');

            header("Location: " . $redirectUrl);
            exit;
        }
        header("Location: " . Config::baseUrl('laporan/harian'));
        exit;
    }

    public function editPresensi(): void {
        AuthController::checkAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $this->presensiModel->updateManual($id, $_POST);
                $referer = $_POST['redirect_url'] ?? $_SERVER['HTTP_REFERER'] ?? Config::baseUrl('laporan/harian');
                header("Location: " . $referer . (strpos($referer, '?') !== false ? '&' : '?') . 'msg=presensi_updated');
                exit;
            }
        }
        header("Location: " . Config::baseUrl('laporan/harian'));
        exit;
    }

    public function detailPresensiJson(): void {
        AuthController::checkAdmin();
        $id = (int)($_GET['id'] ?? 0);
        $data = $this->presensiModel->getById($id);
        header('Content-Type: application/json');
        echo json_encode($data ?: ['error' => 'Data tidak ditemukan']);
        exit;
    }

    /**
     * Sinkronisasi data rekap presensi bulanan ke Google Spreadsheet via Apps Script Webhook
     */
    public function syncSpreadsheet(): void {
        AuthController::checkAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . Config::baseUrl('laporan/bulanan'));
            exit;
        }

        $bulan = (int)($_POST['bulan'] ?? date('m'));
        $tahun = (int)($_POST['tahun'] ?? date('Y'));
        $webhookUrl = trim($_POST['webhook_url'] ?? Config::getSpreadsheetWebhookUrl());

        if (!empty($webhookUrl) && filter_var($webhookUrl, FILTER_VALIDATE_URL) && strpos($webhookUrl, 'script.google.com/macros/s/') !== false) {
            Config::setSetting('spreadsheet_webhook_url', $webhookUrl);
        }

        // Lepaskan kunci file sesi PHP agar tab lain tidak membeku (unblock session) saat cURL berjalan
        session_write_close();

        $dataPresensi = $this->presensiModel->getRekapBulanan($bulan, $tahun, null);
        $result = $this->presensiModel->syncToGoogleSheets($webhookUrl, $dataPresensi, $bulan, $tahun);

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode($result);
            exit;
        }

        if ($result['success']) {
            header('Location: ' . Config::baseUrl("laporan/bulanan?bulan={$bulan}&tahun={$tahun}&msg=synced"));
        } else {
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            $_SESSION['sync_error'] = $result['message'];
            header('Location: ' . Config::baseUrl("laporan/bulanan?bulan={$bulan}&tahun={$tahun}&err=sync_failed"));
        }
        exit;
    }
}

