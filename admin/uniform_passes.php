<?php
/**
 * SVMS - Admin: Uniform Passes
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pdo = getDBConnection();

// Handle issue pass
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'issue') {
    try {
        $passCode = 'TUP-' . generateUUID();
        $validDate = $_POST['valid_date'] ?: date('Y-m-d');
        
        $stmt = $pdo->prepare("INSERT INTO uniform_passes (student_id, pass_code, reason, issued_by, valid_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['student_id'],
            $passCode,
            sanitize($_POST['reason']),
            $_SESSION['user_id'],
            $validDate
        ]);
        
        // Notify the student
        $stuUser = $pdo->prepare("SELECT id FROM users WHERE student_id = ? AND role = 'student'");
        $stuUser->execute([$_POST['student_id']]);
        $stuUserId = $stuUser->fetchColumn();
        
        if ($stuUserId) {
            addNotification(
                $stuUserId,
                'Temporary Uniform Pass Issued',
                'A temporary uniform pass has been issued for you, valid on ' . date('M d, Y', strtotime($validDate)) . '.',
                'success',
                '/student/uniform_pass.php'
            );
        }
        
        logActivity($_SESSION['user_id'], 'pass_issued', "Issued uniform pass $passCode for student #{$_POST['student_id']}");
        setFlash('success', 'Uniform pass issued successfully. Code: ' . $passCode);
    } catch (Exception $e) {
        setFlash('danger', 'Error issuing pass: ' . $e->getMessage());
    }
    header('Location: ' . BASE_PATH . '/admin/uniform_passes.php');
    exit;
}

// Handle revoke
if (isset($_GET['revoke'])) {
    try {
        $pdo->prepare("UPDATE uniform_passes SET status = 'revoked' WHERE id = ? AND status = 'active'")->execute([$_GET['revoke']]);
        
        $pass = $pdo->prepare("SELECT student_id FROM uniform_passes WHERE id = ?");
        $pass->execute([$_GET['revoke']]);
        $studentId = $pass->fetchColumn();
        if ($studentId) {
            $stuUser = $pdo->prepare("SELECT id FROM users WHERE student_id = ? AND role = 'student'");
            $stuUser->execute([$studentId]);
            $stuUserId = $stuUser->fetchColumn();
            if ($stuUserId) {
                addNotification($stuUserId, 'Uniform Pass Revoked', 'Your temporary uniform pass has been revoked.', 'warning', '/student/uniform_pass.php');
            }
        }
        
        logActivity($_SESSION['user_id'], 'pass_revoked', "Revoked uniform pass #{$_GET['revoke']}");
        setFlash('success', 'Pass revoked successfully.');
    } catch (Exception $e) {
        setFlash('danger', 'Error revoking pass.');
    }
    header('Location: ' . BASE_PATH . '/admin/uniform_passes.php');
    exit;
}

$pageTitle = 'Uniform Passes';
$breadcrumbs = ['Dashboard' => '/admin/dashboard.php', 'Uniform Passes' => null];
require_once __DIR__ . '/../includes/header.php';

// Auto-expire old active passes
$pdo->prepare("UPDATE uniform_passes SET status = 'expired' WHERE status = 'active' AND valid_date < ?")->execute([date('Y-m-d')]);

// Get today's active passes
$stmtActivePasses = $pdo->prepare("
    SELECT up.*, s.first_name, s.last_name, s.student_number, s.grade_level, s.section, s.photo,
           u.full_name AS issued_by_name
    FROM uniform_passes up
    JOIN students s ON up.student_id = s.id
    JOIN users u ON up.issued_by = u.id
    WHERE up.status = 'active' AND up.valid_date = ?
    ORDER BY up.created_at DESC
");
$stmtActivePasses->execute([date('Y-m-d')]);
$activePasses = $stmtActivePasses->fetchAll();

// Search + history
$search = $_GET['search'] ?? '';
$historyWhere = '';
$historyParams = [];
if ($search) {
    $historyWhere = "AND (s.first_name LIKE ? OR s.last_name LIKE ? OR s.student_number LIKE ? OR up.pass_code LIKE ?)";
    $searchLike = "%$search%";
    $historyParams = [$searchLike, $searchLike, $searchLike, $searchLike];
}

$page = max(1, intval($_GET['page'] ?? 1));
$historyResult = paginate(
    "SELECT up.*, s.first_name, s.last_name, s.student_number, s.grade_level, s.section,
            u.full_name AS issued_by_name
     FROM uniform_passes up
     JOIN students s ON up.student_id = s.id
     JOIN users u ON up.issued_by = u.id
     WHERE 1=1 $historyWhere
     ORDER BY up.created_at DESC",
    $historyParams, $page, 15
);

$students = $pdo->query("SELECT id, student_number, first_name, last_name, grade_level, section FROM students WHERE status = 'active' ORDER BY last_name, first_name")->fetchAll();
// Stats
$todayCountStmt = $pdo->prepare("SELECT COUNT(*) FROM uniform_passes WHERE valid_date = ?");
$todayCountStmt->execute([date('Y-m-d')]);
$todayCount = $todayCountStmt->fetchColumn();
$activeCount = count($activePasses);
$totalCount = $pdo->query("SELECT COUNT(*) FROM uniform_passes")->fetchColumn();
?>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card stat-purple">
            <div class="stat-header">
                <div class="stat-icon"><i class="bi bi-card-checklist"></i></div>
            </div>
            <div class="stat-value"><?= $activeCount ?></div>
            <div class="stat-label">Active Today</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card stat-blue">
            <div class="stat-header">
                <div class="stat-icon"><i class="bi bi-calendar-check"></i></div>
            </div>
            <div class="stat-value"><?= $todayCount ?></div>
            <div class="stat-label">Issued Today</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card stat-green">
            <div class="stat-header">
                <div class="stat-icon"><i class="bi bi-archive"></i></div>
            </div>
            <div class="stat-value"><?= $totalCount ?></div>
            <div class="stat-label">Total All Time</div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold" style="font-size:16px;">Active Passes Today</h5>
    <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#issueModal">
        <i class="bi bi-plus-lg"></i> Issue Pass
    </button>
</div>

<div class="card-panel mb-4">
    <?php if (empty($activePasses)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-card-checklist d-block mb-2" style="font-size: 36px;"></i>
            <p class="mb-0">No active passes for today</p>
        </div>
    <?php else: ?>
    <div class="data-table-wrapper">
        <table class="data-table">
            <thead><tr><th>Student</th><th>Grade & Section</th><th>Reason</th><th>Pass Code</th><th>Issued By</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($activePasses as $p): ?>
                <tr>
                    <td>
                        <div class="user-cell">
                            <?= getAvatarHtml($p['photo'] ?? null, $p['first_name'].' '.$p['last_name'], 'user-avatar') ?>
                            <div class="user-info">
                                <div class="name"><?= sanitize($p['first_name'].' '.$p['last_name']) ?></div>
                                <div class="sub"><?= sanitize($p['student_number']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td><small><?= sanitize($p['grade_level'].' - '.$p['section']) ?></small></td>
                    <td><small><?= sanitize($p['reason']) ?></small></td>
                    <td><code style="font-size:11px;"><?= sanitize($p['pass_code']) ?></code></td>
                    <td><small><?= sanitize($p['issued_by_name']) ?></small></td>
                    <td>
                        <a href="print_pass.php?id=<?= $p['id'] ?>" class="action-btn text-primary-custom" title="Print Pass" target="_blank">
                            <i class="bi bi-printer"></i>
                        </a>
                        <button type="button" class="action-btn text-danger" title="Revoke Pass" onclick="confirmRevoke(<?= $p['id'] ?>)">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<h5 class="mb-3 fw-bold" style="font-size:16px;">Pass History</h5>
<div class="card-panel">
    <form method="GET" class="mb-3">
        <div class="input-group">
            <input type="text" class="form-control" name="search" placeholder="Search by name, student number, or pass code..." value="<?= sanitize($search) ?>">
            <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
        </div>
    </form>

    <div class="data-table-wrapper">
        <table class="data-table">
            <thead><tr><th>Student</th><th>Reason</th><th>Valid Date</th><th>Status</th><th>Issued By</th><th>Created</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($historyResult['data'] as $h): ?>
                <?php
                    $hStatus = $h['status'];
                    if ($hStatus === 'active' && $h['valid_date'] < date('Y-m-d')) $hStatus = 'expired';
                    $statusClasses = ['active' => 'badge-soft-success', 'expired' => 'badge-soft-secondary', 'revoked' => 'badge-soft-danger'];
                ?>
                <tr>
                    <td>
                        <div class="user-info">
                            <div class="name"><?= sanitize($h['first_name'].' '.$h['last_name']) ?></div>
                            <div class="sub"><?= sanitize($h['student_number']) ?></div>
                        </div>
                    </td>
                    <td><small><?= sanitize($h['reason']) ?></small></td>
                    <td><small><?= formatDate($h['valid_date']) ?></small></td>
                    <td><span class="badge <?= $statusClasses[$hStatus] ?? 'badge-soft-secondary' ?>"><?= ucfirst($hStatus) ?></span></td>
                    <td><small><?= sanitize($h['issued_by_name']) ?></small></td>
                    <td><small class="text-muted"><?= timeAgo($h['created_at']) ?></small></td>
                    <td>
                        <a href="print_pass.php?id=<?= $h['id'] ?>" class="action-btn text-primary-custom" title="Print Pass" target="_blank">
                            <i class="bi bi-printer"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($historyResult['data'])): ?><tr><td colspan="7" class="text-center text-muted py-4">No passes found</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?= renderPagination($historyResult, '?search=' . urlencode($search)) ?>
</div>

<!-- Revoke Confirm Modal -->
<div class="modal fade" id="revokeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:380px;">
        <div class="modal-content" style="border-radius:18px;border:1px solid #ede9ee;">
            <div class="modal-body text-center" style="padding:32px 24px 20px;">
                <div style="width:52px;height:52px;background:rgba(220,53,69,0.1);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                    <i class="bi bi-x-circle" style="font-size:24px;color:#dc3545;"></i>
                </div>
                <h6 style="font-weight:700;margin-bottom:6px;">Revoke this pass?</h6>
                <p style="font-size:13px;color:var(--text-muted);margin-bottom:0;">The student will be notified and the pass will no longer be valid.</p>
            </div>
            <div class="modal-footer" style="border:none;padding:0 24px 24px;gap:8px;">
                <button type="button" class="btn btn-outline-secondary flex-fill" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="revokeConfirmBtn" class="btn btn-danger flex-fill" style="border-radius:10px;">Revoke Pass</a>
            </div>
        </div>
    </div>
</div>

<script>
function confirmRevoke(id) {
    document.getElementById('revokeConfirmBtn').href = '?revoke=' + id;
    new bootstrap.Modal(document.getElementById('revokeModal')).show();
}
</script>

<!-- Issue Pass Modal -->
<div class="modal fade" id="issueModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Issue Temporary Uniform Pass</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST">
                <input type="hidden" name="action" value="issue">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Student *</label>
                        <select class="form-select" name="student_id" required>
                            <option value="">Select student...</option>
                            <?php foreach ($students as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= sanitize($s['last_name'].', '.$s['first_name']) ?> (<?= sanitize($s['student_number']) ?>) — <?= sanitize($s['grade_level'].' '.$s['section']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason *</label>
                        <select class="form-select" name="reason_select" onchange="if(this.value) document.getElementById('adminReasonText').value = this.value; if(this.value==='Other') document.getElementById('adminReasonText').value=''; document.getElementById('adminReasonText').focus();">
                            <option value="">Select or type reason...</option>
                            <option value="Forgot school uniform">Forgot school uniform</option>
                            <option value="Uniform being washed/repaired">Uniform being washed/repaired</option>
                            <option value="Newly enrolled — uniform not yet available">Newly enrolled — uniform not yet available</option>
                            <option value="Uniform damaged">Uniform damaged</option>
                            <option value="PE uniform mismatch">PE uniform mismatch</option>
                            <option value="Other">Other (type below)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason Details *</label>
                        <textarea class="form-control" name="reason" id="adminReasonText" rows="2" required placeholder="Enter or modify reason..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Valid Date</label>
                        <input type="date" class="form-control" name="valid_date" value="<?= date('Y-m-d') ?>">
                        <small class="text-muted">Defaults to today. Pass expires at end of this date.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-primary-custom"><i class="bi bi-card-checklist me-1"></i>Issue Pass</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
