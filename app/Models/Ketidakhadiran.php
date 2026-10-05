<?php
/**
 * Ketidakhadiran (Izin / Sakit / Cuti) Model
 * Absensi Surya Gemilang Mojopahit (SGM)
 */

require_once __DIR__ . '/../Config/Database.php';

class Ketidakhadiran {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function create(array $data): bool {
        $stmt = $this->db->prepare("
            INSERT INTO `ketidakhadiran` 
            (`user_id`, `kategori`, `tanggal_mulai`, `tanggal_selesai`, `keterangan`, `dokumen_bukti`, `status_approval`)
            VALUES (?, ?, ?, ?, ?, ?, 'pending')
        ");
        return $stmt->execute([
            $data['user_id'],
            $data['kategori'],
            $data['tanggal_mulai'],
            $data['tanggal_selesai'],
            $data['keterangan'],
            $data['dokumen_bukti'] ?? null
        ]);
    }

    /**
     * Cek apakah user sudah memiliki pengajuan pada rentang tanggal yang sama (idempotensi/anti-duplikasi)
     */
    public function checkDuplicate(int $userId, string $tanggalMulai, string $tanggalSelesai): ?array {
        $stmt = $this->db->prepare("
            SELECT `id`, `status_approval` FROM `ketidakhadiran` 
            WHERE `user_id` = ? 
              AND `tanggal_mulai` = ? 
              AND `tanggal_selesai` = ? 
              AND `status_approval` IN ('pending', 'disetujui')
            LIMIT 1
        ");
        $stmt->execute([$userId, $tanggalMulai, $tanggalSelesai]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getByUserId(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT * FROM `ketidakhadiran` 
            WHERE `user_id` = ? 
            ORDER BY `created_at` DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getAll(?string $status = null): array {
        $sql = "
            SELECT k.*, u.nama AS nama_pegawai, u.nik, u.jabatan 
            FROM `ketidakhadiran` k
            JOIN `users` u ON k.user_id = u.id
        ";
        $params = [];

        if ($status !== null) {
            $sql .= " WHERE k.status_approval = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY k.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function updateStatus(int $id, string $status, ?string $catatan = null): bool {
        $stmt = $this->db->prepare("
            UPDATE `ketidakhadiran` SET 
                `status_approval` = ?, 
                `catatan_admin` = ? 
            WHERE `id` = ?
        ");
        return $stmt->execute([$status, $catatan, $id]);
    }

    public function countSakitIzinCutiHariIni(string $tanggal): int {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM `ketidakhadiran`
            WHERE `status_approval` = 'disetujui'
            AND ? BETWEEN `tanggal_mulai` AND `tanggal_selesai`
        ");
        $stmt->execute([$tanggal]);
        return (int) $stmt->fetchColumn();
    }
}
