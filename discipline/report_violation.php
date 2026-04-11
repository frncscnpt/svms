<?php
/**
 * SVMS - Admin / Discipline: Submit Violation Report
 */
require_once __DIR__ . '/../includes/auth.php';

// Allow both admin and discipline_officer to access this file
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'discipline_officer'])) {
    header('Location: ' . BASE_PATH . '/login.php');
    exit;
}

$pdo = getDBConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $evidencePath = null;
        if (isset($_FILES['evidence']) && $_FILES['evidence']['error'] === UPLOAD_ERR_OK) {
            $upload = uploadFile($_FILES['evidence'], 'evidence', ALLOWED_EVIDENCE_TYPES);
            if ($upload['success']) $evidencePath = $upload['path'];
        }
        
        $stmt = $pdo->prepare("INSERT INTO violations (student_id, academic_period_id, violation_type_id, reported_by, description, evidence_path, location, date_occurred, status) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $_POST['student_id'],
            getActiveAcademicPeriodId(),
            $_POST['violation_type_id'],
            $_SESSION['user_id'],
            sanitize($_POST['description'] ?? ''),
            $evidencePath,
            sanitize($_POST['location'] ?? ''),
            $_POST['date_occurred'] ?: date('Y-m-d H:i:s'),
            'pending'
        ]);
        
        logActivity($_SESSION['user_id'], 'violation_reported', 'File report for student #' . $_POST['student_id']);
        setFlash('success', 'Violation report submitted successfully.');
        
        // Redirect back where they came from
        if ($_SESSION['role'] === 'admin') {
            header('Location: ' . BASE_PATH . '/admin/violations.php');
        } else {
            header('Location: ' . BASE_PATH . '/discipline/violations.php');
        }
        exit;
    } catch (Exception $e) {
        setFlash('danger', 'Error: ' . $e->getMessage());
    }
}

$pageTitle = 'File Report';
$breadcrumbs = [
    'Dashboard' => '/' . $_SESSION['role'] . '/dashboard.php',
    'Violations' => '/' . $_SESSION['role'] . '/violations.php',
    'File Report' => null
];

require_once __DIR__ . '/../includes/header.php';

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

<div class="row justify-content-center">
    <div class="col-lg-8">
        <form method="POST" enctype="multipart/form-data" class="card-panel">
            <div class="panel-header">
                <h5 class="panel-title"><i class="bi bi-file-earmark-text pe-2"></i> Submit Violation Report</h5>
            </div>
            <div class="panel-body">

                <!-- Student Selection -->
                <div class="mb-4 pb-3 border-bottom">
                    <h6 class="mb-3 text-primary-custom"><i class="bi bi-person-fill me-2"></i>Student Details</h6>
                    <?php if ($student): ?>
                        <input type="hidden" name="student_id" value="<?= $student['id'] ?>">
                        <div class="d-flex align-items-center gap-3">
                            <?= getAvatarHtml($student['photo'] ?? null, $student['first_name'].' '.$student['last_name'], 'profile-avatar', 'width:60px;height:60px;font-size:22px;margin:0;') ?>
                            <div>
                                <strong style="font-size: 16px;"><?= sanitize($student['first_name'].' '.$student['last_name']) ?></strong><br>
                                <span class="text-muted" style="font-size: 13px;"><?= sanitize($student['student_number']) ?> · <?= sanitize($student['grade_level'] . ' - ' . $student['section']) ?></span>
                            </div>
                        </div>
                        <a href="<?= BASE_PATH ?>/discipline/report_violation.php" class="btn btn-sm btn-outline-secondary mt-3"><i class="bi bi-arrow-repeat"></i> Change Student</a>
                    <?php else: ?>
                        <div class="form-group mb-0">
                            <label class="form-label">Select Student *</label>
                            <select class="form-select" name="student_id" required>
                                <option value="">Select student...</option>
                                <?php foreach ($students as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= sanitize($s['last_name'].', '.$s['first_name']) ?> (<?= sanitize($s['student_number']) ?>) - <?= sanitize($s['grade_level']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Violation Details -->
                <div>
                    <h6 class="mb-3 text-primary-custom"><i class="bi bi-exclamation-triangle-fill me-2"></i>Incident Information</h6>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label">Violation Type *</label>
                                <select class="form-select" name="violation_type_id" required>
                                    <option value="">Select type...</option>
                                    <?php foreach ($violationTypes as $vt): ?>
                                    <option value="<?= $vt['id'] ?>">[<?= ucfirst($vt['severity']) ?>] <?= sanitize($vt['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label">Date & Time *</label>
                                <input type="datetime-local" class="form-control" name="date_occurred" value="<?= date('Y-m-d\TH:i') ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Location</label>
                        <input type="text" class="form-control" name="location" placeholder="e.g., Room 201, Canteen, Hallway">
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Description / Remarks</label>
                        <textarea class="form-control" name="description" rows="4" placeholder="Describe the incident in detail..."></textarea>
                    </div>

                    <div class="form-group mb-4">
                        <label class="form-label">Evidence (Optional)</label>
                        <input type="file" class="form-control" name="evidence" accept="image/*,video/mp4">
                        <div class="form-text" style="font-size:11px;">Upload photo or video evidence (max 5MB). Allowed: JPG, PNG, GIF, WEBP, MP4.</div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                    <a href="<?= BASE_PATH ?>/<?= $_SESSION['role'] ?>/violations.php" class="btn-outline-custom d-inline-block text-center text-decoration-none" style="padding: 10px 20px;">Cancel</a>
                    <button type="submit" class="btn-primary-custom" style="padding: 10px 20px;"><i class="bi bi-send-fill me-1"></i> Submit Report</button>
                </div>

            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
