<?php
/**
 * Presensi Model
 * Absensi Surya Gemilang Mojopahit (SGM)
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

    /**
     * Menghitung durasi kerja efektif yang dibatasi (capped) oleh batas jam kerja admin.
     * Mencegah pembengkakan jam kerja jika pegawai lupa pulang atau baru klik pulang larut malam.
     * Batas atas = Jam Pulang Standar Lokasi + Jam Lembur (SPL yang disetujui).
     *
     * @param string $tanggal Tanggal presensi (Y-m-d)
     * @param string|null $jamMasuk Waktu masuk (H:i:s)
     * @param string|null $jamPulang Waktu pulang aktual (H:i:s)
     * @param string|null $jamPulangStandar Jam pulang standar lokasi kantor (H:i:s, default 17:00:00)
     * @param float $jamLembur Jam lembur yang disetujui SPL (default 0.00)
     * @return array [detik, format, jam_desimal, is_capped, waktu_selesai_efektif]
     */
    public function calculateCappedDuration(
        string $tanggal,
        ?string $jamMasuk,
        ?string $jamPulang,
        ?string $jamPulangStandar = '17:00:00',
        float $jamLembur = 0.00
    ): array {
        if (empty($jamMasuk)) {
            return [
                'detik' => 0, 
                'format' => '0 jam 0 menit', 
                'jam_desimal' => 0.0, 
                'is_capped' => false, 
                'waktu_selesai_efektif' => '-'
            ];
        }

        $standarPulang = !empty($jamPulangStandar) ? $jamPulangStandar : '17:00:00';
        $tMasuk = strtotime("{$tanggal} {$jamMasuk}");
        $tStandarPulang = strtotime("{$tanggal} {$standarPulang}");

        $detikLembur = (int)round($jamLembur * 3600);
        $tCapMaksimal = $tStandarPulang + $detikLembur;

        // Fallback jika jam masuk melebihi cap (misal shift malam khusus)
        if ($tMasuk >= $tCapMaksimal) {
            $tCapMaksimal = $tMasuk + (8 * 3600);
        }

        $isCapped = false;
        $today = date('Y-m-d');

        if (!empty($jamPulang)) {
            $tPulang = strtotime("{$tanggal} {$jamPulang}");
            if ($tPulang > $tCapMaksimal) {
                $tSelesai = $tCapMaksimal;
                $isCapped = true;
            } else {
                $tSelesai = $tPulang;
            }
        } else {
            // Belum atau tidak absen pulang
            if ($tanggal === $today) {
                $now = time();
                if ($now > $tCapMaksimal) {
                    $tSelesai = $tCapMaksimal;
                    $isCapped = true;
                } else {
                    $tSelesai = max($tMasuk, $now);
                }
            } else {
                // Hari lampau tapi lupa absen pulang -> capped di jam pulang kantor / SPL
                $tSelesai = $tCapMaksimal;
                $isCapped = true;
            }
        }

        $detik = max(0, $tSelesai - $tMasuk);
        $hours = floor($detik / 3600);
        $minutes = floor(($detik % 3600) / 60);
        $format = "{$hours} jam {$minutes} menit";

        return [
            'detik' => $detik,
            'format' => $format,
            'jam_desimal' => round($detik / 3600, 2),
            'is_capped' => $isCapped,
            'waktu_selesai_efektif' => date('H:i:s', $tSelesai)
        ];
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
        // Fetch location schedule and attendance data for overtime verification
        $stmtPres = $this->db->prepare("
            SELECT p.id, p.user_id, p.tanggal, p.lokasi_id, p.jam_masuk, l.jam_pulang 
            FROM `presensi` p 
            LEFT JOIN `lokasi_presensi` l ON p.lokasi_id = l.id 
            WHERE p.id = ?
        ");
        $stmtPres->execute([$presensiId]);
        $presRow = $stmtPres->fetch();
        $jamPulangStandar = (!empty($presRow['jam_pulang'])) ? $presRow['jam_pulang'] : '17:00:00';

        $jamLembur = 0.00;
        // VALIDASI INTEGRASI SURAT PERINTAH LEMBUR (SPL):
        // Mencegah kecurangan karyawan yang sengaja pulang terlambat demi uang lembur.
        // Lembur HANYA diakui jika Admin telah menerbitkan Surat Perintah Lembur aktif untuk pegawai tersebut pada tanggal ini.
        if (!empty($presRow['user_id']) && !empty($presRow['tanggal'])) {
            require_once __DIR__ . '/PenugasanLembur.php';
            $penugasanModel = new PenugasanLembur();
            $penugasan = $penugasanModel->getPenugasanAktifUser((int)$presRow['user_id'], $presRow['tanggal']);

            if ($penugasan) {
                // Tentukan patokan jam mulai lembur (prioritas jam_mulai SPL, fallback ke jam pulang standar lokasi)
                $jamMulaiLemburRef = !empty($penugasan['jam_mulai']) ? $penugasan['jam_mulai'] : $jamPulangStandar;

                if (strtotime($jamPulang) > strtotime($jamMulaiLemburRef)) {
                    $lemburDiff = strtotime($jamPulang) - strtotime($jamMulaiLemburRef);
                    $hitungLembur = round($lemburDiff / 3600, 2);

                    // Batasi maksimal jam lembur sesuai batas maksimal yang disetujui pada SPL (Capping)
                    $maksimalJam = (float)($penugasan['maksimal_jam'] ?? 0);
                    if ($maksimalJam > 0) {
                        $jamLembur = min($hitungLembur, $maksimalJam);
                    } else {
                        $jamLembur = $hitungLembur;
                    }

                    // Sinkronisasi status dan jam lembur aktual di tabel pivot penugasan_lembur_pegawai
                    if (!empty($penugasan['plp_id'])) {
                        $penugasanModel->updateJamLemburAktual((int)$penugasan['plp_id'], $jamLembur);
                    }
                }
            }
        }

        // Hitung total jam kerja dengan batas atas (auto-capped) jam kerja admin & SPL
        $calcDuration = $this->calculateCappedDuration(
            $presRow['tanggal'] ?? date('Y-m-d'),
            $presRow['jam_masuk'] ?? null,
            $jamPulang,
            $jamPulangStandar,
            $jamLembur
        );
        $totalJam = $calcDuration['format'];

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
                l.nama_lokasi,
                l.jam_pulang AS jam_pulang_standar
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
        $rows = $stmt->fetchAll();

        $today = date('Y-m-d');
        foreach ($rows as &$r) {
            if ((empty($r['total_jam_kerja']) || $r['total_jam_kerja'] === '-') && !empty($r['jam_masuk']) && $r['tanggal'] < $today) {
                $calc = $this->calculateCappedDuration(
                    $r['tanggal'],
                    $r['jam_masuk'],
                    $r['jam_pulang'] ?? null,
                    $r['jam_pulang_standar'] ?? '17:00:00',
                    (float)($r['jam_lembur'] ?? 0)
                );
                $r['total_jam_kerja'] = $calc['format'];
            }
        }
        unset($r);

        return $rows;
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
                l.nama_lokasi,
                l.jam_pulang AS jam_pulang_standar
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
        $rows = $stmt->fetchAll();

        $today = date('Y-m-d');
        foreach ($rows as &$r) {
            if ((empty($r['total_jam_kerja']) || $r['total_jam_kerja'] === '-') && !empty($r['jam_masuk']) && $r['tanggal'] < $today) {
                $calc = $this->calculateCappedDuration(
                    $r['tanggal'],
                    $r['jam_masuk'],
                    $r['jam_pulang'] ?? null,
                    $r['jam_pulang_standar'] ?? '17:00:00',
                    (float)($r['jam_lembur'] ?? 0)
                );
                $r['total_jam_kerja'] = $calc['format'];
            }
        }
        unset($r);

        return $rows;
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

        $userId = (int)($data['user_id'] ?? 0);
        $tanggal = trim($data['tanggal'] ?? '');
        if ($userId <= 0 || empty($tanggal)) {
            return false;
        }

        if (isset($data['jam_lembur']) && is_numeric($data['jam_lembur'])) {
            $jamLembur = (float)$data['jam_lembur'];
        } elseif ($jamMasuk && $jamPulang) {
            $pulangSec = strtotime($jamPulang);
            $standarPulangSec = strtotime($jamPulangStandar);
            if ($pulangSec > $standarPulangSec) {
                $lemburDiff = $pulangSec - $standarPulangSec;
                $jamLembur = round($lemburDiff / 3600, 2);
            }
        }

        if ($jamMasuk && $jamPulang) {
            $calc = $this->calculateCappedDuration(
                $tanggal,
                $jamMasuk,
                $jamPulang,
                $jamPulangStandar,
                $jamLembur
            );
            $totalJam = $calc['format'];
        }

        // Cek apakah sudah pernah presensi di tanggal tersebut
        if ($this->findByUserAndTanggal($userId, $tanggal)) {
            return false;
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

        $tanggal = trim($data['tanggal'] ?? '');
        if (isset($data['jam_lembur']) && is_numeric($data['jam_lembur'])) {
            $jamLembur = (float)$data['jam_lembur'];
        } elseif ($jamMasuk && $jamPulang) {
            $pulangSec = strtotime($jamPulang);
            $standarPulangSec = strtotime($jamPulangStandar);
            if ($pulangSec > $standarPulangSec) {
                $lemburDiff = $pulangSec - $standarPulangSec;
                $jamLembur = round($lemburDiff / 3600, 2);
            }
        }

        if ($jamMasuk && $jamPulang) {
            $calc = $this->calculateCappedDuration(
                $tanggal ?: date('Y-m-d'),
                $jamMasuk,
                $jamPulang,
                $jamPulangStandar,
                $jamLembur
            );
            $totalJam = $calc['format'];
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

    /**
     * Mengambil statistik presensi bulanan pegawai (Month-to-Date)
     * Menggunakan durasi kerja efektif dengan batas atas (auto-capped).
     */
    public function getStatistikBulananPegawai(int $userId, int $bulan, int $tahun): array {
        $stmt = $this->db->prepare("
            SELECT 
                p.`tanggal`,
                p.`jam_masuk`,
                p.`jam_pulang`,
                p.`total_terlambat`,
                p.`jam_lembur`,
                l.jam_pulang AS jam_pulang_standar
            FROM `presensi` p
            LEFT JOIN `lokasi_presensi` l ON p.lokasi_id = l.id
            WHERE p.`user_id` = ? AND MONTH(p.`tanggal`) = ? AND YEAR(p.`tanggal`) = ?
        ");
        $stmt->execute([$userId, $bulan, $tahun]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalHadir = 0;
        $tepatWaktu = 0;
        $terlambat  = 0;
        $totalDetik = 0;
        $totalLembur = 0.0;

        foreach ($rows as $r) {
            if (!empty($r['jam_masuk'])) {
                $totalHadir++;

                if (empty($r['total_terlambat']) || $r['total_terlambat'] === 'Tepat Waktu') {
                    $tepatWaktu++;
                } else {
                    $terlambat++;
                }

                $lembur = (float)($r['jam_lembur'] ?? 0);
                $totalLembur += $lembur;

                $calc = $this->calculateCappedDuration(
                    $r['tanggal'],
                    $r['jam_masuk'],
                    $r['jam_pulang'],
                    $r['jam_pulang_standar'] ?? '17:00:00',
                    $lembur
                );
                $totalDetik += $calc['detik'];
            }
        }

        // Hitung estimasi hari kerja berjalan bulan ini (Senin - Sabtu)
        $hariIni = (int)date('d');
        $hariKerjaBerjalan = 0;
        for ($d = 1; $d <= $hariIni; $d++) {
            $w = date('w', strtotime("{$tahun}-{$bulan}-{$d}"));
            if ($w != 0) { // Bukan hari Minggu
                $hariKerjaBerjalan++;
            }
        }
        $persentase = ($hariKerjaBerjalan > 0) ? min(100, round(($totalHadir / $hariKerjaBerjalan) * 100)) : 100;

        $jamFormat = '0 Jam';
        if ($totalDetik >= 3600) {
            $j = floor($totalDetik / 3600);
            $m = floor(($totalDetik % 3600) / 60);
            $jamFormat = ($m > 0) ? "{$j} Jam {$m} Menit" : "{$j} Jam";
        } elseif ($totalDetik > 0) {
            $m = max(1, floor($totalDetik / 60));
            $jamFormat = "{$m} Menit";
        }

        return [
            'total_hadir'            => $totalHadir,
            'total_tepat_waktu'      => $tepatWaktu,
            'total_terlambat'        => $terlambat,
            'total_detik_kerja'      => $totalDetik,
            'total_jam_kerja_format' => $jamFormat,
            'total_jam_kerja_desimal'=> round($totalDetik / 3600, 1),
            'total_lembur'           => $totalLembur,
            'total_jam_lembur'       => $totalLembur,
            'persentase_kehadiran'   => $persentase,
            'hari_kerja_berjalan'    => $hariKerjaBerjalan
        ];
    }

    /**
     * Mengambil data tren jam kerja dan kehadiran 7 hari terakhir (H-6 s/d Hari Ini)
     */
    public function getTrenTujuhHari(int $userId): array {
        $namaHariIndo = [
            'Sun' => 'Min', 'Mon' => 'Sen', 'Tue' => 'Sel', 
            'Wed' => 'Rab', 'Thu' => 'Kam', 'Fri' => 'Jum', 'Sat' => 'Sab'
        ];

        $tujuhHari = [];
        for ($i = 6; $i >= 0; $i--) {
            $tgl = date('Y-m-d', strtotime("-{$i} days"));
            $ts = strtotime($tgl);
            $dayOfWeek = date('D', $ts);
            $isSunday = ($dayOfWeek === 'Sun');

            $tujuhHari[$tgl] = [
                'tanggal'        => $tgl,
                'hari'           => date('l', $ts),
                'hari_pendek'    => $namaHariIndo[$dayOfWeek] ?? $dayOfWeek,
                'is_sunday'      => $isSunday,
                'is_today'       => ($tgl === date('Y-m-d')),
                'jam_kerja_desimal' => 0.0,
                'jam_kerja_format'  => '0j',
                'height_percent' => 8, // Minimum pill height
                'status'         => $isSunday ? 'libur' : 'kosong',
                'status_label'   => $isSunday ? 'Libur' : 'Belum Ada Data'
            ];
        }

        $minTgl = array_key_first($tujuhHari);
        $stmt = $this->db->prepare("
            SELECT p.`tanggal`, p.`jam_masuk`, p.`jam_pulang`, p.`status_kehadiran`, p.`total_terlambat`, p.`jam_lembur`,
                   l.jam_pulang AS jam_pulang_standar
            FROM `presensi` p
            LEFT JOIN `lokasi_presensi` l ON p.lokasi_id = l.id
            WHERE p.`user_id` = ? AND p.`tanggal` >= ?
        ");
        $stmt->execute([$userId, $minTgl]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $r) {
            $tgl = $r['tanggal'];
            if (isset($tujuhHari[$tgl])) {
                $calc = $this->calculateCappedDuration(
                    $r['tanggal'],
                    $r['jam_masuk'],
                    $r['jam_pulang'],
                    $r['jam_pulang_standar'] ?? '17:00:00',
                    (float)($r['jam_lembur'] ?? 0)
                );
                $detik = $calc['detik'];
                $jam = round($detik / 3600, 1);

                // Hitung tinggi kapsul bar chart (8 jam kerja standar = 100%)
                $heightPercent = ($jam > 0) ? min(100, max(16, round(($jam / 8) * 100))) : 8;

                $tujuhHari[$tgl]['jam_kerja_desimal'] = $jam;
                $tujuhHari[$tgl]['jam_kerja_format']  = ($jam > 0) ? "{$jam}j" : "0j";
                $tujuhHari[$tgl]['height_percent']    = $heightPercent;
                $tujuhHari[$tgl]['status']            = $r['status_kehadiran'] ?? 'hadir';
                $tujuhHari[$tgl]['status_label']      = ($r['total_terlambat'] === 'Tepat Waktu' || empty($r['total_terlambat'])) 
                                                        ? 'Tepat Waktu' : 'Terlambat';
            }
        }

        return array_values($tujuhHari);
    }

    /**
     * Mengambil riwayat aktivitas terpadu terakhir pegawai (Presensi Fisik + Pengajuan Izin/Sakit/Cuti)
     */
    public function getRiwayatTerakhirPegawai(int $userId, int $limit = 5): array {
        $sql = "
            (
                SELECT 
                    'presensi' AS `tipe_aktivitas`,
                    p.id,
                    p.tanggal,
                    COALESCE(p.jam_pulang, p.jam_masuk, '00:00:00') AS `waktu_sort`,
                    p.jam_masuk,
                    p.jam_pulang,
                    p.status_kehadiran,
                    p.total_terlambat,
                    p.total_jam_kerja,
                    p.jam_lembur,
                    l.nama_lokasi,
                    l.jam_pulang AS jam_pulang_standar,
                    NULL AS `kategori`,
                    NULL AS `tanggal_mulai`,
                    NULL AS `tanggal_selesai`,
                    NULL AS `keterangan`,
                    NULL AS `status_approval`,
                    NULL AS `catatan_admin`,
                    NULL AS `dokumen_bukti`,
                    p.created_at
                FROM `presensi` p
                LEFT JOIN `lokasi_presensi` l ON p.lokasi_id = l.id
                WHERE p.user_id = ?
            )
            UNION ALL
            (
                SELECT 
                    'ketidakhadiran' AS `tipe_aktivitas`,
                    k.id,
                    k.tanggal_mulai AS `tanggal`,
                    TIME(k.created_at) AS `waktu_sort`,
                    NULL AS `jam_masuk`,
                    NULL AS `jam_pulang`,
                    k.kategori AS `status_kehadiran`,
                    NULL AS `total_terlambat`,
                    NULL AS `total_jam_kerja`,
                    0.00 AS `jam_lembur`,
                    NULL AS `nama_lokasi`,
                    '17:00:00' AS jam_pulang_standar,
                    k.kategori,
                    k.tanggal_mulai,
                    k.tanggal_selesai,
                    k.keterangan,
                    k.status_approval,
                    k.catatan_admin,
                    k.dokumen_bukti,
                    k.created_at
                FROM `ketidakhadiran` k
                WHERE k.user_id = ?
            )
            ORDER BY `tanggal` DESC, `waktu_sort` DESC, `id` DESC
            LIMIT ?
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $userId, PDO::PARAM_INT);
        $stmt->bindValue(3, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $today = date('Y-m-d');
        foreach ($rows as &$r) {
            if (($r['tipe_aktivitas'] ?? '') === 'presensi' && (empty($r['total_jam_kerja']) || $r['total_jam_kerja'] === '-') && !empty($r['jam_masuk']) && $r['tanggal'] < $today) {
                $calc = $this->calculateCappedDuration(
                    $r['tanggal'],
                    $r['jam_masuk'],
                    $r['jam_pulang'] ?? null,
                    $r['jam_pulang_standar'] ?? '17:00:00',
                    (float)($r['jam_lembur'] ?? 0)
                );
                $r['total_jam_kerja'] = $calc['format'];
            }
        }
        unset($r);

        return $rows;
    }

    /**
     * Mengambil seluruh riwayat aktivitas pegawai untuk halaman mandiri /riwayat
     */
    public function getAllRiwayatPegawai(int $userId, ?int $bulan = null, ?int $tahun = null, ?string $tipe = null): array {
        $tipe = $tipe ?: 'semua';
        $paramsPres = [$userId];
        $paramsIzin = [$userId];
        $paramsLembur = [$userId];

        $wherePresDate = "";
        $whereIzinDate = "";
        $whereLemburDate = "";
        if ($bulan && $tahun) {
            $wherePresDate = " AND MONTH(p.tanggal) = ? AND YEAR(p.tanggal) = ?";
            $paramsPres[] = $bulan;
            $paramsPres[] = $tahun;

            $whereIzinDate = " AND ((MONTH(k.tanggal_mulai) = ? OR MONTH(k.tanggal_selesai) = ?) AND YEAR(k.tanggal_mulai) = ?)";
            $paramsIzin[] = $bulan;
            $paramsIzin[] = $bulan;
            $paramsIzin[] = $tahun;

            $whereLemburDate = " AND MONTH(pl.tanggal) = ? AND YEAR(pl.tanggal) = ?";
            $paramsLembur[] = $bulan;
            $paramsLembur[] = $tahun;
        }

        $sqlPres = "
            SELECT 
                'presensi' AS `tipe_aktivitas`,
                p.id,
                p.tanggal,
                COALESCE(p.jam_pulang, p.jam_masuk, '00:00:00') AS `waktu_sort`,
                p.jam_masuk,
                p.jam_pulang,
                p.status_kehadiran,
                p.total_terlambat,
                p.total_jam_kerja,
                p.jam_lembur,
                l.nama_lokasi,
                l.jam_pulang AS jam_pulang_standar,
                NULL AS `kategori`,
                NULL AS `tanggal_mulai`,
                NULL AS `tanggal_selesai`,
                NULL AS `keterangan`,
                NULL AS `status_approval`,
                NULL AS `catatan_admin`,
                NULL AS `dokumen_bukti`,
                p.created_at
            FROM `presensi` p
            LEFT JOIN `lokasi_presensi` l ON p.lokasi_id = l.id
            WHERE p.user_id = ? {$wherePresDate}
        ";

        $sqlIzin = "
            SELECT 
                'ketidakhadiran' AS `tipe_aktivitas`,
                k.id,
                k.tanggal_mulai AS `tanggal`,
                TIME(k.created_at) AS `waktu_sort`,
                NULL AS `jam_masuk`,
                NULL AS `jam_pulang`,
                k.kategori AS `status_kehadiran`,
                NULL AS `total_terlambat`,
                NULL AS `total_jam_kerja`,
                0.00 AS `jam_lembur`,
                NULL AS `nama_lokasi`,
                '17:00:00' AS jam_pulang_standar,
                k.kategori,
                k.tanggal_mulai,
                k.tanggal_selesai,
                k.keterangan,
                k.status_approval,
                k.catatan_admin,
                k.dokumen_bukti,
                k.created_at
            FROM `ketidakhadiran` k
            WHERE k.user_id = ? {$whereIzinDate}
        ";

        $sqlLembur = "
            SELECT 
                'lembur' AS `tipe_aktivitas`,
                pl.id,
                pl.tanggal,
                pl.jam_mulai AS `waktu_sort`,
                pl.jam_mulai AS `jam_masuk`,
                pl.jam_selesai AS `jam_pulang`,
                pl.status AS `status_kehadiran`,
                NULL AS `total_terlambat`,
                NULL AS `total_jam_kerja`,
                pl.maksimal_jam AS `jam_lembur`,
                l.nama_lokasi,
                l.jam_pulang AS jam_pulang_standar,
                pl.nomor_sp_lembur AS `kategori`,
                pl.tanggal AS `tanggal_mulai`,
                pl.tanggal AS `tanggal_selesai`,
                pl.keterangan,
                plp.status AS `status_approval`,
                CAST(plp.jam_lembur_aktual AS CHAR) AS `catatan_admin`,
                NULL AS `dokumen_bukti`,
                pl.created_at
            FROM `penugasan_lembur_pegawai` plp
            JOIN `penugasan_lembur` pl ON plp.penugasan_lembur_id = pl.id
            LEFT JOIN `lokasi_presensi` l ON pl.lokasi_id = l.id
            WHERE plp.user_id = ? {$whereLemburDate}
        ";

        if ($tipe === 'presensi') {
            $sql = $sqlPres . " ORDER BY `tanggal` DESC, `waktu_sort` DESC";
            $params = $paramsPres;
        } elseif ($tipe === 'ketidakhadiran') {
            $sql = $sqlIzin . " ORDER BY `tanggal` DESC, `waktu_sort` DESC";
            $params = $paramsIzin;
        } elseif ($tipe === 'lembur') {
            $sql = $sqlLembur . " ORDER BY `tanggal` DESC, `waktu_sort` DESC";
            $params = $paramsLembur;
        } else {
            $sql = "({$sqlPres}) UNION ALL ({$sqlIzin}) UNION ALL ({$sqlLembur}) ORDER BY `tanggal` DESC, `waktu_sort` DESC, `id` DESC";
            $params = array_merge($paramsPres, $paramsIzin, $paramsLembur);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $today = date('Y-m-d');
        foreach ($rows as &$r) {
            if (($r['tipe_aktivitas'] ?? '') === 'presensi' && (empty($r['total_jam_kerja']) || $r['total_jam_kerja'] === '-') && !empty($r['jam_masuk']) && $r['tanggal'] < $today) {
                $calc = $this->calculateCappedDuration(
                    $r['tanggal'],
                    $r['jam_masuk'],
                    $r['jam_pulang'] ?? null,
                    $r['jam_pulang_standar'] ?? '17:00:00',
                    (float)($r['jam_lembur'] ?? 0)
                );
                $r['total_jam_kerja'] = $calc['format'];
            }
        }
        unset($r);

        return $rows;
    }

    /**
     * Mengambil statistik presensi harian untuk Admin (Total Hadir, Tepat Waktu, Terlambat)
     * Menggunakan 1 query agregat terindeks
     */
    public function getStatistikHarianAdmin(string $tanggal): array {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) AS total_hadir,
                SUM(CASE WHEN total_terlambat = 'Tepat Waktu' OR total_terlambat = '' OR total_terlambat IS NULL THEN 1 ELSE 0 END) AS tepat_waktu,
                SUM(CASE WHEN total_terlambat != 'Tepat Waktu' AND total_terlambat != '' AND total_terlambat IS NOT NULL THEN 1 ELSE 0 END) AS terlambat
            FROM `presensi`
            WHERE `tanggal` = ? AND `jam_masuk` IS NOT NULL
        ");
        $stmt->execute([$tanggal]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return [
            'total_hadir' => (int)($row['total_hadir'] ?? 0),
            'tepat_waktu' => (int)($row['tepat_waktu'] ?? 0),
            'terlambat'   => (int)($row['terlambat'] ?? 0)
        ];
    }

    /**
     * Mengambil tren kehadiran 7 hari terakhir untuk Admin
     * Dieksekusi dalam 1 query batch GROUP BY tanggal
     */
    public function getTrenKehadiranAdmin(int $totalPegawai): array {
        $namaHariIndo = [
            'Sun' => 'Min', 'Mon' => 'Sen', 'Tue' => 'Sel', 
            'Wed' => 'Rab', 'Thu' => 'Kam', 'Fri' => 'Jum', 'Sat' => 'Sab'
        ];

        $tujuhHari = [];
        for ($i = 6; $i >= 0; $i--) {
            $tgl = date('Y-m-d', strtotime("-{$i} days"));
            $ts = strtotime($tgl);
            $dayOfWeek = date('D', $ts);
            $isSunday = ($dayOfWeek === 'Sun');

            $tujuhHari[$tgl] = [
                'tanggal'        => $tgl,
                'hari'           => date('l', $ts),
                'hari_pendek'    => $namaHariIndo[$dayOfWeek] ?? $dayOfWeek,
                'is_sunday'      => $isSunday,
                'is_today'       => ($tgl === date('Y-m-d')),
                'total_hadir'    => 0,
                'tepat_waktu'    => 0,
                'terlambat'      => 0,
                'persentase'     => 0,
                'height_percent' => 12,
                'status_label'   => $isSunday ? 'Hari Libur' : '0 Hadir'
            ];
        }

        $minTgl = array_key_first($tujuhHari);
        $stmt = $this->db->prepare("
            SELECT `tanggal`,
                   COUNT(*) AS `total_hadir`,
                   SUM(CASE WHEN `total_terlambat` = 'Tepat Waktu' OR `total_terlambat` = '' OR `total_terlambat` IS NULL THEN 1 ELSE 0 END) AS `tepat_waktu`,
                   SUM(CASE WHEN `total_terlambat` != 'Tepat Waktu' AND `total_terlambat` != '' AND `total_terlambat` IS NOT NULL THEN 1 ELSE 0 END) AS `terlambat`
            FROM `presensi`
            WHERE `tanggal` >= ? AND `jam_masuk` IS NOT NULL
            GROUP BY `tanggal`
        ");
        $stmt->execute([$minTgl]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $maxHadir = max(1, $totalPegawai);

        foreach ($rows as $r) {
            $tgl = $r['tanggal'];
            if (isset($tujuhHari[$tgl])) {
                $hadir = (int)($r['total_hadir'] ?? 0);
                $tepat = (int)($r['tepat_waktu'] ?? 0);
                $telat = (int)($r['terlambat'] ?? 0);
                $pct = $totalPegawai > 0 ? round(($hadir / $totalPegawai) * 100) : 0;
                $height = min(100, max(12, round(($hadir / $maxHadir) * 100)));

                $tujuhHari[$tgl]['total_hadir']    = $hadir;
                $tujuhHari[$tgl]['tepat_waktu']    = $tepat;
                $tujuhHari[$tgl]['terlambat']      = $telat;
                $tujuhHari[$tgl]['persentase']     = $pct;
                $tujuhHari[$tgl]['height_percent'] = $height;
                $tujuhHari[$tgl]['status_label']   = "{$hadir} Hadir ({$pct}%) &bull; {$tepat} Tepat Waktu, {$telat} Terlambat";
            }
        }

        return array_values($tujuhHari);
    }

    /**
     * Mengambil riwayat aktivitas presensi masuk terbaru hari ini untuk stream feed Admin
     */
    public function getAktivitasTerbaruHariIni(string $tanggal, int $limit = 6): array {
        $stmt = $this->db->prepare("
            SELECT p.id, p.user_id, p.tanggal, p.jam_masuk, p.jam_pulang, p.total_terlambat, p.foto_masuk,
                   u.nama AS nama_pegawai, u.nik, u.jabatan, u.foto_profil,
                   l.nama_lokasi
            FROM `presensi` p
            JOIN `users` u ON p.user_id = u.id
            LEFT JOIN `lokasi_presensi` l ON p.lokasi_id = l.id
            WHERE p.tanggal = ? AND p.jam_masuk IS NOT NULL
            ORDER BY p.jam_masuk DESC
            LIMIT " . (int)$limit . "
        ");
        $stmt->execute([$tanggal]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Jika hari ini belum ada aktivitas (misal pagi hari sebelum jam operasional), ambil presensi terakhir
        if (empty($rows)) {
            $stmtFallback = $this->db->prepare("
                SELECT p.id, p.user_id, p.tanggal, p.jam_masuk, p.jam_pulang, p.total_terlambat, p.foto_masuk,
                       u.nama AS nama_pegawai, u.nik, u.jabatan, u.foto_profil,
                       l.nama_lokasi
                FROM `presensi` p
                JOIN `users` u ON p.user_id = u.id
                LEFT JOIN `lokasi_presensi` l ON p.lokasi_id = l.id
                WHERE p.jam_masuk IS NOT NULL
                ORDER BY p.tanggal DESC, p.jam_masuk DESC
                LIMIT " . (int)$limit . "
            ");
            $stmtFallback->execute();
            $rows = $stmtFallback->fetchAll(PDO::FETCH_ASSOC);
        }

        return $rows;
    }

    /**
     * Update URL foto presensi masuk (misal setelah berhasil diunggah ke Google Drive)
     */
    public function updateFotoMasukByUserTanggal(int $userId, string $tanggal, string $fotoUrl): bool {
        $stmt = $this->db->prepare("UPDATE `presensi` SET `foto_masuk` = ? WHERE `user_id` = ? AND `tanggal` = ?");
        return $stmt->execute([$fotoUrl, $userId, $tanggal]);
    }

    /**
     * Update URL foto presensi keluar (misal setelah berhasil diunggah ke Google Drive)
     */
    public function updateFotoKeluarById(int $presensiId, string $fotoUrl): bool {
        $stmt = $this->db->prepare("UPDATE `presensi` SET `foto_keluar` = ? WHERE `id` = ?");
        return $stmt->execute([$fotoUrl, $presensiId]);
    }
}


