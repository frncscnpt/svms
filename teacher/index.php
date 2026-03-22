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
        <div class="card-panel h-100" style="overflow:hidden;">
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

    <!-- Quick Student Scan -->
    <div class="col-lg-4">
        <div class="card-panel h-100">
            <div class="panel-header">
                <h5 class="panel-title"><i class="bi bi-qr-code-scan"></i> Quick Student Scan</h5>
                <div class="d-flex align-items-center gap-2">
                    <span id="camToggleLabel" style="font-size:11px;color:var(--text-muted);font-weight:600;">OFF</span>
                    <div class="cam-toggle-wrap" onclick="toggleCamera()" title="Toggle camera">
                        <input type="checkbox" id="camToggle" style="display:none;">
                        <div class="cam-toggle-track cam-off" id="camToggleTrack">
                            <div class="cam-toggle-thumb"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="panel-body text-center" style="padding:16px;">
                <div id="scannerSection">
                    <div id="qrReader" style="width:100%;border-radius:8px;overflow:hidden;margin-bottom:10px;display:none;"></div>
                    <div id="camOffPlaceholder" style="display:flex;width:100%;border-radius:8px;background:#f7f2f8;border:1.5px dashed #ede9ee;min-height:200px;align-items:center;justify-content:center;flex-direction:column;gap:8px;margin-bottom:10px;">
                        <i class="bi bi-camera-video-off" style="font-size:32px;color:var(--text-muted);"></i>
                        <span style="font-size:12px;color:var(--text-muted);">Camera is off</span>
                    </div>
                    <p class="text-muted" style="font-size:12px;margin-bottom:0;">Scan student QR code to view profile or file a report</p>
                </div>
                <!-- Result -->
                <div id="resultSection" style="display:none;text-align:left;">
                    <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
                        <div id="resultAvatarContainer"></div>
                        <div>
                            <h6 id="resultName" class="mb-0" style="color:var(--primary);"></h6>
                            <small class="text-muted" id="resultNumber"></small>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mb-2" style="font-size:13px;">
                        <span class="text-muted">Grade:</span>
                        <strong id="resultGrade"></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-3" style="font-size:13px;">
                        <span class="text-muted">Total Violations:</span>
                        <strong><span class="badge badge-soft-danger" id="resultViolations"></span></strong>
                    </div>
                    <div class="d-grid gap-2">
                        <a href="#" id="reportBtn" class="btn-primary-custom" style="justify-content:center;"><i class="bi bi-file-earmark-plus me-1"></i>File Report</a>
                        <button class="btn btn-sm btn-outline-secondary" onclick="resetScanner()"><i class="bi bi-arrow-repeat me-1"></i>Scan Another</button>
                    </div>
                </div>
                <!-- Error -->
                <div id="errorSection" style="display:none;padding:20px 0;">
                    <i class="bi bi-exclamation-circle text-danger" style="font-size:32px;"></i>
                    <h6 class="mt-2 text-danger">Student Not Found</h6>
                    <p id="errorMsg" class="text-muted" style="font-size:12px;">The QR code is invalid or not registered.</p>
                    <button class="btn btn-sm btn-outline-secondary mt-2" onclick="resetScanner()">Try Again</button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$extraJS = '<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
let html5QrCode;
let cameraOn = false;

function startScanner() {
    document.getElementById("scannerSection").style.display = "block";
    document.getElementById("resultSection").style.display = "none";
    document.getElementById("errorSection").style.display = "none";
    html5QrCode = new Html5Qrcode("qrReader");
    html5QrCode.start(
        { facingMode: "environment" },
        { fps: 10, qrbox: { width: 220, height: 220 } },
        (decodedText) => {
            html5QrCode.stop().then(() => lookupStudent(decodedText)).catch(console.error);
        },
        () => {}
    ).catch(() => {
        document.getElementById("camOffPlaceholder").innerHTML = `
            <i class="bi bi-camera-video-off" style="font-size:32px;color:var(--text-muted);"></i>
            <span style="font-size:12px;color:var(--text-muted);">Camera access required</span>`;
    });
}

function toggleCamera() {
    cameraOn = !cameraOn;
    const label = document.getElementById("camToggleLabel");
    const track = document.getElementById("camToggleTrack");
    const qrReader = document.getElementById("qrReader");
    const placeholder = document.getElementById("camOffPlaceholder");
    if (cameraOn) {
        label.textContent = "ON";
        track.classList.remove("cam-off");
        qrReader.style.display = "block";
        placeholder.style.display = "none";
        startScanner();
    } else {
        label.textContent = "OFF";
        track.classList.add("cam-off");
        if (html5QrCode) html5QrCode.stop().catch(() => {});
        qrReader.style.display = "none";
        placeholder.style.display = "flex";
    }
}

function lookupStudent(qrData) {
    fetch("<?= BASE_PATH ?>/api/qr_lookup.php?qr=" + encodeURIComponent(qrData))
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const s = data.student;
                document.getElementById("resultAvatarContainer").innerHTML = s.avatar_html;
                document.getElementById("resultName").textContent = s.first_name + " " + s.last_name;
                document.getElementById("resultNumber").textContent = s.student_number;
                document.getElementById("resultGrade").textContent = s.grade_level + " - " + s.section;
                document.getElementById("resultViolations").textContent = s.violation_count;
                document.getElementById("reportBtn").href = "<?= BASE_PATH ?>/teacher/report.php?student_id=" + s.id;
                document.getElementById("scannerSection").style.display = "none";
                document.getElementById("resultSection").style.display = "block";
            } else {
                document.getElementById("errorMsg").textContent = data.message || "No student found.";
                document.getElementById("scannerSection").style.display = "none";
                document.getElementById("errorSection").style.display = "block";
            }
        })
        .catch(() => {
            document.getElementById("errorMsg").textContent = "Network error. Please try again.";
            document.getElementById("scannerSection").style.display = "none";
            document.getElementById("errorSection").style.display = "block";
        });
}

function resetScanner() {
    document.getElementById("resultSection").style.display = "none";
    document.getElementById("errorSection").style.display = "none";
    document.getElementById("scannerSection").style.display = "block";
    if (cameraOn) startScanner();
}
</script>';

require_once __DIR__ . '/../includes/footer.php';
endif; ?>
