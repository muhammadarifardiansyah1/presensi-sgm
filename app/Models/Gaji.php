<?php
/**
 * Gaji / Payroll Model
 * Absensi Surya Gemilang Mojopahit (SGM)
 */

require_once __DIR__ . '/../Config/Database.php';

class Gaji {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Get all salary rate configurations by jabatan
     */
    public function getAllPengaturan(): array {
        $stmt = $this->db->query("SELECT * FROM `pengaturan_gaji` ORDER BY `id` ASC");
        return $stmt->fetchAll();
    }

    /**
     * Get single salary configuration for a specific jabatan
     */
    public function getPengaturanByJabatan(string $jabatan): array {
        $stmt = $this->db->prepare("SELECT * FROM `pengaturan_gaji` WHERE LOWER(`jabatan`) = LOWER(?) LIMIT 1");
        $stmt->execute([trim($jabatan)]);
        $res = $stmt->fetch();
        if ($res) {
            return $res;
        }

        // Fallback default
        return [
            'jabatan' => $jabatan,
            'gaji_pokok' => 3500000.00,
            'uang_makan_harian' => 25000.00,
            'tarif_lembur_per_jam' => 25000.00,
            'potongan_alpa_per_hari' => 100000.00,
            'tarif_pengganti_libur_per_hari' => 52000.00
        ];
    }

    /**
     * Update or insert salary configuration for a jabatan
     */
    public function savePengaturan(array $data): bool {
        $stmt = $this->db->prepare("
            INSERT INTO `pengaturan_gaji` (`jabatan`, `gaji_pokok`, `uang_makan_harian`, `tarif_lembur_per_jam`, `potongan_alpa_per_hari`, `tarif_pengganti_libur_per_hari`)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                `gaji_pokok` = VALUES(`gaji_pokok`),
                `uang_makan_harian` = VALUES(`uang_makan_harian`),
                `tarif_lembur_per_jam` = VALUES(`tarif_lembur_per_jam`),
                `potongan_alpa_per_hari` = VALUES(`potongan_alpa_per_hari`),
                `tarif_pengganti_libur_per_hari` = VALUES(`tarif_pengganti_libur_per_hari`)
        ");
        return $stmt->execute([
            $data['jabatan'],
            (float)($data['gaji_pokok'] ?? 3500000),
            (float)($data['uang_makan_harian'] ?? 25000),
            (float)($data['tarif_lembur_per_jam'] ?? 25000),
            (float)($data['potongan_alpa_per_hari'] ?? 100000),
            (float)($data['tarif_pengganti_libur_per_hari'] ?? 52000)
        ]);
    }

    /**
     * Calculate monthly working days count in a given month (excluding Sundays)
     */
    public function getHariKerjaSebulan(int $bulan, int $tahun): int {
        $totalDays = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);
        $workDays = 0;
        for ($d = 1; $d <= $totalDays; $d++) {
            $dayOfWeek = date('N', strtotime(sprintf('%04d-%02d-%02d', $tahun, $bulan, $d)));
            if ($dayOfWeek != 7) { // 7 is Sunday
                $workDays++;
            }
        }
        return $workDays;
    }

    /**
     * Generate full monthly payroll summary for all active employees
     */
    public function getRekapPayroll(int $bulan, int $tahun, ?int $userId = null): array {
        $totalHariKerjaBulan = $this->getHariKerjaSebulan($bulan, $tahun);

        // Fetch users
        $sqlUsers = "
            SELECT u.id, u.nik, u.nama, u.jenis_kelamin, u.jabatan, u.role, l.nama_lokasi 
            FROM `users` u 
            LEFT JOIN `lokasi_presensi` l ON u.lokasi_id = l.id 
            WHERE u.`status` = 'aktif'
        ";
        $paramsUsers = [];
        if ($userId !== null) {
            $sqlUsers .= " AND u.`id` = ?";
            $paramsUsers[] = $userId;
        }
        $sqlUsers .= " ORDER BY u.`role` ASC, u.`nama` ASC";

        $stmtUsers = $this->db->prepare($sqlUsers);
        $stmtUsers->execute($paramsUsers);
        $users = $stmtUsers->fetchAll();

        $ratesMap = [];
        foreach ($this->getAllPengaturan() as $r) {
            $ratesMap[strtolower(trim($r['jabatan']))] = $r;
        }

        // Siapkan rentang tanggal (Sargable Range) agar MySQL memanfaatkan B-Tree Index pada kolom tanggal
        $startDate = sprintf('%04d-%02d-01', $tahun, $bulan);
        $endDate = date('Y-m-t', strtotime($startDate));

        // 1. Batch Query Presensi seluruh user sekaligus (1 query tunggal, eliminasi N+1)
        $sqlPres = "
            SELECT 
                p.user_id,
                COUNT(*) as total_hadir,
                SUM(CASE WHEN p.total_terlambat != 'Tepat Waktu' AND p.total_terlambat IS NOT NULL AND p.total_terlambat != '' THEN 1 ELSE 0 END) as total_terlambat,
                COALESCE(SUM(p.jam_lembur), 0) as total_lembur
            FROM `presensi` p
            WHERE p.tanggal BETWEEN ? AND ?
        ";
        $paramsPres = [$startDate, $endDate];
        if ($userId !== null) {
            $sqlPres .= " AND p.user_id = ?";
            $paramsPres[] = $userId;
        }
        $sqlPres .= " GROUP BY p.user_id";

        $stmtPres = $this->db->prepare($sqlPres);
        $stmtPres->execute($paramsPres);
        $presensiRows = $stmtPres->fetchAll();

        $presensiMap = [];
        foreach ($presensiRows as $row) {
            $presensiMap[(int)$row['user_id']] = $row;
        }

        // 2. Batch Query Ketidakhadiran seluruh user sekaligus (1 query tunggal, eliminasi N+1)
        $sqlIzin = "
            SELECT k.user_id, k.kategori, k.tanggal_mulai, k.tanggal_selesai
            FROM `ketidakhadiran` k
            WHERE k.status_approval = 'disetujui'
              AND (
                  (k.tanggal_mulai BETWEEN ? AND ?) OR
                  (k.tanggal_selesai BETWEEN ? AND ?) OR
                  (k.tanggal_mulai <= ? AND k.tanggal_selesai >= ?)
              )
        ";
        $paramsIzin = [$startDate, $endDate, $startDate, $endDate, $startDate, $endDate];
        if ($userId !== null) {
            $sqlIzin .= " AND k.user_id = ?";
            $paramsIzin[] = $userId;
        }

        $stmtIzin = $this->db->prepare($sqlIzin);
        $stmtIzin->execute($paramsIzin);
        $izinRows = $stmtIzin->fetchAll();

        $izinMap = [];
        foreach ($izinRows as $iz) {
            $izinMap[(int)$iz['user_id']][] = $iz;
        }

        // 3. Batch Query Penyesuaian Payroll bulanan (PL, override, lain-lain) - 1 query tunggal
        $sqlPen = "
            SELECT user_id, pengganti_libur_hari, lembur_jam_override, absen_hari_override, lain_lain_nominal, keterangan
            FROM `payroll_penyesuaian`
            WHERE bulan = ? AND tahun = ?
        ";
        $paramsPen = [$bulan, $tahun];
        if ($userId !== null) {
            $sqlPen .= " AND user_id = ?";
            $paramsPen[] = $userId;
        }
        $stmtPen = $this->db->prepare($sqlPen);
        $stmtPen->execute($paramsPen);
        $penRows = $stmtPen->fetchAll();

        $penMap = [];
        foreach ($penRows as $pr) {
            $penMap[(int)$pr['user_id']] = $pr;
        }

        $results = [];

        foreach ($users as $user) {
            $uId = (int)$user['id'];
            $jabatanKey = strtolower(trim($user['jabatan']));
            $rates = $ratesMap[$jabatanKey] ?? $this->getPengaturanByJabatan($user['jabatan']);

            // 1. Data presensi diambil instan dari in-memory map
            $presData = $presensiMap[$uId] ?? null;
            $totalHadir = (int)($presData['total_hadir'] ?? 0);
            $totalTerlambat = (int)($presData['total_terlambat'] ?? 0);
            $totalJamLembur = (float)($presData['total_lembur'] ?? 0.00);

            // 2. Data ketidakhadiran (Sakit, Izin, Cuti) diambil instan dari in-memory map
            $userIzinRows = $izinMap[$uId] ?? [];
            $sakitDays = 0;
            $izinDays = 0;
            $cutiDays = 0;

            foreach ($userIzinRows as $iz) {
                $start = strtotime($iz['tanggal_mulai']);
                $end = strtotime($iz['tanggal_selesai']);
                $cur = $start;
                while ($cur <= $end) {
                    if ((int)date('m', $cur) === $bulan && (int)date('Y', $cur) === $tahun) {
                        $kat = strtolower($iz['kategori']);
                        if ($kat === 'sakit') $sakitDays++;
                        elseif ($kat === 'izin') $izinDays++;
                        elseif ($kat === 'cuti') $cutiDays++;
                    }
                    $cur = strtotime('+1 day', $cur);
                }
            }

            // 3. Count Alpa (Hari kerja - hadir - izin - sakit - cuti, min 0)
            $totalTidakMasukIzin = $sakitDays + $izinDays + $cutiDays;
            $alpaDays = max(0, $totalHariKerjaBulan - ($totalHadir + $totalTidakMasukIzin));

            // 4. Data Penyesuaian Bulanan (RSUD Basoeni)
            $pData = $penMap[$uId] ?? null;
            $penggantiLiburHari = (int)($pData['pengganti_libur_hari'] ?? 0);
            $lainLainNominal = (float)($pData['lain_lain_nominal'] ?? 0.00);
            $keteranganPenyesuaian = $pData['keterangan'] ?? '';

            // Override jika admin menentukan angka khusus
            $absenOverride = (isset($pData['absen_hari_override']) && $pData['absen_hari_override'] !== null && $pData['absen_hari_override'] !== '')
                ? (int)$pData['absen_hari_override']
                : null;
            $finalAbsenDays = ($absenOverride !== null) ? $absenOverride : $alpaDays;

            $lemburOverride = (isset($pData['lembur_jam_override']) && $pData['lembur_jam_override'] !== null && $pData['lembur_jam_override'] !== '')
                ? (float)$pData['lembur_jam_override']
                : null;
            $finalJamLembur = ($lemburOverride !== null) ? $lemburOverride : $totalJamLembur;

            // 5. Financial Calculations
            $gajiPokok = (float)($rates['gaji_pokok'] ?? 3500000);
            $uangMakanHarian = (float)($rates['uang_makan_harian'] ?? 0);
            $tarifLembur = (float)($rates['tarif_lembur_per_jam'] ?? 20000);
            $dendaAlpa = (float)($rates['potongan_alpa_per_hari'] ?? 52000);
            $tarifPenggantiLibur = (float)($rates['tarif_pengganti_libur_per_hari'] ?? 52000);

            $totalUangMakan = $totalHadir * $uangMakanHarian;
            $totalPenggantiLibur = $penggantiLiburHari * $tarifPenggantiLibur;
            $totalUangLembur = round($finalJamLembur * $tarifLembur);
            $totalPotonganAlpa = $finalAbsenDays * $dendaAlpa;

            // Rumus Total Gaji RSUD Basoeni:
            // Gaji Pokok + Uang Makan + Pengganti Libur - Potongan Absen + Uang Lembur + Lain-Lain
            $totalPenerimaan = $gajiPokok + $totalUangMakan + $totalPenggantiLibur + $totalUangLembur + $lainLainNominal;
            $totalPotongan = $totalPotonganAlpa;
            $takeHomePay = max(0, $totalPenerimaan - $totalPotongan);

            $gender = $user['jenis_kelamin'] ?? 'Laki-laki';
            $statusGender = (strtolower($gender) === 'perempuan') ? 'P' : 'L';

            $results[] = [
                'id' => $uId,
                'user_id' => $uId,
                'nik' => $user['nik'],
                'nama' => $user['nama'],
                'jenis_kelamin' => $gender,
                'status_gender' => $statusGender,
                'jabatan' => $user['jabatan'],
                'nama_lokasi' => $user['nama_lokasi'] ?? 'Kantor SGM',
                'hari_kerja_sebulan' => $totalHariKerjaBulan,
                'hari_kerja_bulan' => $totalHariKerjaBulan,
                'hadir' => $totalHadir,
                'terlambat' => $totalTerlambat,
                'sakit' => $sakitDays,
                'izin' => $izinDays,
                'cuti' => $cutiDays,
                'alpa_sistem' => $alpaDays,
                'alpa' => $finalAbsenDays,
                'absen_hari_override' => $absenOverride,
                'jam_lembur_sistem' => $totalJamLembur,
                'jam_lembur' => $finalJamLembur,
                'lembur_jam_override' => $lemburOverride,
                'pengganti_libur_hari' => $penggantiLiburHari,
                'uang_pengganti_libur' => $totalPenggantiLibur,
                'lain_lain' => $lainLainNominal,
                'keterangan_penyesuaian' => $keteranganPenyesuaian,
                // Finansial
                'gaji_pokok' => $gajiPokok,
                'uang_makan' => $totalUangMakan,
                'uang_lembur' => $totalUangLembur,
                'potongan_alpa' => $totalPotonganAlpa,
                'total_penerimaan' => $totalPenerimaan,
                'total_potongan' => $totalPotongan,
                'take_home_pay' => $takeHomePay,
                // Setting rates used
                'tarif' => [
                    'gaji_pokok' => $gajiPokok,
                    'uang_makan_harian' => $uangMakanHarian,
                    'tarif_lembur_per_jam' => $tarifLembur,
                    'potongan_alpa_per_hari' => $dendaAlpa,
                    'tarif_pengganti_libur_per_hari' => $tarifPenggantiLibur
                ],
                'tarif_lembur' => $tarifLembur,
                'uang_makan_harian' => $uangMakanHarian,
                'denda_alpa_harian' => $dendaAlpa,
                'tarif_pengganti_libur' => $tarifPenggantiLibur
            ];
        }

        return $results;
    }

    /**
     * Ambil seluruh data penyesuaian payroll periode tertentu
     */
    public function getPenyesuaianByPeriode(int $bulan, int $tahun): array {
        $stmt = $this->db->prepare("
            SELECT * FROM `payroll_penyesuaian` 
            WHERE `bulan` = ? AND `tahun` = ?
        ");
        $stmt->execute([$bulan, $tahun]);
        $rows = $stmt->fetchAll();
        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r['user_id']] = $r;
        }
        return $map;
    }

    /**
     * Batch simpan penyesuaian payroll dengan 1 PDO Transaction (Super Cepat & Aman)
     */
    public function saveBatchPenyesuaian(int $bulan, int $tahun, array $rows): bool {
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare("
                INSERT INTO `payroll_penyesuaian` 
                    (`user_id`, `bulan`, `tahun`, `pengganti_libur_hari`, `lembur_jam_override`, `absen_hari_override`, `lain_lain_nominal`, `keterangan`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    `pengganti_libur_hari` = VALUES(`pengganti_libur_hari`),
                    `lembur_jam_override` = VALUES(`lembur_jam_override`),
                    `absen_hari_override` = VALUES(`absen_hari_override`),
                    `lain_lain_nominal` = VALUES(`lain_lain_nominal`),
                    `keterangan` = VALUES(`keterangan`),
                    `updated_at` = CURRENT_TIMESTAMP
            ");

            foreach ($rows as $userId => $item) {
                $pl = max(0, (int)($item['pengganti_libur_hari'] ?? 0));
                $lain = (float)($item['lain_lain_nominal'] ?? 0.00);
                $ket = trim($item['keterangan'] ?? '');
                
                $lemburOverride = (isset($item['lembur_jam_override']) && $item['lembur_jam_override'] !== '' && $item['lembur_jam_override'] !== null) 
                    ? max(0, (float)$item['lembur_jam_override']) 
                    : null;
                $absenOverride = (isset($item['absen_hari_override']) && $item['absen_hari_override'] !== '' && $item['absen_hari_override'] !== null) 
                    ? max(0, (int)$item['absen_hari_override']) 
                    : null;

                $stmt->execute([
                    (int)$userId,
                    $bulan,
                    $tahun,
                    $pl,
                    $lemburOverride,
                    $absenOverride,
                    $lain,
                    $ket ?: null
                ]);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error saveBatchPenyesuaian: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send payroll data to Google Apps Script Webhook (Option A)
     */
    public function syncToGoogleSheets(string $webhookUrl, array $rekapData, int $bulan, int $tahun): array {
        $webhookUrl = trim($webhookUrl);
        if (empty($webhookUrl) || !filter_var($webhookUrl, FILTER_VALIDATE_URL)) {
            return [
                'success' => false,
                'message' => 'URL Webhook Google Apps Script belum diatur atau format tidak valid.'
            ];
        }

        // Deteksi jika user salah memasukkan link dokumen Google Sheets alih-alih Webhook Apps Script
        if (strpos($webhookUrl, 'docs.google.com/spreadsheets') !== false) {
            return [
                'success' => false,
                'message' => 'URL yang dimasukkan adalah link halaman Google Spreadsheet (docs.google.com/spreadsheets/...), bukan URL Webhook Apps Script (script.google.com/macros/s/.../exec). Silakan klik "Panduan Webhook Spreadsheet" untuk panduan membuat Webhook dalam 1 menit.'
            ];
        }

        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $payload = [
            'action' => 'sync_payroll',
            'sheet_name' => 'Rekap Gaji Karyawan',
            'periode' => ($namaBulan[$bulan] ?? $bulan) . ' ' . $tahun,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'timestamp' => date('Y-m-d H:i:s'),
            'total_pegawai' => count($rekapData),
            'data' => [],
            'rows' => []
        ];

        foreach ($rekapData as $r) {
            $rowItem = [
                'nik' => $r['nik'],
                'nama' => $r['nama'],
                'jabatan' => $r['jabatan'],
                'lokasi' => $r['nama_lokasi'],
                'hari_kerja' => $r['hari_kerja_sebulan'],
                'hari_kerja_sebulan' => $r['hari_kerja_sebulan'],
                'hadir' => $r['hadir'],
                'terlambat' => $r['terlambat'],
                'sakit' => $r['sakit'],
                'izin' => $r['izin'],
                'cuti' => $r['cuti'],
                'alpa' => $r['alpa'],
                'jam_lembur' => $r['jam_lembur'],
                'gaji_pokok' => $r['gaji_pokok'],
                'uang_makan' => $r['uang_makan'],
                'uang_lembur' => $r['uang_lembur'],
                'potongan_alpa' => $r['potongan_alpa'],
                'take_home_pay' => $r['take_home_pay'],
                'gaji_bersih' => $r['take_home_pay']
            ];
            $payload['data'][] = $rowItem;
            $payload['rows'][] = $rowItem;
        }

        $jsonPayload = json_encode($payload);

        // Send via cURL to Google Apps Script (HTTP/1.1 with clean 302 redirect)
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
                'message' => $resDecoded['message'] ?? 'Data rekap penggajian berhasil dikirim ke Google Spreadsheet!',
                'raw' => $response
            ];
        }

        return [
            'success' => false,
            'message' => 'Google Apps Script merespons kode HTTP ' . $httpCode . ': ' . substr($response, 0, 150)
        ];
    }
}
