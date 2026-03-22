<?php
/**
 * SVMS - API: Get Latest Unread Notification
 * Used by Service Worker for "Payload-less Push" strategy
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notification_functions.php';

header('Content-Type: application/json');

// Get the user ID from the session (SW has access to cookies)
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT title, message, link FROM notifications WHERE (user_id = ? OR user_id IS NULL) AND is_read = 0 ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$notif = $stmt->fetch(PDO::FETCH_ASSOC);

if ($notif) {
    echo json_encode($notif);
} else {
    // Fallback if no unread notifications
    echo json_encode([
        'title' => 'SVMS Update',
        'message' => 'Check your dashboard for recent updates.',
        'link' => '/notifications.php'
    ]);
}
