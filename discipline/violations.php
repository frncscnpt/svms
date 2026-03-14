<?php
/**
 * SVMS - Discipline Officer: Violations Management
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('discipline_officer');

$pdo = getDBConnection();

// Handle status update
if (isset($_GET['action']) && $_GET['action'] === 'update_status' && isset($_GET['id'], $_GET['new_status'])) {
    $allowedStatuses = ['pending', 'reviewed', 'resolved', 'dismissed'];
    if (in_array($_GET['new_status'], $allowedStatuses)) {
        $pdo->prepare("UPDATE violations SET status=? WHERE id=?")->execute([$_GET['new_status'], $_GET['id']]);
        logActivity($_SESSION['user_id'], 'violation_status', "Updated violation #{$_GET['id']} to {$_GET['new_status']}");
        setFlash('success', 'Violation status updated.');
    }
    header('Location: ' . BASE_PATH . '/discipline/violations.php');
    exit;
}

// Now include header (HTML output starts here)
$pageTitle = 'Violations';
$breadcrumbs = ['Dashboard' => '/discipline/dashboard.php', 'Violations' => null];
require_once __DIR__ . '/../includes/header.php';

// Filters
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$where = "WHERE 1=1";
$params = [];
if ($status) { $where .= " AND v.status=?"; $params[] = $status; }
if ($search) { $where .= " AND (s.first_name LIKE ? OR s.last_name LIKE ? OR s.student_number LIKE ?)"; $params = array_merge($params, ["%$search%","%$search%","%$search%"]); }

$stmt = $pdo->prepare("
    SELECT v.*, s.first_name, s.last_name, s.student_number, s.grade_level, s.section,
           vt.name as violation_name, vt.severity, u.full_name as reporter,
           (SELECT COUNT(*) FROM violations v2 WHERE v2.student_id=v.student_id) as total_violations
    FROM violations v JOIN students s ON v.student_id=s.id
    JOIN violation_types vt ON v.violation_type_id=vt.id
    JOIN users u ON v.reported_by=u.id
    $where ORDER BY v.created_at DESC LIMIT 50
");
$stmt->execute($params);
$violations = $stmt->fetchAll();

$violationTypes = $pdo->query("SELECT * FROM violation_types WHERE status='active' ORDER BY name")->fetchAll();
?>

<div class="card-panel">
    <form class="filter-bar d-flex justify-content-between align-items-center" method="GET">
        <div class="d-flex align-items-center gap-2 flex-grow-1">
            <div class="search-box">
                <i class="bi bi-search"></i>
                <input type="text" name="search" placeholder="Search student..." value="<?= sanitize($search) ?>">
            </div>
            <select name="status" class="form-select" style="width:auto">
                <option value="">All Status</option>
                <option value="pending" <?= $status==='pending'?'selected':'' ?>>Pending</option>
                <option value="reviewed" <?= $status==='reviewed'?'selected':'' ?>>Reviewed</option>
                <option value="resolved" <?= $status==='resolved'?'selected':'' ?>>Resolved</option>
                <option value="dismissed" <?= $status==='dismissed'?'selected':'' ?>>Dismissed</option>
            </select>
            <button type="submit" class="btn-primary-custom"><i class="bi bi-funnel"></i> Filter</button>
            <?php if ($search || $status): ?><a href="<?= BASE_PATH ?>/discipline/violations.php" class="btn btn-outline-secondary btn-sm">Clear</a><?php endif; ?>
        </div>
        <a href="<?= BASE_PATH ?>/discipline/report_violation.php" class="btn-primary-custom" style="padding: 10px 20px; text-decoration: none; white-space: nowrap;">
            <i class="bi bi-file-earmark-plus pe-2"></i>File Direct Report
        </a>
    </form>

    <div class="data-table-wrapper">
        <table class="data-table">
            <thead><tr><th>Student</th><th>Violation</th><th>Severity</th><th>Reporter</th><th>Status</th><th>Repeat?</th><th>Date</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($violations as $v): ?>
                <tr>
                    <td>
                        <div class="user-cell">
                            <?= getAvatarHtml($v['photo'] ?? null, $v['first_name'].' '.$v['last_name'], 'user-avatar') ?>
                            <div class="user-info">
                                <div class="name"><?= sanitize($v['first_name'].' '.$v['last_name']) ?></div>
                                <div class="sub"><?= sanitize($v['student_number']) ?> · <?= sanitize($v['grade_level']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <strong style="font-size:13px;"><?= sanitize($v['violation_name']) ?></strong>
                        <?php if ($v['description']): ?><br><small class="text-muted"><?= sanitize(substr($v['description'],0,60)) ?>...</small><?php endif; ?>
                    </td>
                    <td><?= severityBadge($v['severity']) ?></td>
                    <td><small><?= sanitize($v['reporter']) ?></small></td>
                    <td><?= statusBadge($v['status']) ?></td>
                    <td>
                        <?php if ($v['total_violations'] > 1): ?>
                            <span class="badge badge-soft-danger"><?= $v['total_violations'] ?>x</span>
                        <?php else: ?>
                            <span class="text-muted">1st</span>
                        <?php endif; ?>
                    </td>
                    <td><small class="text-muted"><?= formatDateTime($v['date_occurred']) ?></small></td>
                    <td>
                        <div class="dropdown">
                            <button class="action-btn" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <?php if ($v['status'] === 'pending'): ?>
                                <li><a class="dropdown-item" href="?action=update_status&id=<?=$v['id']?>&new_status=reviewed"><i class="bi bi-eye"></i> Mark Reviewed</a></li>
                                <?php endif; ?>
                                <?php if ($v['status'] !== 'resolved'): ?>
                                <li><a class="dropdown-item" href="?action=update_status&id=<?=$v['id']?>&new_status=resolved"><i class="bi bi-check-circle"></i> Mark Resolved</a></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item" href="<?= BASE_PATH ?>/discipline/actions.php?violation_id=<?=$v['id']?>"><i class="bi bi-hammer"></i> Issue Action</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_PATH ?>/discipline/history.php?student=<?=$v['student_id']?>"><i class="bi bi-clock-history"></i> View History</a></li>
                                <?php if ($v['status'] === 'pending'): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="?action=update_status&id=<?=$v['id']?>&new_status=dismissed"><i class="bi bi-x-circle"></i> Dismiss</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?= BASE_PATH ?>/admin/violation_print.php?id=<?=$v['id']?>" target="_blank"><i class="bi bi-printer"></i> Print Report</a></li>
                            </ul>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($violations)): ?><tr><td colspan="8" class="text-center text-muted py-4">No violations found</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
