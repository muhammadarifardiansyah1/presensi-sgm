<?php
/**
 * Front Controller & Main Router
 * Absensi Surya Gemilang Mojopahit (SGM)
 */

require_once __DIR__ . '/app/Config/Config.php';
require_once __DIR__ . '/app/Controllers/AuthController.php';
require_once __DIR__ . '/app/Controllers/DashboardController.php';
require_once __DIR__ . '/app/Controllers/PresensiController.php';
require_once __DIR__ . '/app/Controllers/LokasiController.php';
require_once __DIR__ . '/app/Controllers/JabatanController.php';
require_once __DIR__ . '/app/Controllers/PegawaiController.php';
require_once __DIR__ . '/app/Controllers/LaporanController.php';
require_once __DIR__ . '/app/Controllers/KetidakhadiranController.php';
require_once __DIR__ . '/app/Controllers/ProfileController.php';
require_once __DIR__ . '/app/Controllers/GajiController.php';
require_once __DIR__ . '/app/Controllers/StorageController.php';
require_once __DIR__ . '/app/Controllers/LemburController.php';

// Parse requested URL route
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));

// Strip script directory from request URI
if ($scriptDir !== '/' && strpos($requestUri, $scriptDir) === 0) {
    $path = substr($requestUri, strlen($scriptDir));
} else {
    $path = $requestUri;
}

// Remove query parameters
$path = parse_url($path, PHP_URL_PATH);
$route = trim($path, '/');

// Support ?url= parameter as fallback
if (isset($_GET['url']) && $_GET['url'] !== '') {
    $route = trim($_GET['url'], '/');
}

// Default route
if ($route === '' || $route === 'index.php') {
    $route = !empty($_SESSION['user_id']) ? 'dashboard' : 'login';
}

// Route mapping
switch ($route) {
    case 'login':
        (new AuthController())->login();
        break;

    case 'logout':
        (new AuthController())->logout();
        break;

    case 'dashboard':
        (new DashboardController())->index();
        break;

    case 'notifikasi':
        (new DashboardController())->notifikasi();
        break;

    case 'profil':
        (new ProfileController())->index();
        break;

    case 'profil/update':
        (new ProfileController())->update();
        break;

    case 'profil/ganti-password':
        (new ProfileController())->updatePassword();
        break;

    case 'presensi/masuk':
        (new PresensiController())->masuk();
        break;

    case 'presensi/keluar':
        (new PresensiController())->keluar();
        break;

    case 'riwayat':
    case 'presensi/riwayat':
        (new PresensiController())->riwayat();
        break;

    case 'master/lokasi':
        (new LokasiController())->index();
        break;

    case 'master/lokasi/tambah':
        (new LokasiController())->tambah();
        break;

    case 'master/lokasi/detail':
        $id = (int) ($_GET['id'] ?? 0);
        (new LokasiController())->detail($id);
        break;

    case 'master/lokasi/edit':
        $id = (int) ($_GET['id'] ?? 0);
        (new LokasiController())->edit($id);
        break;

    case 'master/lokasi/hapus':
        $id = (int) ($_POST['id'] ?? ($_GET['id'] ?? 0));
        (new LokasiController())->hapus($id);
        break;

    case 'master/jabatan':
        (new JabatanController())->index();
        break;

    case 'master/jabatan/tambah':
        (new JabatanController())->tambah();
        break;

    case 'master/jabatan/edit':
        $id = (int) ($_GET['id'] ?? 0);
        (new JabatanController())->edit($id);
        break;

    case 'master/jabatan/hapus':
        $id = (int) ($_POST['id'] ?? ($_GET['id'] ?? 0));
        (new JabatanController())->hapus($id);
        break;

    case 'master/storage':
        (new StorageController())->index();
        break;

    case 'master/storage/cleanup':
        (new StorageController())->cleanup();
        break;

    case 'pegawai':
        (new PegawaiController())->index();
        break;

    case 'pegawai/tambah':
        (new PegawaiController())->tambah();
        break;

    case 'pegawai/edit':
        $id = (int) ($_GET['id'] ?? 0);
        (new PegawaiController())->edit($id);
        break;

    case 'pegawai/hapus':
        $id = (int) ($_GET['id'] ?? 0);
        (new PegawaiController())->hapus($id);
        break;

    case 'pegawai/toggle-status':
        $id = (int) ($_GET['id'] ?? 0);
        (new PegawaiController())->toggleStatus($id);
        break;

    case 'pegawai/import':
        (new PegawaiController())->import();
        break;

    case 'pegawai/template':
        (new PegawaiController())->downloadTemplate();
        break;

    case 'laporan/harian':
        (new LaporanController())->rekapHarian();
        break;

    case 'laporan/bulanan':
        (new LaporanController())->rekapBulanan();
        break;

    case 'laporan/export-excel':
        (new LaporanController())->exportExcel();
        break;

    case 'laporan/export-excel-harian':
        (new LaporanController())->exportExcelHarian();
        break;

    case 'laporan/hapus-presensi':
        $id = (int) ($_GET['id'] ?? 0);
        (new LaporanController())->hapusPresensi($id);
        break;

    case 'laporan/tambah-presensi':
        (new LaporanController())->tambahPresensi();
        break;

    case 'laporan/edit-presensi':
        (new LaporanController())->editPresensi();
        break;

    case 'laporan/detail-presensi':
        (new LaporanController())->detailPresensiJson();
        break;

    case 'laporan/sync-spreadsheet':
        (new LaporanController())->syncSpreadsheet();
        break;

    case 'gaji':
        (new GajiController())->index();
        break;

    case 'gaji/export-csv':
        (new GajiController())->exportCsv();
        break;

    case 'gaji/sync-spreadsheet':
        (new GajiController())->syncSpreadsheet();
        break;

    case 'gaji/simpan-pengaturan':
        (new GajiController())->simpanPengaturan();
        break;

    case 'gaji/simpan-penyesuaian':
        (new GajiController())->simpanPenyesuaian();
        break;

    case 'gaji/cetak-laporan':
        (new GajiController())->cetakLaporan();
        break;

    case 'gaji/slip-bulk':
        (new GajiController())->slipGajiBulk();
        break;

    case 'gaji/slip':
        (new GajiController())->slipGajiSingle();
        break;

    case 'ketidakhadiran':
        (new KetidakhadiranController())->index();
        break;

    case 'ketidakhadiran/ajukan':
        (new KetidakhadiranController())->ajukan();
        break;

    case 'ketidakhadiran/approve':
        $id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
        (new KetidakhadiranController())->approve($id);
        break;

    case 'ketidakhadiran/reject':
        $id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
        (new KetidakhadiranController())->reject($id);
        break;

    case 'ketidakhadiran/edit':
        $id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
        (new KetidakhadiranController())->edit($id);
        break;

    case 'ketidakhadiran/hapus':
        $id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
        (new KetidakhadiranController())->hapus($id);
        break;

    case 'ketidakhadiran/export-excel':
        (new KetidakhadiranController())->exportExcel();
        break;

    case 'lembur':
        (new LemburController())->index();
        break;

    case 'lembur/tambah':
        (new LemburController())->tambah();
        break;

    case 'lembur/edit':
        $id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
        (new LemburController())->edit($id);
        break;

    case 'lembur/detail':
        $id = (int) ($_GET['id'] ?? 0);
        (new LemburController())->detail($id);
        break;

    case 'lembur/hapus':
        $id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
        (new LemburController())->hapus($id);
        break;

    case 'lembur/pegawai-lokasi':
        (new LemburController())->getPegawaiByLokasiJson();
        break;

    default:
        http_response_code(404);
        echo "<div style='font-family:sans-serif;text-align:center;padding:50px;'><h1>404 - Halaman Tidak Ditemukan</h1><p>Halaman yang Anda tuju tidak tersedia.</p><a href='" . Config::baseUrl('dashboard') . "'>Kembali ke Dashboard</a></div>";
        break;
}
