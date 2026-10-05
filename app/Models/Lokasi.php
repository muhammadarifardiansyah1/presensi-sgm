<?php
/**
 * Lokasi Presensi Model
 * Absensi Surya Gading Mandiri (SGM)
 */

require_once __DIR__ . '/../Config/Database.php';

class Lokasi {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function getAll(): array {
        $stmt = $this->db->query("SELECT * FROM `lokasi_presensi` ORDER BY `tipe_lokasi` ASC, `nama_lokasi` ASC");
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM `lokasi_presensi` WHERE `id` = ? LIMIT 1");
        $stmt->execute([$id]);
        $lokasi = $stmt->fetch();
        return $lokasi ?: null;
    }

    public function getDefault(): ?array {
        $stmt = $this->db->query("SELECT * FROM `lokasi_presensi` ORDER BY `tipe_lokasi` ASC, `id` ASC LIMIT 1");
        $lokasi = $stmt->fetch();
        return $lokasi ?: null;
    }

    public function create(array $data): bool {
        $stmt = $this->db->prepare("
            INSERT INTO `lokasi_presensi` 
            (`nama_lokasi`, `alamat_lokasi`, `tipe_lokasi`, `latitude`, `longitude`, `radius`, `zona_waktu`, `jam_masuk`, `jam_pulang`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['nama_lokasi'],
            $data['alamat_lokasi'],
            $data['tipe_lokasi'] ?? 'Cabang',
            $data['latitude'],
            $data['longitude'],
            $data['radius'] ?? 500,
            $data['zona_waktu'] ?? 'WIB',
            $data['jam_masuk'] ?? '08:00:00',
            $data['jam_pulang'] ?? '17:00:00'
        ]);
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->db->prepare("
            UPDATE `lokasi_presensi` SET
                `nama_lokasi` = ?,
                `alamat_lokasi` = ?,
                `tipe_lokasi` = ?,
                `latitude` = ?,
                `longitude` = ?,
                `radius` = ?,
                `zona_waktu` = ?,
                `jam_masuk` = ?,
                `jam_pulang` = ?
            WHERE `id` = ?
        ");
        return $stmt->execute([
            $data['nama_lokasi'],
            $data['alamat_lokasi'],
            $data['tipe_lokasi'],
            $data['latitude'],
            $data['longitude'],
            $data['radius'],
            $data['zona_waktu'],
            $data['jam_masuk'],
            $data['jam_pulang'],
            $id
        ]);
    }

    public function isUsed(int $id): array {
        $stmtUser = $this->db->prepare("SELECT COUNT(*) FROM `users` WHERE `lokasi_id` = ?");
        $stmtUser->execute([$id]);
        $userCount = (int)$stmtUser->fetchColumn();

        $stmtPresensi = $this->db->prepare("SELECT COUNT(*) FROM `presensi` WHERE `lokasi_id` = ?");
        $stmtPresensi->execute([$id]);
        $presensiCount = (int)$stmtPresensi->fetchColumn();

        return [
            'is_used'        => ($userCount > 0 || $presensiCount > 0),
            'user_count'     => $userCount,
            'presensi_count' => $presensiCount
        ];
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM `lokasi_presensi` WHERE `id` = ?");
        return $stmt->execute([$id]);
    }
}
