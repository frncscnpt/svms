<?php
/**
 * SVMS - Admin: Reports
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole(['admin', 'discipline_officer']);

$pdo = getDBConnection();

$reportType = $_GET['type'] ?? 'summary';
$dateFrom = $_GET['from'] ?? date('Y-m-01');
$dateTo = $_GET['to'] ?? date('Y-m-d');
$gradeFilter = $_GET['grade'] ?? '';

// Export CSV (must be before any HTML output)
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="violations_report_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Student No', 'Student Name', 'Grade', 'Section', 'Violation', 'Severity', 'Status', 'Date', 'Reported By']);
    $export = $pdo->prepare("SELECT s.student_number, CONCAT(s.first_name,' ',s.last_name) as name, s.grade_level, s.section, vt.name as violation, vt.severity, v.status, v.date_occurred, u.full_name as reporter FROM violations v JOIN students s ON v.student_id=s.id JOIN violation_types vt ON v.violation_type_id=vt.id JOIN users u ON v.reported_by=u.id WHERE v.date_occurred BETWEEN ? AND ? ORDER BY v.date_occurred DESC");
    $export->execute([$dateFrom, $dateTo . ' 23:59:59']);
    while ($row = $export->fetch()) { fputcsv($out, $row); }
    fclose($out);
    exit;
}

// Now include header (HTML output starts here)
$pageTitle = 'Reports';
$breadcrumbs = ['Dashboard' => '/admin/dashboard.php', 'Reports' => null];
require_once __DIR__ . '/../includes/header.php';

// Summary stats
$summaryQuery = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN vt.severity='minor' THEN 1 ELSE 0 END) as minor_count,
    SUM(CASE WHEN vt.severity='major' THEN 1 ELSE 0 END) as major_count,
    SUM(CASE WHEN vt.severity='critical' THEN 1 ELSE 0 END) as critical_count,
    SUM(CASE WHEN v.status='pending' THEN 1 ELSE 0 END) as pending_count,
    SUM(CASE WHEN v.status='resolved' THEN 1 ELSE 0 END) as resolved_count
    FROM violations v JOIN violation_types vt ON v.violation_type_id=vt.id
    WHERE v.date_occurred BETWEEN ? AND ?";
$summaryStmt = $pdo->prepare($summaryQuery);
$summaryStmt->execute([$dateFrom, $dateTo . ' 23:59:59']);
$summary = $summaryStmt->fetch();

// Top violators
$topViolators = $pdo->prepare("
    SELECT s.student_number, s.first_name, s.last_name, s.grade_level, s.section, COUNT(v.id) as count
    FROM violations v JOIN students s ON v.student_id=s.id
    WHERE v.date_occurred BETWEEN ? AND ?
    GROUP BY s.id ORDER BY count DESC LIMIT 10
");
$topViolators->execute([$dateFrom, $dateTo . ' 23:59:59']);
$topViolators = $topViolators->fetchAll();

// By type
$byType = $pdo->prepare("
    SELECT vt.name, vt.severity, COUNT(v.id) as count
    FROM violations v JOIN violation_types vt ON v.violation_type_id=vt.id
    WHERE v.date_occurred BETWEEN ? AND ?
    GROUP BY vt.id ORDER BY count DESC
");
$byType->execute([$dateFrom, $dateTo . ' 23:59:59']);
$byType = $byType->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <p class="text-muted mb-0" style="font-size:13px;">Generate and export violation reports</p>
    <div class="d-flex gap-2">
        <a href="report_print.php?from=<?= $dateFrom ?>&to=<?= $dateTo ?>" target="_blank" class="btn-outline-custom">
            <i class="bi bi-printer"></i> Print / PDF
        </a>
        <a href="?type=<?= $reportType ?>&from=<?= $dateFrom ?>&to=<?= $dateTo ?>&export=csv" class="btn-accent">
            <i class="bi bi-download"></i> Export CSV
        </a>
    </div>
</div>

<!-- Date Filter -->
<div class="card-panel mb-4">
    <form class="filter-bar" method="GET">
        <div>
            <label class="form-label mb-1" style="font-size:11px;">From</label>
            <input type="date" class="form-control" name="from" value="<?= $dateFrom ?>">
        </div>
        <div>
            <label class="form-label mb-1" style="font-size:11px;">To</label>
            <input type="date" class="form-control" name="to" value="<?= $dateTo ?>">
        </div>
        <button type="submit" class="btn-primary-custom" style="margin-top:18px;"><i class="bi bi-funnel"></i> Generate</button>
    </form>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4 col-xl-2">
        <div class="stat-card stat-purple">
            <div class="stat-header"><div class="stat-label">Total</div><div class="stat-icon"><i class="bi bi-bar-chart-fill"></i></div></div>
            <div class="stat-value"><?= $summary['total'] ?></div>
        </div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="stat-card stat-gold">
            <div class="stat-header"><div class="stat-label">Minor</div><div class="stat-icon"><i class="bi bi-info-circle-fill"></i></div></div>
            <div class="stat-value"><?= $summary['minor_count'] ?></div>
        </div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="stat-card stat-orange">
            <div class="stat-header"><div class="stat-label">Major</div><div class="stat-icon"><i class="bi bi-exclamation-circle-fill"></i></div></div>
            <div class="stat-value"><?= $summary['major_count'] ?></div>
        </div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="stat-card stat-red">
            <div class="stat-header"><div class="stat-label">Critical</div><div class="stat-icon"><i class="bi bi-exclamation-triangle-fill"></i></div></div>
            <div class="stat-value"><?= $summary['critical_count'] ?></div>
        </div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="stat-card stat-gold">
            <div class="stat-header"><div class="stat-label">Pending</div><div class="stat-icon"><i class="bi bi-clock-fill"></i></div></div>
            <div class="stat-value"><?= $summary['pending_count'] ?></div>
        </div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="stat-card stat-green">
            <div class="stat-header"><div class="stat-label">Resolved</div><div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div></div>
            <div class="stat-value"><?= $summary['resolved_count'] ?></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Top Violators -->
    <div class="col-lg-6">
        <div class="card-panel">
            <div class="panel-header"><h5 class="panel-title"><i class="bi bi-person-exclamation"></i> Top Violators</h5></div>
            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead><tr><th>#</th><th>Student</th><th>Grade</th><th>Count</th></tr></thead>
                    <tbody>
                        <?php foreach ($topViolators as $i => $tv): ?>
                        <tr>
                            <td><?= $i+1 ?></td>
                            <td>
                                <div class="user-cell">
                                    <?= getAvatarHtml($tv['photo'] ?? null, $tv['first_name'].' '.$tv['last_name'], 'user-avatar', 'width:30px;height:30px;font-size:11px;') ?>
                                    <div class="user-info"><div class="name"><?= sanitize($tv['first_name'].' '.$tv['last_name']) ?></div></div>
                                </div>
                            </td>
                            <td><small><?= sanitize($tv['grade_level']) ?></small></td>
                            <td><span class="badge badge-soft-danger"><?= $tv['count'] ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($topViolators)): ?><tr><td colspan="4" class="text-center text-muted py-3">No data</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- By Type -->
    <div class="col-lg-6">
        <div class="card-panel">
            <div class="panel-header"><h5 class="panel-title"><i class="bi bi-bar-chart"></i> Violations by Type</h5></div>
            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead><tr><th>Type</th><th>Severity</th><th>Count</th></tr></thead>
                    <tbody>
                        <?php foreach ($byType as $bt): ?>
                        <tr>
                            <td><?= sanitize($bt['name']) ?></td>
                            <td><?= severityBadge($bt['severity']) ?></td>
                            <td><strong><?= $bt['count'] ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($byType)): ?><tr><td colspan="3" class="text-center text-muted py-3">No data</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
