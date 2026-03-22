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

$activePass = null;
$pdo->exec("UPDATE uniform_passes SET status = 'expired' WHERE status = 'active' AND valid_date < CURDATE()");
$passStmt = $pdo->prepare("SELECT up.*, u.full_name AS issued_by_name FROM uniform_passes up JOIN users u ON up.issued_by = u.id WHERE up.student_id = ? AND up.status = 'active' AND up.valid_date = CURDATE() ORDER BY up.created_at DESC LIMIT 1");
$passStmt->execute([$studentId]);
$activePass = $passStmt->fetch();

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

    <!-- Uniform Pass Widget -->
    <div class="col-lg-4">
        <?php if ($activePass): ?>
        <div class="card-panel" style="overflow:hidden;">
            <!-- Header -->
            <div style="background:linear-gradient(135deg,#065f46,#059669);padding:18px 20px;color:white;display:flex;align-items:center;gap:12px;">
                <div style="width:38px;height:38px;background:rgba(255,255,255,0.15);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="bi bi-check-circle-fill" style="font-size:18px;"></i>
                </div>
                <div>
                    <div style="font-weight:700;font-size:14px;">Uniform Pass Active</div>
                    <div style="font-size:11px;color:rgba(255,255,255,0.7);">Valid today</div>
                </div>
                <span class="ms-auto" style="background:rgba(255,255,255,0.2);color:white;font-size:10px;font-weight:700;padding:4px 10px;border-radius:99px;">ACTIVE</span>
            </div>
            <!-- QR + Details -->
            <div style="display:flex;">
                <!-- QR -->
                <div style="flex:0 0 auto;padding:16px;border-right:1px solid #ede9ee;background:#f8fdf8;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;">
                    <div id="dashPassQR" style="display:inline-block;padding:8px;background:white;border-radius:10px;box-shadow:0 1px 6px rgba(0,0,0,0.07);"></div>
                    <p style="font-size:10px;color:var(--text-muted);margin:0;text-align:center;line-height:1.4;">Show to teacher</p>
                </div>
                <!-- Details -->
                <div style="flex:1;display:flex;flex-direction:column;justify-content:center;">
                    <div class="d-flex justify-content-between align-items-center py-2 px-3 border-bottom" style="font-size:12px;">
                        <span class="text-muted">Reason</span>
                        <strong style="max-width:120px;text-align:right;font-size:11px;"><?= sanitize($activePass['reason']) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2 px-3 border-bottom" style="font-size:12px;">
                        <span class="text-muted">Issued By</span>
                        <strong style="font-size:11px;"><?= sanitize($activePass['issued_by_name']) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2 px-3 border-bottom" style="font-size:12px;">
                        <span class="text-muted">Issued</span>
                        <strong style="font-size:11px;"><?= timeAgo($activePass['created_at']) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2 px-3 border-bottom" style="font-size:12px;">
                        <span class="text-muted">Valid Until</span>
                        <strong style="font-size:11px;"><?= formatDate($activePass['valid_date']) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2 px-3" style="font-size:12px;">
                        <span class="text-muted">Pass Code</span>
                        <code style="font-size:9px;color:var(--primary);"><?= sanitize($activePass['pass_code']) ?></code>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="card-panel">
            <div class="panel-body text-center" style="padding:32px 24px;">
                <div style="width:56px;height:56px;background:#f7f2f8;border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
                    <i class="bi bi-card-checklist" style="font-size:26px;color:#ede9ee;"></i>
                </div>
                <div style="font-weight:700;font-size:15px;color:var(--primary);margin-bottom:6px;">No Active Pass</div>
                <div style="font-size:12px;color:var(--text-muted);">You don't have a uniform pass for today.</div>
            </div>
        </div>
        <?php endif; ?>

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

<?php
if ($activePass) {
    echo '<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script>
    new QRCode(document.getElementById("dashPassQR"), {
        text: ' . json_encode($activePass['pass_code']) . ',
        width: 120,
        height: 120,
        colorDark: "#000000",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });
    </script>';
}
require_once __DIR__ . '/../includes/footer.php';
endif; ?>
