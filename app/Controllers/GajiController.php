<?php
/**
 * Gaji & Payroll Controller
 * Absensi Surya Gemilang Mojopahit (SGM)
 */

require_once __DIR__ . '/../Config/Config.php';
require_once __DIR__ . '/AuthController.php';
require_once __DIR__ . '/../Models/Gaji.php';
require_once __DIR__ . '/../Helpers/SpreadsheetHelper.php';
require_once __DIR__ . '/../Helpers/Icon.php';

class GajiController {
    private Gaji $gajiModel;

    public function __construct() {
        $this->gajiModel = new Gaji();
    }

    /**
     * Halaman Utama Rekap Penggajian & Integrasi Spreadsheet
     */
    public function index(): void {
        $currentUser = AuthController::checkAdmin();

        $bulan = (int)($_GET['bulan'] ?? date('m'));
        $tahun = (int)($_GET['tahun'] ?? date('Y'));

        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $rekapPayroll = $this->gajiModel->getRekapPayroll($bulan, $tahun);
        $pengaturanGajiList = $this->gajiModel->getAllPengaturan();

        $spreadsheetUrl = Config::getSpreadsheetUrl();
        $spreadsheetWebhookUrl = Config::getSpreadsheetWebhookUrl();
        $penandatanganKoordinator = Config::getSetting('payroll_penandatangan_koordinator') ?: 'Koordinator';
        $penandatanganSupervisor = Config::getSetting('payroll_penandatangan_supervisor') ?: 'Supervisor Operasional';
        $penandatanganManajer = Config::getSetting('payroll_penandatangan_manajer') ?: 'Manajer Operasional';

        require_once __DIR__ . '/../Views/gaji/index.php';
    }

    /**
     * Download Rekap Payroll Format CSV / Excel
     */
    public function exportCsv(): void {
        AuthController::checkAdmin();

        $bulan = (int)($_GET['bulan'] ?? date('m'));
        $tahun = (int)($_GET['tahun'] ?? date('Y'));

        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $periodeText = ($namaBulan[$bulan] ?? $bulan) . ' ' . $tahun;

        $rekapPayroll = $this->gajiModel->getRekapPayroll($bulan, $tahun);
        $csvContent = SpreadsheetHelper::generatePayrollCsv($rekapPayroll, $periodeText);

        $filename = "Rekap_Payroll_SGM_{$bulan}_{$tahun}.csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        echo $csvContent;
        exit;
    }

    /**
     * Sinkronisasi data ke Google Spreadsheet via Apps Script Webhook (Opsi A)
     */
    public function syncSpreadsheet(): void {
        AuthController::checkAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . Config::baseUrl('gaji'));
            exit;
        }

        $bulan = (int)($_POST['bulan'] ?? date('m'));
        $tahun = (int)($_POST['tahun'] ?? date('Y'));
        $webhookUrl = trim($_POST['webhook_url'] ?? Config::getSpreadsheetWebhookUrl());

        if (!empty($webhookUrl) && filter_var($webhookUrl, FILTER_VALIDATE_URL) && strpos($webhookUrl, 'script.google.com/macros/s/') !== false) {
            Config::setSetting('spreadsheet_webhook_url', $webhookUrl);
        }

        $rekapPayroll = $this->gajiModel->getRekapPayroll($bulan, $tahun);

        // Lepaskan kunci file sesi PHP agar tab lain tidak membeku (unblock session) saat cURL berjalan
        session_write_close();

        $result = $this->gajiModel->syncToGoogleSheets($webhookUrl, $rekapPayroll, $bulan, $tahun);

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode($result);
            exit;
        }

        if ($result['success']) {
            header('Location: ' . Config::baseUrl("gaji?bulan={$bulan}&tahun={$tahun}&msg=synced"));
        } else {
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            $_SESSION['sync_error'] = $result['message'];
            header('Location: ' . Config::baseUrl("gaji?bulan={$bulan}&tahun={$tahun}&err=sync_failed"));
        }
        exit;
    }

    /**
     * Simpan Batch Penyesuaian Payroll Bulanan (PL, Lain-lain, Catatan, Override)
     */
    public function simpanPenyesuaian(): void {
        AuthController::checkAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . Config::baseUrl('gaji'));
            exit;
        }

        $bulan = (int)($_POST['bulan'] ?? date('m'));
        $tahun = (int)($_POST['tahun'] ?? date('Y'));
        $penyesuaian = $_POST['penyesuaian'] ?? [];

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
                  || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

        if (!is_array($penyesuaian)) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Data penyesuaian tidak valid.']);
                exit;
            }
            header('Location: ' . Config::baseUrl("gaji?bulan={$bulan}&tahun={$tahun}&err=invalid_data"));
            exit;
        }

        $saved = $this->gajiModel->saveBatchPenyesuaian($bulan, $tahun, $penyesuaian);

        if ($isAjax) {
            header('Content-Type: application/json');
            if ($saved) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Data penyesuaian payroll (Pengganti Libur & Lain-lain) berhasil disimpan!'
                ]);
            } else {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Gagal menyimpan data penyesuaian ke database.'
                ]);
            }
            exit;
        }

        if ($saved) {
            header('Location: ' . Config::baseUrl("gaji?bulan={$bulan}&tahun={$tahun}&msg=penyesuaian_saved"));
        } else {
            header('Location: ' . Config::baseUrl("gaji?bulan={$bulan}&tahun={$tahun}&err=penyesuaian_failed"));
        }
        exit;
    }

    /**
     * Cetak Laporan Gaji Resmi Format RSUD RA Basoeni (A4 Landscape)
     */
    public function cetakLaporan(): void {
        AuthController::checkAdmin();

        $bulan = (int)($_GET['bulan'] ?? date('m'));
        $tahun = (int)($_GET['tahun'] ?? date('Y'));

        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $periodeText = ($namaBulan[$bulan] ?? $bulan) . ' ' . $tahun;

        $rekapPayroll = $this->gajiModel->getRekapPayroll($bulan, $tahun);
        $penandatanganKoordinator = Config::getSetting('payroll_penandatangan_koordinator') ?: 'Koordinator';
        $penandatanganSupervisor = Config::getSetting('payroll_penandatangan_supervisor') ?: 'Supervisor Operasional';
        $penandatanganManajer = Config::getSetting('payroll_penandatangan_manajer') ?: 'Manajer Operasional';

        require_once __DIR__ . '/../Views/gaji/cetak_laporan.php';
    }

    /**
     * Simpan Pengaturan Link Spreadsheet, Signers & Tarif
     */
    public function simpanPengaturan(): void {
        AuthController::checkAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = [];

            if (isset($_POST['spreadsheet_url'])) {
                $sheetUrl = trim($_POST['spreadsheet_url']);
                if ($sheetUrl !== '') {
                    if (!filter_var($sheetUrl, FILTER_VALIDATE_URL)) {
                        $errors[] = 'Format URL Google Spreadsheet tidak valid.';
                    } else {
                        Config::setSetting('spreadsheet_url', $sheetUrl);
                    }
                } else {
                    Config::setSetting('spreadsheet_url', '');
                }
            }

            if (isset($_POST['spreadsheet_webhook_url'])) {
                $webhookUrl = trim($_POST['spreadsheet_webhook_url']);
                if ($webhookUrl !== '') {
                    if (!filter_var($webhookUrl, FILTER_VALIDATE_URL)) {
                        $errors[] = 'Format URL Webhook Google Apps Script tidak valid.';
                    } elseif (strpos($webhookUrl, 'docs.google.com/spreadsheets') !== false) {
                        $errors[] = 'URL Webhook tidak boleh menggunakan link Google Spreadsheet. Gunakan URL Web App (https://script.google.com/macros/s/.../exec).';
                    } elseif (strpos($webhookUrl, 'script.google.com/macros/s/') === false) {
                        $errors[] = 'URL Webhook Google Apps Script harus berawalan https://script.google.com/macros/s/...';
                    } else {
                        Config::setSetting('spreadsheet_webhook_url', $webhookUrl);
                    }
                } else {
                    Config::setSetting('spreadsheet_webhook_url', '');
                }
            }

            if (isset($_POST['payroll_penandatangan_koordinator'])) {
                Config::setSetting('payroll_penandatangan_koordinator', trim($_POST['payroll_penandatangan_koordinator']));
            }
            if (isset($_POST['payroll_penandatangan_supervisor'])) {
                Config::setSetting('payroll_penandatangan_supervisor', trim($_POST['payroll_penandatangan_supervisor']));
            }
            if (isset($_POST['payroll_penandatangan_manajer'])) {
                Config::setSetting('payroll_penandatangan_manajer', trim($_POST['payroll_penandatangan_manajer']));
            }

            // Simpan tarif jabatan jika ada (dengan perlindungan angka negatif & sanitasi angka)
            if (!empty($_POST['tarif']) && is_array($_POST['tarif'])) {
                foreach ($_POST['tarif'] as $jabatan => $t) {
                    $gajiPokok           = max(0, (float)($t['gaji_pokok'] ?? 3500000));
                    $uangMakan           = max(0, (float)($t['uang_makan_harian'] ?? 0));
                    $tarifLembur         = max(0, (float)($t['tarif_lembur_per_jam'] ?? 20000));
                    $potonganAlpa        = max(0, (float)($t['potongan_alpa_per_hari'] ?? 52000));
                    $tarifPenggantiLibur = max(0, (float)($t['tarif_pengganti_libur_per_hari'] ?? 52000));

                    $this->gajiModel->savePengaturan([
                        'jabatan' => trim($jabatan),
                        'gaji_pokok' => $gajiPokok,
                        'uang_makan_harian' => $uangMakan,
                        'tarif_lembur_per_jam' => $tarifLembur,
                        'potongan_alpa_per_hari' => $potonganAlpa,
                        'tarif_pengganti_libur_per_hari' => $tarifPenggantiLibur
                    ]);
                }
            }

            if (!empty($errors)) {
                if (session_status() === PHP_SESSION_NONE) {
                    @session_start();
                }
                $_SESSION['setting_errors'] = $errors;
                header('Location: ' . Config::baseUrl('gaji?err=setting_invalid'));
                exit;
            }

            header('Location: ' . Config::baseUrl('gaji?msg=settings_saved'));
            exit;
        }

        header('Location: ' . Config::baseUrl('gaji'));
        exit;
    }

    /**
     * Download Seluruh Slip Gaji Karyawan 1 Bulan (Bulk Slip Gaji)
     */
    public function slipGajiBulk(): void {
        $currentUser = AuthController::checkAdmin();

        $bulan = (int)($_GET['bulan'] ?? date('m'));
        $tahun = (int)($_GET['tahun'] ?? date('Y'));

        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $periodeText = ($namaBulan[$bulan] ?? $bulan) . ' ' . $tahun;

        $rekapPayroll = $this->gajiModel->getRekapPayroll($bulan, $tahun);

        require_once __DIR__ . '/../Views/gaji/slip_bulk.php';
    }

    /**
     * Pratinjau & Cetak Slip Gaji Individual (Khusus Admin)
     */
    public function slipGajiSingle(): void {
        $currentUser = AuthController::checkAdmin();

        $userId = (int)($_GET['id'] ?? $currentUser['id']);

        $bulan = (int)($_GET['bulan'] ?? date('m'));
        $tahun = (int)($_GET['tahun'] ?? date('Y'));

        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $periodeText = ($namaBulan[$bulan] ?? $bulan) . ' ' . $tahun;

        $rekap = $this->gajiModel->getRekapPayroll($bulan, $tahun, $userId);
        if (empty($rekap)) {
            echo "Data gaji pegawai tidak ditemukan.";
            exit;
        }

        $pegawaiGaji = $rekap[0];

        require_once __DIR__ . '/../Views/gaji/slip_single.php';
    }
}
