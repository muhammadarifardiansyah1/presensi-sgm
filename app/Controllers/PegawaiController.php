<?php
/**
 * Pegawai Controller
 * Absensi Surya Gemilang Mojopahit (SGM)
 */

require_once __DIR__ . '/../Config/Config.php';
require_once __DIR__ . '/AuthController.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Models/Jabatan.php';
require_once __DIR__ . '/../Models/Lokasi.php';
require_once __DIR__ . '/../Helpers/ImageHelper.php';
require_once __DIR__ . '/../Helpers/SpreadsheetHelper.php';

class PegawaiController {
    private User $userModel;
    private Jabatan $jabatanModel;
    private Lokasi $lokasiModel;

    public function __construct() {
        $this->userModel = new User();
        $this->jabatanModel = new Jabatan();
        $this->lokasiModel = new Lokasi();
    }

    public function index(): void {
        $currentUser = AuthController::checkAdmin();
        $pegawaiList = $this->userModel->getAllPegawai();
        require_once __DIR__ . '/../Views/master/pegawai_index.php';
    }

    public function tambah(): void {
        $currentUser = AuthController::checkAdmin();
        $jabatanList = $this->jabatanModel->getAll();
        $lokasiList = $this->lokasiModel->getAll();
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nama           = trim($_POST['nama'] ?? '');
            $jenisKelamin   = $_POST['jenis_kelamin'] ?? 'Laki-laki';
            $alamat         = trim($_POST['alamat'] ?? '');
            $noHandphone    = trim($_POST['no_handphone'] ?? '');
            $jabatan        = trim($_POST['jabatan'] ?? '');
            $status         = $_POST['status'] ?? 'aktif';

            $username       = trim($_POST['username'] ?? '');
            $password       = trim($_POST['password'] ?? '');
            $ulangiPassword = trim($_POST['ulangi_password'] ?? '');
            $role           = $_POST['role'] ?? 'pegawai';
            $lokasiId       = !empty($_POST['lokasi_id']) ? (int)$_POST['lokasi_id'] : null;

            if ($nama === '' || $username === '' || $password === '') {
                $error = 'Nama, Username, dan Password wajib diisi!';
            } elseif ($password !== $ulangiPassword) {
                $error = 'Konfirmasi Password (Ulangi Password) tidak sama!';
            } elseif ($this->userModel->findByUsername($username)) {
                $error = 'Username sudah digunakan, silakan pilih yang lain!';
            } else {
                // Upload & kompres foto jika ada
                $fotoProfil = null;
                if (!empty($_FILES['foto']['name']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = __DIR__ . '/../../assets/images/uploads/profil/';
                    $fotoProfil = ImageHelper::compressAndSaveUpload($_FILES['foto'], $uploadDir, 'pegawai_' . time(), 500, 80);
                }

                $data = [
                    'nama'          => $nama,
                    'jenis_kelamin' => $jenisKelamin,
                    'alamat'        => $alamat,
                    'no_handphone'  => $noHandphone,
                    'jabatan'       => $jabatan ?: 'Staff Operasional',
                    'status'        => $status,
                    'username'      => $username,
                    'password'      => $password,
                    'role'          => $role,
                    'lokasi_id'     => $lokasiId,
                    'foto_profil'   => $fotoProfil,
                ];

                if ($this->userModel->create($data)) {
                    header("Location: " . Config::baseUrl('pegawai?msg=added'));
                    exit;
                } else {
                    $error = 'Gagal menambahkan pegawai.';
                }
            }
        }

        require_once __DIR__ . '/../Views/master/pegawai_form.php';
    }

    public function edit(int $id): void {
        $currentUser = AuthController::checkAdmin();
        $pegawai = $this->userModel->findById($id);
        if (!$pegawai) {
            header("Location: " . Config::baseUrl('pegawai'));
            exit;
        }

        $jabatanList = $this->jabatanModel->getAll();
        $lokasiList = $this->lokasiModel->getAll();
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nama           = trim($_POST['nama'] ?? '');
            $jenisKelamin   = $_POST['jenis_kelamin'] ?? 'Laki-laki';
            $alamat         = trim($_POST['alamat'] ?? '');
            $noHandphone    = trim($_POST['no_handphone'] ?? '');
            $jabatan        = trim($_POST['jabatan'] ?? '');
            $status         = $_POST['status'] ?? 'aktif';

            $username       = trim($_POST['username'] ?? '');
            $password       = trim($_POST['password'] ?? '');
            $ulangiPassword = trim($_POST['ulangi_password'] ?? '');
            $role           = $_POST['role'] ?? 'pegawai';
            $lokasiId       = !empty($_POST['lokasi_id']) ? (int)$_POST['lokasi_id'] : null;

            if ($nama === '' || $username === '') {
                $error = 'Nama dan Username wajib diisi!';
            } elseif (!empty($password) && $password !== $ulangiPassword) {
                $error = 'Konfirmasi Password (Ulangi Password) tidak sama!';
            } else {
                $existing = $this->userModel->findByUsername($username);
                if ($existing && $existing['id'] !== $id) {
                    $error = 'Username sudah digunakan oleh akun lain!';
                } else {
                    $fotoProfil = $pegawai['foto_profil'] ?? null;
                    if (!empty($_FILES['foto']['name']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                        $uploadDir = __DIR__ . '/../../assets/images/uploads/profil/';
                        $newPhoto = ImageHelper::compressAndSaveUpload($_FILES['foto'], $uploadDir, 'pegawai_' . $id . '_' . time(), 500, 80);
                        if ($newPhoto) {
                            if (!empty($pegawai['foto_profil']) && file_exists($uploadDir . $pegawai['foto_profil'])) {
                                @unlink($uploadDir . $pegawai['foto_profil']);
                            }
                            $fotoProfil = $newPhoto;
                        }
                    }

                    $data = [
                        'nama'          => $nama,
                        'jenis_kelamin' => $jenisKelamin,
                        'alamat'        => $alamat,
                        'no_handphone'  => $noHandphone,
                        'jabatan'       => $jabatan ?: $pegawai['jabatan'],
                        'status'        => $status,
                        'username'      => $username,
                        'role'          => $role,
                        'lokasi_id'     => $lokasiId,
                        'foto_profil'   => $fotoProfil,
                    ];

                    if (!empty($password)) {
                        $data['password'] = $password;
                    }

                    if ($this->userModel->update($id, $data)) {
                        header("Location: " . Config::baseUrl('pegawai?msg=updated'));
                        exit;
                    } else {
                        $error = 'Gagal memperbarui data pegawai.';
                    }
                }
            }
        }

        require_once __DIR__ . '/../Views/master/pegawai_form.php';
    }

    public function hapus(int $id): void {
        AuthController::checkAdmin();
        if ($id === (int)$_SESSION['user_id']) {
            header("Location: " . Config::baseUrl('pegawai?err=self_delete'));
            exit;
        }

        $user = $this->userModel->findById($id);
        if (!$user) {
            header("Location: " . Config::baseUrl('pegawai'));
            exit;
        }

        // Perlindungan Audit: Cegah penghapusan fisik jika pegawai memiliki riwayat presensi/izin
        if ($this->userModel->hasRiwayat($id)) {
            header("Location: " . Config::baseUrl('pegawai?err=has_riwayat&name=' . urlencode($user['nama']) . '&id=' . $id));
            exit;
        }

        // Hapus berkas foto profil jika ada
        if (!empty($user['foto_profil'])) {
            $fotoPath = __DIR__ . '/../../assets/images/uploads/profil/' . $user['foto_profil'];
            if (file_exists($fotoPath)) {
                @unlink($fotoPath);
            }
        }

        $this->userModel->delete($id);
        header("Location: " . Config::baseUrl('pegawai?msg=deleted'));
        exit;
    }

    public function toggleStatus(int $id): void {
        AuthController::checkAdmin();
        if ($id === (int)$_SESSION['user_id']) {
            header("Location: " . Config::baseUrl('pegawai?err=self_status'));
            exit;
        }

        $user = $this->userModel->findById($id);
        if ($user) {
            $newStatus = ($user['status'] === 'aktif') ? 'nonaktif' : 'aktif';
            $this->userModel->updateStatus($id, $newStatus);

            // Jika akun dinonaktifkan, hentikan sesi login aktifnya
            if ($newStatus === 'nonaktif') {
                $this->userModel->clearSession($id);
            }

            header("Location: " . Config::baseUrl('pegawai?msg=status_toggled&status=' . $newStatus . '&name=' . urlencode($user['nama'])));
            exit;
        }

        header("Location: " . Config::baseUrl('pegawai'));
        exit;
    }

    public function downloadTemplate(): void {
        AuthController::checkAdmin();
        $format = strtolower($_GET['format'] ?? 'xlsx');

        while (ob_get_level()) {
            ob_end_clean();
        }

        if ($format === 'csv') {
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="template_import_pegawai.csv"');
            header('Pragma: no-cache');
            header('Expires: 0');
            echo SpreadsheetHelper::generateTemplateCsv();
            exit;
        }

        // Default: Pure native .xlsx file with styled columns and header colors
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="template_import_pegawai.xlsx"');
        header('Cache-Control: max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        echo SpreadsheetHelper::generateTemplateXlsx();
        exit;
    }

    public function import(): void {
        AuthController::checkAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . Config::baseUrl('pegawai'));
            exit;
        }

        if (empty($_FILES['file_import']['name']) || $_FILES['file_import']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['import_result'] = [
                'success' => 0,
                'skipped' => 1,
                'errors'  => ['File tidak ditemukan atau terjadi kesalahan saat upload.']
            ];
            header("Location: " . Config::baseUrl('pegawai?msg=import_error'));
            exit;
        }

        $file = $_FILES['file_import'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['csv', 'xlsx'])) {
            $_SESSION['import_result'] = [
                'success' => 0,
                'skipped' => 1,
                'errors'  => ['Format file tidak didukung. Harap upload file berekstensi .csv atau .xlsx']
            ];
            header("Location: " . Config::baseUrl('pegawai?msg=import_error'));
            exit;
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            $_SESSION['import_result'] = [
                'success' => 0,
                'skipped' => 1,
                'errors'  => ['Ukuran file melebihi batas maksimal 5 MB.']
            ];
            header("Location: " . Config::baseUrl('pegawai?msg=import_error'));
            exit;
        }

        try {
            $rows = SpreadsheetHelper::parse($file['tmp_name'], $file['name']);
        } catch (Exception $e) {
            $_SESSION['import_result'] = [
                'success' => 0,
                'skipped' => 1,
                'errors'  => ['Gagal memproses file: ' . $e->getMessage()]
            ];
            header("Location: " . Config::baseUrl('pegawai?msg=import_error'));
            exit;
        }

        if (empty($rows)) {
            $_SESSION['import_result'] = [
                'success' => 0,
                'skipped' => 0,
                'errors'  => ['File kosong atau tidak memiliki data pegawai yang valid.']
            ];
            header("Location: " . Config::baseUrl('pegawai?msg=import_empty'));
            exit;
        }

        // Cache existing usernames
        $db = Database::getConnection();
        $existingUsers = $db->query("SELECT LOWER(username) FROM `users`")->fetchAll(PDO::FETCH_COLUMN);
        $userMap = array_flip(array_map('strtolower', $existingUsers));

        // Cache Jabatan
        $jabatanList = $this->jabatanModel->getAll();
        $jabatanMap = [];
        foreach ($jabatanList as $j) {
            $jabatanMap[strtolower(trim($j['nama_jabatan']))] = $j['nama_jabatan'];
        }

        // Cache Lokasi
        $lokasiList = $this->lokasiModel->getAll();
        $lokasiMap = [];
        foreach ($lokasiList as $l) {
            $lokasiMap[strtolower(trim($l['nama_lokasi']))] = (int)$l['id'];
        }
        $defaultLokasiId = !empty($lokasiList[0]['id']) ? (int)$lokasiList[0]['id'] : null;

        $successCount = 0;
        $skippedCount = 0;
        $errors = [];

        foreach ($rows as $row) {
            $lineNum  = $row['_line_num'] ?? '?';
            $nama     = trim($row['nama'] ?? '');
            $username = trim($row['username'] ?? '');

            if ($nama === '' || $username === '') {
                $skippedCount++;
                $errors[] = "Baris {$lineNum}: Nama atau Username tidak boleh kosong.";
                continue;
            }

            $lowerUsername = strtolower($username);
            if (isset($userMap[$lowerUsername])) {
                $skippedCount++;
                $errors[] = "Baris {$lineNum}: Username '{$username}' sudah terdaftar di sistem.";
                continue;
            }

            // Role
            $roleRaw = strtolower(trim($row['role'] ?? ''));
            $role = ($roleRaw === 'admin') ? 'admin' : 'pegawai';

            // Automatic sequential NIK generation (Never require manual NIK from Excel)
            $nik = $this->userModel->generateNextNik($role);

            // Jabatan mapping
            $jabatanRaw = strtolower(trim($row['jabatan'] ?? ''));
            $jabatan = $jabatanMap[$jabatanRaw] ?? (!empty($row['jabatan']) ? trim($row['jabatan']) : 'Staff Operasional');

            // Lokasi mapping
            $lokasiRaw = strtolower(trim($row['lokasi_presensi'] ?? ''));
            $lokasiId = $lokasiMap[$lokasiRaw] ?? $defaultLokasiId;

            // Jenis Kelamin
            $jkRaw = strtolower(trim($row['jenis_kelamin'] ?? ''));
            $jenisKelamin = (in_array($jkRaw, ['perempuan', 'p', 'wanita'])) ? 'Perempuan' : 'Laki-laki';

            // Status
            $statusRaw = strtolower(trim($row['status'] ?? ''));
            $status = ($statusRaw === 'nonaktif' || $statusRaw === 'tidak aktif') ? 'nonaktif' : 'aktif';

            // Password
            $password = !empty($row['password']) ? trim($row['password']) : '12345678';

            $userData = [
                'nik'           => $nik,
                'nama'          => $nama,
                'username'      => $username,
                'password'      => $password,
                'jenis_kelamin' => $jenisKelamin,
                'alamat'        => !empty($row['alamat']) ? trim($row['alamat']) : null,
                'no_handphone'  => !empty($row['no_handphone']) ? trim($row['no_handphone']) : null,
                'jabatan'       => $jabatan,
                'lokasi_id'     => $lokasiId,
                'role'          => $role,
                'status'        => $status,
                'foto_profil'   => null
            ];

            if ($this->userModel->create($userData)) {
                $userMap[$lowerUsername] = true;
                $successCount++;
            } else {
                $skippedCount++;
                $errors[] = "Baris {$lineNum}: Gagal menyimpan pegawai '{$nama}'.";
            }
        }

        $_SESSION['import_result'] = [
            'success' => $successCount,
            'skipped' => $skippedCount,
            'errors'  => $errors
        ];

        header("Location: " . Config::baseUrl('pegawai?msg=imported'));
        exit;
    }
}
