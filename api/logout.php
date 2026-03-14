<?php
// SVMS - Logout
if (session_status() === PHP_SESSION_NONE) {
    session_name('SVMS_SESSION');
    session_start();
}
require_once __DIR__ . '/../includes/auth.php';
logout();
