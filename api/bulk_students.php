<?php
/**
 * SVMS - API: Bulk Student Actions
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

header('Content-Type: application/json');

$pdo = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Count Action
    if (isset($_GET['action']) && $_GET['action'] === 'count') {
        $grade = $_GET['grade'] ?? '';
        $section = $_GET['section'] ?? '';
        
        if (!$grade || !$section) {
            echo json_encode(['success' => false, 'error' => 'Missing target grade or section.']);
            exit;
        }
        
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE grade_level = ? AND section = ? AND status = 'active'");
            $stmt->execute([$grade, $section]);
            $count = $stmt->fetchColumn();
            
            echo json_encode(['success' => true, 'count' => (int)$count]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Database error.']);
        }
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Execute Bulk Action
    $targetGrade = $_POST['target_grade'] ?? '';
    $targetSection = $_POST['target_section'] ?? '';
    $actionType = $_POST['action_type'] ?? '';
    
    if (!$targetGrade || !$targetSection || !$actionType) {
        echo json_encode(['success' => false, 'error' => 'Missing required parameters.']);
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        if ($actionType === 'promote') {
            $newGrade = $_POST['new_grade'] ?? '';
            $newSection = $_POST['new_section'] ?? '';
            
            if (!$newGrade || !$newSection) {
                echo json_encode(['success' => false, 'error' => 'Missing new grade level or section for promotion.']);
                exit;
            }
            
            $stmt = $pdo->prepare("UPDATE students SET grade_level = ?, section = ? WHERE grade_level = ? AND section = ? AND status = 'active'");
            $stmt->execute([$newGrade, $newSection, $targetGrade, $targetSection]);
            $affected = $stmt->rowCount();
            
        } elseif ($actionType === 'graduate') {
            $stmt = $pdo->prepare("UPDATE students SET status = 'graduated' WHERE grade_level = ? AND section = ? AND status = 'active'");
            $stmt->execute([$targetGrade, $targetSection]);
            $affected = $stmt->rowCount();
            
        } elseif ($actionType === 'inactive') {
            $stmt = $pdo->prepare("UPDATE students SET status = 'inactive' WHERE grade_level = ? AND section = ? AND status = 'active'");
            $stmt->execute([$targetGrade, $targetSection]);
            $affected = $stmt->rowCount();
            
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid action type.']);
            exit;
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'affected' => $affected]);
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
