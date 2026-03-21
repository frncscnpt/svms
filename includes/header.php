<?php
/**
 * SVMS - Desktop Header (Sidebar Layout)
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
requireLogin();

$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$currentDir = basename(dirname($_SERVER['PHP_SELF']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#2e1731">
    <title><?= $pageTitle ?? 'Dashboard' ?> - SVMS</title>
    <link rel="manifest" href="<?= getManifestDataUri() ?>">
    <link rel="icon" href="<?= BASE_PATH ?>/assets/img/logo.png" type="image/png">
    <link rel="preload" href="<?= BASE_PATH ?>/assets/font/Chillax-Variable.ttf" as="font" type="font/ttf" crossorigin>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_PATH ?>/assets/css/style.css?v=<?= time() ?>" rel="stylesheet">
    <?php if (isset($extraCSS)) echo $extraCSS; ?>
    <script src="<?= BASE_PATH ?>/assets/js/push-manager.js" defer></script>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon">
                <img src="<?= BASE_PATH ?>/assets/img/logo.png" alt="SVMS Logo">
            </div>

            <div class="brand-text">
                <h2>SVMS</h2>
                <p>Lyceum of Subic Bay</p>
            </div>

            <button class="sidebar-close-btn d-lg-none" id="sidebarClose" aria-label="Close menu">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <nav class="sidebar-nav">
            <?php if ($currentUser['role'] === 'admin'): ?>
            <div class="nav-section">
                <div class="nav-section-title">Main</div>
                <a href="<?= BASE_PATH ?>/admin/dashboard.php" class="nav-link-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                    <i class="bi bi-grid-1x2-fill"></i> <span>Dashboard</span>
                </a>
                <a href="<?= BASE_PATH ?>/admin/students.php" class="nav-link-item <?= $currentPage === 'students' ? 'active' : '' ?>">
                    <i class="bi bi-mortarboard-fill"></i> <span>Students</span>
                </a>
                <a href="<?= BASE_PATH ?>/admin/users.php" class="nav-link-item <?= $currentPage === 'users' ? 'active' : '' ?>">
                    <i class="bi bi-people-fill"></i> <span>Users</span>
                </a>
            </div>
            <div class="nav-section">
                <div class="nav-section-title">Management</div>
                <a href="<?= BASE_PATH ?>/admin/violations.php" class="nav-link-item <?= $currentPage === 'violations' ? 'active' : '' ?>">
                    <i class="bi bi-exclamation-triangle-fill"></i> <span>Violations</span>
                </a>
                <a href="<?= BASE_PATH ?>/admin/uniform_passes.php" class="nav-link-item <?= $currentPage === 'uniform_passes' ? 'active' : '' ?>">
                    <i class="bi bi-card-checklist"></i> <span>Uniform Passes</span>
                </a>
                <a href="<?= BASE_PATH ?>/admin/reports.php" class="nav-link-item <?= $currentPage === 'reports' ? 'active' : '' ?>">
                    <i class="bi bi-file-earmark-bar-graph-fill"></i> <span>Reports</span>
                </a>
            </div>
            <?php elseif ($currentUser['role'] === 'discipline_officer'): ?>
            <div class="nav-section">
                <div class="nav-section-title">Main</div>
                <a href="<?= BASE_PATH ?>/discipline/dashboard.php" class="nav-link-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                    <i class="bi bi-grid-1x2-fill"></i> <span>Dashboard</span>
                </a>
                <a href="<?= BASE_PATH ?>/discipline/violations.php" class="nav-link-item <?= $currentPage === 'violations' ? 'active' : '' ?>">
                    <i class="bi bi-exclamation-triangle-fill"></i> <span>Violations</span>
                </a>
                <a href="<?= BASE_PATH ?>/discipline/actions.php" class="nav-link-item <?= $currentPage === 'actions' ? 'active' : '' ?>">
                    <i class="bi bi-hammer"></i> <span>Actions</span>
                </a>
            </div>
            <div class="nav-section">
                <div class="nav-section-title">Records</div>
                <a href="<?= BASE_PATH ?>/discipline/uniform_passes.php" class="nav-link-item <?= $currentPage === 'uniform_passes' ? 'active' : '' ?>">
                    <i class="bi bi-card-checklist"></i> <span>Uniform Passes</span>
                </a>
                <a href="<?= BASE_PATH ?>/discipline/history.php" class="nav-link-item <?= $currentPage === 'history' ? 'active' : '' ?>">
                    <i class="bi bi-clock-history"></i> <span>History</span>
                </a>
                <a href="<?= BASE_PATH ?>/discipline/reports.php" class="nav-link-item <?= $currentPage === 'reports' ? 'active' : '' ?>">
                    <i class="bi bi-file-earmark-bar-graph-fill"></i> <span>Reports</span>
                </a>
            </div>
            <?php elseif ($currentUser['role'] === 'teacher'): ?>
            <div class="nav-section">
                <div class="nav-section-title">Main</div>
                <a href="<?= BASE_PATH ?>/teacher/index.php" class="nav-link-item <?= $currentPage === 'index' && $currentDir === 'teacher' ? 'active' : '' ?>">
                    <i class="bi bi-grid-1x2-fill"></i> <span>Dashboard</span>
                </a>
                <a href="<?= BASE_PATH ?>/teacher/scan.php" class="nav-link-item <?= $currentPage === 'scan' ? 'active' : '' ?>">
                    <i class="bi bi-qr-code-scan"></i> <span>Scan QR</span>
                </a>
                <a href="<?= BASE_PATH ?>/teacher/report.php" class="nav-link-item <?= $currentPage === 'report' ? 'active' : '' ?>">
                    <i class="bi bi-plus-circle-fill"></i> <span>File Report</span>
                </a>
                <a href="<?= BASE_PATH ?>/teacher/my_reports.php" class="nav-link-item <?= $currentPage === 'my_reports' ? 'active' : '' ?>">
                    <i class="bi bi-file-earmark-text-fill"></i> <span>My Reports</span>
                </a>
            </div>
            <?php elseif ($currentUser['role'] === 'student'): ?>
            <div class="nav-section">
                <div class="nav-section-title">Main</div>
                <a href="<?= BASE_PATH ?>/student/index.php" class="nav-link-item <?= $currentPage === 'index' && $currentDir === 'student' ? 'active' : '' ?>">
                    <i class="bi bi-grid-1x2-fill"></i> <span>Dashboard</span>
                </a>
                <a href="<?= BASE_PATH ?>/student/violations.php" class="nav-link-item <?= $currentPage === 'violations' ? 'active' : '' ?>">
                    <i class="bi bi-exclamation-triangle-fill"></i> <span>My Violations</span>
                </a>
                <a href="<?= BASE_PATH ?>/student/uniform_pass.php" class="nav-link-item <?= $currentPage === 'uniform_pass' ? 'active' : '' ?>">
                    <i class="bi bi-card-checklist"></i> <span>Uniform Pass</span>
                </a>
            </div>
            <?php endif; ?>

            <div class="nav-section">
                <div class="nav-section-title">Account</div>
                <a href="<?= BASE_PATH ?>/settings.php" class="nav-link-item <?= $currentPage === 'settings' ? 'active' : '' ?>">
                    <i class="bi bi-person-circle"></i> <span>My Profile</span>
                </a>
            </div>
        </nav>

        <div class="sidebar-user">
            <button onclick="confirmLogout(event)" class="sidebar-logout-btn">
                <i class="bi bi-box-arrow-left"></i>
                <span>Log Out</span>
            </button>
        </div>
    </aside>

    <!-- Sidebar backdrop (mobile) -->
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <!-- Main Content -->
    <main class="main-content">
        <div class="topbar">
            <div class="topbar-left">
                <button class="topbar-btn d-lg-none" id="sidebarToggle"><i class="bi bi-list"></i></button>
                <div>
                    <h1 class="page-title"><?= $pageTitle ?? 'Dashboard' ?></h1>
                    <?php if (isset($breadcrumbs)): ?>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0" style="font-size:12px;">
                            <?php foreach ($breadcrumbs as $label => $url): ?>
                                <?php if ($url): ?>
                                    <li class="breadcrumb-item"><a href="<?= $url ?>"><?= $label ?></a></li>
                                <?php else: ?>
                                    <li class="breadcrumb-item active"><?= $label ?></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ol>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
            <div class="topbar-right">
                <?php 
                $unreadCount = getUnreadNotificationCount($_SESSION['user_id']);
                $recentNotifs = getRecentNotifications($_SESSION['user_id'], 5);
                ?>
                <div class="dropdown me-2">
                    <button class="topbar-btn position-relative" data-bs-toggle="dropdown" aria-expanded="false" id="notificationBell">
                        <i class="bi bi-bell"></i>
                        <?php if ($unreadCount > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 10px; padding: 3px 6px;">
                                <?= $unreadCount > 99 ? '99+' : $unreadCount ?>
                            </span>
                        <?php endif; ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end notification-dropdown p-0">
                        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold">Notifications</h6>
                            <div class="d-flex align-items-center gap-2">
                                <button class="btn btn-sm btn-outline-custom" onclick="enablePushNotifications(this)" id="enablePushBtn" style="font-size: 11px; padding: 2px 8px; display: none;">
                                    <i class="bi bi-bell-fill me-1"></i>Enable Alerts
                                </button>
                                <a href="<?= BASE_PATH ?>/notifications.php" class="text-primary-custom" style="font-size: 12px; text-decoration: none;">View All</a>
                            </div>
                        </div>
                        <div class="notif-list" style="max-height: 350px; overflow-y: auto; width: 300px;">
                            <?php if (empty($recentNotifs)): ?>
                                <div class="p-4 text-center text-muted">
                                    <i class="bi bi-bell-slash mb-2 d-block" style="font-size: 24px;"></i>
                                    <p class="mb-0" style="font-size: 13px;">No notifications yet</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($recentNotifs as $notif): ?>
                                    <div class="notif-item p-3 border-bottom <?= $notif['is_read'] ? '' : 'unread' ?>" onclick="window.location.href='<?= $notif['link'] ? BASE_PATH . $notif['link'] : '#' ?>'">
                                        <div class="d-flex align-items-start">
                                            <div class="notif-icon-circle me-3 bg-<?= $notif['type'] ?>-soft text-<?= $notif['type'] ?>">
                                                <i class="bi <?= $notif['type'] == 'danger' ? 'bi-exclamation-triangle' : ($notif['type'] == 'warning' ? 'bi-exclamation-circle' : ($notif['type'] == 'success' ? 'bi-check-circle' : 'bi-info-circle')) ?>"></i>
                                            </div>
                                            <div class="notif-content flex-grow-1">
                                                <div class="d-flex justify-content-between">
                                                    <span class="notif-title fw-bold" style="font-size: 13px;"><?= sanitize($notif['title']) ?></span>
                                                    <span class="notif-time text-muted" style="font-size: 11px;"><?= date('M d', strtotime($notif['created_at'])) ?></span>
                                                </div>
                                                <p class="notif-message mb-0 text-muted" style="font-size: 12px; line-height: 1.4;"><?= sanitize($notif['message']) ?></p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="ms-1">
                    <a href="<?= BASE_PATH ?>/settings.php" class="topbar-btn d-flex align-items-center justify-content-center p-0" style="border-radius: 50%; overflow: hidden; width: 36px; height: 36px; text-decoration:none;">
                        <?= getAvatarHtml($_SESSION['avatar'] ?? null, $_SESSION['full_name'], 'profile-avatar', 'width: 100%; height: 100%; font-size: 14px; margin: 0;') ?>
                    </a>
                </div>
            </div>
        </div>
        <div class="content-wrapper">
            <?php renderFlash(); ?>
