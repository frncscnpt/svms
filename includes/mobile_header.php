<?php
/**
 * SVMS - Mobile Header (PWA Layout)
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
requireLogin();

$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$currentDir = basename(dirname($_SERVER['PHP_SELF']));
$isTeacher = $currentUser['role'] === 'teacher';
$isStudent = $currentUser['role'] === 'student';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#2e1731">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title><?= $pageTitle ?? 'SVMS' ?> - SVMS</title>
    <link rel="manifest" href="<?= getManifestDataUri() ?>">
    <link rel="icon" href="<?= BASE_PATH ?>/assets/img/icons/icon-72.png" type="image/png">
    <link rel="apple-touch-icon" href="<?= BASE_PATH ?>/assets/img/icons/icon-192.png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_PATH ?>/assets/css/style.css" rel="stylesheet">
    <link href="<?= BASE_PATH ?>/assets/css/mobile.css" rel="stylesheet">
    <?php if (isset($extraCSS)) echo $extraCSS; ?>
</head>
<body>
    <div class="mobile-app">
        <!-- Mobile Top Bar -->
        <div class="mobile-topbar">
            <div>
                <h1 class="top-title"><?= $pageTitle ?? 'SVMS' ?></h1>
                <?php if (isset($pageSubtitle)): ?>
                    <div class="top-subtitle"><?= $pageSubtitle ?></div>
                <?php endif; ?>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= BASE_PATH ?>/api/logout.php" class="top-action"><i class="bi bi-box-arrow-right"></i></a>
            </div>
        </div>

        <div class="mobile-content">
            <?php renderFlash(); ?>
