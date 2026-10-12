<?php
/**
 * Jabatan Model
 * Absensi Surya Gemilang Mojopahit (SGM)
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

    public function getAllWithUsage(): array {
        $sql = "
            SELECT 
                j.*,
                COALESCE(u.user_count, 0) AS user_count
            FROM `jabatan` j
            LEFT JOIN (
                SELECT jabatan, COUNT(*) AS user_count 
                FROM users 
                WHERE jabatan IS NOT NULL AND jabatan != ''
                GROUP BY jabatan
            ) u ON LOWER(j.nama_jabatan) = LOWER(u.jabatan)
            ORDER BY j.nama_jabatan ASC
        ";
        return $this->db->query($sql)->fetchAll();
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
        $namaJabatan = trim($namaJabatan);
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("INSERT INTO `jabatan` (`nama_jabatan`) VALUES (?)");
            $res = $stmt->execute([$namaJabatan]);
            if (!$res) {
                $this->db->rollBack();
                return false;
            }

            // Auto-inisialisasi baris default di pengaturan_gaji jika belum ada
            $stmtCek = $this->db->prepare("SELECT COUNT(*) FROM `pengaturan_gaji` WHERE LOWER(TRIM(`jabatan`)) = LOWER(?)");
            $stmtCek->execute([$namaJabatan]);
            if ((int)$stmtCek->fetchColumn() === 0) {
                $stmtGaji = $this->db->prepare("
                    INSERT INTO `pengaturan_gaji` 
                    (`jabatan`, `gaji_pokok`, `uang_makan_harian`, `tarif_lembur_per_jam`, `potongan_alpa_per_hari`, `tarif_pengganti_libur_per_hari`)
                    VALUES (?, 3500000.00, 25000.00, 25000.00, 100000.00, 52000.00)
                ");
                $stmtGaji->execute([$namaJabatan]);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Gagal membuat jabatan: " . $e->getMessage());
            return false;
        }
    }

    public function update(int $id, string $namaJabatan): bool {
        $namaJabatan = trim($namaJabatan);
        $old = $this->findById($id);
        if (!$old) {
            return false;
        }
        $oldName = trim($old['nama_jabatan']);

        try {
            $this->db->beginTransaction();

            // 1. Update tabel master jabatan
            $stmt = $this->db->prepare("UPDATE `jabatan` SET `nama_jabatan` = ? WHERE `id` = ?");
            $res = $stmt->execute([$namaJabatan, $id]);
            if (!$res) {
                $this->db->rollBack();
                return false;
            }

            // 2. Cascade update ke tabel users jika nama berubah
            if (strcasecmp($oldName, $namaJabatan) !== 0) {
                $stmtUser = $this->db->prepare("
                    UPDATE `users` 
                    SET `jabatan` = ? 
                    WHERE LOWER(TRIM(`jabatan`)) = LOWER(?)
                ");
                $stmtUser->execute([$namaJabatan, $oldName]);

                // 3. Cascade update ke tabel pengaturan_gaji
                $stmtCekGaji = $this->db->prepare("
                    SELECT id FROM `pengaturan_gaji` 
                    WHERE LOWER(TRIM(`jabatan`)) = LOWER(?) 
                    LIMIT 1
                ");
                $stmtCekGaji->execute([$oldName]);
                $gajiOldId = $stmtCekGaji->fetchColumn();

                if ($gajiOldId) {
                    $stmtGaji = $this->db->prepare("
                        UPDATE `pengaturan_gaji` 
                        SET `jabatan` = ? 
                        WHERE `id` = ?
                    ");
                    $stmtGaji->execute([$namaJabatan, $gajiOldId]);
                } else {
                    // Jika belum ada di pengaturan_gaji, insert baru
                    $stmtNewGaji = $this->db->prepare("
                        INSERT IGNORE INTO `pengaturan_gaji` 
                        (`jabatan`, `gaji_pokok`, `uang_makan_harian`, `tarif_lembur_per_jam`, `potongan_alpa_per_hari`, `tarif_pengganti_libur_per_hari`)
                        VALUES (?, 3500000.00, 25000.00, 25000.00, 100000.00, 52000.00)
                    ");
                    $stmtNewGaji->execute([$namaJabatan]);
                }
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Gagal memperbarui jabatan (cascade): " . $e->getMessage());
            return false;
        }
    }

    public function isUsed(int $id): array {
        $jabatan = $this->findById($id);
        if (!$jabatan) {
            return ['is_used' => false, 'count' => 0, 'nama_jabatan' => ''];
        }

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM `users` WHERE LOWER(TRIM(`jabatan`)) = LOWER(?)");
        $stmt->execute([trim($jabatan['nama_jabatan'])]);
        $count = (int)$stmt->fetchColumn();

        return [
            'is_used'      => ($count > 0),
            'count'        => $count,
            'nama_jabatan' => $jabatan['nama_jabatan']
        ];
    }

    public function delete(int $id): bool {
        $old = $this->findById($id);
        if (!$old) {
            return false;
        }
        $oldName = trim($old['nama_jabatan']);

        try {
            $this->db->beginTransaction();

            // 1. Hapus dari tabel jabatan
            $stmt = $this->db->prepare("DELETE FROM `jabatan` WHERE `id` = ?");
            $res = $stmt->execute([$id]);
            if (!$res) {
                $this->db->rollBack();
                return false;
            }

            // 2. Cascade hapus dari pengaturan_gaji agar tidak jadi orphan row
            $stmtGaji = $this->db->prepare("
                DELETE FROM `pengaturan_gaji` 
                WHERE LOWER(TRIM(`jabatan`)) = LOWER(?)
            ");
            $stmtGaji->execute([$oldName]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Gagal menghapus jabatan (cascade): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Kanonisasi string jabatan untuk perbandingan:
     * - Membuang teks dalam kurung seperti "(HRD)", "(CS)"
     * - Menghilangkan tanda baca selain spasi
     * - Menormalisasi kata jamak bahasa Inggris (e.g. "resources" -> "resource")
     */
    public static function canonicalizeJabatan(string $str): string {
        $clean = mb_strtolower(trim($str));
        // Buang teks dalam tanda kurung: "(HRD)" -> ""
        $clean = preg_replace('/\s*\([^)]*\)/', '', $clean);
        // Ganti simbol & menjadi 'dan'
        $clean = str_replace('&', 'dan', $clean);
        // Hapus karakter non-alphanumeric selain spasi
        $clean = preg_replace('/[^a-z0-9\s]/', '', $clean);
        // Hilangkan multiple spaces
        $clean = preg_replace('/\s+/', ' ', trim($clean));
        // Normalisasi kata jamak umum
        $words = explode(' ', $clean);
        $words = array_map(function($w) {
            if ($w === 'resources') return 'resource';
            return $w;
        }, $words);
        return implode(' ', $words);
    }

    /**
     * Smart Resolver untuk Nama Jabatan (Fuzzy Match & Auto-Register):
     * 1. Exact case-insensitive match
     * 2. Alias dictionary ('hrd', 'cs', 'ob', 'it', dll)
     * 3. Canonical match (buang akronim kurung & jamak)
     * 4. Fuzzy similarity match (similar_text >= 82%)
     * 5. Auto-register jika benar-benar posisi baru (< 82%)
     *
     * @return array [ 'nama_jabatan' => string, 'is_new' => bool ]
     */
    public function resolveOrRegisterJabatan(string $rawJabatan): array {
        $raw = trim($rawJabatan);
        if ($raw === '') {
            return ['nama_jabatan' => 'Staff Operasional', 'is_new' => false];
        }

        // 1. Kamus Singkatan & Alias Umum
        $aliasMap = [
            'hrd' => 'Human Resource (HRD)',
            'hr'  => 'Human Resource (HRD)',
            'sdm' => 'Human Resource (HRD)',
            'cs'  => 'Cleaning Service (CS)',
            'ob'  => 'Cleaning Service (CS)',
            'it'  => 'IT Support',
        ];

        $lowerRaw = mb_strtolower($raw);
        if (isset($aliasMap[$lowerRaw])) {
            $masterName = $aliasMap[$lowerRaw];
            $existing = $this->findByName($masterName);
            if ($existing) {
                return ['nama_jabatan' => $existing['nama_jabatan'], 'is_new' => false];
            }
        }

        $allMaster = $this->getAll();
        $canonicalInput = self::canonicalizeJabatan($raw);

        // 2. Exact Match (Case-Insensitive)
        foreach ($allMaster as $m) {
            if (strcasecmp(trim($m['nama_jabatan']), $raw) === 0) {
                return ['nama_jabatan' => $m['nama_jabatan'], 'is_new' => false];
            }
        }

        // 3. Canonical Substring Match (e.g. "Human Resources" vs "Human Resource (HRD)")
        foreach ($allMaster as $m) {
            $canonicalMaster = self::canonicalizeJabatan($m['nama_jabatan']);
            if ($canonicalInput !== '' && $canonicalInput === $canonicalMaster) {
                return ['nama_jabatan' => $m['nama_jabatan'], 'is_new' => false];
            }
        }

        // 4. Fuzzy Similarity Match (similar_text >= 82%)
        $bestMatch = null;
        $highestPercent = 0.0;

        foreach ($allMaster as $m) {
            $canonicalMaster = self::canonicalizeJabatan($m['nama_jabatan']);
            similar_text($canonicalInput, $canonicalMaster, $percentCanonical);
            similar_text($lowerRaw, mb_strtolower(trim($m['nama_jabatan'])), $percentRaw);
            $maxPct = max($percentCanonical, $percentRaw);

            if ($maxPct > $highestPercent) {
                $highestPercent = $maxPct;
                $bestMatch = $m['nama_jabatan'];
            }
        }

        if ($highestPercent >= 82.0 && $bestMatch !== null) {
            return ['nama_jabatan' => $bestMatch, 'is_new' => false];
        }

        // 5. Jabatan benar-benar posisi baru (< 82% kemiripan)
        // Format kapitalisasi rapi (Title Case)
        $cleanName = ucwords($lowerRaw);
        // Pertahankan akronim kapital (IT, HRD, CS, OB)
        $cleanName = preg_replace_callback('/\b(it|hrd|cs|ob|sgm)\b/i', function($matches) {
            return strtoupper($matches[1]);
        }, $cleanName);

        $created = $this->create($cleanName);
        return [
            'nama_jabatan' => $cleanName,
            'is_new' => $created
        ];
    }
}
