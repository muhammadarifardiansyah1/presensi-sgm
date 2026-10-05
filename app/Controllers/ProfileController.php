<?php
/**
 * Profile Controller
 * Absensi Surya Gading Mandiri (SGM)
 */

require_once __DIR__ . '/../Config/Config.php';
require_once __DIR__ . '/AuthController.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Helpers/ImageHelper.php';

class ProfileController {
    private User $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    public function index(): void {
        $currentUser = AuthController::checkAuth();
        $user = $this->userModel->findById((int) $currentUser['id']);

        if (!$user) {
            header("Location: " . Config::baseUrl('logout'));
            exit;
        }

        $activePage = 'profil';
        $pageTitle = 'Profil Pengguna - ' . Config::APP_NAME;

        $success = $_SESSION['success'] ?? '';
        $error   = $_SESSION['error'] ?? '';
        unset($_SESSION['success'], $_SESSION['error']);

        require_once __DIR__ . '/../Views/profil/index.php';
    }

    public function update(): void {
        $currentUser = AuthController::checkAuth();
        $userId = (int) $currentUser['id'];
        $user = $this->userModel->findById($userId);

        if (!$user) {
            header("Location: " . Config::baseUrl('dashboard'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nama = trim($_POST['nama'] ?? '');
            $jenisKelamin = $_POST['jenis_kelamin'] ?? 'Laki-laki';
            $noHandphone = trim($_POST['no_handphone'] ?? '');
            $alamat = trim($_POST['alamat'] ?? '');

            if (empty($nama)) {
                $_SESSION['error'] = 'Nama lengkap wajib diisi!';
                header("Location: " . Config::baseUrl('profil'));
                exit;
            }

            $fotoProfil = $user['foto_profil'] ?? null;
            if (!empty($_FILES['foto']['name']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../assets/images/uploads/profil/';
                $savedFile = ImageHelper::compressAndSaveUpload($_FILES['foto'], $uploadDir, 'profil_' . $userId, 500, 80);
                if ($savedFile) {
                    if (!empty($user['foto_profil']) && file_exists($uploadDir . $user['foto_profil'])) {
                        @unlink($uploadDir . $user['foto_profil']);
                    }
                    $fotoProfil = $savedFile;
                } else {
                    $_SESSION['error'] = 'Format file foto tidak valid atau gagal dikompresi!';
                    header("Location: " . Config::baseUrl('profil'));
                    exit;
                }
            }

            $data = [
                'nama'          => $nama,
                'jenis_kelamin' => $jenisKelamin,
                'no_handphone'  => $noHandphone,
                'alamat'        => $alamat,
                'foto_profil'   => $fotoProfil
            ];

            if ($this->userModel->updateProfile($userId, $data)) {
                // Update session values
                $_SESSION['nama'] = $nama;
                $_SESSION['foto_profil'] = $fotoProfil;
                $_SESSION['success'] = 'Profil berhasil diperbarui!';
            } else {
                $_SESSION['error'] = 'Gagal menyimpan pembaruan profil.';
            }

            header("Location: " . Config::baseUrl('profil'));
            exit;
        }

        header("Location: " . Config::baseUrl('profil'));
        exit;
    }

    public function updatePassword(): void {
        $currentUser = AuthController::checkAuth();
        $userId = (int) $currentUser['id'];
        $user = $this->userModel->findById($userId);

        if (!$user) {
            header("Location: " . Config::baseUrl('dashboard'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword     = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                $_SESSION['error'] = 'Semua kolom password wajib diisi!';
                header("Location: " . Config::baseUrl('profil#ganti-password'));
                exit;
            }

            if (!password_verify($currentPassword, $user['password'])) {
                $_SESSION['error'] = 'Password saat ini tidak sesuai!';
                header("Location: " . Config::baseUrl('profil#ganti-password'));
                exit;
            }

            if (strlen($newPassword) < 6) {
                $_SESSION['error'] = 'Password baru harus memiliki minimal 6 karakter!';
                header("Location: " . Config::baseUrl('profil#ganti-password'));
                exit;
            }

            if ($newPassword !== $confirmPassword) {
                $_SESSION['error'] = 'Konfirmasi password baru tidak cocok!';
                header("Location: " . Config::baseUrl('profil#ganti-password'));
                exit;
            }

            if ($this->userModel->updatePassword($userId, $newPassword)) {
                $_SESSION['success'] = 'Password berhasil diperbarui!';
            } else {
                $_SESSION['error'] = 'Gagal memperbarui password di database.';
            }

            header("Location: " . Config::baseUrl('profil#ganti-password'));
            exit;
        }

        header("Location: " . Config::baseUrl('profil'));
        exit;
    }
}
