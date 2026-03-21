<?php
/**
 * SVMS - Teacher: Submit Violation Report
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('teacher');

$pdo = getDBConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $evidencePath = null;
        if (isset($_FILES['evidence']) && $_FILES['evidence']['error'] === UPLOAD_ERR_OK) {
            $upload = uploadFile($_FILES['evidence'], 'evidence', ALLOWED_EVIDENCE_TYPES);
            if ($upload['success']) $evidencePath = $upload['path'];
        }
        
        $stmt = $pdo->prepare("INSERT INTO violations (student_id, violation_type_id, reported_by, description, evidence_path, location, date_occurred, status) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $_POST['student_id'],
            $_POST['violation_type_id'],
            $_SESSION['user_id'],
            sanitize($_POST['description'] ?? ''),
            $evidencePath,
            sanitize($_POST['location'] ?? ''),
            $_POST['date_occurred'] ?: date('Y-m-d H:i:s'),
            'pending'
        ]);
        
        logActivity($_SESSION['user_id'], 'violation_reported', 'Reported violation for student #' . $_POST['student_id']);
        
        // Notify the student
        $stmtStu = $pdo->prepare("SELECT id FROM users WHERE student_id = ?");
        $stmtStu->execute([$_POST['student_id']]);
        $studentUserId = $stmtStu->fetchColumn();
        
        if ($studentUserId) {
            $vType = $pdo->prepare("SELECT name FROM violation_types WHERE id = ?");
            $vType->execute([$_POST['violation_type_id']]);
            $typeName = $vType->fetchColumn();
            
            addNotification(
                $studentUserId, 
                "New Violation Recorded", 
                "A new report for '$typeName' has been filed against you. Please check your violations list.", 
                'warning', 
                '/student/violations.php'
            );
        }
        
        // Notify all Discipline Officers & Admins
        $stmtOps = $pdo->prepare("SELECT id FROM users WHERE role IN ('admin', 'discipline_officer')");
        $stmtOps->execute();
        $ops = $stmtOps->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($ops as $opId) {
            addNotification(
                $opId, 
                "New Violation Reported", 
                "A teacher has reported a new violation for Student #" . $_POST['student_id'], 
                'info', 
                '/discipline/violations.php'
            );
        }

        setFlash('success', 'Violation report submitted successfully.');
        header('Location: ' . BASE_PATH . '/teacher/my_reports.php');
        exit;
    } catch (Exception $e) {
        setFlash('danger', 'Error: ' . $e->getMessage());
    }
}

// Now include header (HTML output starts here)
$pageTitle = 'File Report';
$breadcrumbs = ['Dashboard' => BASE_PATH.'/teacher/index.php', 'File Report' => null];
require_once __DIR__ . '/../includes/layout.php';
if (IS_MOBILE) {
    require_once __DIR__ . '/../includes/mobile_header.php';
} else {
    require_once __DIR__ . '/../includes/header.php';
}

$studentId = $_GET['student_id'] ?? '';
$student = null;
if ($studentId) {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id=? AND status='active'");
    $stmt->execute([$studentId]);
    $student = $stmt->fetch();
}

$students = $pdo->query("SELECT id, student_number, first_name, last_name, grade_level, section FROM students WHERE status='active' ORDER BY last_name, first_name")->fetchAll();
$violationTypes = $pdo->query("SELECT * FROM violation_types WHERE status='active' ORDER BY severity DESC, name")->fetchAll();
?>

<div class="<?= IS_MOBILE ? '' : 'row justify-content-center' ?>">
<div class="<?= IS_MOBILE ? '' : 'col-lg-7' ?>">
<form method="POST" enctype="multipart/form-data" class="<?= IS_MOBILE ? 'm-form' : '' ?>">

    <!-- Student Selection -->
    <div class="card-panel mb-3">
        <div class="panel-header"><h5 class="panel-title"><i class="bi bi-person-fill"></i> Student</h5></div>
        <div class="panel-body">
            <?php if ($student): ?>
                <input type="hidden" name="student_id" value="<?= $student['id'] ?>">
                <div class="d-flex align-items-center gap-3">
                    <?= getAvatarHtml($student['photo'] ?? null, $student['first_name'].' '.$student['last_name'], 'mobile-profile-avatar', 'width:50px;height:50px;font-size:18px;margin:0;') ?>
                    <div>
                        <strong><?= sanitize($student['first_name'].' '.$student['last_name']) ?></strong><br>
                        <small class="text-muted"><?= sanitize($student['student_number']) ?> · <?= sanitize($student['grade_level']) ?></small>
                    </div>
                </div>
                <a href="<?= BASE_PATH ?>/teacher/report.php" class="d-block mt-2" style="font-size:12px;"><i class="bi bi-arrow-repeat"></i> Change student</a>
            <?php else: ?>
                <div class="form-group mb-0">
                    <select class="form-select" name="student_id" required>
                        <option value="">Select student...</option>
                        <?php foreach ($students as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= sanitize($s['last_name'].', '.$s['first_name']) ?> (<?= sanitize($s['student_number']) ?>) - <?= sanitize($s['grade_level']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="text-center mt-2">
                    <a href="<?= BASE_PATH ?>/teacher/scan.php" style="font-size:12px;"><i class="bi bi-qr-code-scan"></i> Or scan QR code</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Violation Details -->
    <div class="card-panel mb-3">
        <div class="panel-header"><h5 class="panel-title"><i class="bi bi-exclamation-triangle-fill"></i> Violation Details</h5></div>
        <div class="panel-body">
            <div class="form-group">
                <label class="form-label">Violation Type *</label>
                <select class="form-select" name="violation_type_id" required>
                    <option value="">Select type...</option>
                    <?php foreach ($violationTypes as $vt): ?>
                    <option value="<?= $vt['id'] ?>">[<?= ucfirst($vt['severity']) ?>] <?= sanitize($vt['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="description" rows="3" placeholder="Describe the incident..."></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Location</label>
                <input type="text" class="form-control" name="location" placeholder="e.g., Room 201, Hallway">
            </div>
            <div class="form-group">
                <label class="form-label">Date & Time</label>
                <input type="datetime-local" class="form-control" name="date_occurred" value="<?= date('Y-m-d\TH:i') ?>">
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Evidence (Optional)</label>
                <input type="file" class="form-control" name="evidence" accept="image/*,video/mp4" capture="environment">
                <div class="form-text" style="font-size:11px;">Photo or video evidence (max 5MB)</div>
            </div>
        </div>
    </div>

    <button type="submit" class="<?= IS_MOBILE ? 'm-submit-btn' : 'btn-primary-custom w-100' ?>" style="padding:13px;font-size:15px;justify-content:center;margin-bottom:20px;">
        <i class="bi bi-send-fill me-1"></i> Submit Report
    </button>
</form>
</div><?= IS_MOBILE ? '' : '</div>' ?>

<?php
if (IS_MOBILE) {
    require_once __DIR__ . '/../includes/mobile_footer.php';
} else {
    require_once __DIR__ . '/../includes/footer.php';
}
?>
