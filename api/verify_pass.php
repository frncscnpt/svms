<?php
/**
 * SVMS - Verify Uniform Pass API
 * Called when a teacher scans a TUP- prefixed QR code
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$code = $_GET['code'] ?? '';

if (empty($code)) {
    echo json_encode(['success' => false, 'valid' => false, 'message' => 'No pass code provided']);
    exit;
}

$pdo = getDBConnection();

$stmt = $pdo->prepare("
    SELECT up.*, 
           s.first_name, s.last_name, s.student_number, s.grade_level, s.section, s.photo,
           u.full_name AS issued_by_name
    FROM uniform_passes up
    JOIN students s ON up.student_id = s.id
    JOIN users u ON up.issued_by = u.id
    WHERE up.pass_code = ?
");
$stmt->execute([$code]);
$pass = $stmt->fetch();

if (!$pass) {
    echo json_encode([
        'success' => true,
        'valid' => false,
        'message' => 'Invalid pass code — no matching pass found.'
    ]);
    exit;
}

// Determine real-time validity
$today = date('Y-m-d');
$isExpired = $pass['valid_date'] < $today;
$isRevoked = $pass['status'] === 'revoked';
$isActive = $pass['status'] === 'active' && !$isExpired;

// Auto-expire if needed
if ($pass['status'] === 'active' && $isExpired) {
    $pdo->prepare("UPDATE uniform_passes SET status = 'expired' WHERE id = ?")->execute([$pass['id']]);
}

if ($isActive) {
    $statusMessage = 'Valid — This pass is active for today.';
} elseif ($isRevoked) {
    $statusMessage = 'Revoked — This pass has been cancelled by the discipline officer.';
} else {
    $statusMessage = 'Expired — This pass was valid on ' . date('M d, Y', strtotime($pass['valid_date'])) . ' only.';
}

$studentName = $pass['first_name'] . ' ' . $pass['last_name'];

echo json_encode([
    'success' => true,
    'valid' => $isActive,
    'student_name' => $studentName,
    'student_number' => $pass['student_number'],
    'grade_level' => $pass['grade_level'],
    'section' => $pass['section'],
    'reason' => $pass['reason'],
    'issued_by' => $pass['issued_by_name'],
    'valid_date' => $pass['valid_date'],
    'valid_date_formatted' => date('M d, Y', strtotime($pass['valid_date'])),
    'status' => $isActive ? 'active' : ($isRevoked ? 'revoked' : 'expired'),
    'status_message' => $statusMessage,
    'created_at' => $pass['created_at'],
    'avatar_html' => getAvatarHtml($pass['photo'] ?? null, $studentName, 'user-avatar', 'width: 48px; height: 48px; font-size: 18px; margin: 0;')
]);
