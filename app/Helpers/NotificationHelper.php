<?php
/**
 * Notification Helper
 * Absensi Surya Gemilang Mojopahit (SGM)
 */

require_once __DIR__ . '/../Config/Config.php';
require_once __DIR__ . '/../Config/Database.php';
require_once __DIR__ . '/Icon.php';

class NotificationHelper {
    public static function getNotifications(array $currentUser): array {
        if (empty($currentUser['id'])) {
            return ['unread_count' => 0, 'items' => []];
        }

        $db = Database::getConnection();
        $items = [];
        $unreadCount = 0;
        $role = $currentUser['role'] ?? 'pegawai';
        $userId = (int) $currentUser['id'];

        if ($role === 'admin') {
            // 1. Pending Ketidakhadiran (Cuti / Izin / Sakit)
            $stmt = $db->query("
                SELECT k.*, u.nama AS nama_pegawai, u.foto_profil
                FROM `ketidakhadiran` k
                JOIN `users` u ON k.user_id = u.id
                WHERE k.status_approval = 'pending'
                ORDER BY k.created_at DESC
                LIMIT 5
            ");
            $pendingList = $stmt->fetchAll();
            $unreadCount += count($pendingList);

            foreach ($pendingList as $k) {
                $icon = Icon::get('clipboard', 20);
                $items[] = [
                    'id'      => 'cuti_' . $k['id'],
                    'icon'    => $icon,
                    'type'    => 'warning',
                    'title'   => 'Pengajuan ' . ucfirst($k['kategori']),
                    'message' => $k['nama_pegawai'] . ' mengajukan ' . $k['kategori'] . ' (' . date('d/m/Y', strtotime($k['tanggal_mulai'])) . ')',
                    'time'    => self::timeAgo($k['created_at']),
                    'link'    => Config::baseUrl('ketidakhadiran'),
                    'badge'   => 'Pending'
                ];
            }

            // 2. Presensi Hari Ini (Aktivitas terbaru pegawai absen)
            $today = date('Y-m-d');
            $stmt = $db->prepare("
                SELECT p.*, u.nama AS nama_pegawai
                FROM `presensi` p
                JOIN `users` u ON p.user_id = u.id
                WHERE p.tanggal = ?
                ORDER BY COALESCE(p.jam_pulang, p.jam_masuk) DESC
                LIMIT 4
            ");
            $stmt->execute([$today]);
            $recentPresensi = $stmt->fetchAll();

            foreach ($recentPresensi as $p) {
                if (!empty($p['jam_pulang'])) {
                    $items[] = [
                        'id'      => 'absen_pulang_' . $p['id'],
                        'icon'    => Icon::get('logout', 20),
                        'type'    => 'info',
                        'title'   => 'Presensi Pulang',
                        'message' => $p['nama_pegawai'] . ' telah absen pulang pukul ' . substr($p['jam_pulang'], 0, 5),
                        'time'    => substr($p['jam_pulang'], 0, 5) . ' WIB',
                        'link'    => Config::baseUrl('laporan/harian'),
                        'badge'   => 'Pulang'
                    ];
                } elseif (!empty($p['jam_masuk'])) {
                    $isLate = ($p['status_kehadiran'] === 'terlambat');
                    $items[] = [
                        'id'      => 'absen_masuk_' . $p['id'],
                        'icon'    => $isLate ? Icon::get('clock', 20) : Icon::get('check-circle', 20),
                        'type'    => $isLate ? 'danger' : 'success',
                        'title'   => 'Presensi Masuk',
                        'message' => $p['nama_pegawai'] . ' hadir pukul ' . substr($p['jam_masuk'], 0, 5) . ($isLate ? ' (Terlambat)' : ''),
                        'time'    => substr($p['jam_masuk'], 0, 5) . ' WIB',
                        'link'    => Config::baseUrl('laporan/harian'),
                        'badge'   => $isLate ? 'Terlambat' : 'Hadir'
                    ];
                }
            }

            // 3. Surat Perintah Lembur (SPL) Aktif Terbaru (Admin)
            $stmtLemburAdmin = $db->query("
                SELECT pl.id, pl.nomor_sp_lembur, pl.lokasi_id, pl.tanggal, pl.jam_mulai, pl.jam_selesai,
                       pl.maksimal_jam, pl.keterangan, pl.status, pl.created_by, pl.created_at, pl.updated_at,
                       l.nama_lokasi,
                       (SELECT COUNT(*) FROM `penugasan_lembur_pegawai` plp WHERE plp.penugasan_lembur_id = pl.id) AS total_pegawai
                FROM `penugasan_lembur` pl
                LEFT JOIN `lokasi_presensi` l ON pl.lokasi_id = l.id
                WHERE pl.status = 'aktif' AND pl.tanggal >= CURDATE()
                ORDER BY pl.tanggal ASC, pl.jam_mulai ASC
                LIMIT 3
            ");
            $activeLemburAdmin = $stmtLemburAdmin->fetchAll();
            foreach ($activeLemburAdmin as $spl) {
                $items[] = [
                    'id'      => 'admin_spl_' . $spl['id'],
                    'icon'    => Icon::get('clock', 20),
                    'type'    => 'info',
                    'title'   => 'SPL Aktif: ' . $spl['nomor_sp_lembur'],
                    'message' => $spl['total_pegawai'] . ' pegawai ditugaskan (' . date('d/m/Y', strtotime($spl['tanggal'])) . ', ' . substr($spl['jam_mulai'], 0, 5) . ' - ' . substr($spl['jam_selesai'], 0, 5) . ' WIB)',
                    'time'    => 'Hari Ini',
                    'link'    => Config::baseUrl('lembur/detail?id=' . $spl['id']),
                    'badge'   => 'SPL Aktif'
                ];
            }

        } else {
            // Pegawai role
            // 1. Presensi Hari Ini status
            $today = date('Y-m-d');
            $stmt = $db->prepare("SELECT * FROM `presensi` WHERE user_id = ? AND tanggal = ? LIMIT 1");
            $stmt->execute([$userId, $today]);
            $todayPresensi = $stmt->fetch();

            if (!$todayPresensi) {
                $unreadCount++;
                $items[] = [
                    'id'      => 'reminder_absen',
                    'icon'    => Icon::get('clock', 20),
                    'type'    => 'warning',
                    'title'   => 'Pengingat Presensi Masuk',
                    'message' => 'Anda belum melakukan presensi hari ini. Jangan lupa absen ya!',
                    'time'    => 'Hari Ini',
                    'link'    => Config::baseUrl('presensi/masuk'),
                    'badge'   => 'Penting'
                ];
            } elseif (empty($todayPresensi['jam_pulang'])) {
                $items[] = [
                    'id'      => 'reminder_pulang',
                    'icon'    => Icon::get('map-pin', 20),
                    'type'    => 'info',
                    'title'   => 'Presensi Masuk Tercatat',
                    'message' => 'Masuk pukul ' . substr($todayPresensi['jam_masuk'], 0, 5) . '. Jangan lupa absen pulang!',
                    'time'    => substr($todayPresensi['jam_masuk'], 0, 5) . ' WIB',
                    'link'    => Config::baseUrl('presensi/keluar'),
                    'badge'   => 'Aktif'
                ];
            } else {
                $items[] = [
                    'id'      => 'status_lengkap',
                    'icon'    => Icon::get('check-circle', 20),
                    'type'    => 'success',
                    'title'   => 'Presensi Lengkap',
                    'message' => 'Presensi hari ini selesai (' . substr($todayPresensi['jam_masuk'], 0, 5) . ' - ' . substr($todayPresensi['jam_pulang'], 0, 5) . ').',
                    'time'    => 'Selesai',
                    'link'    => Config::baseUrl('dashboard'),
                    'badge'   => 'Selesai'
                ];
            }

            // 2. Status Pengajuan Ketidakhadiran (Terbaru)
            $stmt = $db->prepare("
                SELECT * FROM `ketidakhadiran`
                WHERE user_id = ?
                ORDER BY updated_at DESC
                LIMIT 4
            ");
            $stmt->execute([$userId]);
            $myKetidakhadiran = $stmt->fetchAll();

            foreach ($myKetidakhadiran as $k) {
                if ($k['status_approval'] === 'disetujui') {
                    $items[] = [
                        'id'      => 'cuti_status_' . $k['id'],
                        'icon'    => Icon::get('check-circle', 20),
                        'type'    => 'success',
                        'title'   => 'Pengajuan ' . ucfirst($k['kategori']) . ' Disetujui',
                        'message' => 'Izin/cuti tanggal ' . date('d/m/Y', strtotime($k['tanggal_mulai'])) . ' telah disetujui admin.',
                        'time'    => self::timeAgo($k['updated_at']),
                        'link'    => Config::baseUrl('ketidakhadiran'),
                        'badge'   => 'Disetujui'
                    ];
                } elseif ($k['status_approval'] === 'ditolak') {
                    $items[] = [
                        'id'      => 'cuti_status_' . $k['id'],
                        'icon'    => Icon::get('x-circle', 20),
                        'type'    => 'danger',
                        'title'   => 'Pengajuan ' . ucfirst($k['kategori']) . ' Ditolak',
                        'message' => 'Catatan: ' . ($k['catatan_admin'] ?: 'Tidak disetujui.'),
                        'time'    => self::timeAgo($k['updated_at']),
                        'link'    => Config::baseUrl('ketidakhadiran'),
                        'badge'   => 'Ditolak'
                    ];
                } else {
                    $items[] = [
                        'id'      => 'cuti_status_' . $k['id'],
                        'icon'    => Icon::get('clock', 20),
                        'type'    => 'warning',
                        'title'   => 'Pengajuan ' . ucfirst($k['kategori']) . ' Sedang Ditinjau',
                        'message' => 'Tanggal ' . date('d/m/Y', strtotime($k['tanggal_mulai'])) . ' s/d ' . date('d/m/Y', strtotime($k['tanggal_selesai'])),
                        'time'    => self::timeAgo($k['created_at']),
                        'link'    => Config::baseUrl('ketidakhadiran'),
                        'badge'   => 'Menunggu'
                    ];
                }
            }

            // 3. Notifikasi Surat Perintah Lembur (SPL) untuk Pegawai
            $stmtLembur = $db->prepare("
                SELECT pl.id, pl.nomor_sp_lembur, pl.lokasi_id, pl.tanggal, pl.jam_mulai, pl.jam_selesai,
                       pl.maksimal_jam, pl.keterangan, pl.status, pl.created_by, pl.created_at, pl.updated_at,
                       l.nama_lokasi, plp.status AS plp_status, plp.jam_lembur_aktual
                FROM `penugasan_lembur_pegawai` plp
                JOIN `penugasan_lembur` pl ON plp.penugasan_lembur_id = pl.id
                LEFT JOIN `lokasi_presensi` l ON pl.lokasi_id = l.id
                WHERE plp.user_id = ?
                  AND pl.status = 'aktif'
                  AND pl.tanggal >= CURDATE() - INTERVAL 1 DAY
                ORDER BY pl.tanggal DESC, pl.jam_mulai DESC
                LIMIT 3
            ");
            $stmtLembur->execute([$userId]);
            $lemburList = $stmtLembur->fetchAll();

            foreach ($lemburList as $lembur) {
                $isToday = ($lembur['tanggal'] === date('Y-m-d'));
                if ($isToday && $lembur['plp_status'] === 'ditugaskan') {
                    $unreadCount++;
                }

                $jamMulai = substr($lembur['jam_mulai'], 0, 5);
                $jamSelesai = substr($lembur['jam_selesai'], 0, 5);
                $tglFmt = date('d/m/Y', strtotime($lembur['tanggal']));

                $items[] = [
                    'id'      => 'spl_' . $lembur['id'],
                    'icon'    => Icon::get('clock', 20),
                    'type'    => $isToday ? 'warning' : 'info',
                    'title'   => $isToday ? 'Surat Perintah Lembur Hari Ini' : 'Penugasan Lembur: ' . $lembur['nomor_sp_lembur'],
                    'message' => 'Anda ditugaskan lembur ' . ($isToday ? 'hari ini' : 'tgl ' . $tglFmt) . ' (' . $jamMulai . ' - ' . $jamSelesai . ' WIB, maks ' . (float)$lembur['maksimal_jam'] . ' jam) di ' . ($lembur['nama_lokasi'] ?? 'Kantor') . '. Tugas: "' . htmlspecialchars($lembur['keterangan']) . '"',
                    'time'    => $isToday ? $jamMulai . ' WIB' : $tglFmt,
                    'link'    => Config::baseUrl('dashboard'),
                    'badge'   => $isToday ? 'Lembur Hari Ini' : 'SPL Aktif'
                ];
            }
        }

        return [
            'unread_count' => $unreadCount,
            'items'        => $items
        ];
    }

    /**
     * Mengambil seluruh notifikasi lengkap untuk Halaman Mandiri /notifikasi
     */
    public static function getAllNotifications(array $currentUser, int $limit = 50): array {
        if (empty($currentUser['id'])) {
            return ['unread_count' => 0, 'items' => []];
        }

        $db = Database::getConnection();
        $items = [];
        $unreadCount = 0;
        $role = $currentUser['role'] ?? 'pegawai';
        $userId = (int) $currentUser['id'];

        if ($role === 'admin') {
            // 1. Seluruh Pengajuan Ketidakhadiran
            $stmt = $db->query("
                SELECT k.*, u.nama AS nama_pegawai, u.foto_profil
                FROM `ketidakhadiran` k
                JOIN `users` u ON k.user_id = u.id
                ORDER BY k.created_at DESC
                LIMIT 30
            ");
            $allList = $stmt->fetchAll();

            foreach ($allList as $k) {
                $isPending = ($k['status_approval'] === 'pending');
                if ($isPending) $unreadCount++;
                
                $items[] = [
                    'id'          => 'cuti_' . $k['id'],
                    'icon'        => Icon::get('clipboard', 20),
                    'type'        => $isPending ? 'warning' : (($k['status_approval'] === 'disetujui') ? 'success' : 'danger'),
                    'title'       => 'Pengajuan ' . ucfirst($k['kategori']) . ' (' . ucfirst($k['status_approval']) . ')',
                    'message'     => $k['nama_pegawai'] . ' mengajukan ' . $k['kategori'] . ' (' . date('d/m/Y', strtotime($k['tanggal_mulai'])) . ' - ' . date('d/m/Y', strtotime($k['tanggal_selesai'])) . '). Keterangan: "' . htmlspecialchars($k['keterangan']) . '"',
                    'time'        => self::timeAgo($k['created_at']),
                    'date_detail' => date('d M Y, H:i', strtotime($k['created_at'])),
                    'link'        => Config::baseUrl('ketidakhadiran'),
                    'badge'       => ucfirst($k['status_approval']),
                    'kategori'    => 'ketidakhadiran'
                ];
            }

            // 2. Presensi Terkini
            $today = date('Y-m-d');
            $stmtPres = $db->prepare("
                SELECT p.*, u.nama AS nama_pegawai, l.nama_lokasi
                FROM `presensi` p
                JOIN `users` u ON p.user_id = u.id
                LEFT JOIN `lokasi_presensi` l ON p.lokasi_id = l.id
                ORDER BY p.tanggal DESC, COALESCE(p.jam_pulang, p.jam_masuk) DESC
                LIMIT 25
            ");
            $stmtPres->execute();
            $recentPresensi = $stmtPres->fetchAll();

            foreach ($recentPresensi as $p) {
                if (!empty($p['jam_pulang'])) {
                    $items[] = [
                        'id'          => 'absen_pulang_' . $p['id'],
                        'icon'        => Icon::get('logout', 20),
                        'type'        => 'info',
                        'title'       => 'Presensi Pulang: ' . $p['nama_pegawai'],
                        'message'     => $p['nama_pegawai'] . ' telah absen pulang pukul ' . substr($p['jam_pulang'], 0, 5) . ' WIB (Total: ' . ($p['total_jam_kerja'] ?: '-') . ') di ' . ($p['nama_lokasi'] ?: 'Kantor SGM'),
                        'time'        => self::timeAgo($p['tanggal'] . ' ' . $p['jam_pulang']),
                        'date_detail' => date('d M Y', strtotime($p['tanggal'])) . ', ' . substr($p['jam_pulang'], 0, 5) . ' WIB',
                        'link'        => Config::baseUrl('laporan/harian?tanggal=' . $p['tanggal']),
                        'badge'       => 'Pulang',
                        'kategori'    => 'presensi'
                    ];
                } elseif (!empty($p['jam_masuk'])) {
                    $isLate = ($p['status_kehadiran'] === 'terlambat');
                    $items[] = [
                        'id'          => 'absen_masuk_' . $p['id'],
                        'icon'        => $isLate ? Icon::get('clock', 20) : Icon::get('check-circle', 20),
                        'type'        => $isLate ? 'danger' : 'success',
                        'title'       => 'Presensi Masuk: ' . $p['nama_pegawai'],
                        'message'     => $p['nama_pegawai'] . ' hadir pukul ' . substr($p['jam_masuk'], 0, 5) . ($isLate ? ' (Terlambat: ' . ($p['total_terlambat'] ?: '-') . ')' : ' (Tepat Waktu)') . ' di ' . ($p['nama_lokasi'] ?: 'Kantor SGM'),
                        'time'        => self::timeAgo($p['tanggal'] . ' ' . $p['jam_masuk']),
                        'date_detail' => date('d M Y', strtotime($p['tanggal'])) . ', ' . substr($p['jam_masuk'], 0, 5) . ' WIB',
                        'link'        => Config::baseUrl('laporan/harian?tanggal=' . $p['tanggal']),
                        'badge'       => $isLate ? 'Terlambat' : 'Hadir',
                        'kategori'    => 'presensi'
                    ];
                }
            }

            // 3. Seluruh Surat Perintah Lembur (Admin)
            $stmtLemburAll = $db->query("
                SELECT pl.id, pl.nomor_sp_lembur, pl.lokasi_id, pl.tanggal, pl.jam_mulai, pl.jam_selesai,
                       pl.maksimal_jam, pl.keterangan, pl.status, pl.created_by, pl.created_at, pl.updated_at,
                       l.nama_lokasi,
                       (SELECT COUNT(*) FROM `penugasan_lembur_pegawai` plp WHERE plp.penugasan_lembur_id = pl.id) AS total_pegawai
                FROM `penugasan_lembur` pl
                LEFT JOIN `lokasi_presensi` l ON pl.lokasi_id = l.id
                ORDER BY pl.tanggal DESC, pl.jam_mulai DESC
                LIMIT 20
            ");
            $allLemburAdmin = $stmtLemburAll->fetchAll();
            foreach ($allLemburAdmin as $spl) {
                $isAktif = ($spl['status'] === 'aktif');
                $items[] = [
                    'id'          => 'admin_spl_' . $spl['id'],
                    'icon'        => Icon::get('clock', 20),
                    'type'        => $isAktif ? 'warning' : 'info',
                    'title'       => 'SPL: ' . $spl['nomor_sp_lembur'] . ' (' . ucfirst($spl['status']) . ')',
                    'message'     => $spl['total_pegawai'] . ' pegawai ditugaskan lembur pada ' . date('d/m/Y', strtotime($spl['tanggal'])) . ' (' . substr($spl['jam_mulai'], 0, 5) . ' - ' . substr($spl['jam_selesai'], 0, 5) . ' WIB, maks ' . (float)$spl['maksimal_jam'] . ' jam) di ' . ($spl['nama_lokasi'] ?? 'Kantor') . '. Tugas: "' . htmlspecialchars($spl['keterangan']) . '"',
                    'time'        => self::timeAgo($spl['created_at']),
                    'date_detail' => date('d M Y, H:i', strtotime($spl['created_at'])),
                    'link'        => Config::baseUrl('lembur/detail?id=' . $spl['id']),
                    'badge'       => ucfirst($spl['status']),
                    'kategori'    => 'lembur'
                ];
            }

        } else {
            // Pegawai role
            // 1. Presensi Hari Ini status
            $today = date('Y-m-d');
            $stmt = $db->prepare("SELECT * FROM `presensi` WHERE user_id = ? AND tanggal = ? LIMIT 1");
            $stmt->execute([$userId, $today]);
            $todayPresensi = $stmt->fetch();

            if (!$todayPresensi) {
                $unreadCount++;
                $items[] = [
                    'id'          => 'reminder_absen',
                    'icon'        => Icon::get('clock', 20),
                    'type'        => 'warning',
                    'title'       => 'Pengingat Presensi Masuk Hari Ini',
                    'message'     => 'Anda belum melakukan presensi hari ini. Silakan ambil foto dan lakukan presensi masuk.',
                    'time'        => 'Hari Ini',
                    'date_detail' => date('d M Y'),
                    'link'        => Config::baseUrl('presensi/masuk'),
                    'badge'       => 'Perhatian',
                    'kategori'    => 'presensi'
                ];
            } elseif (empty($todayPresensi['jam_pulang'])) {
                $items[] = [
                    'id'          => 'reminder_pulang',
                    'icon'        => Icon::get('map-pin', 20),
                    'type'        => 'info',
                    'title'       => 'Presensi Masuk Tercatat',
                    'message'     => 'Presensi masuk tercatat pukul ' . substr($todayPresensi['jam_masuk'], 0, 5) . ' WIB. Jangan lupa lakukan presensi pulang saat jam kerja berakhir.',
                    'time'        => substr($todayPresensi['jam_masuk'], 0, 5) . ' WIB',
                    'date_detail' => date('d M Y, H:i', strtotime($todayPresensi['created_at'])),
                    'link'        => Config::baseUrl('presensi/keluar'),
                    'badge'       => 'Aktif',
                    'kategori'    => 'presensi'
                ];
            } else {
                $items[] = [
                    'id'          => 'status_lengkap',
                    'icon'        => Icon::get('check-circle', 20),
                    'type'        => 'success',
                    'title'       => 'Presensi Hari Ini Selesai',
                    'message'     => 'Presensi hari ini lengkap (' . substr($todayPresensi['jam_masuk'], 0, 5) . ' - ' . substr($todayPresensi['jam_pulang'], 0, 5) . ' WIB). Total kerja: ' . ($todayPresensi['total_jam_kerja'] ?: '-') . '.',
                    'time'        => 'Selesai',
                    'date_detail' => date('d M Y'),
                    'link'        => Config::baseUrl('dashboard'),
                    'badge'       => 'Lengkap',
                    'kategori'    => 'presensi'
                ];
            }

            // 2. Seluruh Status Pengajuan Ketidakhadiran Pegawai
            $stmt = $db->prepare("
                SELECT * FROM `ketidakhadiran`
                WHERE user_id = ?
                ORDER BY updated_at DESC, created_at DESC
                LIMIT 30
            ");
            $stmt->execute([$userId]);
            $myKetidakhadiran = $stmt->fetchAll();

            foreach ($myKetidakhadiran as $k) {
                $kat = ucfirst($k['kategori']);
                if ($k['status_approval'] === 'disetujui') {
                    $items[] = [
                        'id'          => 'cuti_status_' . $k['id'],
                        'icon'        => Icon::get('check-circle', 20),
                        'type'        => 'success',
                        'title'       => 'Pengajuan ' . $kat . ' Telah Disetujui',
                        'message'     => 'Izin/cuti untuk periode ' . date('d/m/Y', strtotime($k['tanggal_mulai'])) . ' s/d ' . date('d/m/Y', strtotime($k['tanggal_selesai'])) . ' telah disetujui oleh admin. Keterangan: "' . htmlspecialchars($k['keterangan']) . '".' . (!empty($k['catatan_admin']) ? ' Catatan admin: ' . htmlspecialchars($k['catatan_admin']) : ''),
                        'time'        => self::timeAgo($k['updated_at']),
                        'date_detail' => date('d M Y, H:i', strtotime($k['updated_at'])),
                        'link'        => Config::baseUrl('ketidakhadiran'),
                        'badge'       => 'Disetujui',
                        'kategori'    => 'ketidakhadiran'
                    ];
                } elseif ($k['status_approval'] === 'ditolak') {
                    $items[] = [
                        'id'          => 'cuti_status_' . $k['id'],
                        'icon'        => Icon::get('x-circle', 20),
                        'type'        => 'danger',
                        'title'       => 'Pengajuan ' . $kat . ' Ditolak',
                        'message'     => 'Pengajuan tanggal ' . date('d/m/Y', strtotime($k['tanggal_mulai'])) . ' tidak disetujui. Catatan admin: ' . ($k['catatan_admin'] ?: 'Tidak disetujui.'),
                        'time'        => self::timeAgo($k['updated_at']),
                        'date_detail' => date('d M Y, H:i', strtotime($k['updated_at'])),
                        'link'        => Config::baseUrl('ketidakhadiran'),
                        'badge'       => 'Ditolak',
                        'kategori'    => 'ketidakhadiran'
                    ];
                } else {
                    $unreadCount++;
                    $items[] = [
                        'id'          => 'cuti_status_' . $k['id'],
                        'icon'        => Icon::get('clock', 20),
                        'type'        => 'warning',
                        'title'       => 'Pengajuan ' . $kat . ' Sedang Ditinjau',
                        'message'     => 'Pengajuan izin untuk tanggal ' . date('d/m/Y', strtotime($k['tanggal_mulai'])) . ' s/d ' . date('d/m/Y', strtotime($k['tanggal_selesai'])) . ' telah masuk dan sedang menunggu persetujuan administrator.',
                        'time'        => self::timeAgo($k['created_at']),
                        'date_detail' => date('d M Y, H:i', strtotime($k['created_at'])),
                        'link'        => Config::baseUrl('ketidakhadiran'),
                        'badge'       => 'Menunggu Review',
                        'kategori'    => 'ketidakhadiran'
                    ];
                }
            }

            // 3. Seluruh Riwayat Penugasan Lembur Pegawai
            $stmtLemburAllPeg = $db->prepare("
                SELECT pl.id, pl.nomor_sp_lembur, pl.lokasi_id, pl.tanggal, pl.jam_mulai, pl.jam_selesai,
                       pl.maksimal_jam, pl.keterangan, pl.status, pl.created_by, pl.created_at, pl.updated_at,
                       l.nama_lokasi, plp.status AS plp_status, plp.jam_lembur_aktual
                FROM `penugasan_lembur_pegawai` plp
                JOIN `penugasan_lembur` pl ON plp.penugasan_lembur_id = pl.id
                LEFT JOIN `lokasi_presensi` l ON pl.lokasi_id = l.id
                WHERE plp.user_id = ?
                ORDER BY pl.tanggal DESC, pl.jam_mulai DESC
                LIMIT 20
            ");
            $stmtLemburAllPeg->execute([$userId]);
            $myLemburAll = $stmtLemburAllPeg->fetchAll();

            foreach ($myLemburAll as $lembur) {
                $isToday = ($lembur['tanggal'] === date('Y-m-d'));
                if ($isToday && $lembur['plp_status'] === 'ditugaskan') {
                    $unreadCount++;
                }

                $jamMulai = substr($lembur['jam_mulai'], 0, 5);
                $jamSelesai = substr($lembur['jam_selesai'], 0, 5);

                $items[] = [
                    'id'          => 'spl_' . $lembur['id'],
                    'icon'        => Icon::get('clock', 20),
                    'type'        => ($lembur['status'] === 'aktif') ? 'warning' : 'info',
                    'title'       => 'Surat Perintah Lembur: ' . $lembur['nomor_sp_lembur'],
                    'message'     => 'Penugasan lembur tanggal ' . date('d/m/Y', strtotime($lembur['tanggal'])) . ' (' . $jamMulai . ' - ' . $jamSelesai . ' WIB, maks ' . (float)$lembur['maksimal_jam'] . ' jam) di ' . ($lembur['nama_lokasi'] ?? 'Kantor') . '. Tugas: "' . htmlspecialchars($lembur['keterangan']) . '"',
                    'time'        => self::timeAgo($lembur['created_at']),
                    'date_detail' => date('d M Y, H:i', strtotime($lembur['created_at'])),
                    'link'        => Config::baseUrl('dashboard'),
                    'badge'       => ucfirst($lembur['status']),
                    'kategori'    => 'lembur'
                ];
            }
        }

        return [
            'unread_count' => $unreadCount,
            'items'        => $items
        ];
    }

    /**
     * Mengambil Feed Aktivitas Terpadu Admin (Unified Activity Stream)
     * Menggabungkan Presensi Masuk/Pulang, Pengajuan Izin/Sakit/Cuti, dan Surat Perintah Lembur (SPL)
     */
    public static function getAdminActivityStream(int $limit = 6): array {
        $db = Database::getConnection();
        $feed = [];

        // 1. Presensi Fisik Terbaru (Masuk / Pulang)
        try {
            $stmtPres = $db->query("
                SELECT p.id, p.user_id, p.tanggal, p.jam_masuk, p.jam_pulang, p.total_terlambat, p.status_kehadiran, p.foto_masuk,
                       u.nama AS nama_pegawai, u.nik, u.jabatan, u.foto_profil,
                       l.nama_lokasi,
                       CONCAT(p.tanggal, ' ', COALESCE(p.jam_pulang, p.jam_masuk, '00:00:00')) AS waktu_aktivitas
                FROM `presensi` p
                JOIN `users` u ON p.user_id = u.id
                LEFT JOIN `lokasi_presensi` l ON p.lokasi_id = l.id
                WHERE p.jam_masuk IS NOT NULL
                ORDER BY p.tanggal DESC, COALESCE(p.jam_pulang, p.jam_masuk) DESC
                LIMIT 6
            ");
            $presensiRows = $stmtPres ? $stmtPres->fetchAll(PDO::FETCH_ASSOC) : [];
            foreach ($presensiRows as $p) {
                $isTerlambat = ($p['status_kehadiran'] === 'terlambat' || (!empty($p['total_terlambat']) && $p['total_terlambat'] !== 'Tepat Waktu'));
                $jamAktif = !empty($p['jam_pulang']) ? $p['jam_pulang'] : $p['jam_masuk'];
                $jamDisplay = substr($jamAktif, 0, 5) . ' WIB';
                $tglFmt = date('d M', strtotime($p['tanggal']));
                $isHariIni = ($p['tanggal'] === date('Y-m-d'));

                $feed[] = [
                    'tipe'          => 'presensi',
                    'id'            => $p['id'],
                    'judul'         => $p['nama_pegawai'],
                    'subjudul'      => $p['jabatan'] ?? 'Staff',
                    'lokasi'        => $p['nama_lokasi'] ?? 'Kantor SGM',
                    'jam_masuk'     => !empty($p['jam_masuk']) ? substr($p['jam_masuk'], 0, 5) : '-',
                    'jam_pulang'    => !empty($p['jam_pulang']) ? substr($p['jam_pulang'], 0, 5) : null,
                    'is_terlambat'  => $isTerlambat,
                    'is_hari_ini'   => $isHariIni,
                    'tanggal_text'  => $tglFmt,
                    'badge_text'    => $isTerlambat ? 'Terlambat' : 'Tepat Waktu',
                    'badge_type'    => $isTerlambat ? 'danger' : 'success',
                    'foto_profil'   => $p['foto_profil'] ?? null,
                    'waktu_raw'     => $p['waktu_aktivitas'],
                    'waktu_display' => $jamDisplay,
                    'link'          => Config::baseUrl('laporan/harian')
                ];
            }
        } catch (\Throwable $e) {
            error_log('Error fetching presensi stream: ' . $e->getMessage());
        }

        // 2. Pengajuan Ketidakhadiran Terbaru (Izin / Sakit / Cuti)
        try {
            $stmtIzin = $db->query("
                SELECT k.*,
                       u.nama AS nama_pegawai, u.nik, u.jabatan, u.foto_profil
                FROM `ketidakhadiran` k
                JOIN `users` u ON k.user_id = u.id
                ORDER BY k.created_at DESC
                LIMIT 5
            ");
            $izinRows = $stmtIzin ? $stmtIzin->fetchAll(PDO::FETCH_ASSOC) : [];
            foreach ($izinRows as $k) {
                $kat = strtoupper($k['kategori']);
                $st = strtolower($k['status_approval']);
                $badgeText = ($st === 'disetujui') ? 'Disetujui' : (($st === 'ditolak') ? 'Ditolak' : 'Pending ' . ucfirst($k['kategori']));
                $badgeType = ($st === 'disetujui') ? 'success' : (($st === 'ditolak') ? 'danger' : 'warning');
                $periode = date('d M', strtotime($k['tanggal_mulai']));
                if (!empty($k['tanggal_selesai']) && $k['tanggal_selesai'] !== $k['tanggal_mulai']) {
                    $periode .= ' - ' . date('d M', strtotime($k['tanggal_selesai']));
                }

                $feed[] = [
                    'tipe'          => 'ketidakhadiran',
                    'id'            => $k['id'],
                    'judul'         => $k['nama_pegawai'],
                    'subjudul'      => $k['jabatan'] ?? 'Staff',
                    'kategori'      => $kat,
                    'periode'       => $periode,
                    'keterangan'    => $k['keterangan'] ?? '-',
                    'status'        => $st,
                    'badge_text'    => $badgeText,
                    'badge_type'    => $badgeType,
                    'foto_profil'   => $k['foto_profil'] ?? null,
                    'waktu_raw'     => $k['created_at'] ?? ($k['tanggal_mulai'] . ' 00:00:00'),
                    'waktu_display' => self::timeAgo($k['created_at'] ?? $k['tanggal_mulai']),
                    'link'          => Config::baseUrl('ketidakhadiran')
                ];
            }
        } catch (\Throwable $e) {
            error_log('Error fetching ketidakhadiran stream: ' . $e->getMessage());
        }

        // 3. Surat Perintah Lembur (SPL) Aktif
        try {
            $stmtLembur = $db->query("
                SELECT pl.id, pl.nomor_sp_lembur, pl.lokasi_id, pl.tanggal, pl.jam_mulai, pl.jam_selesai,
                       pl.maksimal_jam, pl.keterangan, pl.status, pl.created_by, pl.created_at, pl.updated_at,
                       l.nama_lokasi,
                       (SELECT COUNT(*) FROM `penugasan_lembur_pegawai` plp WHERE plp.penugasan_lembur_id = pl.id) AS total_pegawai
                FROM `penugasan_lembur` pl
                LEFT JOIN `lokasi_presensi` l ON pl.lokasi_id = l.id
                WHERE pl.status = 'aktif'
                ORDER BY pl.tanggal DESC, pl.created_at DESC
                LIMIT 4
            ");
            $lemburRows = $stmtLembur ? $stmtLembur->fetchAll(PDO::FETCH_ASSOC) : [];
            foreach ($lemburRows as $pl) {
                $tglFmt = date('d M', strtotime($pl['tanggal']));
                $jamMulai = substr($pl['jam_mulai'], 0, 5);
                $jamSelesai = substr($pl['jam_selesai'], 0, 5);

                $feed[] = [
                    'tipe'          => 'lembur',
                    'id'            => $pl['id'],
                    'judul'         => 'SPL ' . $pl['nomor_sp_lembur'],
                    'subjudul'      => ($pl['nama_lokasi'] ?? 'Kantor SGM') . ' • ' . (int)$pl['total_pegawai'] . ' Pegawai Ditugaskan',
                    'keterangan'    => ($pl['keterangan'] ?? 'Penugasan Lembur') . ' (' . $jamMulai . ' - ' . $jamSelesai . ' WIB)',
                    'badge_text'    => 'SPL Aktif',
                    'badge_type'    => 'info',
                    'foto_profil'   => null,
                    'waktu_raw'     => $pl['created_at'] ?? ($pl['tanggal'] . ' ' . $pl['jam_mulai']),
                    'waktu_display' => $tglFmt . ', ' . $jamMulai . ' WIB',
                    'link'          => Config::baseUrl('lembur/detail?id=' . $pl['id'])
                ];
            }
        } catch (\Throwable $e) {
            error_log('Error fetching lembur stream: ' . $e->getMessage());
        }

        // Urutkan kronologis menurun berdasarkan waktu_raw
        usort($feed, function($a, $b) {
            return strcmp($b['waktu_raw'] ?? '', $a['waktu_raw'] ?? '');
        });

        return array_slice($feed, 0, $limit);
    }

    private static function timeAgo(string $datetime): string {
        $timestamp = strtotime($datetime);
        $diff = time() - $timestamp;
        if ($diff < 60) return 'Baru saja';
        if ($diff < 3600) return floor($diff / 60) . ' mnt lalu';
        if ($diff < 86400) return floor($diff / 3600) . ' jam lalu';
        if ($diff < 172800) return 'Kemarin';
        return date('d/m/Y', $timestamp);
    }
}
