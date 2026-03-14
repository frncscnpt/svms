<?php
/**
 * SVMS - Handle Profile Avatar Upload
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error occurred.']);
    exit;
}

// 1. Validate file (size & type)
$file = $_FILES['avatar'];
if ($file['size'] > MAX_FILE_SIZE) {
    echo json_encode(['success' => false, 'message' => 'File too large. Maximum size is 5MB.']);
    exit;
}

if (!in_array($file['type'], ALLOWED_IMAGE_TYPES)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.']);
    exit;
}

// 2. Prepare upload directory
$uploadDir = UPLOAD_DIR . 'avatars/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// 3. Generate unique filename
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'avatar_' . $_SESSION['user_id'] . '_' . uniqid() . '.' . $ext;
$destPath = $uploadDir . $filename;
$publicPath = '/uploads/avatars/' . $filename;

// 4. Move uploaded file
if (move_uploaded_file($file['tmp_name'], $destPath)) {
    try {
        $pdo = getDBConnection();
        $pdo->beginTransaction();

        // 5. Delete old avatar if exists
        if (!empty($_SESSION['avatar'])) {
            // Remove the leading / if present to get relative path for deletion
            $oldFile = __DIR__ . '/..' . $_SESSION['avatar'];
            if (file_exists($oldFile)) {
                unlink($oldFile);
            }
        }

        // 6. Update database record
        $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
        $stmt->execute([$publicPath, $_SESSION['user_id']]);

        // If student, update the students table as well
        if ($_SESSION['role'] === 'student' && !empty($_SESSION['student_id'])) {
            $studentStmt = $pdo->prepare("UPDATE students SET photo = ? WHERE id = ?");
            $studentStmt->execute([$publicPath, $_SESSION['student_id']]);
        }

        // 7. Update session
        $_SESSION['avatar'] = $publicPath;

        $pdo->commit();
        echo json_encode(['success' => true, 'avatar_url' => BASE_PATH . $publicPath]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        // Attempt to clean up uploaded file if DB update fails
        if (file_exists($destPath)) {
            unlink($destPath);
        }
        echo json_encode(['success' => false, 'message' => 'Database error during upload.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save the uploaded file.']);
}
