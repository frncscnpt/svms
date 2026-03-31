<?php
/**
 * SVMS - Student: Violation History
 */
$pageTitle = 'My Violations';

require_once __DIR__ . '/../includes/layout.php';

$breadcrumbs = ['Dashboard' => BASE_PATH.'/student/index.php', 'My Violations' => null];

require_once __DIR__ . '/../includes/header.php';

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

<!-- Section Header -->
<div class="m-section-header mb-3">
    <span class="m-section-title">Violation History</span>
    <span style="font-size:12px;color:#7e747c;font-weight:600;"><?= count($violations) ?> record<?= count($violations) !== 1 ? 's' : '' ?></span>
</div>

<?php if (empty($violations)): ?>
<div class="m-card">
    <div class="m-empty">
        <i class="bi bi-shield-check"></i>
        <h5>Clean Record</h5>
        <p>You have no violations on record!</p>
    </div>
</div>
<?php else: ?>
<div class="m-card">
    <?php foreach ($violations as $v): ?>
    <div style="padding:16px 18px;border-bottom:1px solid #f7f2f8;">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
                <div style="font-weight:700;font-size:14px;color:#130117;"><?= sanitize($v['violation_name']) ?></div>
                <div style="font-size:11px;color:#7e747c;margin-top:2px;"><?= formatDateTime($v['date_occurred'], 'M d, Y') ?> · <?= sanitize($v['reporter']) ?></div>
            </div>
            <?= severityBadge($v['severity']) ?>
        </div>
        <?php if ($v['location']): ?>
        <div style="font-size:11px;color:#7e747c;margin-bottom:6px;"><i class="bi bi-geo-alt me-1"></i><?= sanitize($v['location']) ?></div>
        <?php endif; ?>
        <div class="d-flex gap-2 flex-wrap align-items-center">
            <?= statusBadge($v['status']) ?>
            <?php if ($v['actions_data']): ?>
                <?php foreach (explode(';;', $v['actions_data']) as $ad):
                    $parts = explode('|', $ad);
                    if (count($parts) === 2) echo actionBadge($parts[0]);
                endforeach; ?>
            <?php endif; ?>
        </div>
        <?php if ($v['description']): ?>
        <p style="font-size:12px;color:#4c444b;margin-top:8px;margin-bottom:0;line-height:1.5;"><?= sanitize(substr($v['description'],0,120)) ?></p>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
