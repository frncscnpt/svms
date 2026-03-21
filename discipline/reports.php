<?php
/**
 * SVMS - Discipline Officer: Reports
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('discipline_officer');

$pdo = getDBConnection();

$dateFrom = $_GET['from'] ?? date('Y-m-01');
$dateTo = $_GET['to'] ?? date('Y-m-d');

// Export (must be before any HTML output)
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="violations_report_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Student No','Name','Violation','Severity','Status','Date','Reporter']);
    $export = $pdo->prepare("SELECT s.student_number, CONCAT(s.first_name,' ',s.last_name) as name, vt.name as violation, vt.severity, v.status, v.date_occurred, u.full_name as reporter FROM violations v JOIN students s ON v.student_id=s.id JOIN violation_types vt ON v.violation_type_id=vt.id JOIN users u ON v.reported_by=u.id WHERE v.date_occurred BETWEEN ? AND ? ORDER BY v.date_occurred DESC");
    $export->execute([$dateFrom, $dateTo . ' 23:59:59']);
    while ($row = $export->fetch()) { fputcsv($out, $row); }
    fclose($out);
    exit;
}

// Now include header (HTML output starts here)
$pageTitle = 'Reports';
$breadcrumbs = ['Dashboard' => '/discipline/dashboard.php', 'Reports' => null];
require_once __DIR__ . '/../includes/header.php';

$summaryStmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN vt.severity='minor' THEN 1 ELSE 0 END) as minor_count, SUM(CASE WHEN vt.severity='major' THEN 1 ELSE 0 END) as major_count, SUM(CASE WHEN vt.severity='critical' THEN 1 ELSE 0 END) as critical_count FROM violations v JOIN violation_types vt ON v.violation_type_id=vt.id WHERE v.date_occurred BETWEEN ? AND ?");
$summaryStmt->execute([$dateFrom, $dateTo . ' 23:59:59']);
$summary = $summaryStmt->fetch();

// Fetch paginated records
$page = max(1, intval($_GET['page'] ?? 1));
$recordsResult = paginate(
    "SELECT v.id as violation_id, s.student_number, CONCAT(s.first_name,' ',s.last_name) as name, s.grade_level, s.section, 
           vt.name as violation, vt.severity, v.status, v.date_occurred, u.full_name as reporter, s.photo
    FROM violations v 
    JOIN students s ON v.student_id=s.id 
    JOIN violation_types vt ON v.violation_type_id=vt.id 
    JOIN users u ON v.reported_by=u.id 
    WHERE v.date_occurred BETWEEN ? AND ? 
    ORDER BY v.date_occurred DESC",
    [$dateFrom, $dateTo . ' 23:59:59'], 
    $page, 20
);
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <p class="text-muted mb-0" style="font-size:13px;">Generate violation reports</p>
    <div class="d-flex gap-2">
        <a href="<?= BASE_PATH ?>/admin/report_print.php?from=<?= $dateFrom ?>&to=<?= $dateTo ?>" target="_blank" class="btn-outline-custom">
            <i class="bi bi-printer"></i> Print / PDF
        </a>
        <a href="?from=<?= $dateFrom ?>&to=<?= $dateTo ?>&export=csv" class="btn-accent"><i class="bi bi-download"></i> Export CSV</a>
    </div>
</div>

<div class="card-panel mb-4">
    <form class="filter-bar" method="GET">
        <div><label class="form-label mb-1" style="font-size:11px;">From</label><input type="date" class="form-control" name="from" value="<?= $dateFrom ?>"></div>
        <div><label class="form-label mb-1" style="font-size:11px;">To</label><input type="date" class="form-control" name="to" value="<?= $dateTo ?>"></div>
        <button type="submit" class="btn-primary-custom" style="margin-top:18px;"><i class="bi bi-funnel"></i> Generate</button>
    </form>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card stat-purple">
            <div class="stat-header"><div class="stat-label">Total</div><div class="stat-icon"><i class="bi bi-bar-chart-fill"></i></div></div>
            <div class="stat-value"><?= $summary['total'] ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card stat-gold">
            <div class="stat-header"><div class="stat-label">Minor</div><div class="stat-icon"><i class="bi bi-info-circle-fill"></i></div></div>
            <div class="stat-value"><?= $summary['minor_count'] ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card stat-orange">
            <div class="stat-header"><div class="stat-label">Major</div><div class="stat-icon"><i class="bi bi-exclamation-circle-fill"></i></div></div>
            <div class="stat-value"><?= $summary['major_count'] ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card stat-red">
            <div class="stat-header"><div class="stat-label">Critical</div><div class="stat-icon"><i class="bi bi-exclamation-triangle-fill"></i></div></div>
            <div class="stat-value"><?= $summary['critical_count'] ?></div>
        </div>
    </div>
</div>

<!-- Detailed Records Table -->
<div class="card-panel mt-4">
    <div class="panel-header d-flex justify-content-between align-items-center">
        <h5 class="panel-title mb-0"><i class="bi bi-list-ul"></i> Violation Records for Selected Period</h5>
    </div>
    <div class="data-table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Student</th>
                    <th>Grade / Section</th>
                    <th>Violation</th>
                    <th>Severity</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recordsResult['data'] as $r): ?>
                <tr>
                    <td><small><?= date('M d, Y h:i A', strtotime($r['date_occurred'])) ?></small></td>
                    <td>
                        <div class="user-cell">
                            <?= getAvatarHtml($r['photo'] ?? null, $r['name'], 'user-avatar', 'width:32px;height:32px;font-size:12px;') ?>
                            <div class="user-info">
                                <div class="name"><?= sanitize($r['name']) ?></div>
                                <div class="sub"><?= sanitize($r['student_number']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td><small><?= sanitize($r['grade_level'] . ' - ' . $r['section']) ?></small></td>
                    <td><small><?= sanitize($r['violation']) ?></small></td>
                    <td><?= severityBadge($r['severity']) ?></td>
                    <td><span class="badge badge-soft-<?= $r['status'] == 'resolved' ? 'success' : ($r['status'] == 'pending' ? 'warning' : 'secondary') ?>"><?= ucfirst(sanitize($r['status'])) ?></span></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($recordsResult['data'])): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">No violation records found for the selected period.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?= renderPagination($recordsResult, '?from='.$dateFrom.'&to='.$dateTo) ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
