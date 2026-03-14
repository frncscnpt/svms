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

// Handle login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $user = authenticateUser($username, $password);
        if ($user) {
            header('Location: ' . getRoleRedirect($user['role']));
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}

$msg = $_GET['msg'] ?? '';
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
    <meta name="theme-color" content="#2e1731">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="SVMS">
    <link rel="icon" href="/assets/img/icons/icon-72.png" type="image/png">
    <link rel="apple-touch-icon" href="/assets/img/icons/icon-192.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-container">
            <div class="login-card">
                <div class="login-logo">
                    <div class="logo-icon">
                        <img src="assets/img/icons/icon-192.png" alt="SVMS Logo">
                    </div>

                    <h1>SVMS</h1>
                    <p>Lyceum of Subic Bay</p>
                    <p style="font-size:11px;color:var(--text-muted);margin-top:1px;">Student Violation Management System</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show py-2" role="alert" style="font-size:13px;border-radius:var(--radius-md);">
                        <i class="bi bi-exclamation-circle me-1"></i> <?= $error ?>
                        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($msg === 'logged_out'): ?>
                    <div class="alert alert-info py-2" role="alert" style="font-size:13px;border-radius:var(--radius-md);">
                        <i class="bi bi-check-circle me-1"></i> You have been logged out.
                    </div>
                <?php endif; ?>

                <?php if ($errParam === 'session_expired'): ?>
                    <div class="alert alert-warning py-2" role="alert" style="font-size:13px;border-radius:var(--radius-md);">
                        <i class="bi bi-clock me-1"></i> Session expired. Please log in again.
                    </div>
                <?php endif; ?>

                <form class="login-form" method="POST" action="" id="loginForm">
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-icon"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control" name="username" id="username" 
                                   placeholder="Enter your username" value="<?= sanitize($_POST['username'] ?? '') ?>" 
                                   required autofocus autocomplete="username">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-icon"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" name="password" id="password" 
                                   placeholder="Enter your password" required autocomplete="current-password">
                        </div>
                    </div>
                    <button type="submit" class="login-btn" id="loginBtn">
                        <span class="btn-text">Sign In</span>
                        <span class="spinner-border spinner-border-sm" role="status"></span>
                    </button>
                </form>

                <div class="login-footer">
                    <p>&copy; <?= date('Y') ?> Lyceum of Subic Bay. All rights reserved.</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('loginForm').addEventListener('submit', function() {
            document.getElementById('loginBtn').classList.add('loading');
        });

        // Register service worker
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js', { scope: '/' })
                .then(reg => console.log('SW registered:', reg.scope))
                .catch(err => console.log('SW registration failed:', err));
        }
    </script>
</body>
</html>
