<?php
/**
 * Jabatan Controller
 * Absensi Surya Gemilang Mojopahit (SGM)
 */

require_once __DIR__ . '/../Config/Config.php';
require_once __DIR__ . '/AuthController.php';
require_once __DIR__ . '/../Models/Jabatan.php';

class JabatanController {
    private Jabatan $jabatanModel;

    public function __construct() {
        $this->jabatanModel = new Jabatan();
    }

    public function index(): void {
        $currentUser = AuthController::checkAdmin();
        $jabatanList = $this->jabatanModel->getAll();
        require_once __DIR__ . '/../Views/master/jabatan_index.php';
    }

    public function tambah(): void {
        $currentUser = AuthController::checkAdmin();
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $namaJabatan = trim($_POST['nama_jabatan'] ?? '');

            if ($namaJabatan === '') {
                $error = 'Nama jabatan wajib diisi!';
            } elseif ($this->jabatanModel->findByName($namaJabatan)) {
                $error = 'Nama jabatan ini sudah terdaftar!';
            } else {
                if ($this->jabatanModel->create($namaJabatan)) {
                    header("Location: " . Config::baseUrl('master/jabatan?msg=added'));
                    exit;
                } else {
                    $error = 'Gagal menyimpan data jabatan.';
                }
            }
        }

        require_once __DIR__ . '/../Views/master/jabatan_form.php';
    }

    public function edit(int $id): void {
        $currentUser = AuthController::checkAdmin();
        $jabatan = $this->jabatanModel->findById($id);
        if (!$jabatan) {
            header("Location: " . Config::baseUrl('master/jabatan'));
            exit;
        }

        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $namaJabatan = trim($_POST['nama_jabatan'] ?? '');

            if ($namaJabatan === '') {
                $error = 'Nama jabatan wajib diisi!';
            } else {
                $existing = $this->jabatanModel->findByName($namaJabatan);
                if ($existing && $existing['id'] !== $id) {
                    $error = 'Nama jabatan ini sudah digunakan!';
                } else {
                    if ($this->jabatanModel->update($id, $namaJabatan)) {
                        header("Location: " . Config::baseUrl('master/jabatan?msg=updated'));
                        exit;
                    } else {
                        $error = 'Gagal memperbarui data jabatan.';
                    }
                }
            }
        }

        require_once __DIR__ . '/../Views/master/jabatan_form.php';
    }

    public function hapus(int $id): void {
        AuthController::checkAdmin();
        $usage = $this->jabatanModel->isUsed($id);
        if ($usage['is_used']) {
            header("Location: " . Config::baseUrl('master/jabatan?err=in_use&count=' . $usage['count'] . '&name=' . urlencode($usage['nama_jabatan'])));
            exit;
        }

        $this->jabatanModel->delete($id);
        header("Location: " . Config::baseUrl('master/jabatan?msg=deleted'));
        exit;
    }
}
