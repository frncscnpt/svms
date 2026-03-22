<?php
/**
 * SVMS - Notification Helper Functions
 * STANDALONE VERSION (No Composer required)
 */

// VAPID Configuration
define('VAPID_PUBLIC_KEY', 'BLhYZmVFNN683OyNvSG3xc0q_qO1GwgZxA6ChTtucbEMwH_nISy_28bCW0ENN2YfiAqqfNKI20c0Dxy6D_KM9uY');
define('VAPID_PRIVATE_KEY', '8tA3MhUmQEterlm58X02mLJW62vmTpC8PtV44pf4L-Y');
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
        
        if (empty($subscriptions)) return;
        
        $payload = [
            'title' => $title,
            'message' => $message,
            'link' => $link ?? '/notifications.php'
        ];

        foreach ($subscriptions as $sub) {
            // Standalone push delivery (VAPID only, no Composer)
            $result = deliverStandalonePush($sub, $payload);
            
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
 * Deliver a push notification without external libraries
 * This handles VAPID authentication only.
 */
function deliverStandalonePush($sub, $payload) {
    $endpoint = $sub['endpoint'];
    $parseUrl = parse_url($endpoint);
    $origin = $parseUrl['scheme'] . '://' . $parseUrl['host'];
    
    // Create VAPID Header (JWT)
    $jwtHeader = base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
    $jwtPayload = base64UrlEncode(json_encode([
        'aud' => $origin,
        'exp' => time() + 3600,
        'sub' => VAPID_SUBJECT
    ]));
    
    // --- START VAPID SIGNING (ES256) ---
    $jwt = $jwtHeader . "." . $jwtPayload;
    
    // Decode VAPID private key to raw binary
    $privateKeyBin = base64UrlDecode(VAPID_PRIVATE_KEY);
    $publicKeyBin = base64UrlDecode(VAPID_PUBLIC_KEY);

    // VAPID keys are raw bytes (static 32-byte private key). 
    // To sign with OpenSSL, we must wrap it in a PEM header/footer if not already.
    // However, raw VAPID ES256 keys are tricky toPEM. 
    // Standard approach: If you don't have the PEM, you must construct the ASN.1 structure.
    // BUT OpenSSL 3.0+ simplified this.
    
    // Let's use a simpler "Ping" header if ES256 is too complex for standalone 
    // Actually, I will try to generate a valid ES256 signature.
    $signature = '';
    try {
        // Convert raw private key to PEM (prime256v1 / secp256r1)
        // This is a minimal DER sequence for a P-256 private key
        $der = hex2bin('30770201010420' . bin2hex($privateKeyBin) . 'a00a06082a8648ce3d030107a14403420004' . bin2hex($publicKeyBin));
        $pem = "-----BEGIN PRIVATE KEY-----\n" . chunk_split(base64_encode($der), 64) . "-----END PRIVATE KEY-----";
        
        $keyRes = openssl_pkey_get_private($pem);
        if ($keyRes && openssl_sign($jwt, $derSig, $keyRes, OPENSSL_ALGO_SHA256)) {
            // OpenSSL returns DER signature. We need raw R and S concatenated.
            // Der format: 30 <len> 02 <lenR> <R> 02 <lenS> <S>
            $signature = decodeDerSignature($derSig);
        }
    } catch (Exception $e) {
        error_log("VAPID Sign Error: " . $e->getMessage());
    }

    $headers = [
        'TTL: 86400',
        'Urgency: normal',
        'Authorization: WebPush ' . $jwt . '.' . base64UrlEncode($signature),
        'Crypto-Key: p2=' . VAPID_PUBLIC_KEY,
        'Content-Type: application/json'
    ];

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => '', // Empty payload (SW will fetch data manually)
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $result = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $status;
}

function base64UrlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64UrlDecode($data) {
    return base64_decode(strtr($data, '-_', '+/'));
}

function decodeDerSignature($der) {
    $R = ''; $S = '';
    $offset = 2; // Tag and Length
    if (ord($der[$offset++]) == 0x02) {
        $lenR = ord($der[$offset++]);
        $R = substr($der, $offset, $lenR);
        $offset += $lenR;
    }
    if (ord($der[$offset++]) == 0x02) {
        $lenS = ord($der[$offset++]);
        $S = substr($der, $offset, $lenS);
    }
    // Remove leading zero if necessary (R and S must be 32 bytes)
    $R = ltrim($R, "\x00"); $S = ltrim($S, "\x00");
    return str_pad($R, 32, "\x00", STR_PAD_LEFT) . str_pad($S, 32, "\x00", STR_PAD_LEFT);
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
