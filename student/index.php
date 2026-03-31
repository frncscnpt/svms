<?php
/**
 * SVMS - Student: Dashboard
 */
$pageTitle = 'Dashboard';

require_once __DIR__ . '/../includes/layout.php';

$breadcrumbs = ['Dashboard' => null];

require_once __DIR__ . '/../includes/header.php';

requireRole('student');

$pdo = getDBConnection();
$studentId = $_SESSION['student_id'];

if (!$studentId) {
    setFlash('danger', 'Student profile not linked. Please contact admin.');
    header('Location: ' . BASE_PATH . '/index.php');
    exit;
}

$student = $pdo->prepare("SELECT * FROM students WHERE id=?");
$student->execute([$studentId]);
$student = $student->fetch();

$totalViolations = $pdo->prepare("SELECT COUNT(*) FROM violations WHERE student_id=?");
$totalViolations->execute([$studentId]);
$totalViolations = $totalViolations->fetchColumn();

$pendingActions = $pdo->prepare("SELECT COUNT(*) FROM disciplinary_actions da JOIN violations v ON da.violation_id=v.id WHERE v.student_id=? AND da.status IN ('pending','active')");
$pendingActions->execute([$studentId]);
$pendingActions = $pendingActions->fetchColumn();

$recent = $pdo->prepare("
    SELECT v.*, vt.name as violation_name, vt.severity, u.full_name as reporter
    FROM violations v JOIN violation_types vt ON v.violation_type_id=vt.id
    JOIN users u ON v.reported_by=u.id
    WHERE v.student_id=? ORDER BY v.created_at DESC LIMIT 5
");
$recent->execute([$studentId]);
$recent = $recent->fetchAll();

$activeActions = $pdo->prepare("
    SELECT da.*, vt.name as violation_name
    FROM disciplinary_actions da JOIN violations v ON da.violation_id=v.id
    JOIN violation_types vt ON v.violation_type_id=vt.id
    WHERE v.student_id=? AND da.status IN ('pending','active')
    ORDER BY da.created_at DESC LIMIT 1
");
$activeActions->execute([$studentId]);
$activeActions = $activeActions->fetchAll();

$activePass = null;
$pdo->exec("UPDATE uniform_passes SET status = 'expired' WHERE status = 'active' AND valid_date < CURDATE()");
$passStmt = $pdo->prepare("SELECT up.*, u.full_name AS issued_by_name FROM uniform_passes up JOIN users u ON up.issued_by = u.id WHERE up.student_id = ? AND up.status = 'active' AND up.valid_date = CURDATE() ORDER BY up.created_at DESC LIMIT 1");
$passStmt->execute([$studentId]);
$activePass = $passStmt->fetch();
?>

<!-- Greeting -->
<div class="m-greeting">
    <div class="m-greeting-sub">Welcome back,</div>
    <div class="m-greeting-name">Hi, <?= sanitize($student['first_name']) ?>!</div>
</div>

<!-- Bento Stats -->
<div class="m-stats">
    <div class="m-stat primary">
        <div class="m-stat-header">
            <i class="bi bi-exclamation-triangle-fill m-stat-icon"></i>
            <span class="m-stat-label">Total</span>
        </div>
        <div>
            <div class="m-stat-value"><?= str_pad($totalViolations, 2, '0', STR_PAD_LEFT) ?></div>
            <div class="m-stat-desc">Violations Recorded</div>
        </div>
    </div>
    <div class="m-stat secondary">
        <div class="m-stat-header">
            <i class="bi bi-hammer m-stat-icon"></i>
            <span class="m-stat-label">Active</span>
        </div>
        <div>
            <div class="m-stat-value"><?= str_pad($pendingActions, 2, '0', STR_PAD_LEFT) ?></div>
            <div class="m-stat-desc">Pending Action<?= $pendingActions !== 1 ? 's' : '' ?></div>
        </div>
    </div>
</div>

<!-- Active Disciplinary Action -->
<?php if (!empty($activeActions)): $aa = $activeActions[0]; ?>
<div class="m-section">
    <div class="m-section-header">
        <span class="m-section-title">Active Action</span>
    </div>
    <div class="m-card" style="background:#f7f2f8;border-color:#ede9ee;">
        <div class="m-card-body">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div style="width:48px;height:48px;border-radius:14px;background:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 1px 4px rgba(0,0,0,0.06);">
                    <i class="bi bi-person-check-fill" style="font-size:22px;color:#2e1731;"></i>
                </div>
                <div>
                    <div style="font-weight:700;font-size:14px;color:#130117;"><?= ucwords(str_replace('_',' ',$aa['action_type'])) ?></div>
                    <div style="font-size:12px;color:#7e747c;"><?= sanitize($aa['violation_name']) ?></div>
                </div>
            </div>
            <?php if ($aa['description']): ?>
            <p style="font-size:12px;color:#4c444b;margin-bottom:12px;line-height:1.5;"><?= sanitize(substr($aa['description'],0,100)) ?></p>
            <?php endif; ?>
            <div class="d-flex gap-2 flex-wrap">
                <?= statusBadge($aa['status']) ?>
                <?php if ($aa['start_date']): ?>
                <span class="m-pill white"><i class="bi bi-calendar3 me-1"></i><?= formatDate($aa['start_date']) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Recent Violations -->
<div class="m-section">
    <div class="m-section-header">
        <span class="m-section-title">Recent Violations</span>
        <a href="<?= BASE_PATH ?>/student/violations.php" class="m-section-link">View All</a>
    </div>
    <div class="m-card">
        <?php if (empty($recent)): ?>
        <div class="m-empty">
            <i class="bi bi-shield-check"></i>
            <h5>Clean Record</h5>
            <p>You have no violations. Keep it up!</p>
        </div>
        <?php else: ?>
            <?php foreach ($recent as $r): ?>
            <div class="m-list-item">
                <div class="m-list-icon <?= $r['severity'] ?>">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <div class="m-list-content">
                    <div class="m-list-title"><?= sanitize($r['violation_name']) ?></div>
                    <div class="m-list-sub"><?= formatDateTime($r['date_occurred'], 'M d, Y') ?> · <?= sanitize($r['reporter']) ?></div>
                </div>
                <?= severityBadge($r['severity']) ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Support Card -->
<div class="m-section">
    <div class="m-action-card">
        <h4>Need guidance?</h4>
        <p>Connect with our counseling office to discuss your records.</p>
        <a href="#" class="m-action-btn"><i class="bi bi-chat-dots-fill"></i> Contact Office</a>
        <span class="m-action-bg-icon"><i class="bi bi-person-circle"></i></span>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
