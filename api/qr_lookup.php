<?php
/**
 * SVMS - QR Code Lookup API
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$qrData = $_GET['qr'] ?? '';

if (empty($qrData)) {
    echo json_encode(['success' => false, 'message' => 'No QR code data provided']);
    exit;
}

$pdo = getDBConnection();

$stmt = $pdo->prepare("
    SELECT s.*, q.qr_data,
           (SELECT COUNT(*) FROM violations WHERE student_id=s.id) as violation_count
    FROM qr_codes q
    JOIN students s ON q.student_id = s.id
    WHERE q.qr_data = ? AND s.status = 'active'
");
$stmt->execute([$qrData]);
$student = $stmt->fetch();

if ($student) {
    echo json_encode([
        'success' => true,
        'student' => [
            'id' => $student['id'],
            'student_number' => $student['student_number'],
            'first_name' => $student['first_name'],
            'last_name' => $student['last_name'],
            'middle_name' => $student['middle_name'],
            'gender' => $student['gender'],
            'grade_level' => $student['grade_level'],
            'section' => $student['section'],
            'contact' => $student['contact'],
            'guardian_name' => $student['guardian_name'],
            'guardian_contact' => $student['guardian_contact'],
            'violation_count' => $student['violation_count'],
            'avatar_html' => getAvatarHtml($student['photo'] ?? null, $student['first_name'] . ' ' . $student['last_name'], 'user-avatar', 'width: 48px; height: 48px; font-size: 18px; margin: 0;')
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'No student found for this QR code']);
}
