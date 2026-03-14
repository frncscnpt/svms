<?php
/**
 * SVMS - Notification Helper Functions
 */

// VAPID Configuration
// Generate your own keys at: https://vapidkeys.com/
define('VAPID_PUBLIC_KEY', 'BLhYZmVFNN683OyNvSG3xc0q_qO1GwgZxA6ChTtucbEMwH_nISy_28bCW0ENN2YfiAqqfNKI20c0Dxy6D_KM9uY');
define('VAPID_PRIVATE_KEY', '8tA3MhUmQEterlm58X02mLJW62vmTpC8PtV44pf4L-Y');
define('VAPID_SUBJECT', 'mailto:canapatijohnfrancis@gmail.com');

/**
 * Add a notification for a user (or global)
 * Also triggers Web Push delivery if the user has active subscriptions.
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
        
        if (empty($subscriptions)) return;
        
        $payload = json_encode([
            'title' => $title,
            'message' => $message,
            'link' => $link ?? '/notifications.php'
        ]);
        
        foreach ($subscriptions as $sub) {
            $result = sendWebPush($sub['endpoint'], $payload);
            
            // If endpoint is gone (410 Gone or 404), remove the subscription
            if ($result === 410 || $result === 404) {
                $pdo->prepare("DELETE FROM push_subscriptions WHERE id = ?")->execute([$sub['id']]);
            }
        }
    } catch (Exception $e) {
        error_log("Web Push error: " . $e->getMessage());
    }
}

/**
 * Send a single Web Push notification (lightweight, no library needed)
 * Returns HTTP status code
 */
function sendWebPush($endpoint, $payload) {
    try {
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($payload),
                'TTL: 86400'
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode;
    } catch (Exception $e) {
        error_log("Web Push delivery error: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get unread notification count for a user
 */
function getUnreadNotificationCount($userId) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE (user_id = ? OR user_id IS NULL) AND is_read = 0");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Get recent notifications for a user
 */
function getRecentNotifications($userId, $limit = 5) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? OR user_id IS NULL ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Mark a specific notification as read
 */
function markNotificationRead($notificationId, $userId) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND (user_id = ? OR user_id IS NULL)");
        return $stmt->execute([$notificationId, $userId]);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Mark all notifications as read for a user
 */
function markAllNotificationsRead($userId) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE (user_id = ? OR user_id IS NULL)");
        return $stmt->execute([$userId]);
    } catch (Exception $e) {
        return false;
    }
}
