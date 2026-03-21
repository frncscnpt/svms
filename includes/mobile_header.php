<?php
/**
 * SVMS - Mobile Header (PWA Layout)
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
requireLogin();

$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$currentDir  = basename(dirname($_SERVER['PHP_SELF']));
$isTeacher   = $currentUser['role'] === 'teacher';
$isStudent   = $currentUser['role'] === 'student';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#fdf8fd">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title><?= $pageTitle ?? 'SVMS' ?></title>
    <link rel="manifest" href="<?= getManifestDataUri() ?>">
    <link rel="icon" href="<?= BASE_PATH ?>/assets/img/logo.png" type="image/png">
    <link rel="apple-touch-icon" href="<?= BASE_PATH ?>/assets/img/logo.png">
    <link rel="preload" href="<?= BASE_PATH ?>/assets/font/Chillax-Variable.ttf" as="font" type="font/ttf" crossorigin>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_PATH ?>/assets/css/style.css?v=<?= time() ?>" rel="stylesheet">
    <link href="<?= BASE_PATH ?>/assets/css/mobile.css?v=<?= time() ?>" rel="stylesheet">
    <?php if (isset($extraCSS)) echo $extraCSS; ?>
</head>
<body>
<div class="mobile-app">

    <!-- Top App Bar -->
    <header class="m-topbar">
        <div class="m-topbar-brand">
            <div class="m-topbar-logo-wrap">
                <img src="<?= BASE_PATH ?>/assets/img/logo.png" alt="SVMS" class="m-topbar-logo">
            </div>
            <span class="m-topbar-title">Lyceum SVMS</span>
        </div>
        <div class="m-topbar-actions">
            <?php $unreadCount = getUnreadNotificationCount($_SESSION['user_id']); ?>
            <a href="<?= BASE_PATH ?>/notifications.php" class="m-topbar-btn position-relative">
                <i class="bi bi-bell"></i>
                <?php if ($unreadCount > 0): ?>
                <span class="m-notif-dot"></span>
                <?php endif; ?>
            </a>
            <?php if ($isTeacher): ?>
            <?php endif; ?>
        </div>
    </header>

    <div class="mobile-content">
        <?php renderFlash(); ?>
