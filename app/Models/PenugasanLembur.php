<?php
/**
 * Penugasan Lembur Model
 * Absensi Surya Gemilang Mojopahit (SGM)
 */

require_once __DIR__ . '/../Config/Database.php';

class PenugasanLembur {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Generate Nomor Surat Perintah Lembur (SPL-YYYYMMDD-001)
     */
    public function generateNomorSPL(string $tanggal): string {
        $prefix = 'SPL-' . date('Ymd', strtotime($tanggal)) . '-';
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM `penugasan_lembur` 
            WHERE `nomor_sp_lembur` LIKE ?
        ");
        $stmt->execute([$prefix . '%']);
        $count = (int)$stmt->fetchColumn() + 1;
        return $prefix . str_pad((string)$count, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Mengambil seluruh data penugasan lembur dengan filter bulan, tahun, dan lokasi
     */
    public function getAllPenugasan(?int $bulan = null, ?int $tahun = null, ?int $lokasiId = null, ?string $status = null): array {
        $sql = "
            SELECT 
                pl.id, pl.nomor_sp_lembur, pl.lokasi_id, pl.tanggal, pl.jam_mulai, pl.jam_selesai,
                pl.maksimal_jam, pl.keterangan, pl.status, pl.created_by, pl.created_at, pl.updated_at,
                l.nama_lokasi,
                l.jam_pulang AS jam_pulang_standar,
                u_creator.nama AS nama_creator,
                COUNT(plp.id) AS total_pegawai,
                GROUP_CONCAT(u_pegawai.nama ORDER BY u_pegawai.nama ASC SEPARATOR ', ') AS daftar_pegawai_text
            FROM `penugasan_lembur` pl
            LEFT JOIN `lokasi_presensi` l ON pl.lokasi_id = l.id
            LEFT JOIN `users` u_creator ON pl.created_by = u_creator.id
            LEFT JOIN `penugasan_lembur_pegawai` plp ON pl.id = plp.penugasan_lembur_id
            LEFT JOIN `users` u_pegawai ON plp.user_id = u_pegawai.id
            WHERE 1=1
        ";
        $params = [];

        if ($bulan && $tahun) {
            $sql .= " AND MONTH(pl.tanggal) = ? AND YEAR(pl.tanggal) = ?";
            $params[] = $bulan;
            $params[] = $tahun;
        } elseif ($tahun) {
            $sql .= " AND YEAR(pl.tanggal) = ?";
            $params[] = $tahun;
        }

        if ($lokasiId) {
            $sql .= " AND pl.lokasi_id = ?";
            $params[] = $lokasiId;
        }

        if ($status) {
            $sql .= " AND pl.status = ?";
            $params[] = $status;
        }

        $sql .= " GROUP BY pl.id, pl.nomor_sp_lembur, pl.lokasi_id, pl.tanggal, pl.jam_mulai, pl.jam_selesai, pl.maksimal_jam, pl.keterangan, pl.status, pl.created_by, pl.created_at, pl.updated_at, l.nama_lokasi, l.jam_pulang, u_creator.nama ORDER BY pl.tanggal DESC, pl.jam_mulai DESC, pl.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Mengambil 1 data penugasan lembur berdasarkan ID
     */
    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT 
                pl.*,
                l.nama_lokasi,
                l.alamat_lokasi,
                l.jam_masuk AS jam_masuk_lokasi,
                l.jam_pulang AS jam_pulang_lokasi,
                u.nama AS nama_creator
            FROM `penugasan_lembur` pl
            LEFT JOIN `lokasi_presensi` l ON pl.lokasi_id = l.id
            LEFT JOIN `users` u ON pl.created_by = u.id
            WHERE pl.id = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Mengambil daftar pegawai yang ditugaskan pada suatu penugasan lembur
     */
    public function getPegawaiByPenugasan(int $penugasanId): array {
        $stmt = $this->db->prepare("
            SELECT 
                plp.*,
                u.nik,
                u.nama AS nama_pegawai,
                u.jabatan,
                u.foto_profil,
                p.jam_masuk,
                p.jam_pulang,
                p.total_jam_kerja,
                p.jam_lembur AS jam_lembur_presensi
            FROM `penugasan_lembur_pegawai` plp
            JOIN `users` u ON plp.user_id = u.id
            JOIN `penugasan_lembur` pl ON plp.penugasan_lembur_id = pl.id
            LEFT JOIN `presensi` p ON (p.user_id = u.id AND p.tanggal = pl.tanggal)
            WHERE plp.penugasan_lembur_id = ?
            ORDER BY u.nama ASC
        ");
        $stmt->execute([$penugasanId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Mengambil ID seluruh pegawai yang terdaftar di penugasan lembur
     */
    public function getPegawaiIdsByPenugasan(int $penugasanId): array {
        $stmt = $this->db->prepare("
            SELECT user_id FROM `penugasan_lembur_pegawai` WHERE penugasan_lembur_id = ?
        ");
        $stmt->execute([$penugasanId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Mengambil daftar pegawai aktif berdasarkan lokasi (serta menyertakan pegawai yang ditugaskan)
     */
    public function getPegawaiByLokasi(?int $lokasiId = null, array $includeUserIds = []): array {
        $sql = "
            SELECT u.id, u.nik, u.nama, u.jabatan, u.foto_profil, l.nama_lokasi
            FROM `users` u
            LEFT JOIN `lokasi_presensi` l ON u.lokasi_id = l.id
            WHERE u.role = 'pegawai' AND u.status = 'aktif'
        ";
        $params = [];

        if ($lokasiId && $lokasiId > 0) {
            if (!empty($includeUserIds)) {
                $inClause = implode(',', array_map('intval', $includeUserIds));
                $sql .= " AND (u.lokasi_id = ? OR u.id IN ({$inClause}))";
                $params[] = $lokasiId;
            } else {
                $sql .= " AND u.lokasi_id = ?";
                $params[] = $lokasiId;
            }
        }

        $sql .= " ORDER BY u.nama ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Jika lokasi yang dipilih tidak memiliki satupun pegawai terdaftar,
        // otomatis fallback memuat seluruh pegawai aktif agar tabel checklist tidak kosong melompong
        if (empty($res) && $lokasiId && $lokasiId > 0 && empty($includeUserIds)) {
            return $this->getPegawaiByLokasi(null);
        }

        return $res;
    }

    /**
     * Membuat penugasan lembur baru
     */
    public function createPenugasan(array $data, array $selectedUserIds, ?int $adminId = null): int {
        $tanggal = trim($data['tanggal']);
        $lokasiId = (int)$data['lokasi_id'];
        $jamMulai = trim($data['jam_mulai'] ?? '17:00:00');
        $jamSelesai = trim($data['jam_selesai'] ?? '20:00:00');
        $keterangan = trim($data['keterangan'] ?? '');
        $status = in_array($data['status'] ?? '', ['aktif', 'selesai', 'dibatalkan']) ? $data['status'] : 'aktif';

        if (strlen($jamMulai) === 5) $jamMulai .= ':00';
        if (strlen($jamSelesai) === 5) $jamSelesai .= ':00';

        // Hitung durasi maksimal jam lembur
        $tMulai = strtotime($tanggal . ' ' . $jamMulai);
        $tSelesai = strtotime($tanggal . ' ' . $jamSelesai);
        $maksimalJam = 0.00;
        if ($tSelesai > $tMulai) {
            $maksimalJam = round(($tSelesai - $tMulai) / 3600, 2);
        }

        $nomorSPL = !empty($data['nomor_sp_lembur']) ? trim($data['nomor_sp_lembur']) : $this->generateNomorSPL($tanggal);

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO `penugasan_lembur` (
                    `nomor_sp_lembur`, `lokasi_id`, `tanggal`, `jam_mulai`, `jam_selesai`, `maksimal_jam`, `keterangan`, `status`, `created_by`
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $nomorSPL, $lokasiId, $tanggal, $jamMulai, $jamSelesai, $maksimalJam, $keterangan, $status, $adminId
            ]);
            $penugasanId = (int)$this->db->lastInsertId();

            // Masukkan pivot pegawai
            if (!empty($selectedUserIds)) {
                $stmtPivot = $this->db->prepare("
                    INSERT IGNORE INTO `penugasan_lembur_pegawai` (`penugasan_lembur_id`, `user_id`, `status`)
                    VALUES (?, ?, 'ditugaskan')
                ");
                foreach ($selectedUserIds as $uid) {
                    $uIdInt = (int)$uid;
                    if ($uIdInt > 0) {
                        $stmtPivot->execute([$penugasanId, $uIdInt]);
                    }
                }
            }

            $this->db->commit();
            return $penugasanId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Memperbarui penugasan lembur yang sudah ada
     */
    public function updatePenugasan(int $id, array $data, array $selectedUserIds): bool {
        $tanggal = trim($data['tanggal']);
        $lokasiId = (int)$data['lokasi_id'];
        $jamMulai = trim($data['jam_mulai'] ?? '17:00:00');
        $jamSelesai = trim($data['jam_selesai'] ?? '20:00:00');
        $keterangan = trim($data['keterangan'] ?? '');
        $status = in_array($data['status'] ?? '', ['aktif', 'selesai', 'dibatalkan']) ? $data['status'] : 'aktif';

        if (strlen($jamMulai) === 5) $jamMulai .= ':00';
        if (strlen($jamSelesai) === 5) $jamSelesai .= ':00';

        $tMulai = strtotime($tanggal . ' ' . $jamMulai);
        $tSelesai = strtotime($tanggal . ' ' . $jamSelesai);
        $maksimalJam = 0.00;
        if ($tSelesai > $tMulai) {
            $maksimalJam = round(($tSelesai - $tMulai) / 3600, 2);
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                UPDATE `penugasan_lembur` SET
                    `lokasi_id` = ?,
                    `tanggal` = ?,
                    `jam_mulai` = ?,
                    `jam_selesai` = ?,
                    `maksimal_jam` = ?,
                    `keterangan` = ?,
                    `status` = ?
                WHERE `id` = ?
            ");
            $stmt->execute([
                $lokasiId, $tanggal, $jamMulai, $jamSelesai, $maksimalJam, $keterangan, $status, $id
            ]);

            // Sinkronisasi pivot pegawai
            // 1. Hapus pegawai yang tidak ada di daftar pilihan baru
            if (!empty($selectedUserIds)) {
                $inClause = implode(',', array_map('intval', $selectedUserIds));
                $this->db->exec("DELETE FROM `penugasan_lembur_pegawai` WHERE `penugasan_lembur_id` = {$id} AND `user_id` NOT IN ({$inClause})");
            } else {
                $this->db->exec("DELETE FROM `penugasan_lembur_pegawai` WHERE `penugasan_lembur_id` = {$id}");
            }

            // 2. Tambahkan pegawai yang baru dipilih
            if (!empty($selectedUserIds)) {
                $stmtPivot = $this->db->prepare("
                    INSERT IGNORE INTO `penugasan_lembur_pegawai` (`penugasan_lembur_id`, `user_id`, `status`)
                    VALUES (?, ?, 'ditugaskan')
                ");
                foreach ($selectedUserIds as $uid) {
                    $uIdInt = (int)$uid;
                    if ($uIdInt > 0) {
                        $stmtPivot->execute([$id, $uIdInt]);
                    }
                }
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Menghapus penugasan lembur (cascade ke penugasan_lembur_pegawai)
     */
    public function deletePenugasan(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM `penugasan_lembur` WHERE `id` = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Pengecekan Inti Absensi Pulang: Cek apakah user memiliki penugasan lembur aktif pada tanggal tertentu
     */
    public function getPenugasanAktifUser(int $userId, string $tanggal): ?array {
        $stmt = $this->db->prepare("
            SELECT 
                pl.*,
                plp.id AS plp_id,
                plp.status AS plp_status,
                plp.jam_lembur_aktual
            FROM `penugasan_lembur` pl
            JOIN `penugasan_lembur_pegawai` plp ON pl.id = plp.penugasan_lembur_id
            WHERE plp.user_id = ?
              AND pl.tanggal = ?
              AND pl.status = 'aktif'
            ORDER BY pl.jam_mulai ASC
            LIMIT 1
        ");
        $stmt->execute([$userId, $tanggal]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    /**
     * Update jam lembur aktual pada pivot penugasan_lembur_pegawai saat pegawai selesai absen pulang
     */
    public function updateJamLemburAktual(int $plpId, float $jamLembur): bool {
        $stmt = $this->db->prepare("
            UPDATE `penugasan_lembur_pegawai` SET
                `jam_lembur_aktual` = ?,
                `status` = 'selesai'
            WHERE `id` = ?
        ");
        return $stmt->execute([$jamLembur, $plpId]);
    }
}
