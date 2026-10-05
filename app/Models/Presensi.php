<?php
/**
 * Presensi Model
 * Absensi Surya Gading Mandiri (SGM)
 */

require_once __DIR__ . '/../Config/Database.php';

class Presensi {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function getHariIni(int $userId, string $tanggal): ?array {
        $stmt = $this->db->prepare("
            SELECT p.*, l.nama_lokasi 
            FROM `presensi` p
            LEFT JOIN `lokasi_presensi` l ON p.lokasi_id = l.id
            WHERE p.user_id = ? AND p.tanggal = ?
            LIMIT 1
        ");
        $stmt->execute([$userId, $tanggal]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByUserAndTanggal(int $userId, string $tanggal): ?array {
        return $this->getHariIni($userId, $tanggal);
    }

    public function absenMasuk(
        int $userId, 
        int $lokasiId, 
        string $tanggal, 
        string $jamMasuk, 
        string $fotoMasuk, 
        float $lat, 
        float $long, 
        float $jarak, 
        string $status, 
        string $terlambat
    ): bool {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO `presensi` 
                (`user_id`, `lokasi_id`, `tanggal`, `jam_masuk`, `foto_masuk`, `lat_masuk`, `long_masuk`, `jarak_masuk`, `status_kehadiran`, `total_terlambat`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            return $stmt->execute([
                $userId, $lokasiId, $tanggal, $jamMasuk, $fotoMasuk, $lat, $long, $jarak, $status, $terlambat
            ]);
        } catch (\PDOException $e) {
            // Tangani race condition / duplicate entry (MySQL Error 1062 / SQLSTATE 23000)
            if ($e->getCode() === '23000' || ($e->errorInfo[1] ?? 0) === 1062) {
                error_log("[Presensi] Race condition duplicate absen masuk dicegah untuk user_id: {$userId}, tanggal: {$tanggal}");
                return false;
            }
            throw $e;
        }
    }

    public function absenKeluar(
        int $presensiId, 
        string $jamPulang, 
        string $fotoKeluar, 
        float $lat, 
        float $long, 
        float $jarak, 
        string $totalJam
    ): bool {
        // Fetch location schedule for overtime calculation
        $stmtPres = $this->db->prepare("
            SELECT p.lokasi_id, l.jam_pulang 
            FROM `presensi` p 
            LEFT JOIN `lokasi_presensi` l ON p.lokasi_id = l.id 
            WHERE p.id = ?
        ");
        $stmtPres->execute([$presensiId]);
        $presRow = $stmtPres->fetch();
        $jamPulangStandar = (!empty($presRow['jam_pulang'])) ? $presRow['jam_pulang'] : '17:00:00';

        $jamLembur = 0.00;
        if (strtotime($jamPulang) > strtotime($jamPulangStandar)) {
            $lemburDiff = strtotime($jamPulang) - strtotime($jamPulangStandar);
            $jamLembur = round($lemburDiff / 3600, 2);
        }

        $stmt = $this->db->prepare("
            UPDATE `presensi` SET
                `jam_pulang` = ?,
                `foto_keluar` = ?,
                `lat_keluar` = ?,
                `long_keluar` = ?,
                `jarak_keluar` = ?,
                `total_jam_kerja` = ?,
                `jam_lembur` = ?
            WHERE `id` = ? AND `jam_pulang` IS NULL
        ");
        $stmt->execute([
            $jamPulang, $fotoKeluar, $lat, $long, $jarak, $totalJam, $jamLembur, $presensiId
        ]);
        return $stmt->rowCount() > 0;
    }

    public function getRekapBulanan(int $bulan, int $tahun, ?int $userId = null): array {
        $sql = "
            SELECT 
                p.*,
                u.nama AS nama_pegawai,
                u.nik,
                u.jabatan,
                l.nama_lokasi
            FROM `presensi` p
            JOIN `users` u ON p.user_id = u.id
            LEFT JOIN `lokasi_presensi` l ON p.lokasi_id = l.id
            WHERE MONTH(p.tanggal) = ? AND YEAR(p.tanggal) = ?
        ";
        $params = [$bulan, $tahun];

        if ($userId !== null) {
            $sql .= " AND p.user_id = ?";
            $params[] = $userId;
        }

        $sql .= " ORDER BY p.tanggal DESC, p.jam_masuk DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getRekapHarian(string $tanggalMulai, ?string $tanggalSelesai = null, ?int $userId = null): array {
        if (empty($tanggalSelesai)) {
            $tanggalSelesai = $tanggalMulai;
        }

        $sql = "
            SELECT 
                p.*,
                u.nama AS nama_pegawai,
                u.nik,
                u.jabatan,
                l.nama_lokasi
            FROM `presensi` p
            JOIN `users` u ON p.user_id = u.id
            LEFT JOIN `lokasi_presensi` l ON p.lokasi_id = l.id
            WHERE p.tanggal BETWEEN ? AND ?
        ";
        $params = [$tanggalMulai, $tanggalSelesai];

        if ($userId !== null) {
            $sql .= " AND p.user_id = ?";
            $params[] = $userId;
        }

        $sql .= " ORDER BY p.tanggal DESC, p.jam_masuk DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countHadirHariIni(string $tanggal): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM `presensi` WHERE `tanggal` = ? AND `jam_masuk` IS NOT NULL");
        $stmt->execute([$tanggal]);
        return (int) $stmt->fetchColumn();
    }

    public function countAlpaHariIni(string $tanggal): int {
        // Pegawai aktif yang tidak hadir dan tidak ada izin/sakit/cuti
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM `users` u
            WHERE u.status = 'aktif' AND u.role = 'pegawai'
            AND u.id NOT IN (
                SELECT user_id FROM `presensi` WHERE `tanggal` = ?
            )
            AND u.id NOT IN (
                SELECT user_id FROM `ketidakhadiran` 
                WHERE `status_approval` = 'disetujui' 
                AND ? BETWEEN `tanggal_mulai` AND `tanggal_selesai`
            )
        ");
        $stmt->execute([$tanggal, $tanggal]);
        return (int) $stmt->fetchColumn();
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT p.*, u.nama AS nama_pegawai, u.nik, u.jabatan, l.nama_lokasi, l.jam_pulang AS jam_pulang_standar, l.jam_masuk AS jam_masuk_standar
            FROM `presensi` p
            JOIN `users` u ON p.user_id = u.id
            LEFT JOIN `lokasi_presensi` l ON p.lokasi_id = l.id
            WHERE p.id = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function createManual(array $data): bool {
        $jamMasuk = !empty($data['jam_masuk']) ? $data['jam_masuk'] : null;
        $jamPulang = !empty($data['jam_pulang']) ? $data['jam_pulang'] : null;
        $totalJam = '-';
        $totalTerlambat = 'Tepat Waktu';
        $jamLembur = 0.00;

        $lokasiId = !empty($data['lokasi_id']) ? (int)$data['lokasi_id'] : null;
        $jamMasukStandar = '08:00:00';
        $jamPulangStandar = '17:00:00';
        if ($lokasiId) {
            $stmtLok = $this->db->prepare("SELECT jam_masuk, jam_pulang FROM `lokasi_presensi` WHERE id = ?");
            $stmtLok->execute([$lokasiId]);
            $lok = $stmtLok->fetch();
            if ($lok) {
                $jamMasukStandar = $lok['jam_masuk'] ?: '08:00:00';
                $jamPulangStandar = $lok['jam_pulang'] ?: '17:00:00';
            }
        }

        if ($jamMasuk) {
            $masukSec = strtotime($jamMasuk);
            $standarSec = strtotime($jamMasukStandar);
            if ($masukSec > $standarSec) {
                $diff = $masukSec - $standarSec;
                $jam = floor($diff / 3600);
                $menit = floor(($diff % 3600) / 60);
                $totalTerlambat = ($jam > 0 ? "{$jam} jam " : "") . "{$menit} menit";
            }
        }

        if ($jamMasuk && $jamPulang) {
            $diff = strtotime($jamPulang) - strtotime($jamMasuk);
            if ($diff > 0) {
                $jam = floor($diff / 3600);
                $menit = floor(($diff % 3600) / 60);
                $totalJam = "{$jam} jam {$menit} menit";
            }

            $pulangSec = strtotime($jamPulang);
            $standarPulangSec = strtotime($jamPulangStandar);
            if ($pulangSec > $standarPulangSec) {
                $lemburDiff = $pulangSec - $standarPulangSec;
                $jamLembur = round($lemburDiff / 3600, 2);
            }
        }

        $userId = (int)($data['user_id'] ?? 0);
        $tanggal = trim($data['tanggal'] ?? '');
        if ($userId <= 0 || empty($tanggal)) {
            return false;
        }

        // Cek apakah sudah pernah presensi di tanggal tersebut
        if ($this->findByUserAndTanggal($userId, $tanggal)) {
            return false;
        }

        if (isset($data['jam_lembur']) && is_numeric($data['jam_lembur'])) {
            $jamLembur = (float)$data['jam_lembur'];
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO `presensi` (
                    `user_id`, `lokasi_id`, `tanggal`, `jam_masuk`, `jam_pulang`, 
                    `total_jam_kerja`, `total_terlambat`, `jam_lembur`, `status_kehadiran`, `keterangan_admin`
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            return $stmt->execute([
                $userId,
                $lokasiId,
                $tanggal,
                $jamMasuk,
                $jamPulang,
                $totalJam,
                $totalTerlambat,
                $jamLembur,
                $data['status_kehadiran'] ?? 'hadir',
                $data['keterangan_admin'] ?? 'Input manual oleh Admin'
            ]);
        } catch (PDOException $e) {
            // Tangkap MySQL error 1062 duplicate constraint violation atau kesalahan integritas lainnya
            return false;
        }
    }

    public function updateManual(int $id, array $data): bool {
        $jamMasuk = !empty($data['jam_masuk']) ? $data['jam_masuk'] : null;
        $jamPulang = !empty($data['jam_pulang']) ? $data['jam_pulang'] : null;
        $totalJam = '-';
        $totalTerlambat = 'Tepat Waktu';
        $jamLembur = 0.00;

        $lokasiId = !empty($data['lokasi_id']) ? (int)$data['lokasi_id'] : null;
        $jamMasukStandar = '08:00:00';
        $jamPulangStandar = '17:00:00';
        if ($lokasiId) {
            $stmtLok = $this->db->prepare("SELECT jam_masuk, jam_pulang FROM `lokasi_presensi` WHERE id = ?");
            $stmtLok->execute([$lokasiId]);
            $lok = $stmtLok->fetch();
            if ($lok) {
                $jamMasukStandar = $lok['jam_masuk'] ?: '08:00:00';
                $jamPulangStandar = $lok['jam_pulang'] ?: '17:00:00';
            }
        }

        if ($jamMasuk) {
            $masukSec = strtotime($jamMasuk);
            $standarSec = strtotime($jamMasukStandar);
            if ($masukSec > $standarSec) {
                $diff = $masukSec - $standarSec;
                $jam = floor($diff / 3600);
                $menit = floor(($diff % 3600) / 60);
                $totalTerlambat = ($jam > 0 ? "{$jam} jam " : "") . "{$menit} menit";
            }
        }

        if ($jamMasuk && $jamPulang) {
            $diff = strtotime($jamPulang) - strtotime($jamMasuk);
            if ($diff > 0) {
                $jam = floor($diff / 3600);
                $menit = floor(($diff % 3600) / 60);
                $totalJam = "{$jam} jam {$menit} menit";
            }

            $pulangSec = strtotime($jamPulang);
            $standarPulangSec = strtotime($jamPulangStandar);
            if ($pulangSec > $standarPulangSec) {
                $lemburDiff = $pulangSec - $standarPulangSec;
                $jamLembur = round($lemburDiff / 3600, 2);
            }
        }

        if (isset($data['jam_lembur']) && is_numeric($data['jam_lembur'])) {
            $jamLembur = (float)$data['jam_lembur'];
        }

        $stmt = $this->db->prepare("
            UPDATE `presensi` SET
                `lokasi_id` = ?,
                `tanggal` = ?,
                `jam_masuk` = ?,
                `jam_pulang` = ?,
                `total_jam_kerja` = ?,
                `total_terlambat` = ?,
                `jam_lembur` = ?,
                `status_kehadiran` = ?,
                `keterangan_admin` = ?
            WHERE `id` = ?
        ");
        return $stmt->execute([
            $lokasiId,
            $data['tanggal'],
            $jamMasuk,
            $jamPulang,
            $totalJam,
            $totalTerlambat,
            $jamLembur,
            $data['status_kehadiran'] ?? 'hadir',
            $data['keterangan_admin'] ?? null,
            $id
        ]);
    }

    /**
     * Hapus data presensi beserta berkas foto fisik terkait (Clean Deletion)
     */
    public function delete(int $id): bool {
        // Ambil data foto sebelum dihapus untuk membersihkan berkas fisik dari disk
        $stmtFoto = $this->db->prepare("SELECT `foto_masuk`, `foto_keluar` FROM `presensi` WHERE `id` = ?");
        $stmtFoto->execute([$id]);
        $row = $stmtFoto->fetch();

        if ($row) {
            $baseDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'presensi';
            foreach (['foto_masuk', 'foto_keluar'] as $field) {
                if (!empty($row[$field])) {
                    $cleanedRel = ltrim(str_replace(['../', '..\\'], '', $row[$field]), '/\\');
                    $filePath = $baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $cleanedRel);
                    if (file_exists($filePath) && is_file($filePath)) {
                        @unlink($filePath);
                    }
                }
            }
        }

        $stmt = $this->db->prepare("DELETE FROM `presensi` WHERE `id` = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Send monthly attendance records to Google Apps Script Webhook
     */
    public function syncToGoogleSheets(string $webhookUrl, array $dataPresensi, int $bulan, int $tahun): array {
        $webhookUrl = trim($webhookUrl);
        if (empty($webhookUrl) || !filter_var($webhookUrl, FILTER_VALIDATE_URL)) {
            return [
                'success' => false,
                'message' => 'URL Webhook Google Apps Script belum diatur atau format tidak valid.'
            ];
        }

        if (strpos($webhookUrl, 'docs.google.com/spreadsheets') !== false) {
            return [
                'success' => false,
                'message' => 'URL yang dimasukkan adalah link dokumen Google Spreadsheet (docs.google.com/spreadsheets/...), bukan URL Webhook Apps Script (script.google.com/macros/s/.../exec).'
            ];
        }

        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $bulanText = $namaBulan[$bulan] ?? (string)$bulan;
        $sheetName = "Rekap Absensi {$bulanText} {$tahun}";
        $periodeText = "{$bulanText} {$tahun}";

        $rows = [];
        $no = 1;
        foreach ($dataPresensi as $r) {
            $statusMasuk = 'Belum Masuk';
            if (!empty($r['jam_masuk'])) {
                $statusMasuk = ($r['total_terlambat'] === 'Tepat Waktu' || empty($r['total_terlambat'])) ? 'Tepat Waktu' : 'Terlambat';
            }

            $rows[] = [
                'no' => $no++,
                'nik' => $r['nik'] ?? '-',
                'nama' => $r['nama_pegawai'] ?? '-',
                'jabatan' => $r['jabatan'] ?? '-',
                'lokasi' => $r['nama_lokasi'] ?? '-',
                'tanggal' => $r['tanggal'] ?? '-',
                'jam_masuk' => $r['jam_masuk'] ?? '-',
                'status_masuk' => $statusMasuk,
                'terlambat' => $r['total_terlambat'] ?? 'Tepat Waktu',
                'jam_pulang' => $r['jam_pulang'] ?? '-',
                'total_jam_kerja' => $r['total_jam_kerja'] ?? '-',
                'jam_lembur' => (float)($r['jam_lembur'] ?? 0),
                'keterangan' => !empty($r['keterangan_admin']) ? $r['keterangan_admin'] : ucfirst($r['status_kehadiran'] ?? 'Hadir')
            ];
        }

        $payload = [
            'action' => 'sync_presensi',
            'sheet_name' => $sheetName,
            'periode' => $periodeText,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'timestamp' => date('Y-m-d H:i:s'),
            'total_records' => count($rows),
            'data' => $rows,
            'rows' => $rows
        ];

        $jsonPayload = json_encode($payload);

        $ch = curl_init($webhookUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_USERAGENT, 'SGM-Absensi-Engine/1.0');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return [
                'success' => false,
                'message' => 'Gagal terhubung ke Google Apps Script: ' . $error
            ];
        }

        $resDecoded = json_decode($response, true);
        if ($httpCode >= 200 && $httpCode < 300) {
            if (isset($resDecoded['status']) && $resDecoded['status'] === 'error') {
                return [
                    'success' => false,
                    'message' => 'Google Apps Script Error: ' . ($resDecoded['message'] ?? 'Unknown error')
                ];
            }
            return [
                'success' => true,
                'message' => $resDecoded['message'] ?? 'Data rekap presensi berhasil disinkronkan ke Google Spreadsheet!',
                'raw' => $response
            ];
        }

        return [
            'success' => false,
            'message' => 'Google Apps Script merespons kode HTTP ' . $httpCode . ': ' . substr($response, 0, 150)
        ];
    }
}

