<?php
/**
 * SVMS - All Notifications Page
 * Renders with mobile layout for students/teachers, desktop layout for admin/discipline.
 */

require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Notifications';
$pageSubtitle = 'Your alerts and updates';
$currentUser = $_SESSION;
$isMobileRole = in_array($_SESSION['role'], ['student', 'teacher']);

// Handle Mark All as Read
if (isset($_POST['mark_all_read'])) {
    markAllNotificationsRead($currentUser['user_id']);
    setFlash('success', 'All notifications marked as read.');
    header('Location: notifications.php');
    exit;
}

// Handle mark single as read via query param
if (isset($_GET['mark_read'])) {
    markNotificationRead($_GET['mark_read'], $currentUser['user_id']);
}

// Get all notifications with pagination
$page = $_GET['page'] ?? 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$pdo = getDBConnection();
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? OR user_id IS NULL");
$stmtCount->execute([$currentUser['user_id']]);
$totalNotifs = $stmtCount->fetchColumn();
$totalPages = ceil($totalNotifs / $limit);

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? OR user_id IS NULL ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->execute([$currentUser['user_id'], $limit, $offset]);
$notifications = $stmt->fetchAll();

// Include the correct header based on role
if ($isMobileRole) {
    require_once __DIR__ . '/includes/mobile_header.php';
} else {
    include __DIR__ . '/includes/header.php';
}
?>

<?php if ($isMobileRole): ?>
<!-- MOBILE LAYOUT -->
<div class="mb-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0" style="color: var(--primary);">Notification Center</h6>
        <div class="d-flex gap-2">
            <button class="btn btn-sm" onclick="enablePushNotifications(this)" id="enablePushBtn" style="background: var(--accent); color: white; font-size: 11px; border-radius: 20px; padding: 4px 12px; display: none;">
                <i class="bi bi-bell-fill me-1"></i>Enable Alerts
            </button>
            <?php if ($totalNotifs > 0): ?>
            <form method="POST" class="d-inline">
                <button type="submit" name="mark_all_read" class="btn btn-sm" style="background: var(--accent-bg); color: var(--accent); font-size: 12px; border-radius: 20px; padding: 4px 12px;">
                    <i class="bi bi-check2-all me-1"></i>Read All
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($notifications)): ?>
        <div class="m-card text-center p-4">
            <i class="bi bi-bell-slash d-block mb-2" style="font-size: 36px; color: var(--text-muted);"></i>
            <h6 style="color: var(--text-secondary);">No notifications yet</h6>
            <p class="text-muted mb-0" style="font-size: 13px;">When you have alerts or updates, they will appear here.</p>
        </div>
    <?php else: ?>
        <?php foreach ($notifications as $notif): ?>
            <div class="m-card mb-2" style="padding:16px 18px;<?= $notif['is_read'] ? 'opacity:0.7;' : 'border-left:3px solid var(--accent);' ?>">
                <div class="d-flex">
                    <div class="notif-icon-circle me-3 bg-<?= $notif['type'] ?>-soft text-<?= $notif['type'] ?>" style="width: 36px; height: 36px; min-width: 36px;">
                        <i class="bi <?= $notif['type'] == 'danger' ? 'bi-exclamation-triangle' : ($notif['type'] == 'warning' ? 'bi-exclamation-circle' : ($notif['type'] == 'success' ? 'bi-check-circle' : 'bi-info-circle')) ?>"></i>
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <strong style="font-size: 13px; color: var(--primary);"><?= sanitize($notif['title']) ?></strong>
                            <small class="text-muted flex-shrink-0 ms-2" style="font-size: 11px;"><?= date('M d', strtotime($notif['created_at'])) ?></small>
                        </div>
                        <p class="mb-0 text-muted" style="font-size: 12px; line-height: 1.4;"><?= sanitize($notif['message']) ?></p>
                        <?php if ($notif['link']): ?>
                            <?php 
                            $separator = (strpos($notif['link'], '?') !== false) ? '&' : '?';
                            $detailsUrl = BASE_PATH . $notif['link'] . $separator . 'mark_read=' . $notif['id'];
                            ?>
                            <a href="<?= $detailsUrl ?>" class="btn btn-sm mt-2" style="background: var(--accent); color: white; font-size: 11px; border-radius: 20px; padding: 3px 12px;">
                                View Details
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if ($totalPages > 1): ?>
            <div class="d-flex justify-content-center gap-2 mt-3 mb-4">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>" class="btn btn-sm btn-outline-secondary" style="border-radius: 20px; font-size: 12px;">← Previous</a>
                <?php endif; ?>
                <span class="text-muted align-self-center" style="font-size: 12px;">Page <?= $page ?> of <?= $totalPages ?></span>
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>" class="btn btn-sm btn-outline-secondary" style="border-radius: 20px; font-size: 12px;">Next →</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php else: ?>
<!-- DESKTOP LAYOUT -->
<div class="row">
    <div class="col-md-10 col-lg-8 mx-auto">
        <div class="card-panel">
            <div class="panel-header">
                <h5 class="panel-title"><i class="bi bi-bell-fill"></i> Notification Center</h5>
                <?php if ($totalNotifs > 0): ?>
                <form method="POST">
                    <button type="submit" name="mark_all_read" class="btn-outline-custom" style="font-size:12px;padding:6px 14px;">
                        <i class="bi bi-check2-all me-1"></i> Mark All as Read
                    </button>
                </form>
                <?php endif; ?>
            </div>
            <div class="panel-body">
            <?php if (empty($notifications)): ?>
                <div class="empty-state">
                    <i class="bi bi-bell-slash"></i>
                    <h5>No notifications found</h5>
                    <p>When you have alerts or updates, they will appear here.</p>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notif): ?>
                    <div class="d-flex gap-3 p-3 rounded-3 mb-2 border" style="<?= $notif['is_read'] ? 'background:#fdf8fd;' : 'background:#fff;border-left:4px solid var(--accent) !important;' ?>">
                        <div class="notif-icon-circle bg-<?= $notif['type'] ?>-soft text-<?= $notif['type'] ?>" style="flex-shrink:0;">
                            <i class="bi <?= $notif['type'] == 'danger' ? 'bi-exclamation-triangle' : ($notif['type'] == 'warning' ? 'bi-exclamation-circle' : ($notif['type'] == 'success' ? 'bi-check-circle' : 'bi-info-circle')) ?>"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <h6 class="mb-0" style="font-size:14px;font-weight:700;color:var(--primary);"><?= sanitize($notif['title']) ?></h6>
                                <small class="text-muted"><?= date('M d, Y h:i A', strtotime($notif['created_at'])) ?></small>
                            </div>
                            <p class="mb-2 text-secondary" style="font-size: 13px;"><?= sanitize($notif['message']) ?></p>
                            <?php if ($notif['link']): ?>
                                <?php 
                                $separator = (strpos($notif['link'], '?') !== false) ? '&' : '?';
                                $detailsUrl = BASE_PATH . $notif['link'] . $separator . 'mark_read=' . $notif['id'];
                                ?>
                                <a href="<?= $detailsUrl ?>" class="btn-accent" style="font-size:12px;padding:5px 14px;">View Details</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if ($totalPages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a>
                            </li>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
if ($isMobileRole) {
    include __DIR__ . '/includes/mobile_footer.php';
} else {
    include __DIR__ . '/includes/footer.php';
}
?>
