<?php
/**
 * Auth Controller
 * Absensi Surya Gemilang Mojopahit (SGM)
 */

require_once __DIR__ . '/../Config/Config.php';
require_once __DIR__ . '/../Models/User.php';

class AuthController {
    private User $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    public static function checkAuth(): array {
        if (empty($_SESSION['user_id'])) {
            $requestUri = $_SERVER['REQUEST_URI'] ?? '';
            if (!empty($requestUri) && strpos($requestUri, 'login') === false && strpos($requestUri, 'logout') === false) {
                $_SESSION['redirect_after_login'] = $requestUri;
            }
            header("Location: " . Config::baseUrl('login'));
            exit;
        }

        $now = time();
        $userRole = $_SESSION['role'] ?? 'pegawai';

        // 1. Validasi Batas Waktu Sesi (Session Lifecycle)
        // Admin: Idle timeout 2 jam (7200 detik) tanpa aktivitas
        // Pegawai: Maksimal 12 jam (43200 detik) sejak jam masuk / login kerja
        if ($userRole === 'admin') {
            $adminIdleLimit = 7200; // 2 jam
            if (!empty($_SESSION['last_activity']) && ($now - $_SESSION['last_activity']) > $adminIdleLimit) {
                self::forceLogout('Sesi Admin telah berakhir karena tidak ada aktivitas selama 2 jam.');
            }
        } else {
            $pegawaiShiftLimit = 43200; // 12 jam shift kerja
            if (!empty($_SESSION['login_time']) && ($now - $_SESSION['login_time']) > $pegawaiShiftLimit) {
                self::forceLogout('Sesi kerja Anda telah berakhir (12 jam). Silakan masuk kembali.');
            }
        }

        // Perbarui waktu aktivitas terakhir
        $_SESSION['last_activity'] = $now;

        // 2. Sinkronisasi Data Pengguna & Validasi Status
        $userModel = new User();
        $freshUser = $userModel->findById((int) $_SESSION['user_id']);
        if (!$freshUser || $freshUser['status'] !== 'aktif') {
            self::forceLogout('Akun Anda dinonaktifkan atau tidak ditemukan. Silakan hubungi Administrator.');
        }

        // 3. Proteksi Single Active Device Login (1 Akun = 1 Perangkat Aktif)
        $currentSessionId = session_id();
        if (empty($freshUser['session_id'])) {
            // Adopsi session saat ini untuk akun yang sedang aktif
            $userModel->updateSession((int)$freshUser['id'], $currentSessionId);
        } elseif ($freshUser['session_id'] !== $currentSessionId) {
            // Akun telah login di perangkat/browser lain
            self::forceLogout('Akun Anda telah masuk di perangkat lain. Sesi pada perangkat ini telah diakhiri demi keamanan.');
        }

        // Perbarui info sesi dengan data terbaru dari database
        $_SESSION['foto_profil'] = $freshUser['foto_profil'] ?? null;
        $_SESSION['nama']        = $freshUser['nama'] ?? ($_SESSION['nama'] ?? '');
        $_SESSION['jabatan']     = $freshUser['jabatan'] ?? ($_SESSION['jabatan'] ?? 'Pegawai');
        $_SESSION['role']        = $freshUser['role'] ?? ($_SESSION['role'] ?? 'pegawai');

        return [
            'id'          => $_SESSION['user_id'],
            'nik'         => $_SESSION['nik'] ?? ($freshUser['nik'] ?? ''),
            'nama'        => $_SESSION['nama'] ?? ($freshUser['nama'] ?? ''),
            'username'    => $_SESSION['username'] ?? ($freshUser['username'] ?? ''),
            'role'        => $_SESSION['role'] ?? ($freshUser['role'] ?? 'pegawai'),
            'jabatan'     => $_SESSION['jabatan'] ?? ($freshUser['jabatan'] ?? 'Pegawai'),
            'lokasi_id'   => $_SESSION['lokasi_id'] ?? ($freshUser['lokasi_id'] ?? null),
            'foto'        => $_SESSION['foto_profil'] ?? null,
            'foto_profil' => $_SESSION['foto_profil'] ?? null,
        ];
    }

    public static function checkAdmin(): array {
        $user = self::checkAuth();
        if ($user['role'] !== 'admin') {
            header("Location: " . Config::baseUrl('dashboard'));
            exit;
        }
        return $user;
    }

    public static function forceLogout(string $message): void {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        @session_destroy();

        // Cek jika request berasal dari AJAX / Fetch
        $isAjax = (
            (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
            (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
            ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'] ?? '', 'presensi/') !== false)
        );

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status' => 'error',
                'session_expired' => true,
                'message' => $message,
                'redirect' => Config::baseUrl('login')
            ]);
            exit;
        }

        // Simpan pesan flash ke cookie sementara (60 detik) agar dibaca halaman login
        setcookie('sgm_flash_msg', $message, time() + 60, '/', '', false, true);

        header("Location: " . Config::baseUrl('login'));
        exit;
    }

    public function login(): void {
        // If already logged in, redirect to dashboard
        if (!empty($_SESSION['user_id'])) {
            header("Location: " . Config::baseUrl('dashboard'));
            exit;
        }

        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');

            if ($username === '' || $password === '') {
                $error = 'Username dan password wajib diisi!';
            } else {
                $user = $this->userModel->findByUsername($username);
                if ($user && $user['status'] === 'aktif' && password_verify($password, $user['password'])) {
                    // Regenerate session id untuk mencegah session fixation
                    session_regenerate_id(true);
                    $newSessionId = session_id();

                    $_SESSION['user_id']       = $user['id'];
                    $_SESSION['nik']           = $user['nik'];
                    $_SESSION['nama']          = $user['nama'];
                    $_SESSION['username']      = $user['username'];
                    $_SESSION['role']          = $user['role'];
                    $_SESSION['jabatan']       = $user['jabatan'];
                    $_SESSION['lokasi_id']     = $user['lokasi_id'] ?? null;
                    $_SESSION['foto_profil']   = $user['foto_profil'];
                    $_SESSION['login_time']    = time();
                    $_SESSION['last_activity'] = time();

                    // Simpan session_id terbaru ke database untuk Single Device Login
                    $this->userModel->updateSession((int)$user['id'], $newSessionId);

                    $redirect = $_SESSION['redirect_after_login'] ?? Config::baseUrl('dashboard');
                    unset($_SESSION['redirect_after_login']);
                    header("Location: " . $redirect);
                    exit;
                } else {
                    $error = 'Username atau password tidak sesuai, atau akun nonaktif!';
                }
            }
        }

        require_once __DIR__ . '/../Views/auth/login.php';
    }

    public function logout(): void {
        if (!empty($_SESSION['user_id'])) {
            $this->userModel->clearSession((int)$_SESSION['user_id']);
        }
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        @session_destroy();
        header("Location: " . Config::baseUrl('login'));
        exit;
    }
}

