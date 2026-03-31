<?php
/**
 * SVMS - Teacher: Dashboard
 */
$pageTitle = 'Dashboard';

require_once __DIR__ . '/../includes/layout.php';

$breadcrumbs = ['Dashboard' => null];

require_once __DIR__ . '/../includes/header.php';

requireRole('teacher');

$pdo = getDBConnection();

$myReportsCount = $pdo->prepare("SELECT COUNT(*) FROM violations WHERE reported_by=?");
$myReportsCount->execute([$_SESSION['user_id']]);
$myReportsCount = $myReportsCount->fetchColumn();

$pendingCount = $pdo->prepare("SELECT COUNT(*) FROM violations WHERE reported_by=? AND status='pending'");
$pendingCount->execute([$_SESSION['user_id']]);
$pendingCount = $pendingCount->fetchColumn();

$thisMonthCount = $pdo->prepare("SELECT COUNT(*) FROM violations WHERE reported_by=? AND MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())");
$thisMonthCount->execute([$_SESSION['user_id']]);
$thisMonthCount = $thisMonthCount->fetchColumn();

$resolvedCount = $pdo->prepare("SELECT COUNT(*) FROM violations WHERE reported_by=? AND status='resolved'");
$resolvedCount->execute([$_SESSION['user_id']]);
$resolvedCount = $resolvedCount->fetchColumn();

$recent = $pdo->prepare("
    SELECT v.*, s.first_name, s.last_name, s.student_number, s.photo, vt.name as violation_name, vt.severity
    FROM violations v JOIN students s ON v.student_id=s.id
    JOIN violation_types vt ON v.violation_type_id=vt.id
    WHERE v.reported_by=? ORDER BY v.created_at DESC LIMIT 8
");
$recent->execute([$_SESSION['user_id']]);
$recent = $recent->fetchAll();
?>

<!-- Greeting -->
<div class="m-greeting">
    <div class="m-greeting-sub">Good day,</div>
    <div class="m-greeting-name"><?= sanitize($currentUser['full_name']) ?></div>
</div>

<!-- Bento Stats -->
<div class="m-stats">
    <!-- Wide: Total violations -->
    <div class="m-stat primary" style="grid-column:span 2;">
        <div class="m-stat-header">
            <span class="m-stat-label">Total Violations Filed</span>
            <i class="bi bi-hammer m-stat-icon"></i>
        </div>
        <div style="display:flex;align-items:flex-end;justify-content:space-between;gap:8px;">
            <div class="m-stat-value"><?= $myReportsCount ?></div>
            <div style="text-align:right;padding-bottom:4px;">
                <div style="font-size:12px;font-weight:700;color:rgba(255,255,255,0.65);line-height:1.3;">+<?= $thisMonthCount ?> this month</div>
                <div style="font-size:10px;color:rgba(255,255,255,0.4);font-weight:600;text-transform:uppercase;letter-spacing:0.06em;">trending up</div>
            </div>
        </div>
    </div>
    <!-- Pending -->
    <div class="m-stat secondary">
        <div class="m-stat-header">
            <span class="m-stat-label" style="display:flex;align-items:center;gap:5px;"><span style="width:7px;height:7px;border-radius:50%;background:#dc2626;display:inline-block;"></span>Pending</span>
        </div>
        <div class="m-stat-value"><?= $pendingCount ?></div>
        <div class="m-stat-desc">Awaiting review</div>
    </div>
    <!-- Resolved -->
    <div class="m-stat secondary">
        <div class="m-stat-header">
            <span class="m-stat-label" style="display:flex;align-items:center;gap:5px;"><span style="width:7px;height:7px;border-radius:50%;background:#2e1731;display:inline-block;"></span>Resolved</span>
        </div>
        <div class="m-stat-value"><?= $resolvedCount ?></div>
        <div class="m-stat-desc">Cases closed</div>
    </div>
    <!-- Wide: This month -->
    <div class="m-stat" style="grid-column:span 2;background:#ebdeee;color:#2e1731;flex-direction:row;align-items:center;gap:16px;padding:16px 18px;">
        <div style="width:44px;height:44px;border-radius:50%;background:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 1px 4px rgba(0,0,0,0.06);">
            <i class="bi bi-calendar-check" style="font-size:20px;color:#2e1731;"></i>
        </div>
        <div style="flex:1;">
            <div style="font-size:11px;font-weight:600;color:#7e747c;text-transform:uppercase;letter-spacing:0.06em;">This month</div>
            <div style="font-size:17px;font-weight:800;color:#130117;letter-spacing:-0.02em;">+<?= $thisMonthCount ?> reported</div>
        </div>
        <i class="bi bi-graph-up-arrow" style="font-size:22px;color:#2e1731;opacity:0.35;"></i>
    </div>
</div>

<!-- Recent Reports -->
<div class="m-section">
    <div class="m-section-header">
        <span class="m-section-title">Recent Reports</span>
        <a href="<?= BASE_PATH ?>/teacher/my_reports.php" class="m-section-link">See all</a>
    </div>
    <div style="display:flex;flex-direction:column;gap:8px;">
        <?php if (empty($recent)): ?>
        <div class="m-card">
            <div class="m-empty">
                <i class="bi bi-file-text"></i>
                <h5>No Reports Yet</h5>
                <p>Start by filing a violation report.</p>
            </div>
        </div>
        <?php else: ?>
            <?php foreach ($recent as $r):
                $severityClass = match($r['severity']) {
                    'critical' => 'danger',
                    'major'    => 'warn',
                    default    => 'success'
                };
            ?>
            <div class="m-activity-item">
                <?= getAvatarHtml($r['photo'] ?? null, $r['first_name'].' '.$r['last_name'], 'm-activity-avatar', '') ?>
                <div class="m-activity-content">
                    <div class="m-activity-name"><?= sanitize($r['first_name'].' '.$r['last_name']) ?></div>
                    <div class="m-activity-sub"><?= sanitize($r['violation_name']) ?></div>
                </div>
                <div class="m-activity-meta">
                    <div class="m-activity-time"><?= formatDateTime($r['created_at'], 'M d') ?></div>
                    <span class="m-pill <?= $severityClass ?>"><?= ucfirst($r['severity']) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
