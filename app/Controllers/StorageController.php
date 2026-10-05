<?php
/**
 * Storage Controller
 * Absensi Surya Gemilang Mojopahit (SGM)
 *
 * Mengelola antarmuka pemantauan kuota penyimpanan foto presensi
 * dan eksekusi retensi berkas untuk Super Admin.
 */

require_once __DIR__ . '/../Config/Config.php';
require_once __DIR__ . '/AuthController.php';
require_once __DIR__ . '/../Helpers/StorageManager.php';

class StorageController {

    public function index(): void {
        $currentUser = AuthController::checkAdmin();
        $activePage = 'master-storage';
        $stats = StorageManager::getStats();
        
        $lastResult = $_SESSION['storage_cleanup_result'] ?? null;
        unset($_SESSION['storage_cleanup_result']);

        require_once __DIR__ . '/../Views/master/storage_index.php';
    }

    public function cleanup(): void {
        $currentUser = AuthController::checkAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . Config::baseUrl('master/storage'));
            exit;
        }

        $days = (int) ($_POST['days'] ?? 180);
        $mode = $_POST['mode'] ?? 'simulate';
        $isDryRun = ($mode !== 'execute');

        if ($days < 7) {
            $days = 180;
        }

        try {
            $result = StorageManager::cleanupOldPhotos($days, $isDryRun);

            // Jika request AJAX (fetch API)
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'success', 'data' => $result]);
                exit;
            }

            $_SESSION['storage_cleanup_result'] = $result;
            header("Location: " . Config::baseUrl('master/storage'));
            exit;

        } catch (Exception $e) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
                exit;
            }

            $_SESSION['storage_cleanup_result'] = [
                'success' => false,
                'error'   => $e->getMessage()
            ];
            header("Location: " . Config::baseUrl('master/storage'));
            exit;
        }
    }
}
