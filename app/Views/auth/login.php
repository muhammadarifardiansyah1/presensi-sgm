<?php
require_once __DIR__ . '/../../Config/Config.php';
require_once __DIR__ . '/../../Helpers/Icon.php';

$flashMsg = $_COOKIE['sgm_flash_msg'] ?? '';
if (!empty($flashMsg)) {
    setcookie('sgm_flash_msg', '', time() - 3600, '/');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - <?= Config::APP_TITLE ?></title>

    <!-- Anti-FOUC Early Theme Initializer -->
    <script>
        (function() {
            try {
                var savedTheme = localStorage.getItem('sgm_theme');
                var theme = savedTheme ? savedTheme : 'light';
                document.documentElement.setAttribute('data-theme', theme);
            } catch(e) {}
        })();
    </script>
    <style>
        html { background-color: #f8fafc; color-scheme: light; }
        html[data-theme="dark"] { background-color: #0b0f19; color-scheme: dark; }
    </style>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Base CSS with Auto Cache-Busting -->
    <link rel="stylesheet" href="<?= Config::baseUrl('assets/css/style.css') ?>?v=<?= filemtime(__DIR__ . '/../../../assets/css/style.css') ?>">

    <style>
        :root {
            --login-blue-primary: #2563eb;
            --login-blue-dark: #1d4ed8;
            --login-blue-deep: #1e3a8a;
            --bg-form-panel: #ffffff;
            --bg-page-mobile: #f8fafc;
            --text-heading: #0f172a;
            --text-body: #64748b;
            --text-label: #334155;
            --border-input: #e2e8f0;
            --bg-input: #ffffff;
            --input-placeholder: #94a3b8;
            --card-border: #e2e8f0;
            --card-bg: #ffffff;
            --card-shadow: 0 20px 35px -8px rgba(0, 0, 0, 0.07), 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        [data-theme="dark"] {
            --bg-form-panel: #0b0f19;
            --bg-page-mobile: #070a10;
            --text-heading: #f8fafc;
            --text-body: #94a3b8;
            --text-label: #e2e8f0;
            --border-input: #1e293b;
            --bg-input: #111827;
            --input-placeholder: #64748b;
            --card-border: #1e293b;
            --card-bg: #111827;
            --card-shadow: 0 25px 40px -10px rgba(0, 0, 0, 0.5), 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            background: var(--bg-form-panel);
            color: var(--text-heading);
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* ===== SPLIT LAYOUT WRAPPER ===== */
        .login-split-wrapper {
            display: flex;
            min-height: 100vh;
            width: 100%;
            position: relative;
        }

        /* ===== LEFT PANEL (BRAND HERO SHOWCASE) ===== */
        .login-brand-panel {
            flex: 1.15;
            background: linear-gradient(145deg, #1d4ed8 0%, #2563eb 45%, #1e40af 100%);
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 48px;
            color: #ffffff;
            overflow: hidden;
            z-index: 1;
        }

        /* Decorative Background Circles */
        .brand-decor-circle {
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
        }
        .circle-1 {
            width: 480px;
            height: 480px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.08) 0%, rgba(255, 255, 255, 0) 70%);
            top: -120px;
            left: -120px;
        }
        .circle-2 {
            width: 540px;
            height: 540px;
            background: radial-gradient(circle, rgba(15, 23, 42, 0.35) 0%, rgba(15, 23, 42, 0) 70%);
            bottom: -150px;
            right: -60px;
        }
        .circle-3 {
            width: 260px;
            height: 260px;
            background: rgba(255, 255, 255, 0.04);
            top: 25%;
            right: 8%;
        }

        /* Brand Hero Content Container */
        .brand-hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 460px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .brand-hero-tag {
            font-size: 0.88rem;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.92);
            margin-bottom: 28px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
        }

        .brand-hero-emblem {
            width: 154px;
            height: 154px;
            margin-bottom: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
            border-radius: 50%;
            padding: 8px;
            box-shadow: 0 14px 32px rgba(15, 23, 42, 0.3), 0 0 0 4px rgba(255, 255, 255, 0.25);
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.3s ease;
        }
        .brand-hero-emblem:hover {
            transform: scale(1.05);
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.4), 0 0 0 6px rgba(255, 255, 255, 0.35);
        }
        .brand-hero-logo-img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 50%;
            display: block;
        }

        .brand-hero-name {
            font-size: 1.85rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-bottom: 8px;
            color: #ffffff;
            text-wrap: balance;
            text-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
        }

        .brand-hero-desc {
            font-size: 0.95rem;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.85);
            line-height: 1.5;
            text-wrap: pretty;
        }

        /* Organic Smooth Wave Border along the right side */
        .brand-panel-curve {
            position: absolute;
            top: 0;
            bottom: 0;
            right: -1px;
            width: 80px;
            height: 100%;
            pointer-events: none;
            z-index: 4;
        }
        .brand-panel-curve svg {
            width: 100%;
            height: 100%;
            display: block;
            fill: var(--bg-form-panel);
            transition: fill var(--transition-normal);
        }

        /* ===== RIGHT PANEL (FORM AREA) ===== */
        .login-form-panel {
            flex: 1;
            background: var(--bg-form-panel);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 48px 40px;
            position: relative;
            z-index: 3;
            transition: background var(--transition-normal);
        }

        /* Floating Theme Toggle */
        .login-theme-toggle {
            position: absolute;
            top: 24px;
            right: 28px;
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: var(--card-bg);
            border: 1.5px solid var(--border-input);
            color: var(--text-heading);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transition: transform 0.15s ease, background 0.15s ease, border-color 0.15s ease;
            z-index: 10;
        }
        .login-theme-toggle:hover {
            transform: scale(1.08);
            border-color: var(--login-blue-primary);
        }
        .login-theme-toggle .theme-icon-light { display: none; }
        .login-theme-toggle .theme-icon-dark { display: inline-block; }
        [data-theme="dark"] .login-theme-toggle .theme-icon-light { display: inline-block; }
        [data-theme="dark"] .login-theme-toggle .theme-icon-dark { display: none; }

        /* Login Card Container */
        .login-card-container {
            width: 100%;
            max-width: 430px;
        }

        /* Mobile Brand Header (Visible ONLY on mobile <= 960px) */
        .mobile-brand-header {
            display: none;
            align-items: center;
            gap: 14px;
            margin-bottom: 28px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-input);
        }
        .mobile-brand-emblem {
            width: 48px;
            height: 48px;
            flex-shrink: 0;
            background: #ffffff;
            border-radius: 50%;
            padding: 3px;
            border: 1px solid var(--border-input);
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .mobile-brand-logo-img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 50%;
            display: block;
        }
        .mobile-brand-title {
            font-size: 1rem;
            font-weight: 800;
            color: var(--text-heading);
            letter-spacing: -0.3px;
        }
        .mobile-brand-subtitle {
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--text-body);
            margin-top: 2px;
        }

        /* Header Group */
        .login-header-group {
            margin-bottom: 28px;
        }
        .login-title {
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--text-heading);
            letter-spacing: -0.6px;
            margin-bottom: 8px;
        }
        .login-subtitle {
            font-size: 0.92rem;
            color: var(--text-body);
            font-weight: 500;
            line-height: 1.5;
            text-wrap: pretty;
        }

        /* Alert Error Banner */
        .login-alert-error {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 0.88rem;
            font-weight: 600;
            line-height: 1.4;
        }
        [data-theme="dark"] .login-alert-error {
            background: rgba(239, 68, 68, 0.12);
            border-color: rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        /* Form Body & Inputs */
        .login-form-body {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-group-custom {
            display: flex;
            flex-direction: column;
        }

        .form-label-custom {
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--text-label);
            margin-bottom: 8px;
        }

        .input-icon-container {
            position: relative;
            display: flex;
            align-items: center;
            width: 100%;
        }

        .input-left-icon {
            position: absolute;
            left: 14px;
            color: var(--input-placeholder);
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
            transition: color 0.15s ease;
            z-index: 2;
        }

        .form-control-custom {
            width: 100%;
            height: 52px;
            padding: 12px 16px 12px 44px;
            border-radius: 12px;
            border: 1.5px solid var(--border-input);
            background: var(--bg-input);
            color: var(--text-heading);
            font-size: 0.95rem;
            font-family: inherit;
            transition: border-color 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
        }

        .form-control-custom::placeholder {
            color: var(--input-placeholder);
            font-weight: 400;
        }

        .form-control-custom:focus {
            outline: none;
            border-color: var(--login-blue-primary);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
        }

        .input-icon-container:focus-within .input-left-icon {
            color: var(--login-blue-primary);
        }

        /* Password specific input */
        .input-icon-container .form-control-password {
            padding-right: 48px;
        }

        .btn-toggle-password {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            width: 38px;
            height: 38px;
            border-radius: 8px;
            background: transparent;
            border: none;
            color: var(--input-placeholder);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: color 0.15s ease, background 0.15s ease;
            z-index: 2;
        }
        .btn-toggle-password:hover {
            color: var(--text-heading);
            background: rgba(0, 0, 0, 0.05);
        }
        [data-theme="dark"] .btn-toggle-password:hover {
            background: rgba(255, 255, 255, 0.08);
        }

        /* Checkbox Row */
        .form-extra-row {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            margin-top: -2px;
        }

        .custom-checkbox-label {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            font-size: 0.88rem;
            font-weight: 500;
            color: var(--text-body);
            user-select: none;
        }

        .custom-checkbox-label input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            height: 0;
            width: 0;
        }

        .checkbox-box {
            width: 19px;
            height: 19px;
            border: 1.8px solid #cbd5e1;
            border-radius: 5px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--bg-input);
            transition: all 0.15s ease;
            flex-shrink: 0;
        }
        [data-theme="dark"] .checkbox-box {
            border-color: #334155;
        }

        .custom-checkbox-label:hover .checkbox-box {
            border-color: var(--login-blue-primary);
        }

        .custom-checkbox-label input:checked ~ .checkbox-box {
            background: var(--login-blue-primary);
            border-color: var(--login-blue-primary);
        }

        .custom-checkbox-label input:checked ~ .checkbox-box::after {
            content: '';
            width: 5px;
            height: 9px;
            border: solid white;
            border-width: 0 2.2px 2.2px 0;
            transform: rotate(45deg) translate(-0.5px, -1px);
        }

        /* Submit Button */
        .btn-primary-login {
            width: 100%;
            height: 52px;
            padding: 0 24px;
            margin-top: 6px;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #ffffff;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            font-family: inherit;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            cursor: pointer;
            box-shadow: 0 10px 24px -4px rgba(37, 99, 235, 0.45);
            transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
        }

        .btn-primary-login:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
            transform: translateY(-2px);
            box-shadow: 0 14px 28px -4px rgba(37, 99, 235, 0.55);
        }

        .btn-primary-login:active {
            transform: scale(0.98);
            box-shadow: 0 6px 16px -2px rgba(37, 99, 235, 0.35);
        }

        /* ===== RESPONSIVE MOBILE VIEW (MAX-WIDTH: 960PX) ===== */
        @media (max-width: 960px) {
            .login-brand-panel {
                display: none;
            }

            .login-split-wrapper {
                background: var(--bg-page-mobile);
                align-items: center;
                justify-content: center;
                padding: 32px 18px;
            }

            .login-form-panel {
                background: transparent;
                padding: 0;
                width: 100%;
                max-width: 440px;
                flex: none;
            }

            .login-card-container {
                background: var(--card-bg);
                border: 1px solid var(--card-border);
                border-radius: 24px;
                padding: 38px 30px;
                box-shadow: var(--card-shadow);
            }

            .mobile-brand-header {
                display: flex;
            }

            .login-title {
                font-size: 1.85rem;
            }

            .login-theme-toggle {
                top: 16px;
                right: 16px;
            }
        }

        @media (max-width: 420px) {
            .login-card-container {
                padding: 30px 20px;
                border-radius: 20px;
            }
            .login-title {
                font-size: 1.65rem;
            }
        }
    </style>
</head>
<body>

<div class="login-split-wrapper">
    <!-- ======================================================== -->
    <!-- LEFT PANEL: Brand Hero Showcase (Desktop View)            -->
    <!-- ======================================================== -->
    <section class="login-brand-panel" aria-label="Informasi Perusahaan">
        <div class="brand-decor-circle circle-1"></div>
        <div class="brand-decor-circle circle-2"></div>
        <div class="brand-decor-circle circle-3"></div>

        <div class="brand-hero-content">
            <span class="brand-hero-tag">APLIKASI PRESENSI &amp; PENGGAJIAN</span>

            <!-- Official Logo -->
            <div class="brand-hero-emblem">
                <img src="<?= Config::baseUrl('assets/images/logo.png') ?>?v=<?= filemtime(__DIR__ . '/../../../assets/images/logo.png') ?>" alt="Logo <?= Config::APP_NAME ?>" class="brand-hero-logo-img">
            </div>

            <h1 class="brand-hero-name"><?= Config::APP_NAME ?></h1>
            <p class="brand-hero-desc">Sistem Informasi Presensi &amp; Penggajian Karyawan</p>
        </div>

        <!-- Smooth Organic Wave Curve on the Right Edge -->
        <div class="brand-panel-curve" aria-hidden="true">
            <svg viewBox="0 0 100 1000" preserveAspectRatio="none">
                <path d="M40,0 C90,180 90,320 40,500 C10,680 60,850 20,1000 L100,1000 L100,0 Z"></path>
            </svg>
        </div>
    </section>

    <!-- ======================================================== -->
    <!-- RIGHT PANEL: Login Form Area (Responsive Desktop & Mobile) -->
    <!-- ======================================================== -->
    <main class="login-form-panel">
        <!-- Floating Theme Toggle (Light / Dark Mode) -->
        <button type="button" class="login-theme-toggle" id="theme-toggle" title="Ganti Tema" aria-label="Ganti Tema">
            <span class="theme-icon-dark"><?= Icon::get('moon', 18) ?></span>
            <span class="theme-icon-light"><?= Icon::get('sun', 18) ?></span>
        </button>

        <div class="login-card-container">
            <!-- Mobile Brand Header (Visible ONLY on Mobile View <= 960px) -->
            <div class="mobile-brand-header">
                <div class="mobile-brand-emblem">
                    <img src="<?= Config::baseUrl('assets/images/logo.png') ?>?v=<?= filemtime(__DIR__ . '/../../../assets/images/logo.png') ?>" alt="Logo <?= Config::APP_NAME ?>" class="mobile-brand-logo-img">
                </div>
                <div class="mobile-brand-info">
                    <div class="mobile-brand-title"><?= Config::APP_NAME ?></div>
                    <div class="mobile-brand-subtitle">Sistem Presensi &amp; Penggajian</div>
                </div>
            </div>

            <!-- Login Form Title & Subtitle -->
            <div class="login-header-group">
                <h2 class="login-title">Masuk</h2>
                <p class="login-subtitle">Masuk ke akun Anda untuk mengakses sistem informasi presensi</p>
            </div>

            <!-- Flash Error / Info Message -->
            <?php if (!empty($error)): ?>
                <div class="login-alert-error" role="alert">
                    <?= Icon::get('alert-circle', 18) ?>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php elseif (!empty($flashMsg)): ?>
                <div class="login-alert-error" role="alert" style="background: rgba(37,99,235,0.08); border: 1px solid rgba(37,99,235,0.25); color: #1d4ed8;">
                    <?= Icon::get('info', 18) ?>
                    <span><?= htmlspecialchars($flashMsg) ?></span>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" action="<?= Config::baseUrl('login') ?>" class="login-form-body">
                <!-- Username Input -->
                <div class="form-group-custom">
                    <label class="form-label-custom" for="username">Username</label>
                    <div class="input-icon-container">
                        <span class="input-left-icon" aria-hidden="true">
                            <?= Icon::get('user', 18) ?>
                        </span>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            class="form-control-custom" 
                            placeholder="Masukkan username Anda" 
                            required 
                            autofocus 
                            autocomplete="username"
                        >
                    </div>
                </div>

                <!-- Password Input -->
                <div class="form-group-custom">
                    <label class="form-label-custom" for="password">Password</label>
                    <div class="input-icon-container">
                        <span class="input-left-icon" aria-hidden="true">
                            <?= Icon::get('lock', 18) ?>
                        </span>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-control-custom form-control-password" 
                            placeholder="Masukkan password Anda" 
                            required 
                            autocomplete="current-password"
                        >
                        <button 
                            type="button" 
                            class="btn-toggle-password" 
                            id="login-password-toggle"
                            title="Tampilkan / Sembunyikan password" 
                            aria-label="Tampilkan atau sembunyikan password"
                        >
                            <span class="eye-open"><?= Icon::get('eye', 18) ?></span>
                            <span class="eye-closed" style="display:none;"><?= Icon::get('eye-off', 18) ?></span>
                        </button>
                    </div>
                </div>

                <!-- Remember Me Checkbox (Checked by default) -->
                <div class="form-extra-row">
                    <label class="custom-checkbox-label">
                        <input type="checkbox" name="remember" value="1" checked>
                        <span class="checkbox-box" aria-hidden="true"></span>
                        <span class="checkbox-text">Ingat saya selama 30 hari</span>
                    </label>
                </div>

                <!-- Submit Button with Arrow -->
                <button type="submit" class="btn-primary-login" id="btn-submit-login">
                    <span>Masuk ke Aplikasi</span>
                    <?= Icon::get('arrow-right', 18) ?>
                </button>
            </form>
        </div>
    </main>
</div>

<!-- Password Visibility & Theme Handling Script -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Password Visibility Toggle
    const toggleBtn = document.getElementById('login-password-toggle');
    const passwordInput = document.getElementById('password');
    if (toggleBtn && passwordInput) {
        toggleBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
            const eyeOpen = toggleBtn.querySelector('.eye-open');
            const eyeClosed = toggleBtn.querySelector('.eye-closed');
            if (eyeOpen && eyeClosed) {
                eyeOpen.style.display = isPassword ? 'none' : 'inline-block';
                eyeClosed.style.display = isPassword ? 'inline-block' : 'none';
            }
        });
    }
});
</script>

<script src="<?= Config::baseUrl('assets/js/app.js') ?>?v=<?= filemtime(__DIR__ . '/../../../assets/js/app.js') ?>"></script>
</body>
</html>
