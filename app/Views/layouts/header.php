<?php
require_once __DIR__ . '/../../Config/Config.php';
require_once __DIR__ . '/../../Controllers/AuthController.php';
require_once __DIR__ . '/../../Helpers/NotificationHelper.php';
require_once __DIR__ . '/../../Helpers/Icon.php';

if (!isset($currentUser)) {
    $currentUser = AuthController::checkAuth();
}

$notificationsData = NotificationHelper::getNotifications($currentUser);
$unreadCount = $notificationsData['unread_count'] ?? 0;
$notificationList = $notificationsData['items'] ?? [];
$activePage = $activePage ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? Config::APP_TITLE) ?></title>

    <!-- Anti-FOUC Early Theme Initializer (Mencegah Glitch Layar Putih saat Navigasi Dark Mode) -->
    <script>
        (function() {
            try {
                var savedTheme = localStorage.getItem('sgm_theme');
                var theme = savedTheme ? savedTheme : (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                document.documentElement.setAttribute('data-theme', theme);
            } catch(e) {}
        })();
    </script>
    <style>
        /* Instant canvas rendering before stylesheets parse */
        html { background-color: #f0f4f8; color-scheme: light; }
        html[data-theme="dark"] { background-color: #0b0f19; color-scheme: dark; }
    </style>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@500;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Leaflet CSS for Map (Local Asset) -->
    <link rel="stylesheet" href="<?= Config::baseUrl('assets/vendor/leaflet/leaflet.css') ?>"/>
    
    <!-- Custom Style with Auto Cache-Busting -->
    <link rel="stylesheet" href="<?= Config::baseUrl('assets/css/style.css') ?>?v=<?= filemtime(__DIR__ . '/../../../assets/css/style.css') ?>">
</head>
<body>

<header class="main-header">
    <div class="header-top">
        <div class="brand-badge">
            <button class="mobile-nav-toggle" id="mobile-nav-toggle" aria-label="Toggle Navigation"><?= Icon::get('menu', 20) ?></button>
            <a href="<?= Config::baseUrl('dashboard') ?>" style="display:flex;align-items:center;gap:10px;">
                <div class="brand-logo-wrapper">
                    <img src="<?= Config::baseUrl('assets/images/logo.png') ?>?v=<?= filemtime(__DIR__ . '/../../../assets/images/logo.png') ?>" alt="Logo <?= Config::APP_NAME ?>" class="brand-logo-img">
                </div>
                <div class="brand-text">
                    <h1><?= Config::APP_NAME ?></h1>
                    <span><?= Config::APP_TAGLINE ?></span>
                </div>
            </a>
        </div>

        <div class="header-actions">
            <!-- Dark Mode Toggle -->
            <button class="theme-toggle-btn" id="theme-toggle" title="Ganti Tema" type="button">
                <span class="theme-icon-dark"><?= Icon::get('moon', 19) ?></span>
                <span class="theme-icon-sun"><?= Icon::get('sun', 19) ?></span>
            </button>

            <!-- Notifications Dropdown -->
            <div class="dropdown-wrapper notification-wrapper">
                <button class="icon-btn" id="notification-toggle" type="button" aria-expanded="false" title="Notifikasi">
                    <?= Icon::get('bell', 19) ?>
                    <?php if ($unreadCount > 0): ?>
                        <span class="notification-badge-dot" title="<?= $unreadCount ?> Notifikasi Baru"></span>
                    <?php endif; ?>
                </button>
                <div class="dropdown-panel notification-panel" id="notification-panel">
                    <div class="dropdown-panel-header">
                        <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                            <span style="font-weight: 700; font-size: 0.95rem; color: var(--text-main); display: inline-flex; align-items: center; gap: 6px;">
                                <?= Icon::get('bell', 16) ?> Notifikasi
                            </span>
                            <?php if ($unreadCount > 0): ?>
                                <span class="badge badge-danger" style="font-size: 0.72rem; padding: 2px 8px; border-radius: 12px;"><?= $unreadCount ?> Baru</span>
                            <?php else: ?>
                                <span style="font-size: 0.75rem; color: var(--text-muted);">Semua dibaca</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="notification-panel-body">
                        <?php if (empty($notificationList)): ?>
                            <div class="dropdown-empty-state">
                                <span style="font-size: 1.8rem; display: block; margin-bottom: 6px;"><?= Icon::get('bell', 28) ?></span>
                                <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0;">Tidak ada notifikasi baru saat ini.</p>
                            </div>
                        <?php else: ?>
                            <div class="notification-list">
                                <?php foreach ($notificationList as $notif): ?>
                                    <a href="<?= $notif['link'] ?>" class="notification-item">
                                        <div class="notification-item-icon">
                                            <?= $notif['icon'] ?>
                                        </div>
                                        <div class="notification-item-content">
                                            <div class="notification-item-title">
                                                <span><?= htmlspecialchars($notif['title']) ?></span>
                                                <span class="notification-item-badge badge-<?= $notif['type'] ?>"><?= htmlspecialchars($notif['badge']) ?></span>
                                            </div>
                                            <div class="notification-item-message"><?= htmlspecialchars($notif['message']) ?></div>
                                            <div class="notification-item-time"><?= htmlspecialchars($notif['time']) ?></div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="dropdown-panel-footer">
                        <?php if (($currentUser['role'] ?? '') === 'admin'): ?>
                            <a href="<?= Config::baseUrl('ketidakhadiran') ?>" class="dropdown-footer-link">Tinjau Pengajuan Ketidakhadiran →</a>
                        <?php else: ?>
                            <a href="<?= Config::baseUrl('ketidakhadiran') ?>" class="dropdown-footer-link">Lihat Riwayat Izin & Cuti →</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- User Profile Dropdown -->
            <?php 
                $userPhoto = $currentUser['foto_profil'] ?? $currentUser['foto'] ?? null;
                $photoPath = __DIR__ . '/../../../assets/images/uploads/profil/' . $userPhoto;
                $hasPhoto  = !empty($userPhoto) && file_exists($photoPath);
            ?>
            <div class="dropdown-wrapper profile-wrapper">
                <button class="user-profile-pill" id="profile-toggle" type="button" aria-expanded="false" title="Menu Profil">
                    <div class="user-avatar">
                        <?php if ($hasPhoto): ?>
                            <img src="<?= Config::baseUrl('assets/images/uploads/profil/' . $userPhoto) ?>" alt="Foto" style="width:100%;height:100%;border-radius:50%;object-fit:cover;display:block;">
                        <?php else: ?>
                            <?= strtoupper(substr($currentUser['nama'] ?? 'U', 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                    <div class="user-meta">
                        <span class="user-name"><?= htmlspecialchars($currentUser['username'] ?? 'User') ?></span>
                        <span class="user-role"><?= htmlspecialchars($currentUser['jabatan'] ?? ucfirst($currentUser['role'] ?? 'pegawai')) ?></span>
                    </div>
                    <span class="profile-caret"><?= Icon::get('chevron-down', 13) ?></span>
                </button>
                <div class="dropdown-panel profile-panel" id="profile-panel">
                    <div class="profile-panel-user">
                        <div class="profile-panel-avatar">
                            <?php if ($hasPhoto): ?>
                                <img src="<?= Config::baseUrl('assets/images/uploads/profil/' . $userPhoto) ?>" alt="Foto" style="width:100%;height:100%;border-radius:50%;object-fit:cover;display:block;">
                            <?php else: ?>
                                <?= strtoupper(substr($currentUser['nama'] ?? 'U', 0, 1)) ?>
                            <?php endif; ?>
                        </div>
                        <div class="profile-panel-info">
                            <strong class="profile-panel-name"><?= htmlspecialchars($currentUser['nama'] ?? 'User') ?></strong>
                            <span class="profile-panel-sub"><?= htmlspecialchars($currentUser['jabatan'] ?? ucfirst($currentUser['role'] ?? 'pegawai')) ?></span>
                            <span class="badge <?= (($currentUser['role'] ?? '') === 'admin') ? 'badge-primary' : 'badge-info' ?>" style="font-size: 0.7rem; padding: 2px 8px; border-radius: 10px; margin-top: 4px; display: inline-block;">
                                <?= (($currentUser['role'] ?? '') === 'admin') ? 'Super Admin' : 'Pegawai' ?>
                            </span>
                        </div>
                    </div>
                    <div class="dropdown-divider"></div>
                    <div class="profile-panel-menu">
                        <a href="<?= Config::baseUrl('profil') ?>" class="profile-menu-link">
                            <span class="profile-menu-icon"><?= Icon::get('user', 18) ?></span>
                            <div class="profile-menu-text">
                                <span class="profile-menu-title">Profil Saya</span>
                                <span class="profile-menu-desc">Edit data diri & info akun</span>
                            </div>
                        </a>
                        <a href="<?= Config::baseUrl('profil#ganti-password') ?>" class="profile-menu-link">
                            <span class="profile-menu-icon"><?= Icon::get('lock', 18) ?></span>
                            <div class="profile-menu-text">
                                <span class="profile-menu-title">Ganti Password</span>
                                <span class="profile-menu-desc">Keamanan & kata sandi</span>
                            </div>
                        </a>
                    </div>
                    <div class="dropdown-divider"></div>
                    <div class="dropdown-panel-footer">
                        <a href="<?= Config::baseUrl('logout') ?>" class="dropdown-logout-link" onclick="return confirm('Apakah Anda yakin ingin keluar?');">
                            <?= Icon::get('logout', 16) ?> Keluar dari Sistem
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Menu Bar -->
    <nav class="nav-bar" id="nav-bar">
        <ul class="nav-menu">
            <li class="nav-item <?= ($activePage === 'dashboard') ? 'active' : '' ?>">
                <a href="<?= Config::baseUrl('dashboard') ?>" class="nav-link">
                    <?= Icon::get('home', 18) ?>
                    <span>Home</span>
                </a>
            </li>

            <?php if (($currentUser['role'] ?? '') === 'admin'): ?>
                <li class="nav-item <?= ($activePage === 'pegawai') ? 'active' : '' ?>">
                    <a href="<?= Config::baseUrl('pegawai') ?>" class="nav-link">
                        <?= Icon::get('users', 18) ?>
                        <span>Pegawai</span>
                    </a>
                </li>

                <li class="nav-item dropdown <?= strpos($activePage, 'master') !== false ? 'active' : '' ?>">
                    <a href="javascript:void(0)" class="nav-link dropdown-toggle" role="button">
                        <?= Icon::get('database', 18) ?>
                        <span>Master Data</span>
                        <?= Icon::get('chevron-down', 13, 'nav-chevron') ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a href="<?= Config::baseUrl('master/jabatan') ?>" class="dropdown-item">Jabatan</a></li>
                        <li><a href="<?= Config::baseUrl('master/lokasi') ?>" class="dropdown-item">Lokasi Presensi</a></li>
                        <li><a href="<?= Config::baseUrl('master/storage') ?>" class="dropdown-item">Penyimpanan Foto</a></li>
                    </ul>
                </li>
            <?php endif; ?>

            <li class="nav-item dropdown <?= strpos($activePage, 'laporan') !== false ? 'active' : '' ?>">
                <a href="javascript:void(0)" class="nav-link dropdown-toggle" role="button">
                    <?= Icon::get('clipboard-check', 18) ?>
                    <span>Rekap Presensi</span>
                    <?= Icon::get('chevron-down', 13, 'nav-chevron') ?>
                </a>
                <ul class="dropdown-menu">
                    <li><a href="<?= Config::baseUrl('laporan/harian') ?>" class="dropdown-item">Rekap Harian</a></li>
                    <li><a href="<?= Config::baseUrl('laporan/bulanan') ?>" class="dropdown-item">Rekap Bulanan</a></li>
                </ul>
            </li>

            <li class="nav-item <?= ($activePage === 'ketidakhadiran') ? 'active' : '' ?>">
                <a href="<?= Config::baseUrl('ketidakhadiran') ?>" class="nav-link">
                    <?= Icon::get('clipboard-x', 18) ?>
                    <span>Ketidak Hadiran</span>
                </a>
            </li>

            <?php if (($currentUser['role'] ?? '') === 'admin'): ?>
                <li class="nav-item <?= ($activePage === 'gaji') ? 'active' : '' ?>">
                    <a href="<?= Config::baseUrl('gaji') ?>" class="nav-link">
                        <?= Icon::get('wallet', 18) ?>
                        <span>Penggajian</span>
                    </a>
                </li>
            <?php endif; ?>

            <li class="nav-item">
                <a href="<?= Config::baseUrl('logout') ?>" class="nav-link" onclick="return confirm('Apakah Anda yakin ingin keluar?');">
                    <?= Icon::get('logout', 18) ?>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </nav>
</header>
<main class="container">
