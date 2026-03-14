<?php
/**
 * SVMS - Student: Mobile Dashboard
 */
$pageTitle = 'Dashboard';
$pageSubtitle = 'Student Portal';
require_once __DIR__ . '/../includes/mobile_header.php';
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

// Recent violations
$recent = $pdo->prepare("
    SELECT v.*, vt.name as violation_name, vt.severity, u.full_name as reporter
    FROM violations v JOIN violation_types vt ON v.violation_type_id=vt.id
    JOIN users u ON v.reported_by=u.id
    WHERE v.student_id=? ORDER BY v.created_at DESC LIMIT 5
");
$recent->execute([$studentId]);
$recent = $recent->fetchAll();

// Active actions
$activeActions = $pdo->prepare("
    SELECT da.*, vt.name as violation_name
    FROM disciplinary_actions da JOIN violations v ON da.violation_id=v.id
    JOIN violation_types vt ON v.violation_type_id=vt.id
    WHERE v.student_id=? AND da.status IN ('pending','active')
    ORDER BY da.created_at DESC
");
$activeActions->execute([$studentId]);
$activeActions = $activeActions->fetchAll();
?>

<div class="mobile-greeting">
    <h2>Hello, <?= sanitize($student['first_name']) ?>!</h2>
    <p><?= sanitize($student['grade_level'] . ' - ' . $student['section']) ?> · <?= sanitize($student['student_number']) ?></p>
</div>

<div class="mobile-stats">
    <div class="mobile-stat">
        <div class="stat-num"><?= $totalViolations ?></div>
        <div class="stat-text">Violations</div>
    </div>
    <div class="mobile-stat">
        <div class="stat-num" style="color:<?= $pendingActions > 0 ? 'var(--danger)' : 'var(--success)' ?>;"><?= $pendingActions ?></div>
        <div class="stat-text">Active Actions</div>
    </div>
</div>

<?php if (!empty($activeActions)): ?>
<div class="mobile-section-title" style="margin-top:28px;">
    <span><i class="bi bi-exclamation-circle-fill text-danger me-1"></i> Active Actions</span>
</div>
<?php foreach ($activeActions as $aa): ?>
<div class="mobile-card mb-2">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <?= actionBadge($aa['action_type']) ?>
                <div style="font-size:13px;margin-top:6px;font-weight:600;"><?= sanitize($aa['violation_name']) ?></div>
                <?php if ($aa['description']): ?><p style="font-size:12px;color:var(--text-muted);margin:4px 0 0;"><?= sanitize(substr($aa['description'],0,100)) ?></p><?php endif; ?>
            </div>
            <?= statusBadge($aa['status']) ?>
        </div>
        <?php if ($aa['start_date']): ?>
        <div style="font-size:11px;color:var(--text-muted);margin-top:8px;">
            <i class="bi bi-calendar3"></i> <?= formatDate($aa['start_date']) ?><?= $aa['end_date'] ? ' - ' . formatDate($aa['end_date']) : '' ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<div class="mobile-section-title">
    Recent Violations
    <a href="<?= BASE_PATH ?>/student/violations.php" class="see-all">See All →</a>
</div>

<div class="mobile-card">
    <?php if (empty($recent)): ?>
    <div class="mobile-empty">
        <img src="<?= BASE_PATH ?>/assets/img/icons/icon-96.png" alt="SVMS Logo" style="width: 64px; height: 64px; margin-bottom: 12px; object-fit: contain;">

        <h5>No Violations</h5>
        <p>You have a clean record. Keep it up!</p>
    </div>
    <?php else: ?>
        <?php foreach ($recent as $r): ?>
        <div class="mobile-list-item">
            <div class="item-icon <?= $r['severity'] ?>">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <div class="item-content">
                <div class="item-title"><?= sanitize($r['violation_name']) ?></div>
                <div class="item-sub">Reported by <?= sanitize($r['reporter']) ?> · <?= timeAgo($r['created_at']) ?></div>
            </div>
            <div class="item-badge"><?= statusBadge($r['status']) ?></div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/mobile_footer.php'; ?>
