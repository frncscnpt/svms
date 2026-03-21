<?php
/**
 * SVMS - API: Import Students Excel (.xlsx)
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['excel_file'])) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded.']);
    exit;
}

$file = $_FILES['excel_file'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'File upload error. Code: ' . $file['error']]);
    exit;
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if ($ext !== 'xlsx') {
    echo json_encode(['success' => false, 'error' => 'Invalid file format. Please upload an .xlsx file.']);
    exit;
}

require_once __DIR__ . '/../includes/SimpleXLSX.php';

$xlsx = Shuchkin\SimpleXLSX::parse($file['tmp_name']);
if (!$xlsx) {
    echo json_encode(['success' => false, 'error' => 'Could not parse Excel file: ' . Shuchkin\SimpleXLSX::parseError()]);
    exit;
}

$rows = $xlsx->rows();
if (count($rows) < 2) {
    echo json_encode(['success' => false, 'error' => 'Excel file is empty or missing data rows.']);
    exit;
}

// Get headers from first row
$headers = array_map('strtolower', array_map('trim', $rows[0]));

$required = ['student_number', 'first_name', 'last_name'];
foreach ($required as $req) {
    if (!in_array($req, $headers)) {
        echo json_encode(['success' => false, 'error' => "Missing required column: {$req}"]);
        exit;
    }
}

$pdo = getDBConnection();
$pdo->beginTransaction();

$created = 0;
$updated = 0;
$failed = 0;
$errors = [];

try {
    // Prepare statements
    $checkStmt = $pdo->prepare("SELECT id FROM students WHERE student_number = ?");
    
    $insertStudentStmt = $pdo->prepare("
        INSERT INTO students (student_number, first_name, last_name, middle_name, gender, date_of_birth, grade_level, section, status, address, contact, email, guardian_name, guardian_contact)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $updateStudentStmt = $pdo->prepare("
        UPDATE students 
        SET first_name=?, last_name=?, middle_name=?, gender=?, date_of_birth=?, grade_level=?, section=?, status=?, address=?, contact=?, email=?, guardian_name=?, guardian_contact=?
        WHERE student_number=?
    ");
    
    $qrStmt = $pdo->prepare("INSERT INTO qr_codes (student_id, qr_data) VALUES (?, ?)");
    $userStmt = $pdo->prepare("INSERT INTO users (username, password, full_name, email, role, student_id) VALUES (?,?,?,?,'student',?)");

    for ($i = 1; $i < count($rows); $i++) {
        $data = $rows[$i];
        $rowNum = $i + 1; // Excel row number
        
        // Skip completely empty lines
        if (empty(array_filter($data))) continue;
        
        // Map row data to header keys and strip any leading apostrophes (used for Excel formatting)
        $row = [];
        foreach ($headers as $j => $headerName) {
            $val = isset($data[$j]) ? trim($data[$j]) : '';
            if (strpos($val, "'") === 0) {
                $val = substr($val, 1);
            }
            $row[$headerName] = $val;
        }
        
        $stdNo = $row['student_number'] ?? '';
        $fName = $row['first_name'] ?? '';
        $lName = $row['last_name'] ?? '';
        
        if (empty($stdNo) || empty($fName) || empty($lName)) {
            $failed++;
            $errors[] = ['row' => $rowNum, 'message' => 'Missing student_number, first_name, or last_name'];
            continue;
        }
        
        // Process optional fields
        $mName = $row['middle_name'] ?? '';
        $gender = !empty($row['gender']) ? ucfirst(strtolower($row['gender'])) : 'Male';
        
        // SimpleXLSX returns dates depending on format, usually as Excel timestamp or string
        // If it's a string like 2005-08-15, it stays a string. If it's a number, it's an excel date.
        // We will just assume it's correctly formatted as string 'YYYY-MM-DD' since it's forced as text in template.
        $dob = !empty($row['date_of_birth']) ? $row['date_of_birth'] : null;
        
        $grade = $row['grade_level'] ?? '';
        $section = $row['section'] ?? '';
        
        $status = !empty($row['status']) ? strtolower($row['status']) : 'active';
        if (!in_array($status, ['active', 'inactive', 'graduated'])) $status = 'active';
        
        $address = $row['address'] ?? '';
        $contact = $row['contact'] ?? '';
        $email = $row['email'] ?? '';
        $gName = $row['guardian_name'] ?? '';
        $gContact = $row['guardian_contact'] ?? '';
        
        // Check if student exists
        $checkStmt->execute([$stdNo]);
        $existingId = $checkStmt->fetchColumn();
        
        if ($existingId) {
            // Update Existing Student
            try {
                $updateStudentStmt->execute([
                    $fName, $lName, $mName, $gender, $dob, $grade, $section, $status, $address, $contact, $email, $gName, $gContact,
                    $stdNo
                ]);
                $updated++;
            } catch (Exception $e) {
                $failed++;
                $errors[] = ['row' => $rowNum, 'message' => 'Update failed: ' . $e->getMessage()];
            }
        } else {
            // Insert New Student
            try {
                $insertStudentStmt->execute([
                    $stdNo, $fName, $lName, $mName, $gender, $dob, $grade, $section, $status, $address, $contact, $email, $gName, $gContact
                ]);
                $newId = $pdo->lastInsertId();
                
                // Auto-generate QR code
                $qrData = QR_PREFIX . generateUUID();
                $qrStmt->execute([$newId, $qrData]);
                
                // Create login account
                $password = password_hash('student123', PASSWORD_DEFAULT);
                $fullName = $fName . ' ' . $lName;
                $userStmt->execute([$stdNo, $password, $fullName, $email, 'student', $newId]);
                
                $created++;
            } catch (Exception $e) {
                $failed++;
                $errors[] = ['row' => $rowNum, 'message' => 'Insert failed: ' . $e->getMessage()];
            }
        }
    }
    
    $pdo->commit();
    echo json_encode([
        'success' => true,
        'created' => $created,
        'updated' => $updated,
        'failed' => $failed,
        'errors' => $errors
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
