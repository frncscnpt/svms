<?php
/**
 * SVMS - Teacher: Mobile Dashboard
 */
$pageTitle = 'Dashboard';
$pageSubtitle = 'Teacher Portal';
require_once __DIR__ . '/../includes/mobile_header.php';
requireRole('teacher');

$pdo = getDBConnection();

$myReports = $pdo->prepare("SELECT COUNT(*) FROM violations WHERE reported_by=?")->execute([$_SESSION['user_id']]);
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

// Recent reports
$recent = $pdo->prepare("
    SELECT v.*, s.first_name, s.last_name, s.student_number, vt.name as violation_name, vt.severity
    FROM violations v JOIN students s ON v.student_id=s.id
    JOIN violation_types vt ON v.violation_type_id=vt.id
    WHERE v.reported_by=? ORDER BY v.created_at DESC LIMIT 5
");
$recent->execute([$_SESSION['user_id']]);
$recent = $recent->fetchAll();
?>

<!-- Greeting -->
<div class="mobile-greeting">
    <h2>Hello, <?= sanitize(explode(' ', $currentUser['full_name'])[0]) ?>!</h2>
    <p>Lyceum of Subic Bay · Teacher Portal</p>
</div>

<!-- Stats -->
<div class="mobile-stats">
    <div class="mobile-stat">
        <div class="stat-num"><?= $myReportsCount ?></div>
        <div class="stat-text">Total Reports</div>
    </div>
    <div class="mobile-stat">
        <div class="stat-num" style="color:var(--warning);"><?= $pendingCount ?></div>
        <div class="stat-text">Pending</div>
    </div>
    <div class="mobile-stat">
        <div class="stat-num" style="color:var(--success);"><?= $resolvedCount ?></div>
        <div class="stat-text">Resolved</div>
    </div>
    <div class="mobile-stat">
        <div class="stat-num" style="color:var(--info);"><?= $thisMonthCount ?></div>
        <div class="stat-text">This Month</div>
    </div>
</div>

<!-- Quick Actions -->
<div class="mobile-section-title" style="margin-top:28px;">Quick Actions</div>
<div class="row g-2 mb-3">
    <div class="col-6">
        <a href="<?= BASE_PATH ?>/teacher/scan.php" class="mobile-card text-center text-decoration-none" style="display:block;padding:20px;">
            <i class="bi bi-qr-code-scan" style="font-size:32px;color:var(--primary);"></i>
            <div style="font-size:13px;font-weight:600;margin-top:8px;color:var(--text-primary);">Scan QR Code</div>
        </a>
    </div>
    <div class="col-6">
        <a href="<?= BASE_PATH ?>/teacher/report.php" class="mobile-card text-center text-decoration-none" style="display:block;padding:20px;">
            <i class="bi bi-plus-circle-fill" style="font-size:32px;color:var(--accent-dark);"></i>
            <div style="font-size:13px;font-weight:600;margin-top:8px;color:var(--text-primary);">File Report</div>
        </a>
    </div>
</div>

<!-- Recent Reports -->
<div class="mobile-section-title">
    Recent Reports
    <a href="<?= BASE_PATH ?>/teacher/my_reports.php" class="see-all">See All →</a>
</div>

<div class="mobile-card">
    <?php if (empty($recent)): ?>
        <div class="mobile-empty">
            <i class="bi bi-file-text"></i>
            <h5>No reports yet</h5>
            <p>Scan a QR code to file your first violation report</p>
        </div>
    <?php else: ?>
        <?php foreach ($recent as $r): ?>
        <div class="mobile-list-item">
            <div style="width: 40px; height: 40px; margin-right: 12px; flex-shrink: 0;">
                <?= getAvatarHtml($r['photo'] ?? null, $r['first_name'].' '.$r['last_name'], 'profile-avatar', 'width: 100%; height: 100%; font-size: 16px; margin: 0;') ?>
            </div>
            <div class="item-content">
                <div class="item-title"><?= sanitize($r['first_name'].' '.$r['last_name']) ?></div>
                <div class="item-sub"><?= sanitize($r['violation_name']) ?> · <?= timeAgo($r['created_at']) ?></div>
            </div>
            <div class="item-badge"><?= statusBadge($r['status']) ?></div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/mobile_footer.php'; ?>
