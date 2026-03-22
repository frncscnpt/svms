<?php
/**
 * SVMS - Teacher: My Reports
 */
$pageTitle = 'My Reports';

require_once __DIR__ . '/../includes/layout.php';

$breadcrumbs = ['Dashboard' => BASE_PATH.'/teacher/index.php', 'My Reports' => null];

if (IS_MOBILE) {
    require_once __DIR__ . '/../includes/mobile_header.php';
} else {
    require_once __DIR__ . '/../includes/header.php';
}

requireRole('teacher');

$pdo = getDBConnection();

$period_id = $_GET['period_id'] ?? '';
$where = "WHERE v.reported_by=?";
$params = [$_SESSION['user_id']];

if ($period_id) {
    $where .= " AND v.academic_period_id=?";
    $params[] = $period_id;
}

$reports_stmt = $pdo->prepare("
    SELECT v.*, s.first_name, s.last_name, s.student_number, s.grade_level, s.photo,
           vt.name as violation_name, vt.severity,
           (SELECT da.action_type FROM disciplinary_actions da WHERE da.violation_id=v.id ORDER BY da.created_at DESC LIMIT 1) as action_taken
    FROM violations v
    JOIN students s ON v.student_id=s.id
    JOIN violation_types vt ON v.violation_type_id=vt.id
    $where
    ORDER BY v.created_at DESC
");
$reports_stmt->execute($params);
$reports = $reports_stmt->fetchAll();

$periods = $pdo->query("SELECT id, name FROM academic_periods ORDER BY start_date DESC")->fetchAll();

if (IS_MOBILE):
?>

<!-- Section Header -->
<div class="m-section-header mb-2">
    <span class="m-section-title">My Reports</span>
    <a href="<?= BASE_PATH ?>/teacher/scan.php" class="m-pill purple"><i class="bi bi-plus-lg me-1"></i>New</a>
</div>

<form method="GET" class="mb-3 px-3">
    <select name="period_id" class="form-select form-select-sm rounded-pill bg-light border-0" onchange="this.form.submit()">
        <option value="">All Periods</option>
        <?php foreach ($periods as $p): ?>
        <option value="<?= $p['id'] ?>" <?= $period_id == $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option>
        <?php endforeach; ?>
    </select>
</form>

<?php if (empty($reports)): ?>
<div class="m-card">
    <div class="m-empty">
        <i class="bi bi-file-text"></i>
        <h5>No Reports Yet</h5>
        <p>Start by filing a violation report.</p>
    </div>
</div>
<div class="m-action-card mt-3">
    <h4>Ready to report?</h4>
    <p>Scan a student's QR code or manually file a violation.</p>
    <a href="<?= BASE_PATH ?>/teacher/scan.php" class="m-action-btn"><i class="bi bi-qr-code-scan"></i> Scan & Report</a>
    <span class="m-action-bg-icon"><i class="bi bi-qr-code"></i></span>
</div>
<?php else: ?>
<div class="m-card">
    <?php foreach ($reports as $r): ?>
    <div style="padding:16px 18px;border-bottom:1px solid #f7f2f8;">
        <div class="d-flex align-items-center gap-3 mb-2">
            <?= getAvatarHtml($r['photo'] ?? null, $r['first_name'].' '.$r['last_name'], 'm-list-icon', 'width:42px;height:42px;font-size:14px;border-radius:50%;flex-shrink:0;') ?>
            <div style="flex:1;min-width:0;">
                <div style="font-weight:700;font-size:14px;color:#130117;"><?= sanitize($r['first_name'].' '.$r['last_name']) ?></div>
                <div style="font-size:11px;color:#7e747c;"><?= sanitize($r['student_number']) ?> · <?= sanitize($r['grade_level']) ?></div>
            </div>
            <?= severityBadge($r['severity']) ?>
        </div>
        <div style="font-size:13px;font-weight:600;color:#2e1731;margin-bottom:4px;"><?= sanitize($r['violation_name']) ?></div>
        <div style="font-size:11px;color:#7e747c;margin-bottom:8px;"><i class="bi bi-calendar3 me-1"></i><?= formatDateTime($r['date_occurred'], 'M d, Y') ?></div>
        <div class="d-flex gap-2 flex-wrap">
            <?= statusBadge($r['status']) ?>
            <?= $r['action_taken'] ? actionBadge($r['action_taken']) : '' ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/mobile_footer.php';
else: // DESKTOP ?>

<div class="card-panel">
    <div class="panel-header">
        <h5 class="panel-title"><i class="bi bi-file-earmark-text-fill"></i> My Reports</h5>
        <a href="<?= BASE_PATH ?>/teacher/scan.php" class="btn-primary-custom" style="font-size:12px;padding:7px 16px;">
            <i class="bi bi-plus-lg"></i> New Report
        </a>
    </div>

    <div class="panel-body border-bottom bg-light py-2">
        <form method="GET" class="d-flex align-items-center gap-2 px-1">
            <span class="text-muted small fw-bold">Academic Period:</span>
            <select name="period_id" class="form-select form-select-sm" style="width: 200px;" onchange="this.form.submit()">
                <option value="">All Periods</option>
                <?php foreach ($periods as $p): ?>
                <option value="<?= $p['id'] ?>" <?= $period_id == $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if ($period_id): ?>
                <a href="<?= BASE_PATH ?>/teacher/my_reports.php" class="btn btn-link btn-sm text-secondary text-decoration-none">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (empty($reports)): ?>
    <div class="panel-body text-center py-5">
        <i class="bi bi-file-text" style="font-size:52px;color:#ede9ee;"></i>
        <p style="color:var(--text-muted);margin-top:14px;font-size:14px;">No reports submitted yet.</p>
        <a href="<?= BASE_PATH ?>/teacher/scan.php" class="btn-primary-custom mt-2"><i class="bi bi-qr-code-scan"></i> Scan & Report</a>
    </div>
    <?php else: ?>
    <div class="data-table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>Student</th>
                <th>Violation</th>
                <th>Grade</th>
                <th>Severity</th>
                <th>Date</th>
                <th>Status</th>
                <th>Action</th>
            </tr></thead>
            <tbody>
            <?php foreach ($reports as $r): ?>
            <tr>
                <td>
                    <div class="user-cell">
                                <?= getAvatarHtml($r['photo'] ?? null, $r['first_name'] . ' ' . $r['last_name'], 'user-avatar') ?>
                        <div class="user-info">
                            <div class="name"><?= sanitize($r['first_name'].' '.$r['last_name']) ?></div>
                            <div class="sub"><?= sanitize($r['student_number']) ?></div>
                        </div>
                    </div>
                </td>
                <td><?= sanitize($r['violation_name']) ?></td>
                <td><?= sanitize($r['grade_level']) ?></td>
                <td><?= severityBadge($r['severity']) ?></td>
                <td><?= formatDateTime($r['date_occurred'], 'M d, Y') ?></td>
                <td><?= statusBadge($r['status']) ?></td>
                <td><?= $r['action_taken'] ? actionBadge($r['action_taken']) : '<span style="color:var(--text-muted);font-size:12px;">—</span>' ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php';
endif; ?>
