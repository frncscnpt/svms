<?php
/**
 * SVMS - Mark Notification as Read API
 */

require_once __DIR__ . '/../includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'single';
$notificationId = $_GET['id'] ?? null;

if ($action === 'all') {
    $result = markAllNotificationsRead($userId);
    echo json_encode(['success' => $result]);
} else if ($action === 'single' && $notificationId) {
    $result = markNotificationRead($notificationId, $userId);
    echo json_encode(['success' => $result]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
}
