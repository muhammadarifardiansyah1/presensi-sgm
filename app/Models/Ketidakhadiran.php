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
     * Cek apakah user sudah memiliki pengajuan pada rentang tanggal yang bentrok / bertabrakan (idempotensi & anti-overlap)
     */
    public function checkDuplicate(int $userId, string $tanggalMulai, string $tanggalSelesai, ?int $excludeId = null): ?array {
        $sql = "
            SELECT `id`, `kategori`, `tanggal_mulai`, `tanggal_selesai`, `status_approval` 
            FROM `ketidakhadiran` 
            WHERE `user_id` = ? 
              AND `status_approval` IN ('pending', 'disetujui')
              AND `tanggal_mulai` <= ? 
              AND `tanggal_selesai` >= ?
        ";
        $params = [$userId, $tanggalSelesai, $tanggalMulai];
        if ($excludeId !== null) {
            $sql .= " AND `id` != ?";
            $params[] = $excludeId;
        }
        $sql .= " LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
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

    public function getAll(?string $status = null, ?string $kategori = null, ?string $tanggalMulai = null, ?string $tanggalSelesai = null): array {
        $sql = "
            SELECT k.*, u.nama AS nama_pegawai, u.nik, u.jabatan,
                   (
                       SELECT CONCAT(UPPER(o.kategori), ' (', DATE_FORMAT(o.tanggal_mulai, '%d %b'), ' - ', DATE_FORMAT(o.tanggal_selesai, '%d %b %Y'), ')')
                       FROM `ketidakhadiran` o
                       WHERE o.user_id = k.user_id
                         AND o.id != k.id
                         AND o.status_approval = 'disetujui'
                         AND o.tanggal_mulai <= k.tanggal_selesai
                         AND o.tanggal_selesai >= k.tanggal_mulai
                       LIMIT 1
                   ) AS bentrok_dengan
            FROM `ketidakhadiran` k
            JOIN `users` u ON k.user_id = u.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($status)) {
            $sql .= " AND k.status_approval = ?";
            $params[] = $status;
        }

        if (!empty($kategori)) {
            $sql .= " AND k.kategori = ?";
            $params[] = $kategori;
        }

        if (!empty($tanggalMulai)) {
            $sql .= " AND k.tanggal_selesai >= ?";
            $params[] = $tanggalMulai;
        }

        if (!empty($tanggalSelesai)) {
            $sql .= " AND k.tanggal_mulai <= ?";
            $params[] = $tanggalSelesai;
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

    public function update(int $id, array $data): bool {
        $stmt = $this->db->prepare("
            UPDATE `ketidakhadiran` SET 
                `kategori` = ?, 
                `tanggal_mulai` = ?, 
                `tanggal_selesai` = ?, 
                `keterangan` = ?, 
                `status_approval` = ?, 
                `catatan_admin` = ? 
            WHERE `id` = ?
        ");
        return $stmt->execute([
            $data['kategori'],
            $data['tanggal_mulai'],
            $data['tanggal_selesai'],
            $data['keterangan'],
            $data['status_approval'],
            $data['catatan_admin'] ?? null,
            $id
        ]);
    }

    public function delete(int $id): bool {
        $item = $this->findById($id);
        if (!$item) {
            return false;
        }

        $stmt = $this->db->prepare("DELETE FROM `ketidakhadiran` WHERE `id` = ?");
        $deleted = $stmt->execute([$id]);

        if ($deleted && !empty($item['dokumen_bukti'])) {
            $filename = basename($item['dokumen_bukti']);
            $filePath = __DIR__ . '/../../assets/images/uploads/dokumen/' . $filename;
            if (file_exists($filePath) && is_file($filePath)) {
                @unlink($filePath);
            }
        }

        return $deleted;
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

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT k.*, u.nama AS nama_pegawai, u.nik, u.jabatan 
            FROM `ketidakhadiran` k
            JOIN `users` u ON k.user_id = u.id
            WHERE k.id = ? 
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Menghitung statistik pengajuan izin, sakit, dan cuti pegawai pada bulan berjalan
     */
    public function countStatistikPegawai(int $userId, int $bulan, int $tahun): array {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(CASE WHEN `status_approval` = 'disetujui' THEN 1 END) AS `total_disetujui`,
                COUNT(CASE WHEN `status_approval` = 'pending' THEN 1 END) AS `total_pending`,
                COUNT(*) AS `total_pengajuan`
            FROM `ketidakhadiran`
            WHERE `user_id` = ? 
              AND (MONTH(`tanggal_mulai`) = ? OR MONTH(`tanggal_selesai`) = ?) 
              AND YEAR(`tanggal_mulai`) = ?
        ");
        $stmt->execute([$userId, $bulan, $bulan, $tahun]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return [
            'total_disetujui' => (int)($row['total_disetujui'] ?? 0),
            'total_pending'   => (int)($row['total_pending'] ?? 0),
            'total_pengajuan' => (int)($row['total_pengajuan'] ?? 0)
        ];
    }

    /**
     * Menghitung total pengajuan izin/sakit/cuti yang berstatus 'pending' (menunggu verifikasi admin)
     */
    public function countPending(): int {
        $stmt = $this->db->query("SELECT COUNT(*) FROM `ketidakhadiran` WHERE `status_approval` = 'pending'");
        return (int) $stmt->fetchColumn();
    }
}

