<?php
/**
 * SVMS - Teacher: My Reports
 */
$pageTitle = 'My Reports';
$pageSubtitle = 'View submitted violation reports';
require_once __DIR__ . '/../includes/mobile_header.php';
requireRole('teacher');

$pdo = getDBConnection();

$reports = $pdo->prepare("
    SELECT v.*, s.first_name, s.last_name, s.student_number, s.grade_level, s.photo,
           vt.name as violation_name, vt.severity,
           (SELECT da.action_type FROM disciplinary_actions da WHERE da.violation_id=v.id LIMIT 1) as action_taken
    FROM violations v
    JOIN students s ON v.student_id=s.id
    JOIN violation_types vt ON v.violation_type_id=vt.id
    WHERE v.reported_by=?
    ORDER BY v.created_at DESC
");
$reports->execute([$_SESSION['user_id']]);
$reports = $reports->fetchAll();
?>

<?php if (empty($reports)): ?>
    <div class="mobile-empty">
        <i class="bi bi-file-text"></i>
        <h5>No reports submitted</h5>
        <p>You haven't filed any violation reports yet</p>
        <a href="<?= BASE_PATH ?>/teacher/scan.php" class="mobile-form submit-btn" style="width:auto;display:inline-block;padding:10px 24px;margin-top:12px;">
            <i class="bi bi-qr-code-scan me-1"></i> Scan & Report
        </a>
    </div>
<?php else: ?>
    <div class="mobile-card">
        <?php foreach ($reports as $r): ?>
        <div class="mobile-list-item">
            <div style="width: 40px; height: 40px; margin-right: 12px; flex-shrink: 0;">
                <?= getAvatarHtml($r['photo'] ?? null, $r['first_name'].' '.$r['last_name'], 'profile-avatar', 'width: 100%; height: 100%; font-size: 16px; margin: 0;') ?>
            </div>
            <div class="item-content">
                <div class="item-title"><?= sanitize($r['first_name'].' '.$r['last_name']) ?></div>
                <div class="item-sub">
                    <?= sanitize($r['violation_name']) ?><br>
                    <small><?= sanitize($r['grade_level']) ?> · <?= formatDateTime($r['date_occurred'], 'M d, Y h:i A') ?></small>
                </div>
            </div>
            <div class="item-badge">
                <?= statusBadge($r['status']) ?>
                <?php if ($r['action_taken']): ?>
                    <div class="mt-1"><?= actionBadge($r['action_taken']) ?></div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/mobile_footer.php'; ?>
