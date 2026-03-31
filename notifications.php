<?php
/**
 * SVMS - All Notifications Page
 */

require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Notifications';
$breadcrumbs = ['Notifications' => null];
$currentUser = $_SESSION;

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

require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Notification Header -->
<div class="m-section">
    <div class="m-section-header">
        <span class="m-section-title">Notification Center</span>
        <div class="d-flex gap-2">
            <button class="btn btn-sm" onclick="enablePushNotifications(this)" id="enablePushBtn" style="background:var(--accent);color:white;font-size:11px;border-radius:20px;padding:4px 12px;display:none;">
                <i class="bi bi-bell-fill me-1"></i>Enable Alerts
            </button>
            <?php if ($totalNotifs > 0): ?>
            <form method="POST" class="d-inline">
                <button type="submit" name="mark_all_read" class="btn btn-sm" style="background:var(--accent-bg);color:var(--accent);font-size:12px;border-radius:20px;padding:4px 12px;">
                    <i class="bi bi-check2-all me-1"></i>Read All
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Notifications List -->
<?php if (empty($notifications)): ?>
    <div class="m-card text-center" style="padding:48px 24px;">
        <i class="bi bi-bell-slash d-block mb-3" style="font-size:48px;color:var(--text-muted);"></i>
        <h5 style="color:var(--text-secondary);margin-bottom:8px;">No notifications yet</h5>
        <p class="text-muted mb-0" style="font-size:13px;">When you have alerts or updates, they will appear here.</p>
    </div>
<?php else: ?>
    <div style="display:flex;flex-direction:column;gap:12px;">
        <?php foreach ($notifications as $notif): ?>
            <?php 
            $separator = (strpos($notif['link'], '?') !== false) ? '&' : '?';
            $detailsUrl = $notif['link'] ? BASE_PATH . $notif['link'] . $separator . 'mark_read=' . $notif['id'] : '#';
            $isClickable = !empty($notif['link']);
            ?>
            <<?= $isClickable ? 'a' : 'div' ?> 
                <?= $isClickable ? 'href="' . $detailsUrl . '"' : '' ?> 
                class="m-card" 
                style="padding:16px 18px;<?= $notif['is_read'] ? 'opacity:0.7;' : 'border-left:3px solid var(--accent);' ?><?= $isClickable ? 'cursor:pointer;text-decoration:none;color:inherit;transition:all 0.2s;' : '' ?>"
                <?= $isClickable ? 'onmouseover="this.style.background=\'#f7f2f8\'" onmouseout="this.style.background=\'#fff\'"' : '' ?>>
                <div class="d-flex gap-3">
                    <div class="notif-icon-circle me-3 bg-<?= $notif['type'] ?>-soft text-<?= $notif['type'] ?>" style="width:36px;height:36px;min-width:36px;flex-shrink:0;">
                        <i class="bi <?= $notif['type'] == 'danger' ? 'bi-exclamation-triangle' : ($notif['type'] == 'warning' ? 'bi-exclamation-circle' : ($notif['type'] == 'success' ? 'bi-check-circle' : 'bi-info-circle')) ?>"></i>
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <strong style="font-size:13px;color:var(--primary);"><?= sanitize($notif['title']) ?></strong>
                            <small class="text-muted flex-shrink-0 ms-2" style="font-size:11px;"><?= date('M d, Y', strtotime($notif['created_at'])) ?></small>
                        </div>
                        <p class="mb-0 text-muted" style="font-size:12px;line-height:1.4;"><?= sanitize($notif['message']) ?></p>
                    </div>
                </div>
            </<?= $isClickable ? 'a' : 'div' ?>>
        <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
        <div class="d-flex justify-content-center gap-2 mt-4 mb-4">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>" class="btn btn-sm btn-outline-secondary" style="border-radius:20px;font-size:12px;">← Previous</a>
            <?php endif; ?>
            <span class="text-muted align-self-center" style="font-size:12px;">Page <?= $page ?> of <?= $totalPages ?></span>
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>" class="btn btn-sm btn-outline-secondary" style="border-radius:20px;font-size:12px;">Next →</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
