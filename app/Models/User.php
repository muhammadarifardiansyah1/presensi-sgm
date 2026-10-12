<?php
/**
 * User Model
 * Absensi Surya Gemilang Mojopahit (SGM)
 */

require_once __DIR__ . '/../Config/Database.php';

class User {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function findByUsername(string $username): ?array {
        $stmt = $this->db->prepare("SELECT * FROM `users` WHERE `username` = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT u.*, l.nama_lokasi, l.jam_masuk AS jam_masuk_lokasi, l.jam_pulang AS jam_pulang_lokasi, l.radius AS radius_lokasi 
            FROM `users` u 
            LEFT JOIN `lokasi_presensi` l ON u.lokasi_id = l.id 
            WHERE u.`id` = ? 
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function getAllPegawai(): array {
        $stmt = $this->db->query("
            SELECT u.*, l.nama_lokasi,
                   (SELECT COUNT(*) FROM `presensi` p WHERE p.`user_id` = u.`id`) AS `total_presensi`,
                   (SELECT COUNT(*) FROM `ketidakhadiran` k WHERE k.`user_id` = u.`id`) AS `total_izin`
            FROM `users` u 
            LEFT JOIN `lokasi_presensi` l ON u.lokasi_id = l.id 
            ORDER BY u.`role` ASC, u.`nama` ASC
        ");
        return $stmt->fetchAll();
    }

    public function countPegawaiAktif(): int {
        $stmt = $this->db->query("SELECT COUNT(*) FROM `users` WHERE `status` = 'aktif' AND `role` = 'pegawai'");
        return (int) $stmt->fetchColumn();
    }

    public function generateNextNik(string $role = 'pegawai'): string {
        $prefix = ($role === 'admin') ? 'ADM-' : 'PEG-';
        $stmt = $this->db->prepare("SELECT `nik` FROM `users` WHERE `nik` LIKE ?");
        $stmt->execute([$prefix . '%']);
        $niks = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $maxNum = 0;
        foreach ($niks as $n) {
            if (preg_match('/^' . preg_quote($prefix, '/') . '(\d+)$/', $n, $m)) {
                $num = (int)$m[1];
                if ($num > $maxNum) {
                    $maxNum = $num;
                }
            }
        }
        return $prefix . str_pad((string)($maxNum + 1), 4, '0', STR_PAD_LEFT);
    }

    public function create(array $data): bool {
        $nik = !empty($data['nik']) ? $data['nik'] : $this->generateNextNik($data['role'] ?? 'pegawai');
        $stmt = $this->db->prepare("
            INSERT INTO `users` (
                `nik`, `nama`, `jenis_kelamin`, `alamat`, `no_handphone`, 
                `username`, `password`, `role`, `jabatan`, `lokasi_id`, `foto_profil`, `status`
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $nik,
            $data['nama'],
            $data['jenis_kelamin'] ?? 'Laki-laki',
            $data['alamat'] ?? null,
            $data['no_handphone'] ?? null,
            $data['username'],
            password_hash($data['password'], PASSWORD_BCRYPT),
            $data['role'] ?? 'pegawai',
            $data['jabatan'] ?? 'Pegawai',
            !empty($data['lokasi_id']) ? (int)$data['lokasi_id'] : null,
            $data['foto_profil'] ?? null,
            $data['status'] ?? 'aktif'
        ]);
    }

    public function update(int $id, array $data): bool {
        $fields = [
            '`nama` = ?',
            '`jenis_kelamin` = ?',
            '`alamat` = ?',
            '`no_handphone` = ?',
            '`username` = ?',
            '`role` = ?',
            '`jabatan` = ?',
            '`lokasi_id` = ?',
            '`status` = ?'
        ];
        $params = [
            $data['nama'],
            $data['jenis_kelamin'] ?? 'Laki-laki',
            $data['alamat'] ?? null,
            $data['no_handphone'] ?? null,
            $data['username'],
            $data['role'] ?? 'pegawai',
            $data['jabatan'] ?? 'Pegawai',
            !empty($data['lokasi_id']) ? (int)$data['lokasi_id'] : null,
            $data['status'] ?? 'aktif'
        ];

        if (!empty($data['nik'])) {
            $fields[] = '`nik` = ?';
            $params[] = $data['nik'];
        }

        if (!empty($data['password'])) {
            $fields[] = '`password` = ?';
            $params[] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        if (isset($data['foto_profil']) && $data['foto_profil'] !== null) {
            $fields[] = '`foto_profil` = ?';
            $params[] = $data['foto_profil'];
        }

        $params[] = $id;
        $sql = "UPDATE `users` SET " . implode(', ', $fields) . " WHERE `id` = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function hasRiwayat(int $userId): bool {
        $stmt = $this->db->prepare("SELECT 1 FROM `presensi` WHERE `user_id` = ? LIMIT 1");
        $stmt->execute([$userId]);
        if ($stmt->fetch()) {
            return true;
        }

        $stmt2 = $this->db->prepare("SELECT 1 FROM `ketidakhadiran` WHERE `user_id` = ? LIMIT 1");
        $stmt2->execute([$userId]);
        if ($stmt2->fetch()) {
            return true;
        }

        $stmt3 = $this->db->prepare("SELECT 1 FROM `penugasan_lembur_pegawai` WHERE `user_id` = ? LIMIT 1");
        $stmt3->execute([$userId]);
        if ($stmt3->fetch()) {
            return true;
        }

        $stmt4 = $this->db->prepare("SELECT 1 FROM `payroll_penyesuaian` WHERE `user_id` = ? LIMIT 1");
        $stmt4->execute([$userId]);
        return (bool)$stmt4->fetch();
    }

    public function updateStatus(int $id, string $status): bool {
        $status = ($status === 'aktif') ? 'aktif' : 'nonaktif';
        $stmt = $this->db->prepare("UPDATE `users` SET `status` = ? WHERE `id` = ?");
        return $stmt->execute([$status, $id]);
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM `users` WHERE `id` = ?");
        return $stmt->execute([$id]);
    }

    public function updateProfile(int $id, array $data): bool {
        $fields = [
            '`nama` = ?',
            '`jenis_kelamin` = ?',
            '`alamat` = ?',
            '`no_handphone` = ?'
        ];
        $params = [
            $data['nama'],
            $data['jenis_kelamin'] ?? 'Laki-laki',
            $data['alamat'] ?? null,
            $data['no_handphone'] ?? null
        ];

        if (isset($data['foto_profil']) && $data['foto_profil'] !== null) {
            $fields[] = '`foto_profil` = ?';
            $params[] = $data['foto_profil'];
        }

        $params[] = $id;
        $sql = "UPDATE `users` SET " . implode(', ', $fields) . " WHERE `id` = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function updatePassword(int $id, string $hashedPassword): bool {
        $stmt = $this->db->prepare("UPDATE `users` SET `password` = ? WHERE `id` = ?");
        return $stmt->execute([$hashedPassword, $id]);
    }

    public function updateSession(int $userId, string $sessionId): bool {
        $stmt = $this->db->prepare("UPDATE `users` SET `session_id` = ?, `last_login_at` = NOW(), `last_active_at` = NOW() WHERE `id` = ?");
        return $stmt->execute([$sessionId, $userId]);
    }

    public function clearSession(int $userId): bool {
        $stmt = $this->db->prepare("UPDATE `users` SET `session_id` = NULL WHERE `id` = ?");
        return $stmt->execute([$userId]);
    }
}

