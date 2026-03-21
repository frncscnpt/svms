<?php
/**
 * SVMS - Discipline Officer Dashboard
 */
$pageTitle = 'Dashboard';
$breadcrumbs = ['Dashboard' => null];
require_once __DIR__ . '/../includes/header.php';
requireRole('discipline_officer');

$pdo = getDBConnection();

$pendingCount  = $pdo->query("SELECT COUNT(*) FROM violations WHERE status='pending'")->fetchColumn();
$reviewedCount = $pdo->query("SELECT COUNT(*) FROM violations WHERE status='reviewed'")->fetchColumn();
$resolvedCount = $pdo->query("SELECT COUNT(*) FROM violations WHERE status='resolved'")->fetchColumn();
$thisMonth     = $pdo->query("SELECT COUNT(*) FROM violations WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())")->fetchColumn();

// Pending violations
$pending = $pdo->query("
    SELECT v.*, s.first_name, s.last_name, s.student_number, s.grade_level, s.section,
           vt.name as violation_name, vt.severity, u.full_name as reporter
    FROM violations v
    JOIN students s ON v.student_id=s.id
    JOIN violation_types vt ON v.violation_type_id=vt.id
    JOIN users u ON v.reported_by=u.id
    WHERE v.status='pending' ORDER BY v.created_at DESC LIMIT 10
")->fetchAll();

// Recent actions
$recentActions = $pdo->query("
    SELECT da.*, v.description as v_desc, s.first_name, s.last_name, s.student_number,
           u.full_name as issuer_name, vt.name as violation_name
    FROM disciplinary_actions da
    JOIN violations v ON da.violation_id=v.id
    JOIN students s ON v.student_id=s.id
    JOIN violation_types vt ON v.violation_type_id=vt.id
    JOIN users u ON da.issued_by=u.id
    ORDER BY da.created_at DESC LIMIT 5
")->fetchAll();
?>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card stat-gold animate-slideUp stagger-1">
            <div class="stat-header">
                <div class="stat-label">Pending</div>
                <div class="stat-icon"><i class="bi bi-clock-fill"></i></div>
            </div>
            <div class="stat-value"><?= $pendingCount ?></div>
            <div class="mt-2"><span class="stat-trend <?= $pendingCount > 0 ? 'down' : 'neutral' ?>"><?= $pendingCount > 0 ? 'Needs attention' : 'All clear' ?></span></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card stat-blue animate-slideUp stagger-2">
            <div class="stat-header">
                <div class="stat-label">Under Review</div>
                <div class="stat-icon"><i class="bi bi-eye-fill"></i></div>
            </div>
            <div class="stat-value"><?= $reviewedCount ?></div>
            <div class="mt-2"><span class="stat-trend neutral">In progress</span></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card stat-green animate-slideUp stagger-3">
            <div class="stat-header">
                <div class="stat-label">Resolved</div>
                <div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div>
            </div>
            <div class="stat-value"><?= $resolvedCount ?></div>
            <div class="mt-2"><span class="stat-trend up">Cases closed</span></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card stat-red animate-slideUp stagger-4">
            <div class="stat-header">
                <div class="stat-label">This Month</div>
                <div class="stat-icon"><i class="bi bi-calendar-event-fill"></i></div>
            </div>
            <div class="stat-value"><?= $thisMonth ?></div>
            <div class="mt-2"><span class="stat-trend neutral">vs last month</span></div>
        </div>
    </div>
</div>

<!-- QR Scanner + Pending Violations -->
<div class="row g-3 mb-4">
    <!-- QR Scanner -->
    <div class="col-lg-4">
        <div class="card-panel h-100">
            <div class="panel-header">
                <h5 class="panel-title"><i class="bi bi-qr-code-scan"></i> Quick Student Scan</h5>
            </div>
            <div class="panel-body text-center" style="padding:16px;">
                <div id="scannerSection">
                    <div id="qrReader" style="width:100%;border-radius:8px;overflow:hidden;margin-bottom:10px;"></div>
                    <p class="text-muted" style="font-size:12px;margin-bottom:0;">Scan student QR code to view profile or file action</p>
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
                        <a href="#" id="historyBtn" class="btn-outline-custom" style="justify-content:center;"><i class="bi bi-clock-history me-1"></i>View History</a>
                        <a href="<?= BASE_PATH ?>/discipline/report_violation.php" class="btn-primary-custom" style="justify-content:center;"><i class="bi bi-file-earmark-plus me-1"></i>File Report</a>
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

    <!-- Pending Violations -->
    <div class="col-lg-8">
        <div class="card-panel h-100">
            <div class="panel-header">
                <h5 class="panel-title"><i class="bi bi-exclamation-triangle"></i> Pending Violations</h5>
                <a href="<?= BASE_PATH ?>/discipline/violations.php?status=pending" class="btn btn-sm btn-outline-custom">View All</a>
            </div>
            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr><th>Student</th><th>Violation</th><th>Severity</th><th>Reported By</th><th>Date</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending as $p): ?>
                        <tr>
                            <td>
                                <div class="user-cell">
                                    <?= getAvatarHtml($p['photo'] ?? null, $p['first_name'].' '.$p['last_name'], 'user-avatar') ?>
                                    <div class="user-info">
                                        <div class="name"><?= sanitize($p['first_name'].' '.$p['last_name']) ?></div>
                                        <div class="sub"><?= sanitize($p['student_number']) ?> · <?= sanitize($p['grade_level']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?= sanitize($p['violation_name']) ?></td>
                            <td><?= severityBadge($p['severity']) ?></td>
                            <td><small><?= sanitize($p['reporter']) ?></small></td>
                            <td><small class="text-muted"><?= formatDateTime($p['date_occurred']) ?></small></td>
                            <td>
                                <a href="<?= BASE_PATH ?>/discipline/violations.php?action=review&id=<?= $p['id'] ?>" class="btn-primary-custom" style="padding:6px 14px;font-size:12px;">
                                    <i class="bi bi-eye"></i> Review
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($pending)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No pending violations</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Recent Disciplinary Actions -->
<div class="row g-3">
    <div class="col-12">
        <div class="card-panel">
            <div class="panel-header">
                <h5 class="panel-title"><i class="bi bi-hammer"></i> Recent Disciplinary Actions</h5>
                <a href="<?= BASE_PATH ?>/discipline/actions.php" class="btn btn-sm btn-outline-custom">View All</a>
            </div>
            <div class="panel-body">
                <?php if (empty($recentActions)): ?>
                    <p class="text-muted text-center">No actions issued yet</p>
                <?php else: ?>
                <div class="timeline">
                    <?php foreach ($recentActions as $a): ?>
                    <div class="timeline-item <?= in_array($a['action_type'], ['suspension','expulsion']) ? 'danger' : ($a['action_type'] === 'warning' ? 'warning' : '') ?>">
                        <div class="timeline-content">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong style="font-size:13px;"><?= sanitize($a['first_name'].' '.$a['last_name']) ?></strong>
                                    <span class="ms-2"><?= actionBadge($a['action_type']) ?></span>
                                </div>
                                <?= statusBadge($a['status']) ?>
                            </div>
                            <p style="font-size:12px;margin:4px 0 0;color:var(--text-secondary);">
                                <?= sanitize(substr($a['description'] ?? $a['violation_name'], 0, 100)) ?>
                            </p>
                        </div>
                        <div class="timeline-time"><?= timeAgo($a['created_at']) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$extraJS = '<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
let html5QrCode;

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
        document.getElementById("scannerSection").innerHTML = `
            <div class="text-center py-4">
                <i class="bi bi-camera-video-off text-danger" style="font-size:32px;"></i>
                <h6 class="mt-2">Camera Access Required</h6>
                <p class="text-muted mb-0" style="font-size:12px;">Please allow camera access to scan QR codes.</p>
            </div>`;
    });
}

function lookupStudent(qrData) {
    fetch("/api/qr_lookup.php?qr=" + encodeURIComponent(qrData))
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const s = data.student;
                document.getElementById("resultAvatarContainer").innerHTML = s.avatar_html;
                document.getElementById("resultName").textContent = s.first_name + " " + s.last_name;
                document.getElementById("resultNumber").textContent = s.student_number;
                document.getElementById("resultGrade").textContent = s.grade_level + " - " + s.section;
                document.getElementById("resultViolations").textContent = s.violation_count;
                document.getElementById("historyBtn").href = "/discipline/history.php?student=" + encodeURIComponent(s.id);
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

function resetScanner() { startScanner(); }

if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
    startScanner();
} else {
    document.getElementById("scannerSection").innerHTML = "<p class=\"text-muted text-center py-4\">Camera not supported on this browser/device.</p>";
}
</script>';
require_once __DIR__ . '/../includes/footer.php';
?>
