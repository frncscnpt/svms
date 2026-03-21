<?php
/**
 * SVMS - Student: Violation History
 */
$pageTitle = 'My Violations';

require_once __DIR__ . '/../includes/layout.php';

$breadcrumbs = ['Dashboard' => BASE_PATH.'/student/index.php', 'My Violations' => null];

if (IS_MOBILE) {
    require_once __DIR__ . '/../includes/mobile_header.php';
} else {
    require_once __DIR__ . '/../includes/header.php';
}

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

if (IS_MOBILE):
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

<?php require_once __DIR__ . '/../includes/mobile_footer.php';
else: // DESKTOP ?>

<div class="card-panel">
    <div class="panel-header">
        <h5 class="panel-title"><i class="bi bi-exclamation-triangle-fill"></i> My Violations</h5>
        <span style="font-size:13px;color:var(--text-muted);"><?= count($violations) ?> record<?= count($violations) !== 1 ? 's' : '' ?></span>
    </div>
    <?php if (empty($violations)): ?>
    <div class="panel-body text-center py-5">
        <i class="bi bi-shield-check" style="font-size:52px;color:#ede9ee;"></i>
        <p style="color:var(--text-muted);margin-top:14px;font-size:14px;">No violations on record. Keep it up!</p>
    </div>
    <?php else: ?>
    <div class="data-table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>Violation</th>
                <th>Severity</th>
                <th>Location</th>
                <th>Reported By</th>
                <th>Date</th>
                <th>Status</th>
                <th>Action</th>
            </tr></thead>
            <tbody>
            <?php foreach ($violations as $v): ?>
            <tr>
                <td><?= sanitize($v['violation_name']) ?></td>
                <td><?= severityBadge($v['severity']) ?></td>
                <td><?= sanitize($v['location'] ?? '—') ?></td>
                <td><?= sanitize($v['reporter']) ?></td>
                <td><?= formatDateTime($v['date_occurred'], 'M d, Y') ?></td>
                <td><?= statusBadge($v['status']) ?></td>
                <td>
                    <?php if ($v['actions_data']): ?>
                        <?php foreach (explode(';;', $v['actions_data']) as $ad):
                            $parts = explode('|', $ad);
                            if (count($parts) === 2) echo actionBadge($parts[0]);
                        endforeach; ?>
                    <?php else: ?>
                        <span style="color:var(--text-muted);font-size:12px;">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php';
endif; ?>
