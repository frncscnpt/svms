<?php
/**
 * SVMS - Teacher: Dashboard
 */
$pageTitle = 'Dashboard';

require_once __DIR__ . '/../includes/layout.php';

$breadcrumbs = ['Dashboard' => null];

if (IS_MOBILE) {
    require_once __DIR__ . '/../includes/mobile_header.php';
} else {
    require_once __DIR__ . '/../includes/header.php';
}

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

if (IS_MOBILE):
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

<!-- Quick Actions -->
<div class="m-section">
    <div class="m-section-header">
        <span class="m-section-title">Quick Actions</span>
    </div>
    <div style="display:flex;flex-direction:column;gap:10px;">
        <!-- Scan QR — dark gradient -->
        <a href="<?= BASE_PATH ?>/teacher/scan.php" class="m-quick-action-primary">
            <div class="m-quick-action-icon" style="background:rgba(255,255,255,0.12);">
                <i class="bi bi-qr-code-scan" style="color:#fff;font-size:20px;"></i>
            </div>
            <div class="m-quick-action-text">
                <div class="m-quick-action-title" style="color:#fff;">Scan Student QR</div>
                <div class="m-quick-action-sub" style="color:rgba(255,255,255,0.55);">Instant ID verification</div>
            </div>
            <i class="bi bi-chevron-right" style="color:rgba(255,255,255,0.35);font-size:16px;"></i>
        </a>
        <!-- File Report — white card -->
        <a href="<?= BASE_PATH ?>/teacher/report.php" class="m-quick-action-secondary">
            <div class="m-quick-action-icon" style="background:#ebe7ec;">
                <i class="bi bi-exclamation-triangle-fill" style="color:#2e1731;font-size:20px;"></i>
            </div>
            <div class="m-quick-action-text">
                <div class="m-quick-action-title">File Violation Report</div>
                <div class="m-quick-action-sub">Manual incident documentation</div>
            </div>
            <i class="bi bi-chevron-right" style="color:#ccc;font-size:16px;"></i>
        </a>
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

<?php require_once __DIR__ . '/../includes/mobile_footer.php';
else: // DESKTOP ?>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card stat-purple">
            <div class="stat-header">
                <span class="stat-label">Total Reports</span>
                <div class="stat-icon"><i class="bi bi-file-earmark-text-fill"></i></div>
            </div>
            <div class="stat-value"><?= $myReportsCount ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-orange">
            <div class="stat-header">
                <span class="stat-label">Pending</span>
                <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
            </div>
            <div class="stat-value"><?= $pendingCount ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-green">
            <div class="stat-header">
                <span class="stat-label">Resolved</span>
                <div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div>
            </div>
            <div class="stat-value"><?= $resolvedCount ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-blue">
            <div class="stat-header">
                <span class="stat-label">This Month</span>
                <div class="stat-icon"><i class="bi bi-calendar-month"></i></div>
            </div>
            <div class="stat-value"><?= $thisMonthCount ?></div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Recent Reports -->
    <div class="col-lg-8">
        <div class="card-panel">
            <div class="panel-header">
                <h5 class="panel-title"><i class="bi bi-clock-history"></i> Recent Reports</h5>
                <a href="<?= BASE_PATH ?>/teacher/my_reports.php" class="btn-outline-custom" style="font-size:12px;padding:5px 14px;">View All</a>
            </div>
            <?php if (empty($recent)): ?>
            <div class="panel-body text-center py-5">
                <i class="bi bi-file-text" style="font-size:48px;color:#ede9ee;"></i>
                <p style="color:var(--text-muted);margin-top:12px;font-size:14px;">No reports submitted yet.</p>
            </div>
            <?php else: ?>
            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead><tr>
                        <th>Student</th>
                        <th>Violation</th>
                        <th>Severity</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($recent as $r): ?>
                    <tr>
                        <td>
                            <div class="user-cell">
                                <?= getAvatarHtml($r['photo'] ?? null, $r['first_name'].' '.$r['last_name'], 'user-avatar', 'width:34px;height:34px;font-size:12px;margin:0;flex-shrink:0;') ?>
                                <div class="user-info">
                                    <div class="name"><?= sanitize($r['first_name'].' '.$r['last_name']) ?></div>
                                    <div class="sub"><?= sanitize($r['student_number']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td><?= sanitize($r['violation_name']) ?></td>
                        <td><?= severityBadge($r['severity']) ?></td>
                        <td><?= formatDateTime($r['created_at'], 'M d, Y') ?></td>
                        <td><?= statusBadge($r['status']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="col-lg-4">
        <div class="card-panel mb-4" style="background:linear-gradient(135deg,#130117,#2e1731);border:none;">
            <div class="panel-body" style="padding:28px 24px;">
                <h5 style="color:#fff;font-weight:700;margin-bottom:6px;">QR Quick Scan</h5>
                <p style="color:rgba(255,255,255,0.55);font-size:13px;margin-bottom:20px;">Instantly identify a student by scanning their ID QR code.</p>
                <a href="<?= BASE_PATH ?>/teacher/scan.php" class="btn-primary-custom" style="background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.2);width:100%;justify-content:center;padding:12px;">
                    <i class="bi bi-qr-code-scan"></i> Launch Scanner
                </a>
            </div>
        </div>

        <div class="card-panel">
            <div class="panel-header"><h5 class="panel-title"><i class="bi bi-lightning-fill"></i> Quick Actions</h5></div>
            <div class="panel-body" style="padding:12px;">
                <a href="<?= BASE_PATH ?>/teacher/report.php" class="d-flex align-items-center gap-3 p-3 text-decoration-none" style="border-radius:12px;transition:background 0.2s;" onmouseover="this.style.background='#f7f2f8'" onmouseout="this.style.background='transparent'">
                    <div style="width:40px;height:40px;background:rgba(46,23,49,0.07);border-radius:10px;display:flex;align-items:center;justify-content:center;color:var(--primary);font-size:18px;"><i class="bi bi-plus-circle-fill"></i></div>
                    <div>
                        <div style="font-weight:600;font-size:14px;color:var(--text-primary);">File a Report</div>
                        <div style="font-size:12px;color:var(--text-muted);">Submit a new violation</div>
                    </div>
                </a>
                <a href="<?= BASE_PATH ?>/teacher/my_reports.php" class="d-flex align-items-center gap-3 p-3 text-decoration-none" style="border-radius:12px;transition:background 0.2s;" onmouseover="this.style.background='#f7f2f8'" onmouseout="this.style.background='transparent'">
                    <div style="width:40px;height:40px;background:rgba(46,23,49,0.07);border-radius:10px;display:flex;align-items:center;justify-content:center;color:var(--primary);font-size:18px;"><i class="bi bi-file-earmark-text"></i></div>
                    <div>
                        <div style="font-weight:600;font-size:14px;color:var(--text-primary);">My Reports</div>
                        <div style="font-size:12px;color:var(--text-muted);">View submitted reports</div>
                    </div>
                </a>
                <a href="<?= BASE_PATH ?>/settings.php" class="d-flex align-items-center gap-3 p-3 text-decoration-none" style="border-radius:12px;transition:background 0.2s;" onmouseover="this.style.background='#f7f2f8'" onmouseout="this.style.background='transparent'">
                    <div style="width:40px;height:40px;background:rgba(46,23,49,0.07);border-radius:10px;display:flex;align-items:center;justify-content:center;color:var(--primary);font-size:18px;"><i class="bi bi-person-circle"></i></div>
                    <div>
                        <div style="font-weight:600;font-size:14px;color:var(--text-primary);">My Profile</div>
                        <div style="font-size:12px;color:var(--text-muted);">Account settings</div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php';
endif; ?>
