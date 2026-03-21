<?php
/**
 * SVMS - Login Page
 * Lyceum of Subic Bay
 */

session_name('SVMS_SESSION');
session_start();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// If already logged in, redirect
if (isLoggedIn()) {
    header('Location: ' . getRoleRedirect($_SESSION['role']));
    exit;
}

// Handle login — supports both AJAX (JSON) and normal POST
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $user = authenticateUser($username, $password);
        if ($user) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'redirect' => getRoleRedirect($user['role'])]);
                exit;
            }
            header('Location: ' . getRoleRedirect($user['role']));
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $error]);
        exit;
    }
}

$msg      = $_GET['msg']   ?? '';
$errParam = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Student Violation Management System - Lyceum of Subic Bay">
    <title>Login - SVMS | Lyceum of Subic Bay</title>
    <link rel="manifest" href="<?= getManifestDataUri() ?>">
    <meta name="theme-color" content="#130117">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="SVMS">
    <link rel="icon" href="assets/img/logo.png" type="image/png">
    <link rel="apple-touch-icon" href="assets/img/logo.png">
    <link rel="preload" href="assets/font/Chillax-Variable.ttf" as="font" type="font/ttf" crossorigin>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        @font-face {
            font-family: 'Chillax';
            src: url('assets/font/Chillax-Variable.ttf') format('truetype');
            font-weight: 200 700;
            font-display: swap;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { -webkit-font-smoothing: antialiased; }

        :root {
            --primary:           #130117;
            --primary-container: #2e1731;
            --accent:            #ff5900;
            --accent-dark:       #e04e00;
            --surface:           #fdf8fd;
            --surface-high:      #ebe7ec;
            --on-surface:        #1c1b1f;
            --on-surface-var:    #4c444b;
            --outline:           #7e747c;
            --outline-var:       #cfc3cb;
        }


        body {
            font-family: 'Chillax', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(145deg, #130117 0%, #2e1731 100%);
            color: var(--on-surface);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 16px;
            position: relative;
            overflow: hidden;
        }

        /* Same blob decorations on the page bg */
        body::before {
            content: '';
            position: fixed;
            top: -15%;
            right: -10%;
            width: 600px;
            height: 600px;
            background: rgba(107, 61, 112, 0.4);
            border-radius: 50%;
            filter: blur(120px);
            pointer-events: none;
        }
        body::after {
            content: '';
            position: fixed;
            bottom: -10%;
            left: -8%;
            width: 450px;
            height: 450px;
            background: rgba(255, 89, 0, 0.07);
            border-radius: 50%;
            filter: blur(100px);
            pointer-events: none;
        }

        /* ── Outer container ── */
        .login-container {
            width: 100%;
            max-width: 1200px;
            min-height: 680px;
            background: #ffffff;
            box-shadow: 0 8px 48px rgba(19, 1, 23, 0.10);
            display: flex;
            border: 1.5px solid rgba(255, 255, 255, 0.25);
            position: relative;
            z-index: 1;
        }

        /* ── Left: Branding ── */
        .login-left {
            flex: 1;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 48px 48px;
        }

        /* Video background */
        .login-left .bg-video {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 0;
        }

        /* Gradient color overlay on top of video */
        .login-left .bg-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(145deg, rgba(19, 1, 23, 0.88) 0%, rgba(46, 23, 49, 0.82) 100%);
            z-index: 1;
        }

        /* Decorative blobs — on top of overlay */
        .login-left::before {
            content: '';
            position: absolute;
            top: -15%;
            right: -15%;
            width: 400px;
            height: 400px;
            background: rgba(107, 61, 112, 0.35);
            border-radius: 50%;
            filter: blur(90px);
            pointer-events: none;
            z-index: 2;
        }
        .login-left::after {
            content: '';
            position: absolute;
            bottom: -10%;
            left: -10%;
            width: 300px;
            height: 300px;
            background: rgba(255, 89, 0, 0.08);
            border-radius: 50%;
            filter: blur(80px);
            pointer-events: none;
            z-index: 2;
        }

        .brand-top {
            position: relative;
            z-index: 3;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-logo {
            height: 48px;
            width: auto;
            object-fit: contain;
        }

        .brand-center {
            position: relative;
            z-index: 3;
        }

        .brand-center h1 {
            font-family: 'Manrope', sans-serif;
            font-size: clamp(28px, 3.5vw, 42px);
            font-weight: 800;
            color: #fff;
            line-height: 1.15;
            letter-spacing: -0.03em;
            margin-bottom: 16px;
        }

        .brand-center p {
            font-size: 14px;
            color: rgba(255,255,255,0.55);
            line-height: 1.7;
        }

        .brand-footer {
            position: relative;
            z-index: 3;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-footer .divider {
            width: 32px;
            height: 1px;
            background: rgba(255,255,255,0.18);
        }

        .brand-footer span {
            font-size: 10px;
            color: rgba(255,255,255,0.28);
            text-transform: uppercase;
            letter-spacing: 0.12em;
        }

        /* ── Right: Form ── */
        .login-right {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 48px;
            background: #ffffff;
            border-left: 1px solid #ede9ee;
        }

        .form-wrap {
            width: 100%;
            max-width: 320px;
        }

        .form-header {
            margin-bottom: 32px;
        }

        .form-header h2 {
            font-family: 'Manrope', sans-serif;
            font-size: 26px;
            font-weight: 800;
            color: var(--on-surface);
            letter-spacing: -0.02em;
            margin-bottom: 6px;
        }

        .form-header p {
            font-size: 13px;
            color: var(--on-surface-var);
            line-height: 1.5;
        }

        /* Alerts */
        .login-alert {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 14px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 20px;
            background: #f0f0f0;
            color: var(--on-surface);
        }
        .login-alert.alert-error   { border-left: 3px solid #dc2626; }
        .login-alert.alert-success { border-left: 3px solid #16a34a; }
        .login-alert.alert-warn    { border-left: 3px solid #d97706; }

        /* Fields */
        .field-group {
            margin-bottom: 18px;
        }

        .field-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--on-surface-var);
            margin-bottom: 7px;
        }

        .input-wrap {
            display: flex;
            align-items: center;
            background: #f0f0f0;
            border: none;
            border-radius: 12px;
            overflow: hidden;
            transition: box-shadow 0.2s ease, background 0.2s ease;
        }

        .input-wrap:focus-within {
            box-shadow: 0 0 0 2px var(--primary-container);
            background: #e8e8e8;
        }

        .input-wrap .icon {
            padding: 0 4px 0 14px;
            display: flex;
            align-items: center;
            color: var(--outline);
            transition: color 0.2s ease;
        }

        .input-wrap:focus-within .icon {
            color: var(--primary-container);
        }

        .input-wrap .icon i {
            font-size: 17px;
        }

        .input-wrap input {
            flex: 1;
            border: none;
            background: transparent;
            padding: 13px 12px;
            font-family: 'Chillax', 'Inter', sans-serif;
            font-size: 14px;
            color: var(--on-surface);
            outline: none;
        }

        .input-wrap input::placeholder {
            color: var(--outline);
        }

        .toggle-pass {
            background: none;
            border: none;
            color: var(--outline);
            padding: 0 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            transition: color 0.15s ease;
        }

        .toggle-pass:hover { color: var(--on-surface); }

        .toggle-pass i { font-size: 17px; }

        /* Submit */
        .submit-btn {
            width: 100%;
            padding: 14px;
            margin-top: 8px;
            background: linear-gradient(135deg, #130117 0%, #2e1731 100%);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-family: 'Manrope', sans-serif;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.25s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            letter-spacing: 0.01em;
            box-shadow: 0 8px 24px rgba(19, 1, 23, 0.18);
        }

        .submit-btn:hover {
            box-shadow: 0 12px 32px rgba(19, 1, 23, 0.28);
            transform: translateY(-1px);
        }

        .submit-btn:active {
            transform: scale(0.98);
        }

        .submit-btn .arrow {
            font-size: 20px;
            transition: transform 0.2s ease;
        }

        .submit-btn:hover .arrow {
            transform: translateX(3px);
        }

        .submit-btn .spinner {
            display: none;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }

        .submit-btn.loading .btn-label,
        .submit-btn.loading .arrow { display: none; }
        .submit-btn.loading .spinner { display: inline-block; }

        @keyframes spin { to { transform: rotate(360deg); } }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%       { transform: translateX(-6px); }
            40%       { transform: translateX(6px); }
            60%       { transform: translateX(-4px); }
            80%       { transform: translateX(4px); }
        }

        .shake { animation: shake 0.45s ease; }

        /* Footer */
        .form-footer {
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid var(--outline-var);
            text-align: center;
            font-size: 13px;
            color: var(--on-surface-var);
        }

        .form-footer a {
            color: var(--primary-container);
            font-weight: 600;
            text-decoration: none;
        }

        .form-footer a:hover { text-decoration: underline; }

        .mobile-brand-footer { display: none; }

        .mobile-video-bg {
            display: none;
        }

        @media (max-width: 768px) {
            /* Full-screen video fixed behind everything */
            .mobile-video-bg {
                display: block;
                position: fixed;
                inset: 0;
                width: 100%;
                height: 100%;
                object-fit: cover;
                z-index: 0;
            }

            /* Dark overlay on top of video */
            .mobile-video-bg + .mobile-video-overlay {
                display: block;
                position: fixed;
                inset: 0;
                background: linear-gradient(160deg, rgba(19,1,23,0.75) 0%, rgba(46,23,49,0.68) 100%);
                z-index: 1;
            }
        }

        /* ── Tablet: switch to single column at 900px ── */
        @media (max-width: 900px) and (min-width: 769px) {
            body { padding: 24px 16px; }

            .login-container {
                flex-direction: column;
                max-width: 480px;
                min-height: auto;
                border-radius: 12px;
            }

            .login-left {
                padding: 36px 36px 28px;
                min-height: 220px;
            }

            .login-right {
                padding: 32px 36px 40px;
                border-left: none;
                border-top: 1px solid #ede9ee;
            }

            .form-wrap { max-width: 100%; }
        }

        /* ── Mobile video bg ── */
        .mobile-video-overlay { display: none; }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            body {
                padding: 0;
                align-items: stretch;
                min-height: 100dvh;
                background: #130117;
                flex-direction: column;
            }

            /* Full-page flex column: logo → form → footer */
            .login-container {
                flex-direction: column;
                flex: 1;
                min-height: 100dvh;
                border-radius: 0 !important;
                box-shadow: none !important;
                border: none !important;
                background: transparent !important;
                position: relative;
                z-index: 2;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 24px;
                padding: 48px 0 32px;
                animation: none !important;
                transform: none !important;
                overflow: visible;
            }

            /* Ensure form-wrap children are visible without animation delay */
            .login-right .form-wrap {
                opacity: 1 !important;
                animation: none !important;
                transform: none !important;
            }

            /* Hide the in-panel video — using fixed bg instead */
            .login-left .bg-video,
            .login-left .bg-overlay { display: none; }

            /* Logo section — centered at top */
            .login-left {
                flex: none;
                width: 100%;
                padding: 0 24px;
                min-height: auto;
                background: transparent;
                overflow: visible;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
            }

            .brand-top {
                padding: 0;
                flex-direction: column;
                align-items: center;
                gap: 0;
                opacity: 1 !important;
                animation: none !important;
                transform: none !important;
            }

            .brand-logo { height: 64px; }

            /* Show brand text under logo, centered */
            .brand-center {
                display: block;
                text-align: center;
                margin-top: 14px;
                width: 100%;
            }

            .brand-center h1 {
                font-size: 20px;
                margin-bottom: 8px;
                line-height: 1.25;
            }

            .brand-center p {
                font-size: 12px;
                color: rgba(255,255,255,0.55);
                line-height: 1.6;
            }

            .brand-footer { display: none; }

            /* Form card */
            .login-right {
                flex: none;
                width: 100%;
                padding: 0 24px;
                border: none;
                background: transparent !important;
                justify-content: center;
                align-items: center;
            }

            .form-wrap {
                width: 100%;
                max-width: 100%;
                background: #ffffff !important;
                border-radius: 12px;
                padding: 28px 24px 32px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
                border: none;
                isolation: isolate;
            }

            .form-header h2 { font-size: 24px; }

            /* Copyright footer — centered at bottom */
            .mobile-brand-footer {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 12px;
                flex: none;
                width: 100%;
                padding-bottom: env(safe-area-inset-bottom);
            }

            .mobile-brand-footer .divider {
                width: 32px;
                height: 1px;
                background: rgba(255,255,255,0.18);
            }

            .mobile-brand-footer span {
                font-size: 10px;
                color: rgba(255,255,255,0.28);
                text-transform: uppercase;
                letter-spacing: 0.12em;
            }
        }

        /* Large phones */
        @media (max-width: 480px) {
            .login-container { padding: 40px 0 28px; }
            .login-left, .login-right { padding: 0 20px; }
            .brand-logo { height: 56px; }
            .form-wrap { padding: 24px 20px 28px; }
            .form-header h2 { font-size: 20px; }
            .input-wrap input { padding: 12px 10px; font-size: 15px; }
            .submit-btn { padding: 13px; font-size: 14px; }
        }

        /* Small phones */
        @media (max-width: 360px) {
            .login-container { padding: 32px 0 24px; }
            .login-left, .login-right { padding: 0 16px; }
            .brand-logo { height: 48px; }
            .form-wrap { padding: 20px 16px 24px; }
            .form-header h2 { font-size: 18px; }
        }

        /* PWA standalone safe areas */
        @media (display-mode: standalone) {
            .login-container {
                padding-top: calc(48px + env(safe-area-inset-top));
            }
        }

        /* ── Intro animation (desktop only) ── */

        @keyframes containerReveal {
            0%   { transform: scaleY(0.012); opacity: 0.7; }
            60%  { transform: scaleY(1.025); opacity: 1; }
            80%  { transform: scaleY(0.975); opacity: 1; }
            100% { transform: scaleY(1);     opacity: 1; }
        }

        @keyframes fadeUp {
            0%   { opacity: 0; transform: translateY(14px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        @media (min-width: 769px) {
            .login-container {
                transform-origin: center center;
                animation: containerReveal 0.65s cubic-bezier(0.34, 1.56, 0.64, 1) 0.1s both;
                overflow: hidden;
                border-radius: 12px;
            }

            .login-left .brand-top,
            .login-left .brand-center,
            .login-left .brand-footer,
            .login-right .form-wrap {
                opacity: 0;
                animation: fadeUp 0.45s ease forwards;
                animation-delay: 0.65s;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile full-screen video background -->
    <video class="mobile-video-bg" autoplay muted loop playsinline>
        <source src="assets/img/bg/lsb.mp4" type="video/mp4">
    </video>
    <div class="mobile-video-overlay"></div>

    <div class="login-container">

        <!-- Left: Branding -->
        <div class="login-left">
            <!-- Background video -->
            <video class="bg-video" autoplay muted loop playsinline>
                <source src="assets/img/bg/lsb.mp4" type="video/mp4">
            </video>
            <!-- Color overlay -->
            <div class="bg-overlay"></div>
            <div class="brand-top">
                <img src="assets/img/logo.png" alt="SVMS Logo" class="brand-logo">
            </div>

            <div class="brand-center">
                <h1>Preserving Academic Integrity.</h1>
                <p>Student Violation Management System of Lyceum of Subic Bay. A refined digital platform for discipline and student records.</p>
            </div>

            <div class="brand-footer">
                <div class="divider"></div>
                <span>Lyceum of Subic Bay &copy; 2024</span>
            </div>
        </div>

        <!-- Right: Form -->
        <div class="login-right">
            <div class="form-wrap">
                <div class="form-header">
                    <h2>Welcome back</h2>
                    <p>Enter your credentials to access the dashboard.</p>
                </div>

                <?php if ($error): ?>
                    <div class="login-alert alert-error" id="loginAlert">
                        <i class="bi bi-exclamation-circle"></i> <span id="loginAlertMsg"><?= $error ?></span>
                    </div>
                <?php else: ?>
                    <div class="login-alert alert-error" id="loginAlert" style="display:none;">
                        <i class="bi bi-exclamation-circle"></i> <span id="loginAlertMsg"></span>
                    </div>
                <?php endif; ?>

                <?php if ($errParam === 'session_expired'): ?>
                    <div class="login-alert alert-warn">
                        <i class="bi bi-clock"></i> Session expired. Please log in again.
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="loginForm">
                    <div class="field-group">
                        <label for="username">Username</label>
                        <div class="input-wrap">
                            <span class="icon"><i class="bi bi-person"></i></span>
                            <input type="text" name="username" id="username"
                                   placeholder="Enter your username"
                                   value="<?= sanitize($_POST['username'] ?? '') ?>"
                                   required autofocus autocomplete="username">
                        </div>
                    </div>

                    <div class="field-group">
                        <label for="password">Password</label>
                        <div class="input-wrap">
                            <span class="icon"><i class="bi bi-lock"></i></span>
                            <input type="password" name="password" id="password"
                                   placeholder="••••••••"
                                   required autocomplete="current-password">
                            <button type="button" class="toggle-pass" onclick="togglePass()" tabindex="-1">
                                <i class="bi bi-eye-slash" id="eyeIcon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="submit-btn" id="loginBtn">
                        <span class="btn-label">Sign In</span>
                        <i class="bi bi-arrow-right arrow"></i>
                        <span class="spinner"></span>
                    </button>
                </form>

                <div class="form-footer">
                    Need assistance? <a href="#">Contact System Admin</a>
                </div>

            </div>
        </div>

        <!-- Mobile copyright footer -->
        <div class="mobile-brand-footer">
            <div class="divider"></div>
            <span>Lyceum of Subic Bay &copy; 2024</span>
        </div>

    </div><!-- /.login-container -->

    <script>
        const loginForm = document.getElementById('loginForm');
        const loginBtn  = document.getElementById('loginBtn');
        const loginAlert    = document.getElementById('loginAlert');
        const loginAlertMsg = document.getElementById('loginAlertMsg');

        loginForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            loginBtn.classList.add('loading');
            loginAlert.style.display = 'none';

            try {
                const res = await fetch('', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: new FormData(loginForm)
                });
                const data = await res.json();

                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    loginAlertMsg.textContent = data.error;
                    loginAlert.style.display = 'flex';
                    loginBtn.classList.remove('loading');
                    // Shake the form wrap
                    loginForm.closest('.form-wrap').classList.add('shake');
                    setTimeout(() => loginForm.closest('.form-wrap').classList.remove('shake'), 500);
                }
            } catch (err) {
                loginAlertMsg.textContent = 'Network error. Please try again.';
                loginAlert.style.display = 'flex';
                loginBtn.classList.remove('loading');
            }
        });

        function togglePass() {
            const pwd  = document.getElementById('password');
            const icon = document.getElementById('eyeIcon');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            } else {
                pwd.type = 'password';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            }
        }

        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js', { scope: '/' })
                .catch(() => {});
        }
    </script>
</body>
</html>
