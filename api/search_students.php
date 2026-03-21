<?php
/**
 * SVMS - Student Search API
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$query = trim($_GET['q'] ?? '');

if (empty($query)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a search term']);
    exit;
}

$pdo = getDBConnection();

// Search by student_number (exact/partial match) or first/last name
$stmt = $pdo->prepare("
    SELECT s.*, 
           (SELECT COUNT(*) FROM violations WHERE student_id=s.id) as violation_count
    FROM students s
    WHERE s.status = 'active'
      AND (s.student_number LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ?)
    ORDER BY s.last_name, s.first_name
    LIMIT 20
");

$searchParam = "%{$query}%";
$stmt->execute([$searchParam, $searchParam, $searchParam]);
$results = $stmt->fetchAll();

if ($results) {
    // If multiple results, just return them to the UI
    // For the UI we designed in scan.php, we primarily expect a single result if they scanned or searched exact.
    // If they searched partial, we might need a list. But the current UI is designed for a single card.
    // Let's modify the response to return a list, and the frontend can handle showing the first one or a list.
    
    $formattedOptions = array_map(function($s) {
        return [
            'id' => $s['id'],
            'student_number' => $s['student_number'],
            'first_name' => $s['first_name'],
            'last_name' => $s['last_name'],
            'middle_name' => $s['middle_name'],
            'gender' => $s['gender'],
            'grade_level' => $s['grade_level'],
            'section' => $s['section'],
            'contact' => $s['contact'],
            'guardian_name' => $s['guardian_name'],
            'guardian_contact' => $s['guardian_contact'],
            'violation_count' => $s['violation_count'],
            'avatar_html' => getAvatarHtml($s['photo'] ?? null, $s['first_name'] . ' ' . $s['last_name'], 'user-avatar', 'width: 48px; height: 48px; font-size: 18px; margin: 0;')
        ];
    }, $results);

    echo json_encode([
        'success' => true,
        'results' => $formattedOptions
        // Also provide the 'student' key for backwards compatibility with the existing qr_lookup JS logic if needed,
        // we'll just map the first result to it so the same JS can be reused easily.
    ] + ['student' => $formattedOptions[0]]);
} else {
    echo json_encode(['success' => false, 'message' => 'No students found matching your search']);
}
