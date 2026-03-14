<?php
/**
 * SVMS - Discipline Officer: Disciplinary Actions
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('discipline_officer');

$pdo = getDBConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("INSERT INTO disciplinary_actions (violation_id, action_type, description, start_date, end_date, issued_by, status) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([
            $_POST['violation_id'],
            $_POST['action_type'],
            sanitize($_POST['description'] ?? ''),
            $_POST['start_date'] ?: null,
            $_POST['end_date'] ?: null,
            $_SESSION['user_id'],
            'active'
        ]);
        
        // Update violation status to reviewed
        $pdo->prepare("UPDATE violations SET status='reviewed' WHERE id=? AND status='pending'")->execute([$_POST['violation_id']]);
        
        // Notify the reporter (Teacher)
        $stmtRep = $pdo->prepare("SELECT v.reported_by, v.id FROM violations v WHERE v.id = ?");
        $stmtRep->execute([$_POST['violation_id']]);
        $repId = $stmtRep->fetchColumn();
        
        if ($repId) {
            addNotification(
                $repId, 
                "Report Reviewed", 
                "Your report for Violation #{$_POST['violation_id']} has been reviewed and action is being taken.", 
                'success', 
                '/teacher/my_reports.php'
            );
        }
        
        logActivity($_SESSION['user_id'], 'action_issued', "Issued {$_POST['action_type']} for violation #{$_POST['violation_id']}");

        // Notify the student
        $stmtStu = $pdo->prepare("SELECT v.student_id, u.id as user_id FROM violations v JOIN users u ON v.student_id = u.student_id WHERE v.id = ?");
        $stmtStu->execute([$_POST['violation_id']]);
        $stuData = $stmtStu->fetch();
        
        if ($stuData && $stuData['user_id']) {
            addNotification(
                $stuData['user_id'], 
                "Disciplinary Action Issued", 
                "A new action (" . ucfirst($_POST['action_type']) . ") has been issued following your recent violation review.", 
                'danger', 
                '/student/violations.php'
            );
        }

        setFlash('success', 'Disciplinary action issued successfully.');
    } catch (Exception $e) {
        setFlash('danger', 'Error: ' . $e->getMessage());
    }
    header('Location: ' . BASE_PATH . '/discipline/actions.php');
    exit;
}

// Handle status update
if (isset($_GET['update_action'])) {
    $pdo->prepare("UPDATE disciplinary_actions SET status=? WHERE id=?")->execute([$_GET['new_status'], $_GET['update_action']]);
    if ($_GET['new_status'] === 'completed') {
        $vId = $pdo->prepare("SELECT violation_id FROM disciplinary_actions WHERE id=?");
        $vId->execute([$_GET['update_action']]);
        $vId = $vId->fetchColumn();
        $pdo->prepare("UPDATE violations SET status='resolved' WHERE id=?")->execute([$vId]);
    }
    
    setFlash('success', 'Action status updated.');
    header('Location: ' . BASE_PATH . '/discipline/actions.php');
    exit;
}

// Now include header (HTML output starts here)
$pageTitle = 'Disciplinary Actions';
$breadcrumbs = ['Dashboard' => '/discipline/dashboard.php', 'Actions' => null];
require_once __DIR__ . '/../includes/header.php';

// Pre-fill violation from query param
$prefillViolation = null;
if (isset($_GET['violation_id'])) {
    $prefill = $pdo->prepare("SELECT v.*, s.first_name, s.last_name, s.student_number, vt.name as violation_name FROM violations v JOIN students s ON v.student_id=s.id JOIN violation_types vt ON v.violation_type_id=vt.id WHERE v.id=?");
    $prefill->execute([$_GET['violation_id']]);
    $prefillViolation = $prefill->fetch();
}

// List actions
$actions = $pdo->query("
    SELECT da.*, v.description as v_desc, s.first_name, s.last_name, s.student_number, s.grade_level,
           vt.name as violation_name, u.full_name as issuer
    FROM disciplinary_actions da
    JOIN violations v ON da.violation_id=v.id
    JOIN students s ON v.student_id=s.id
    JOIN violation_types vt ON v.violation_type_id=vt.id
    JOIN users u ON da.issued_by=u.id
    ORDER BY da.created_at DESC LIMIT 30
")->fetchAll();

// Unactioned violations
$unactioned = $pdo->query("
    SELECT v.id, s.first_name, s.last_name, s.student_number, vt.name as violation_name
    FROM violations v
    JOIN students s ON v.student_id=s.id
    JOIN violation_types vt ON v.violation_type_id=vt.id
    LEFT JOIN disciplinary_actions da ON v.id=da.violation_id
    WHERE da.id IS NULL AND v.status != 'dismissed'
    ORDER BY v.created_at DESC
")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <p class="text-muted mb-0" style="font-size:13px;">Issue and manage disciplinary actions</p>
    <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#actionModal">
        <i class="bi bi-plus-lg"></i> Issue Action
    </button>
</div>

<div class="card-panel">
    <div class="data-table-wrapper">
        <table class="data-table">
            <thead><tr><th>Student</th><th>Violation</th><th>Action</th><th>Duration</th><th>Status</th><th>Issued By</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($actions as $a): ?>
                <tr>
                    <td>
                        <div class="user-cell">
                            <?= getAvatarHtml($a['photo'] ?? null, $a['first_name'].' '.$a['last_name'], 'user-avatar') ?>
                            <div class="user-info">
                                <div class="name"><?= sanitize($a['first_name'].' '.$a['last_name']) ?></div>
                                <div class="sub"><?= sanitize($a['student_number']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td><small><?= sanitize($a['violation_name']) ?></small></td>
                    <td><?= actionBadge($a['action_type']) ?></td>
                    <td>
                        <small class="text-muted">
                            <?= $a['start_date'] ? formatDate($a['start_date']) : '-' ?>
                            <?= $a['end_date'] ? ' to ' . formatDate($a['end_date']) : '' ?>
                        </small>
                    </td>
                    <td><?= statusBadge($a['status']) ?></td>
                    <td><small><?= sanitize($a['issuer']) ?></small></td>
                    <td>
                        <?php if ($a['status'] === 'active'): ?>
                        <a href="?update_action=<?=$a['id']?>&new_status=completed" class="action-btn" title="Mark Complete"><i class="bi bi-check-lg"></i></a>
                        <?php elseif ($a['status'] === 'pending'): ?>
                        <a href="?update_action=<?=$a['id']?>&new_status=active" class="action-btn" title="Activate"><i class="bi bi-play-fill"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($actions)): ?><tr><td colspan="7" class="text-center text-muted py-4">No actions issued yet</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Action Modal -->
<div class="modal fade" id="actionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Issue Disciplinary Action</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Violation *</label>
                        <select class="form-select" name="violation_id" required>
                            <option value="">Select violation...</option>
                            <?php foreach ($unactioned as $uv): ?>
                            <option value="<?=$uv['id']?>" <?= ($prefillViolation && $prefillViolation['id']==$uv['id']) ? 'selected' : '' ?>>
                                <?= sanitize($uv['first_name'].' '.$uv['last_name']) ?> - <?= sanitize($uv['violation_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Action Type *</label>
                        <select class="form-select" name="action_type" required>
                            <option value="warning">Warning</option>
                            <option value="detention">Detention</option>
                            <option value="suspension">Suspension</option>
                            <option value="community_service">Community Service</option>
                            <option value="counseling">Counseling</option>
                            <option value="expulsion">Expulsion</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3" placeholder="Details..."></textarea>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" name="start_date" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-control" name="end_date">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-primary-custom">Issue Action</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
if ($prefillViolation) {
    $extraJS = '<script>new bootstrap.Modal(document.getElementById("actionModal")).show();</script>';
}
require_once __DIR__ . '/../includes/footer.php';
?>
