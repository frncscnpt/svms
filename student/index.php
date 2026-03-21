<?php
/**
 * SVMS - Student: Dashboard
 */
$pageTitle = 'Dashboard';

require_once __DIR__ . '/../includes/layout.php';

$breadcrumbs = ['Dashboard' => null];

if (IS_MOBILE) {
    require_once __DIR__ . '/../includes/mobile_header.php';
} else {
    require_once __DIR__ . '/../includes/header.php';
}

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

if (IS_MOBILE):
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

<?php require_once __DIR__ . '/../includes/mobile_footer.php';
else: // DESKTOP ?>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card stat-purple">
            <div class="stat-header">
                <span class="stat-label">Total Violations</span>
                <div class="stat-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
            </div>
            <div class="stat-value"><?= $totalViolations ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-orange">
            <div class="stat-header">
                <span class="stat-label">Pending Actions</span>
                <div class="stat-icon"><i class="bi bi-hammer"></i></div>
            </div>
            <div class="stat-value"><?= $pendingActions ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-green">
            <div class="stat-header">
                <span class="stat-label">Grade Level</span>
                <div class="stat-icon"><i class="bi bi-mortarboard-fill"></i></div>
            </div>
            <div class="stat-value" style="font-size:20px;"><?= sanitize($student['grade_level'] ?? '—') ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-blue">
            <div class="stat-header">
                <span class="stat-label">Section</span>
                <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
            </div>
            <div class="stat-value" style="font-size:20px;"><?= sanitize($student['section'] ?? '—') ?></div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Recent Violations -->
    <div class="col-lg-8">
        <div class="card-panel">
            <div class="panel-header">
                <h5 class="panel-title"><i class="bi bi-clock-history"></i> Recent Violations</h5>
                <a href="<?= BASE_PATH ?>/student/violations.php" class="btn-outline-custom" style="font-size:12px;padding:5px 14px;">View All</a>
            </div>
            <?php if (empty($recent)): ?>
            <div class="panel-body text-center py-5">
                <i class="bi bi-shield-check" style="font-size:48px;color:#ede9ee;"></i>
                <p style="color:var(--text-muted);margin-top:12px;font-size:14px;">No violations on record. Keep it up!</p>
            </div>
            <?php else: ?>
            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead><tr>
                        <th>Violation</th>
                        <th>Severity</th>
                        <th>Reported By</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($recent as $r): ?>
                    <tr>
                        <td><?= sanitize($r['violation_name']) ?></td>
                        <td><?= severityBadge($r['severity']) ?></td>
                        <td><?= sanitize($r['reporter']) ?></td>
                        <td><?= formatDateTime($r['date_occurred'], 'M d, Y') ?></td>
                        <td><?= statusBadge($r['status']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Profile Widget -->
    <div class="col-lg-4">
        <div class="card-panel">
            <div class="panel-body text-center" style="padding:28px 24px;">
                <?= getAvatarHtml($student['photo'], $student['first_name'].' '.$student['last_name'], 'user-avatar', 'width:72px;height:72px;font-size:24px;margin:0 auto 12px;') ?>
                <div style="font-weight:700;font-size:17px;color:var(--primary);"><?= sanitize($student['first_name'].' '.$student['last_name']) ?></div>
                <div style="font-size:13px;color:var(--text-muted);margin-bottom:16px;"><?= sanitize($student['student_number']) ?></div>
                <a href="<?= BASE_PATH ?>/student/profile.php" class="btn-outline-custom w-100" style="justify-content:center;"><i class="bi bi-person-fill me-1"></i> View Profile</a>
            </div>
        </div>

        <?php if (!empty($activeActions)): $aa = $activeActions[0]; ?>
        <div class="card-panel mt-3" style="background:#f7f2f8;">
            <div class="panel-header"><h5 class="panel-title"><i class="bi bi-hammer"></i> Active Action</h5></div>
            <div class="panel-body">
                <div style="font-weight:700;font-size:14px;color:#130117;"><?= ucwords(str_replace('_',' ',$aa['action_type'])) ?></div>
                <div style="font-size:12px;color:#7e747c;margin-bottom:8px;"><?= sanitize($aa['violation_name']) ?></div>
                <?= statusBadge($aa['status']) ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php';
endif; ?>
