<?php
/**
 * SVMS - Save Push Subscription API
 */

require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['endpoint'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid subscription data']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Check if subscription already exists for this endpoint
    $stmt = $pdo->prepare("SELECT id FROM push_subscriptions WHERE endpoint = ?");
    $stmt->execute([$input['endpoint']]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Update user_id for existing endpoint (in case user switched accounts or logging in)
        $stmt = $pdo->prepare("UPDATE push_subscriptions SET user_id = ?, p256dh = ?, auth = ? WHERE id = ?");
        $stmt->execute([
            $userId, 
            $input['keys']['p256dh'], 
            $input['keys']['auth'], 
            $existing['id']
        ]);
    } else {
        // Insert new subscription
        $stmt = $pdo->prepare("INSERT INTO push_subscriptions (user_id, endpoint, p256dh, auth) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $userId,
            $input['endpoint'],
            $input['keys']['p256dh'],
            $input['keys']['auth']
        ]);
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
