<?php
/**
 * SVMS - Notification Helper Functions
 * Using minishlink/web-push library for proper VAPID implementation
 */

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

// VAPID Configuration
define('VAPID_PUBLIC_KEY', 'BBCiSrLfOgW6yINtSFAxLRgYo2QJ73guhYbfmyMgRQHBZBcno91z78tSQdBYViffdIwsLqMXQbx8G8elKXakZQE');
define('VAPID_PRIVATE_KEY', 'EjwcVlmvFeN8UTA_PZrMTVPqv9zmuAEF-YTV45-aZcE');
define('VAPID_SUBJECT', 'mailto:canapatijohnfrancis@gmail.com');

/**
 * Add a notification for a user (or global)
 */
function addNotification($userId, $title, $message, $type = 'info', $link = null) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)");
        $result = $stmt->execute([$userId, $title, $message, $type, $link]);
        
        // Trigger Web Push if user has subscriptions
        if ($result && $userId) {
            sendWebPushToUser($userId, $title, $message, $link);
        }
        
        return $result;
    } catch (Exception $e) {
        error_log("Error adding notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Send Web Push notification to all subscriptions for a user
 */
function sendWebPushToUser($userId, $title, $message, $link = null) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM push_subscriptions WHERE user_id = ?");
        $stmt->execute([$userId]);
        $subscriptions = $stmt->fetchAll();
        
        if (empty($subscriptions)) {
            error_log("No push subscriptions found for user $userId");
            return;
        }
        
        // Initialize WebPush with VAPID credentials
        $auth = [
            'VAPID' => [
                'subject' => VAPID_SUBJECT,
                'publicKey' => VAPID_PUBLIC_KEY,
                'privateKey' => VAPID_PRIVATE_KEY,
            ],
        ];
        
        $webPush = new WebPush($auth);
        
        // Prepare notification payload
        $payload = json_encode([
            'title' => $title,
            'message' => $message,
            'link' => $link ?? '/notifications.php'
        ]);
        
        error_log("Sending push to " . count($subscriptions) . " subscription(s) for user $userId");
        
        // Queue notifications for all subscriptions
        foreach ($subscriptions as $sub) {
            $subscription = Subscription::create([
                'endpoint' => $sub['endpoint'],
                'keys' => [
                    'p256dh' => $sub['p256dh'],
                    'auth' => $sub['auth']
                ]
            ]);
            
            $webPush->queueNotification($subscription, $payload);
        }
        
        // Send all queued notifications
        foreach ($webPush->flush() as $report) {
            $endpoint = $report->getEndpoint();
            
            if ($report->isSuccess()) {
                error_log("Push notification sent successfully to: " . substr($endpoint, 0, 50) . "...");
            } else {
                $reason = $report->getReason();
                error_log("Push notification failed for: " . substr($endpoint, 0, 50) . "... - Reason: $reason");
                
                // Remove invalid subscriptions (410 Gone or 404 Not Found)
                if ($report->isSubscriptionExpired()) {
                    $pdo->prepare("DELETE FROM push_subscriptions WHERE endpoint = ?")->execute([$endpoint]);
                    error_log("Removed expired subscription: " . substr($endpoint, 0, 50) . "...");
                }
            }
        }
    } catch (Exception $e) {
        error_log("Web Push error: " . $e->getMessage());
    }
}

function base64UrlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Get unread notification count
 */
function getUnreadNotificationCount($userId) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE (user_id = ? OR user_id IS NULL) AND is_read = 0");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    } catch (Exception $e) { return 0; }
}

/**
 * Get recent notifications
 */
function getRecentNotifications($userId, $limit = 5) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? OR user_id IS NULL ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$userId, (int)$limit]);
        return $stmt->fetchAll();
    } catch (Exception $e) { return []; }
}

/**
 * Mark a specific notification as read
 */
function markNotificationRead($notificationId, $userId) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND (user_id = ? OR user_id IS NULL)");
        return $stmt->execute([$notificationId, $userId]);
    } catch (Exception $e) { return false; }
}

/**
 * Mark all notifications as read
 */
function markAllNotificationsRead($userId) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE (user_id = ? OR user_id IS NULL)");
        return $stmt->execute([$userId]);
    } catch (Exception $e) { return false; }
}
