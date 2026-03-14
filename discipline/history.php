<?php
/**
 * SVMS - Discipline Officer: Student History
 */
$pageTitle = 'Violation History';
$breadcrumbs = ['Dashboard' => '/discipline/dashboard.php', 'History' => null];
require_once __DIR__ . '/../includes/header.php';
requireRole('discipline_officer');

$pdo = getDBConnection();
$studentId = $_GET['student'] ?? '';
$student = null;
$violations = [];
$actions = [];

if ($studentId) {
    $student = $pdo->prepare("SELECT * FROM students WHERE id=?");
    $student->execute([$studentId]);
    $student = $student->fetch();
    
    if ($student) {
        $pageTitle = 'History: ' . $student['first_name'] . ' ' . $student['last_name'];
        
        $violations = $pdo->prepare("
            SELECT v.*, vt.name as violation_name, vt.severity, u.full_name as reporter
            FROM violations v JOIN violation_types vt ON v.violation_type_id=vt.id
            JOIN users u ON v.reported_by=u.id
            WHERE v.student_id=? ORDER BY v.date_occurred DESC
        ");
        $violations->execute([$studentId]);
        $violations = $violations->fetchAll();
        
        $actions = $pdo->prepare("
            SELECT da.*, vt.name as violation_name, u.full_name as issuer
            FROM disciplinary_actions da
            JOIN violations v ON da.violation_id=v.id
            JOIN violation_types vt ON v.violation_type_id=vt.id
            JOIN users u ON da.issued_by=u.id
            WHERE v.student_id=? ORDER BY da.created_at DESC
        ");
        $actions->execute([$studentId]);
        $actions = $actions->fetchAll();
    }
}

// Search students
$searchResults = [];
$searchQuery = $_GET['search'] ?? '';
if ($searchQuery && !$studentId) {
    $stmt = $pdo->prepare("SELECT s.*, (SELECT COUNT(*) FROM violations WHERE student_id=s.id) as v_count FROM students s WHERE (s.first_name LIKE ? OR s.last_name LIKE ? OR s.student_number LIKE ?) AND s.status='active' LIMIT 20");
    $stmt->execute(["%$searchQuery%","%$searchQuery%","%$searchQuery%"]);
    $searchResults = $stmt->fetchAll();
}
?>

<?php if (!$student): ?>
<!-- Search Section -->
<div class="card-panel">
    <div class="panel-header"><h5 class="panel-title"><i class="bi bi-search"></i> Search Student Record</h5></div>
    <form class="filter-bar" method="GET">
        <div class="search-box">
            <i class="bi bi-search"></i>
            <input type="text" name="search" placeholder="Search by name or student number..." value="<?= sanitize($searchQuery) ?>" autofocus>
        </div>
        <button type="submit" class="btn-primary-custom"><i class="bi bi-search"></i> Search</button>
    </form>
    
    <?php if (!empty($searchResults)): ?>
    <div class="data-table-wrapper">
        <table class="data-table">
            <thead><tr><th>Student</th><th>Grade</th><th>Violations</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($searchResults as $sr): ?>
                <tr>
                    <td>
                        <div class="user-cell">
                            <?= getAvatarHtml($sr['photo'] ?? null, $sr['first_name'].' '.$sr['last_name'], 'user-avatar') ?>
                            <div class="user-info">
                                <div class="name"><?= sanitize($sr['first_name'].' '.$sr['last_name']) ?></div>
                                <div class="sub"><?= sanitize($sr['student_number']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?= sanitize($sr['grade_level'].' - '.$sr['section']) ?></td>
                    <td><span class="badge <?= $sr['v_count'] > 0 ? 'badge-soft-danger' : 'badge-soft-success' ?>"><?= $sr['v_count'] ?></span></td>
                    <td><a href="?student=<?= $sr['id'] ?>" class="btn-primary-custom" style="padding:5px 14px;font-size:12px;">View History</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php elseif ($searchQuery): ?>
    <div class="empty-state"><i class="bi bi-search"></i><h5>No students found</h5></div>
    <?php endif; ?>
</div>

<?php else: ?>
<!-- Student Profile + History -->
<div class="row g-3 mb-4">
    <div class="col-lg-4">
        <div class="card-panel">
            <div class="profile-header">
                <?= getAvatarHtml($student['photo'] ?? null, $student['first_name'].' '.$student['last_name'], 'profile-avatar') ?>
                <h5 style="color:white;margin:0;"><?= sanitize($student['first_name'].' '.$student['last_name']) ?></h5>
                <p style="color:rgba(255,255,255,0.7);margin:4px 0 0;font-size:13px;"><?= sanitize($student['student_number']) ?></p>
            </div>
            <div class="panel-body">
                <div class="mb-2"><small class="text-muted">Grade</small><br><strong><?= sanitize($student['grade_level'].' - '.$student['section']) ?></strong></div>
                <div class="mb-2"><small class="text-muted">Contact</small><br><strong><?= sanitize($student['contact'] ?? 'N/A') ?></strong></div>
                <div class="mb-2"><small class="text-muted">Guardian</small><br><strong><?= sanitize($student['guardian_name'] ?? 'N/A') ?></strong></div>
                <div><small class="text-muted">Total Violations</small><br><span class="badge badge-soft-danger fs-6"><?= count($violations) ?></span></div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <!-- Violations -->
        <div class="card-panel mb-3">
            <div class="panel-header"><h5 class="panel-title"><i class="bi bi-exclamation-triangle"></i> Violations (<?= count($violations) ?>)</h5></div>
            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead><tr><th>Violation</th><th>Severity</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                        <?php foreach ($violations as $v): ?>
                        <tr>
                            <td><?= sanitize($v['violation_name']) ?></td>
                            <td><?= severityBadge($v['severity']) ?></td>
                            <td><?= statusBadge($v['status']) ?></td>
                            <td><small class="text-muted"><?= formatDateTime($v['date_occurred']) ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Actions -->
        <div class="card-panel">
            <div class="panel-header"><h5 class="panel-title"><i class="bi bi-hammer"></i> Actions (<?= count($actions) ?>)</h5></div>
            <div class="panel-body">
                <?php if (empty($actions)): ?>
                <p class="text-muted text-center">No actions issued</p>
                <?php else: ?>
                <div class="timeline">
                    <?php foreach ($actions as $a): ?>
                    <div class="timeline-item">
                        <div class="timeline-content">
                            <div class="d-flex justify-content-between align-items-center">
                                <div><?= actionBadge($a['action_type']) ?> <small class="ms-1">for <?= sanitize($a['violation_name']) ?></small></div>
                                <?= statusBadge($a['status']) ?>
                            </div>
                            <?php if ($a['description']): ?><p style="font-size:12px;margin:4px 0 0;color:var(--text-secondary);"><?= sanitize($a['description']) ?></p><?php endif; ?>
                        </div>
                        <div class="timeline-time"><?= formatDate($a['created_at']) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<a href="<?= BASE_PATH ?>/discipline/history.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back to Search</a>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
