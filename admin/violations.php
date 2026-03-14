<?php
/**
 * SVMS - Admin: Violations
 */
$pageTitle = 'Violations';
$breadcrumbs = ['Dashboard' => '/admin/dashboard.php', 'Violations' => null];
require_once __DIR__ . '/../includes/header.php';
requireRole('admin');

$pdo = getDBConnection();

$search = $_GET['search'] ?? '';
$severity = $_GET['severity'] ?? '';
$status = $_GET['status'] ?? '';
$where = "WHERE 1=1";
$params = [];

if ($search) { $where .= " AND (s.first_name LIKE ? OR s.last_name LIKE ? OR s.student_number LIKE ?)"; $params = array_merge($params, ["%$search%","%$search%","%$search%"]); }
if ($severity) { $where .= " AND vt.severity=?"; $params[] = $severity; }
if ($status) { $where .= " AND v.status=?"; $params[] = $status; }

$stmt = $pdo->prepare("
    SELECT v.*, s.first_name, s.last_name, s.student_number, vt.name as violation_name, vt.severity,
           u.full_name as reporter_name,
           (SELECT da.action_type FROM disciplinary_actions da WHERE da.violation_id=v.id ORDER BY da.created_at DESC LIMIT 1) as action_taken
    FROM violations v
    JOIN students s ON v.student_id=s.id
    JOIN violation_types vt ON v.violation_type_id=vt.id
    JOIN users u ON v.reported_by=u.id
    $where ORDER BY v.created_at DESC LIMIT 50
");
$stmt->execute($params);
$violations = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <p class="text-muted mb-0" style="font-size:13px;">View and manage all violation records</p>
    </div>
    <a href="<?= BASE_PATH ?>/admin/report_violation.php" class="btn-primary-custom" style="padding: 10px 20px; text-decoration: none;">
        <i class="bi bi-file-earmark-plus pe-2"></i>File Direct Report
    </a>
</div>

<div class="card-panel">
    <form class="filter-bar" method="GET">
        <div class="search-box">
            <i class="bi bi-search"></i>
            <input type="text" name="search" placeholder="Search student..." value="<?= sanitize($search) ?>">
        </div>
        <select name="severity" class="form-select" style="width:auto">
            <option value="">All Severity</option>
            <option value="minor" <?= $severity==='minor'?'selected':'' ?>>Minor</option>
            <option value="major" <?= $severity==='major'?'selected':'' ?>>Major</option>
            <option value="critical" <?= $severity==='critical'?'selected':'' ?>>Critical</option>
        </select>
        <select name="status" class="form-select" style="width:auto">
            <option value="">All Status</option>
            <option value="pending" <?= $status==='pending'?'selected':'' ?>>Pending</option>
            <option value="reviewed" <?= $status==='reviewed'?'selected':'' ?>>Reviewed</option>
            <option value="resolved" <?= $status==='resolved'?'selected':'' ?>>Resolved</option>
        </select>
        <button type="submit" class="btn-primary-custom"><i class="bi bi-funnel"></i> Filter</button>
        <?php if ($search || $severity || $status): ?>
            <a href="<?= BASE_PATH ?>/admin/violations.php" class="btn btn-outline-secondary btn-sm">Clear</a>
        <?php endif; ?>
    </form>

    <div class="data-table-wrapper">
        <table class="data-table">
            <thead>
                <tr><th>Student</th><th>Violation</th><th>Severity</th><th>Reported By</th><th>Status</th><th>Action Taken</th><th>Date</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($violations as $v): ?>
                <tr>
                    <td>
                        <div class="user-cell">
                            <?= getAvatarHtml($v['photo'] ?? null, $v['first_name'].' '.$v['last_name'], 'user-avatar') ?>
                            <div class="user-info">
                                <div class="name"><?= sanitize($v['first_name'].' '.$v['last_name']) ?></div>
                                <div class="sub"><?= sanitize($v['student_number']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?= sanitize($v['violation_name']) ?></td>
                    <td><?= severityBadge($v['severity']) ?></td>
                    <td><small><?= sanitize($v['reporter_name']) ?></small></td>
                    <td><?= statusBadge($v['status']) ?></td>
                    <td><?= $v['action_taken'] ? actionBadge($v['action_taken']) : '<span class="text-muted">-</span>' ?></td>
                    <td><small class="text-muted"><?= formatDateTime($v['date_occurred']) ?></small></td>
                    <td>
                        <div class="dropdown">
                            <button class="action-btn" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?= BASE_PATH ?>/admin/violation_print.php?id=<?=$v['id']?>" target="_blank"><i class="bi bi-printer"></i> Print Report</a></li>
                            </ul>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($violations)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No violations found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
