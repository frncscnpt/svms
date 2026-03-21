<?php
/**
 * SVMS - Layout Helper
 * Detects mobile vs desktop and includes the appropriate header/footer.
 * Usage: set $pageTitle, $breadcrumbs (desktop only), then require this file.
 */

// Ensure database config (and BASE_PATH) is loaded
if (!defined('BASE_PATH')) {
    require_once __DIR__ . '/../config/database.php';
}

function isMobileDevice() {
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    return (bool) preg_match(
        '/Mobile|Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i',
        $ua
    );
}

if (!defined('IS_MOBILE')) {
    define('IS_MOBILE', isMobileDevice());
}
