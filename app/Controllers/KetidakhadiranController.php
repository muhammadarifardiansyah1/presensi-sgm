<?php
/**
 * Ketidakhadiran Controller (Izin / Sakit / Cuti)
 * Absensi Surya Gemilang Mojopahit (SGM)
 */

require_once __DIR__ . '/../Config/Config.php';
require_once __DIR__ . '/AuthController.php';
require_once __DIR__ . '/../Models/Ketidakhadiran.php';
require_once __DIR__ . '/../Helpers/ImageHelper.php';
require_once __DIR__ . '/../Helpers/RateLimiter.php';
require_once __DIR__ . '/../Helpers/GoogleSyncHelper.php';

class KetidakhadiranController {
    private Ketidakhadiran $ketidakhadiranModel;

    public function __construct() {
        $this->ketidakhadiranModel = new Ketidakhadiran();
    }

    public function index(): void {
        $currentUser = AuthController::checkAuth();

        if ($currentUser['role'] === 'admin') {
            $statusFilter = $_GET['status'] ?? null;
            $dataIzin = $this->ketidakhadiranModel->getAll($statusFilter);
        } else {
            $dataIzin = $this->ketidakhadiranModel->getByUserId($currentUser['id']);
        }

        require_once __DIR__ . '/../Views/ketidakhadiran/index.php';
    }

    public function ajukan(): void {
        $currentUser = AuthController::checkAuth();
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Proteksi Rate Limiting & Anti-Spam Pengajuan (Max 4 per 60 detik per user)
            $userId = (int)$currentUser['id'];
            $rateKey = 'ketidakhadiran:user:' . $userId;
            if (RateLimiter::tooManyAttempts($rateKey, 4)) {
                $seconds = RateLimiter::availableIn($rateKey);
                http_response_code(429);
                header('Retry-After: ' . $seconds);
                $error = "Terlalu banyak pengajuan ketidakhadiran dalam waktu singkat. Mohon tunggu {$seconds} detik sebelum mencoba kembali.";
                require_once __DIR__ . '/../Views/ketidakhadiran/form.php';
                return;
            }
            RateLimiter::hit($rateKey, 60);

            $kategori       = $_POST['kategori'] ?? 'izin';
            $tanggalMulai   = $_POST['tanggal_mulai'] ?? '';
            $tanggalSelesai = $_POST['tanggal_selesai'] ?? '';
            $keterangan     = trim($_POST['keterangan'] ?? '');

            if ($tanggalMulai === '' || $tanggalSelesai === '' || $keterangan === '') {
                $error = 'Tanggal dan keterangan pengajuan wajib diisi!';
            } elseif (strtotime($tanggalSelesai) < strtotime($tanggalMulai)) {
                $error = 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai!';
            } else {
                // Proteksi Idempotensi / Anti Double-Submission
                $duplicate = $this->ketidakhadiranModel->checkDuplicate($currentUser['id'], $tanggalMulai, $tanggalSelesai);
                if ($duplicate) {
                    header("Location: " . Config::baseUrl('ketidakhadiran?msg=already_exists'));
                    exit;
                }

                $dokumenBukti = null;

                // Handle file upload jika ada lampiran dokumen bukti
                if (isset($_FILES['dokumen']) && $_FILES['dokumen']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $uploadError = $_FILES['dokumen']['error'];
                    if ($uploadError === UPLOAD_ERR_INI_SIZE || $uploadError === UPLOAD_ERR_FORM_SIZE) {
                        $error = 'Ukuran file lampiran terlalu besar! Batas maksimal server terlampaui.';
                    } elseif ($uploadError !== UPLOAD_ERR_OK) {
                        $error = 'Terjadi kesalahan saat mengunggah berkas lampiran.';
                    } else {
                        $uploadDir = __DIR__ . '/../../assets/images/uploads/dokumen/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }

                        // Deteksi MIME type biner asli
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $realMime = finfo_file($finfo, $_FILES['dokumen']['tmp_name']);
                        finfo_close($finfo);

                        $imageMimes = ['image/jpeg', 'image/png', 'image/webp'];

                        if ($realMime === 'application/pdf') {
                            // Batas maksimal dokumen PDF adalah 1 MB (1.048.576 bytes)
                            if ($_FILES['dokumen']['size'] > 1 * 1024 * 1024) {
                                $error = 'Ukuran file dokumen PDF maksimal 1MB!';
                            } else {
                                $safeName = 'doc_' . $currentUser['id'] . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.pdf';
                                $dest = $uploadDir . $safeName;
                                if (move_uploaded_file($_FILES['dokumen']['tmp_name'], $dest)) {
                                    $dokumenBukti = $safeName;
                                } else {
                                    $error = 'Gagal menyimpan dokumen PDF lampiran.';
                                }
                            }
                        } elseif (in_array($realMime, $imageMimes)) {
                            // Batas maksimal berkas foto adalah 5 MB (sebelum dikompresi otomatis)
                            if ($_FILES['dokumen']['size'] > 5 * 1024 * 1024) {
                                $error = 'Ukuran berkas foto lampiran maksimal 5MB!';
                            } else {
                                // Kompresi foto surat dokter ke max 1200px (sangat tajam untuk teks namun hemat 95% storage)
                                $compressed = ImageHelper::compressAndSaveUpload(
                                    $_FILES['dokumen'], 
                                    $uploadDir, 
                                    'doc_' . $currentUser['id'], 
                                    1200, 
                                    80
                                );
                                if ($compressed) {
                                    $dokumenBukti = $compressed;
                                } else {
                                    $error = 'Gagal mengompresi dan menyimpan foto lampiran.';
                                }
                            }
                        } else {
                            $error = 'Format file tidak didukung! Hanya diperbolehkan berkas foto (JPG, PNG, WebP) atau dokumen PDF.';
                        }
                    }
                }

                // Simpan ke database jika tidak ada error
                if (empty($error)) {
                    // Upload ke Google Drive jika ada dokumen dan webhook aktif
                    if (!empty($dokumenBukti) && GoogleSyncHelper::isConfigured()) {
                        $fullDocPath = $uploadDir . $dokumenBukti;
                        if (file_exists($fullDocPath)) {
                            $driveRes = GoogleSyncHelper::syncDocument($fullDocPath, $dokumenBukti, [
                                'nama' => $currentUser['nama'] ?? '-',
                                'nik' => $currentUser['nik'] ?? '-',
                                'kategori' => $kategori,
                                'tanggal_mulai' => $tanggalMulai,
                                'tanggal_selesai' => $tanggalSelesai,
                                'keterangan' => $keterangan
                            ]);
                            if (!empty($driveRes['drive_url'])) {
                                $dokumenBukti = $driveRes['drive_url'];
                            }
                        }
                    }

                    $sukses = $this->ketidakhadiranModel->create([
                        'user_id'         => $currentUser['id'],
                        'kategori'        => $kategori,
                        'tanggal_mulai'   => $tanggalMulai,
                        'tanggal_selesai' => $tanggalSelesai,
                        'keterangan'      => $keterangan,
                        'dokumen_bukti'   => $dokumenBukti
                    ]);

                    if ($sukses) {
                        header("Location: " . Config::baseUrl('ketidakhadiran?msg=submitted'));
                        exit;
                    } else {
                        $error = 'Gagal menyimpan pengajuan ketidakhadiran ke database.';
                    }
                }
            }
        }

        require_once __DIR__ . '/../Views/ketidakhadiran/form.php';
    }

    public function approve(int $id): void {
        AuthController::checkAdmin();
        if ($id <= 0) {
            header("Location: " . Config::baseUrl('ketidakhadiran'), true, 303);
            exit;
        }
        $catatan = trim($_POST['catatan'] ?? '') ?: 'Disetujui oleh Administrator';
        $this->ketidakhadiranModel->updateStatus($id, 'disetujui', $catatan);
        header("Location: " . Config::baseUrl('ketidakhadiran?msg=approved'), true, 303);
        exit;
    }

    public function reject(int $id): void {
        AuthController::checkAdmin();
        if ($id <= 0) {
            header("Location: " . Config::baseUrl('ketidakhadiran'), true, 303);
            exit;
        }
        $catatan = trim($_POST['catatan'] ?? '') ?: 'Pengajuan ditolak oleh Administrator';
        $this->ketidakhadiranModel->updateStatus($id, 'ditolak', $catatan);
        header("Location: " . Config::baseUrl('ketidakhadiran?msg=rejected'), true, 303);
        exit;
    }

    public function edit(int $id): void {
        AuthController::checkAdmin();
        if ($id <= 0) {
            header("Location: " . Config::baseUrl('ketidakhadiran?err=invalid_id'), true, 303);
            exit;
        }

        $item = $this->ketidakhadiranModel->findById($id);
        if (!$item) {
            header("Location: " . Config::baseUrl('ketidakhadiran?err=not_found'), true, 303);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $kategori       = trim($_POST['kategori'] ?? 'izin');
            $tanggalMulai   = trim($_POST['tanggal_mulai'] ?? '');
            $tanggalSelesai = trim($_POST['tanggal_selesai'] ?? '');
            $keterangan     = trim($_POST['keterangan'] ?? '');
            $statusApproval = trim($_POST['status_approval'] ?? 'pending');
            $catatanAdmin   = trim($_POST['catatan_admin'] ?? '');

            if ($tanggalMulai === '' || $tanggalSelesai === '' || $keterangan === '') {
                header("Location: " . Config::baseUrl('ketidakhadiran?err=empty_fields'), true, 303);
                exit;
            }

            if (strtotime($tanggalSelesai) < strtotime($tanggalMulai)) {
                header("Location: " . Config::baseUrl('ketidakhadiran?err=invalid_date'), true, 303);
                exit;
            }

            // Validasi tabrakan tanggal jika status disetujui
            if ($statusApproval === 'disetujui') {
                $duplicate = $this->ketidakhadiranModel->checkDuplicate((int)$item['user_id'], $tanggalMulai, $tanggalSelesai, $id);
                if ($duplicate && $duplicate['status_approval'] === 'disetujui') {
                    header("Location: " . Config::baseUrl('ketidakhadiran?err=bentrok_disetujui'), true, 303);
                    exit;
                }
            }

            $this->ketidakhadiranModel->update($id, [
                'kategori'        => $kategori,
                'tanggal_mulai'   => $tanggalMulai,
                'tanggal_selesai' => $tanggalSelesai,
                'keterangan'      => $keterangan,
                'status_approval' => $statusApproval,
                'catatan_admin'   => $catatanAdmin
            ]);

            header("Location: " . Config::baseUrl('ketidakhadiran?msg=updated'), true, 303);
            exit;
        }

        header("Location: " . Config::baseUrl('ketidakhadiran'), true, 303);
        exit;
    }

    public function hapus(int $id): void {
        AuthController::checkAdmin();
        if ($id <= 0) {
            header("Location: " . Config::baseUrl('ketidakhadiran?err=invalid_id'), true, 303);
            exit;
        }

        $sukses = $this->ketidakhadiranModel->delete($id);
        if ($sukses) {
            header("Location: " . Config::baseUrl('ketidakhadiran?msg=deleted'), true, 303);
        } else {
            header("Location: " . Config::baseUrl('ketidakhadiran?err=delete_failed'), true, 303);
        }
        exit;
    }

    public function exportExcel(): void {
        AuthController::checkAdmin();

        $statusFilter         = !empty($_GET['status']) ? trim($_GET['status']) : null;
        $kategoriFilter       = !empty($_GET['kategori']) ? trim($_GET['kategori']) : null;
        $tanggalMulaiFilter   = !empty($_GET['tanggal_mulai']) ? trim($_GET['tanggal_mulai']) : null;
        $tanggalSelesaiFilter = !empty($_GET['tanggal_selesai']) ? trim($_GET['tanggal_selesai']) : null;

        $dataIzin = $this->ketidakhadiranModel->getAll($statusFilter, $kategoriFilter, $tanggalMulaiFilter, $tanggalSelesaiFilter);

        $filename = "Rekap_Ketidakhadiran_" . date('Ymd_His') . ".xls";
        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Cache-Control: max-age=0");

        require_once __DIR__ . '/../Views/ketidakhadiran/export_excel.php';
        exit;
    }
}
