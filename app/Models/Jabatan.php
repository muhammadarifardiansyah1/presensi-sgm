<?php
/**
 * Jabatan Model
 * Absensi Surya Gading Mandiri (SGM)
 */

require_once __DIR__ . '/../Config/Database.php';

class Jabatan {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function getAll(): array {
        $stmt = $this->db->query("SELECT * FROM `jabatan` ORDER BY `nama_jabatan` ASC");
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM `jabatan` WHERE `id` = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByName(string $name): ?array {
        $stmt = $this->db->prepare("SELECT * FROM `jabatan` WHERE `nama_jabatan` = ? LIMIT 1");
        $stmt->execute([$name]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(string $namaJabatan): bool {
        $stmt = $this->db->prepare("INSERT INTO `jabatan` (`nama_jabatan`) VALUES (?)");
        return $stmt->execute([$namaJabatan]);
    }

    public function update(int $id, string $namaJabatan): bool {
        $stmt = $this->db->prepare("UPDATE `jabatan` SET `nama_jabatan` = ? WHERE `id` = ?");
        return $stmt->execute([$namaJabatan, $id]);
    }

    public function isUsed(int $id): array {
        $jabatan = $this->findById($id);
        if (!$jabatan) {
            return ['is_used' => false, 'count' => 0, 'nama_jabatan' => ''];
        }

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM `users` WHERE `jabatan` = ?");
        $stmt->execute([$jabatan['nama_jabatan']]);
        $count = (int)$stmt->fetchColumn();

        return [
            'is_used'      => ($count > 0),
            'count'        => $count,
            'nama_jabatan' => $jabatan['nama_jabatan']
        ];
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM `jabatan` WHERE `id` = ?");
        return $stmt->execute([$id]);
    }
}
