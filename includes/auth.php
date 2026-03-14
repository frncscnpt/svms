<?php
/**
 * SVMS - Authentication & Authorization Middleware
 */

if (session_status() === PHP_SESSION_NONE) {
    session_name('SVMS_SESSION');
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/notification_functions.php';

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

/**
 * Require login — redirect to login page if not authenticated
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_PATH . '/index.php?error=session_expired');
        exit;
    }
}

/**
 * Require specific role(s)
 */
function requireRole($roles) {
    requireLogin();
    if (is_string($roles)) {
        $roles = [$roles];
    }
    if (!in_array($_SESSION['role'], $roles)) {
        header('HTTP/1.1 403 Forbidden');
        include __DIR__ . '/../includes/403.php';
        exit;
    }
}

/**
 * Authenticate user with credentials
 */
function authenticateUser($username, $password) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT id, username, password, full_name, email, role, student_id, avatar, status FROM users WHERE username = ? AND status = 'active'");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['student_id'] = $user['student_id'];
        $_SESSION['avatar'] = $user['avatar'];
        $_SESSION['login_time'] = time();
        
        // Update last login
        $update = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $update->execute([$user['id']]);
        
        // Log activity
        logActivity($user['id'], 'login', 'User logged in successfully');
        
        return $user;
    }
    
    return false;
}

/**
 * Logout user
 */
function logout() {
    if (isset($_SESSION['user_id'])) {
        logActivity($_SESSION['user_id'], 'logout', 'User logged out');
    }
    session_unset();
    session_destroy();
    header('Location: ' . BASE_PATH . '/index.php?msg=logged_out');
    exit;
}

/**
 * Get current user data
 */
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'full_name' => $_SESSION['full_name'],
        'email' => $_SESSION['email'],
        'role' => $_SESSION['role'],
        'student_id' => $_SESSION['student_id'],
        'avatar' => $_SESSION['avatar']
    ];
}

/**
 * Get redirect URL based on role
 */
function getRoleRedirect($role) {
    switch ($role) {
        case 'admin':
            return BASE_PATH . '/admin/dashboard.php';
        case 'discipline_officer':
            return BASE_PATH . '/discipline/dashboard.php';
        case 'teacher':
            return BASE_PATH . '/teacher/index.php';
        case 'student':
            return BASE_PATH . '/student/index.php';
        default:
            return BASE_PATH . '/index.php';
    }
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Log user activity
 */
function logActivity($userId, $action, $details = '') {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $action, $details, $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0']);
    } catch (Exception $e) {
        error_log("Activity log error: " . $e->getMessage());
    }
}
