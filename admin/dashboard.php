<?php
/**
 * SVMS - Admin Dashboard
 */
$pageTitle = 'Dashboard';
$breadcrumbs = ['Dashboard' => null];
require_once __DIR__ . '/../includes/header.php';
requireRole('admin');

$pdo = getDBConnection();

// Stats
$totalStudents       = $pdo->query("SELECT COUNT(*) FROM students WHERE status='active'")->fetchColumn();
$totalViolations     = $pdo->query("SELECT COUNT(*) FROM violations")->fetchColumn();
$pendingViolations   = $pdo->query("SELECT COUNT(*) FROM violations WHERE status='pending'")->fetchColumn();
$totalUsers          = $pdo->query("SELECT COUNT(*) FROM users WHERE status='active'")->fetchColumn();
$thisMonthViolations = $pdo->query("SELECT COUNT(*) FROM violations WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())")->fetchColumn();
$lastMonthViolations = $pdo->query("SELECT COUNT(*) FROM violations WHERE MONTH(created_at)=MONTH(DATE_SUB(NOW(),INTERVAL 1 MONTH)) AND YEAR(created_at)=YEAR(DATE_SUB(NOW(),INTERVAL 1 MONTH))")->fetchColumn();

// Trend badge helper
function trendBadge($current, $previous, $label = 'vs last month') {
    if ($previous == 0) return '<span class="stat-trend neutral">— ' . $label . '</span>';
    $diff = $current - $previous;
    $pct  = round(abs($diff / $previous) * 100);
    if ($diff > 0) return '<span class="stat-trend up"><i class="bi bi-arrow-up-short"></i>' . $pct . '% ' . $label . '</span>';
    return '<span class="stat-trend down"><i class="bi bi-arrow-down-short"></i>' . $pct . '% ' . $label . '</span>';
}

// Recent violations
$recentViolations = $pdo->query("
    SELECT v.*, s.first_name, s.last_name, s.student_number, vt.name as violation_name, vt.severity,
           u.full_name as reporter_name
    FROM violations v
    JOIN students s ON v.student_id = s.id
    JOIN violation_types vt ON v.violation_type_id = vt.id
    JOIN users u ON v.reported_by = u.id
    ORDER BY v.created_at DESC LIMIT 8
")->fetchAll();

// Violations by type (for chart)
$violationsByType = $pdo->query("
    SELECT vt.name, COUNT(v.id) as count
    FROM violation_types vt
    LEFT JOIN violations v ON vt.id = v.violation_type_id
    GROUP BY vt.id, vt.name
    HAVING count > 0
    ORDER BY count DESC LIMIT 8
")->fetchAll();

// Monthly trend
$monthlyTrend = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%b %Y') as month, COUNT(*) as count
    FROM violations
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m'), DATE_FORMAT(created_at, '%b %Y')
    ORDER BY MIN(created_at)
")->fetchAll();

// Recent activity
$recentActivity = $pdo->query("
    SELECT al.*, u.full_name FROM activity_log al
    LEFT JOIN users u ON al.user_id = u.id
    ORDER BY al.created_at DESC LIMIT 6
")->fetchAll();
?>

<!-- ── Stat Cards ── -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card stat-purple animate-slideUp stagger-1">
            <div class="stat-header">
                <div class="stat-label">Total Students</div>
                <div class="stat-icon"><i class="bi bi-mortarboard-fill"></i></div>
            </div>
            <div class="stat-value"><?= number_format($totalStudents) ?></div>
            <div class="mt-2"><?= trendBadge($totalStudents, $totalStudents) ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card stat-red animate-slideUp stagger-2">
            <div class="stat-header">
                <div class="stat-label">Total Violations</div>
                <div class="stat-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
            </div>
            <div class="stat-value"><?= number_format($totalViolations) ?></div>
            <div class="mt-2"><?= trendBadge($thisMonthViolations, $lastMonthViolations) ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card stat-gold animate-slideUp stagger-3">
            <div class="stat-header">
                <div class="stat-label">Pending Review</div>
                <div class="stat-icon"><i class="bi bi-clock-fill"></i></div>
            </div>
            <div class="stat-value"><?= number_format($pendingViolations) ?></div>
            <div class="mt-2"><span class="stat-trend <?= $pendingViolations > 0 ? 'down' : 'neutral' ?>"><?= $pendingViolations > 0 ? 'Needs attention' : 'All clear' ?></span></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card stat-blue animate-slideUp stagger-4">
            <div class="stat-header">
                <div class="stat-label">Active Users</div>
                <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
            </div>
            <div class="stat-value"><?= number_format($totalUsers) ?></div>
            <div class="mt-2"><span class="stat-trend neutral">Staff &amp; faculty</span></div>
        </div>
    </div>
</div>

<!-- ── Charts Row (2/3 + 1/3) ── -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card-panel h-100">
            <div class="panel-header">
                <h5 class="panel-title"><i class="bi bi-bar-chart-fill"></i> Violation Trend</h5>
                <span class="badge badge-soft-secondary">Last 6 months</span>
            </div>
            <div class="panel-body">
                <div class="chart-container"><canvas id="trendChart"></canvas></div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card-panel h-100">
            <div class="panel-header">
                <h5 class="panel-title"><i class="bi bi-pie-chart-fill"></i> By Type</h5>
            </div>
            <div class="panel-body">
                <div class="chart-container"><canvas id="typeChart"></canvas></div>
            </div>
        </div>
    </div>
</div>

<!-- ── Recent Violations + Activity ── -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card-panel">
            <div class="panel-header">
                <h5 class="panel-title"><i class="bi bi-exclamation-triangle"></i> Recent Violations</h5>
                <a href="<?= BASE_PATH ?>/admin/violations.php" class="btn btn-sm btn-outline-custom">View All</a>
            </div>
            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Violation</th>
                            <th>Severity</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentViolations as $v): ?>
                        <tr>
                            <td>
                                <div class="user-cell">
                                    <?= getAvatarHtml($v['photo'] ?? null, $v['first_name'] . ' ' . $v['last_name'], 'user-avatar') ?>
                                    <div class="user-info">
                                        <div class="name"><?= sanitize($v['first_name'] . ' ' . $v['last_name']) ?></div>
                                        <div class="sub"><?= sanitize($v['student_number']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?= sanitize($v['violation_name']) ?></td>
                            <td><?= severityBadge($v['severity']) ?></td>
                            <td><?= statusBadge($v['status']) ?></td>
                            <td><small class="text-muted"><?= formatDateTime($v['date_occurred']) ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentViolations)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No violations recorded yet</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card-panel h-100">
            <div class="panel-header">
                <h5 class="panel-title"><i class="bi bi-activity"></i> Recent Activity</h5>
            </div>
            <div class="panel-body">
                <div class="timeline">
                    <?php foreach ($recentActivity as $i => $act):
                        $colors = ['accent','success','warning','danger','info','accent'];
                        $cls = $colors[$i % count($colors)];
                    ?>
                    <div class="timeline-item <?= $cls === 'success' ? 'success' : ($cls === 'warning' ? 'warning' : ($cls === 'danger' ? 'danger' : '')) ?>">
                        <div class="timeline-content">
                            <strong style="font-size:13px;"><?= sanitize($act['full_name'] ?? 'System') ?></strong>
                            <p style="font-size:12px;margin:2px 0 0;color:var(--text-secondary);"><?= sanitize($act['action']) ?></p>
                        </div>
                        <div class="timeline-time"><?= timeAgo($act['created_at']) ?></div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($recentActivity)): ?>
                    <p class="text-muted text-center" style="font-size:13px;">No recent activity</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── QR Scanner (dark gradient card) ── -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="qr-scanner-card">
            <!-- Faded background QR icon -->
            <div class="qr-scanner-bg-icon"><i class="bi bi-qr-code"></i></div>

            <div class="row align-items-center position-relative" style="z-index:1;">
                <div class="col-md-5 text-center px-4" style="border-right: 1px solid rgba(255,255,255,0.1);">
                    <div class="qr-scanner-label mb-3">
                        <i class="bi bi-qr-code-scan me-2"></i>Quick Student Lookup
                    </div>
                    <div id="qrReader" style="width:100%;max-width:280px;margin:0 auto;border-radius:12px;overflow:hidden;"></div>
                    <button id="startScanBtn" class="btn-qr-start mt-3" style="display:none;" onclick="startScanner()">
                        <i class="bi bi-camera me-2"></i>Start Scanner
                    </button>
                    <p class="qr-scanner-hint mt-2">Point camera at student QR code</p>
                </div>

                <div class="col-md-7 ps-md-4 mt-4 mt-md-0">
                    <!-- Placeholder -->
                    <div id="placeholderSection" class="text-center" style="padding: 32px 0;">
                        <i class="bi bi-person-bounding-box" style="font-size:56px;opacity:0.2;color:#fff;"></i>
                        <h6 class="mt-3" style="color:rgba(255,255,255,0.7);">Awaiting Scan</h6>
                        <p style="font-size:13px;color:rgba(255,255,255,0.4);">Student details will appear here once a QR code is recognized.</p>
                    </div>

                    <!-- Result -->
                    <div id="resultSection" style="display:none;">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <div id="resultAvatarContainer"></div>
                            <div>
                                <h5 id="resultName" class="mb-1" style="color:#fff;font-size:18px;"></h5>
                                <div id="resultNumber" style="color:rgba(255,255,255,0.55);font-family:monospace;font-size:13px;"></div>
                            </div>
                        </div>
                        <div class="row g-2 mb-4">
                            <div class="col-sm-6">
                                <div class="qr-result-chip">
                                    <small style="color:rgba(255,255,255,0.45);font-size:10px;text-transform:uppercase;letter-spacing:1px;display:block;">Grade &amp; Section</small>
                                    <strong id="resultGrade" style="color:#fff;font-size:14px;"></strong>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="qr-result-chip">
                                    <small style="color:rgba(255,255,255,0.45);font-size:10px;text-transform:uppercase;letter-spacing:1px;display:block;">Violations</small>
                                    <strong><span class="badge badge-soft-danger fs-6" id="resultViolations"></span></strong>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="#" id="viewProfileBtn" class="btn-qr-action-primary">
                                <i class="bi bi-person-lines-fill me-1"></i>View Profile
                            </a>
                            <button class="btn-qr-action-secondary" onclick="resetScanner()">
                                <i class="bi bi-arrow-repeat me-1"></i>Scan Again
                            </button>
                        </div>
                    </div>

                    <!-- Error -->
                    <div id="errorSection" style="display:none;text-align:center;padding:32px 0;">
                        <i class="bi bi-exclamation-circle" style="font-size:44px;color:rgba(220,38,38,0.8);"></i>
                        <h5 class="mt-3" style="color:#fff;">Student Not Found</h5>
                        <p id="errorMsg" style="font-size:13px;color:rgba(255,255,255,0.45);">The scanned QR code does not match any active student.</p>
                        <button class="btn-qr-action-secondary mt-2" onclick="resetScanner()">Try Again</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$extraJS = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
const primaryColor = "#2e1731";
const colors = ["#2e1731","#6b3d70","#4f46e5","#dc2626","#16a34a","#d97706","#0ea5e9","#9333ea"];

// Monthly Trend Bar Chart
const trendCtx = document.getElementById("trendChart");
if (trendCtx) {
    new Chart(trendCtx, {
        type: "bar",
        data: {
            labels: ' . json_encode(array_column($monthlyTrend, 'month')) . ',
            datasets: [{
                label: "Violations",
                data: ' . json_encode(array_map('intval', array_column($monthlyTrend, 'count'))) . ',
                backgroundColor: primaryColor + "cc",
                borderColor: primaryColor,
                borderWidth: 0,
                borderRadius: 8,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: "rgba(0,0,0,0.04)" } },
                x: { grid: { display: false } }
            }
        }
    });
}

// By Type Doughnut
const typeCtx = document.getElementById("typeChart");
if (typeCtx) {
    new Chart(typeCtx, {
        type: "doughnut",
        data: {
            labels: ' . json_encode(array_column($violationsByType, 'name')) . ',
            datasets: [{
                data: ' . json_encode(array_map('intval', array_column($violationsByType, 'count'))) . ',
                backgroundColor: colors,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: "bottom", labels: { font: { size: 11 }, padding: 12 } } },
            cutout: "65%"
        }
    });
}
</script>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
let html5QrCode;

function startScanner() {
    document.getElementById("startScanBtn").style.display = "none";
    document.getElementById("qrReader").style.display = "block";
    document.getElementById("resultSection").style.display = "none";
    document.getElementById("errorSection").style.display = "none";
    document.getElementById("placeholderSection").style.display = "block";

    html5QrCode = new Html5Qrcode("qrReader");
    html5QrCode.start(
        { facingMode: "environment" },
        { fps: 10, qrbox: { width: 220, height: 220 } },
        (decodedText) => {
            html5QrCode.stop().then(() => lookupStudent(decodedText)).catch(console.error);
        },
        () => {}
    ).catch(() => {
        document.getElementById("qrReader").style.display = "none";
        document.getElementById("startScanBtn").style.display = "inline-block";
    });
}

function lookupStudent(qrData) {
    document.getElementById("placeholderSection").style.display = "none";
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
                document.getElementById("viewProfileBtn").href = `/admin/students.php?search=${encodeURIComponent(s.student_number)}`;
                document.getElementById("resultSection").style.display = "block";
            } else {
                document.getElementById("errorMsg").textContent = data.message || "No student found.";
                document.getElementById("errorSection").style.display = "block";
            }
        })
        .catch(() => {
            document.getElementById("errorMsg").textContent = "Network error. Please try again.";
            document.getElementById("errorSection").style.display = "block";
        });
}

function resetScanner() { startScanner(); }

if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
    startScanner();
} else {
    document.getElementById("qrReader").innerHTML = "<p style=\"color:rgba(255,255,255,0.4);text-align:center;padding:32px 0;\">Camera not supported</p>";
}
</script>';
require_once __DIR__ . '/../includes/footer.php';
?>
