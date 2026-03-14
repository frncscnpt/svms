<?php
/**
 * SVMS - Student: Violation History
 */
$pageTitle = 'My Violations';
$pageSubtitle = 'Complete violation history';
require_once __DIR__ . '/../includes/mobile_header.php';
requireRole('student');

$pdo = getDBConnection();
$studentId = $_SESSION['student_id'];

$violations = $pdo->prepare("
    SELECT v.*, vt.name as violation_name, vt.severity, u.full_name as reporter,
           (SELECT GROUP_CONCAT(CONCAT(da.action_type,'|',da.status) SEPARATOR ';;') FROM disciplinary_actions da WHERE da.violation_id=v.id) as actions_data
    FROM violations v
    JOIN violation_types vt ON v.violation_type_id=vt.id
    JOIN users u ON v.reported_by=u.id
    WHERE v.student_id=?
    ORDER BY v.date_occurred DESC
");
$violations->execute([$studentId]);
$violations = $violations->fetchAll();
?>

<?php if (empty($violations)): ?>
<div class="mobile-empty">
    <img src="<?= BASE_PATH ?>/assets/img/icons/icon-96.png" alt="SVMS Logo" style="width: 64px; height: 64px; margin-bottom: 12px; object-fit: contain;">

    <h5>No Violations</h5>
    <p>You have a clean record!</p>
</div>
<?php else: ?>

<div class="mb-3" style="font-size:13px;color:var(--text-muted);">
    Total: <strong><?= count($violations) ?></strong> violation<?= count($violations) > 1 ? 's' : '' ?>
</div>

<?php foreach ($violations as $v): ?>
<div class="violation-detail-mobile">
    <div style="padding:14px 18px;border-bottom:1px solid var(--border-light);">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <strong style="font-size:14px;"><?= sanitize($v['violation_name']) ?></strong>
                <div class="mt-1"><?= severityBadge($v['severity']) ?> <?= statusBadge($v['status']) ?></div>
            </div>
        </div>
    </div>
    <div class="detail-row">
        <span class="detail-label">Date</span>
        <span class="detail-value"><?= formatDateTime($v['date_occurred'], 'M d, Y h:i A') ?></span>
    </div>
    <div class="detail-row">
        <span class="detail-label">Reported By</span>
        <span class="detail-value"><?= sanitize($v['reporter']) ?></span>
    </div>
    <?php if ($v['location']): ?>
    <div class="detail-row">
        <span class="detail-label">Location</span>
        <span class="detail-value"><?= sanitize($v['location']) ?></span>
    </div>
    <?php endif; ?>
    <?php if ($v['description']): ?>
    <div style="padding:12px 18px;font-size:13px;color:var(--text-secondary);border-top:1px solid var(--border-light);">
        <?= sanitize($v['description']) ?>
    </div>
    <?php endif; ?>
    <?php if ($v['actions_data']): ?>
    <div style="padding:12px 18px;border-top:1px solid var(--border-light);background:var(--bg-body);">
        <small class="text-muted d-block mb-1" style="font-weight:600;">Disciplinary Action:</small>
        <?php 
        $actionsArr = explode(';;', $v['actions_data']);
        foreach ($actionsArr as $ad) {
            $parts = explode('|', $ad);
            if (count($parts) === 2) {
                echo actionBadge($parts[0]) . ' ' . statusBadge($parts[1]) . ' ';
            }
        }
        ?>
    </div>
    <?php endif; ?>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/mobile_footer.php'; ?>
