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
        }

        return [
            'unread_count' => $unreadCount,
            'items'        => $items
        ];
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
