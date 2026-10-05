<?php
/**
 * Ketidakhadiran Controller (Izin / Sakit / Cuti)
 * Absensi Surya Gading Mandiri (SGM)
 */

require_once __DIR__ . '/../Config/Config.php';
require_once __DIR__ . '/AuthController.php';
require_once __DIR__ . '/../Models/Ketidakhadiran.php';
require_once __DIR__ . '/../Helpers/ImageHelper.php';

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
        $catatan = $_POST['catatan'] ?? 'Disetujui oleh Administrator';
        $this->ketidakhadiranModel->updateStatus($id, 'disetujui', $catatan);
        header("Location: " . Config::baseUrl('ketidakhadiran?msg=approved'));
        exit;
    }

    public function reject(int $id): void {
        AuthController::checkAdmin();
        $catatan = $_POST['catatan'] ?? 'Pengajuan ditolak';
        $this->ketidakhadiranModel->updateStatus($id, 'ditolak', $catatan);
        header("Location: " . Config::baseUrl('ketidakhadiran?msg=rejected'));
        exit;
    }
}
