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
$totalStudents = $pdo->query("SELECT COUNT(*) FROM students WHERE status='active'")->fetchColumn();
$totalViolations = $pdo->query("SELECT COUNT(*) FROM violations")->fetchColumn();
$pendingViolations = $pdo->query("SELECT COUNT(*) FROM violations WHERE status='pending'")->fetchColumn();
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE status='active'")->fetchColumn();
$thisMonthViolations = $pdo->query("SELECT COUNT(*) FROM violations WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())")->fetchColumn();

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
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
    FROM violations
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY month ORDER BY month
")->fetchAll();

// Recent activity
$recentActivity = $pdo->query("
    SELECT al.*, u.full_name FROM activity_log al
    LEFT JOIN users u ON al.user_id = u.id
    ORDER BY al.created_at DESC LIMIT 6
")->fetchAll();
?>

<!-- Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="stat-card stat-purple animate-slideUp stagger-1">
            <div class="stat-header">
                <div class="stat-icon"><i class="bi bi-mortarboard-fill"></i></div>
            </div>
            <div class="stat-value"><?= number_format($totalStudents) ?></div>
            <div class="stat-label">Total Students</div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="stat-card stat-red animate-slideUp stagger-2">
            <div class="stat-header">
                <div class="stat-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
            </div>
            <div class="stat-value"><?= number_format($totalViolations) ?></div>
            <div class="stat-label">Total Violations</div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="stat-card stat-gold animate-slideUp stagger-3">
            <div class="stat-header">
                <div class="stat-icon"><i class="bi bi-clock-fill"></i></div>
            </div>
            <div class="stat-value"><?= number_format($pendingViolations) ?></div>
            <div class="stat-label">Pending Review</div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="stat-card stat-blue animate-slideUp stagger-4">
            <div class="stat-header">
                <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
            </div>
            <div class="stat-value"><?= number_format($totalUsers) ?></div>
            <div class="stat-label">Active Users</div>
        </div>
    </div>
</div>

    </div>
</div>

<!-- QR Scanner Row -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card-panel">
            <div class="panel-header">
                <h5 class="panel-title"><i class="bi bi-qr-code-scan"></i> Quick Student Lookup</h5>
            </div>
            <div class="panel-body">
                <div class="row align-items-center">
                    <div class="col-md-5 text-center px-4 border-end">
                        <div id="scannerSection">
                            <div id="qrReader" style="width: 100%; max-width: 300px; margin: 0 auto; border-radius: 8px; overflow: hidden; margin-bottom: 10px;"></div>
                            <p class="text-muted" style="font-size: 13px; margin-bottom: 0;">Scan student QR code to instantly view their profile</p>
                            <button id="startScanBtn" class="btn btn-outline-custom mt-3" style="display:none;" onclick="startScanner()"><i class="bi bi-camera"></i> Start Scanner</button>
                        </div>
                    </div>
                    
                    <div class="col-md-7 ps-md-4 mt-4 mt-md-0">
                        <!-- Result area -->
                        <div id="resultSection" style="display:none;">
                            <div class="d-flex align-items-center gap-3 mb-4">
                                <div id="resultAvatarContainer"></div>
                                <div>
                                    <h5 id="resultName" class="mb-1" style="color: var(--primary);"></h5>
                                    <div class="text-muted" id="resultNumber" style="font-family: monospace;"></div>
                                </div>
                            </div>
                            
                            <div class="row g-3 mb-4 text-start">
                                <div class="col-sm-6">
                                    <div class="p-3 bg-light rounded border">
                                        <small class="text-muted d-block text-uppercase" style="font-size:10px;letter-spacing:1px;">Grade & Section</small>
                                        <strong id="resultGrade"></strong>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="p-3 bg-light rounded border">
                                        <small class="text-muted d-block text-uppercase" style="font-size:10px;letter-spacing:1px;">Total Violations</small>
                                        <strong><span class="badge badge-soft-danger fs-6" id="resultViolations"></span></strong>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <a href="#" id="viewProfileBtn" class="btn btn-primary-custom">
                                    <i class="bi bi-person-lines-fill"></i> Edit Student Profile
                                </a>
                                <button class="btn btn-outline-secondary" onclick="resetScanner()">
                                    <i class="bi bi-arrow-repeat"></i> Scan Another
                                </button>
                            </div>
                        </div>

                        <!-- Error Section -->
                        <div id="errorSection" style="display:none; text-align: center; padding: 40px 0;">
                            <i class="bi bi-exclamation-circle text-danger mb-3" style="font-size: 48px;"></i>
                            <h5 class="text-danger">Student Not Found</h5>
                            <p id="errorMsg" class="text-muted">The scanned QR code does not belong to any active student.</p>
                            <button class="btn btn-outline-secondary mt-2" onclick="resetScanner()">Try Again</button>
                        </div>
                        
                        <!-- Initial Placeholder -->
                        <div id="placeholderSection" class="text-center text-muted" style="padding: 40px 0;">
                            <i class="bi bi-person-bounding-box" style="font-size: 64px; opacity: 0.2;"></i>
                            <h6 class="mt-3">Awaiting Scan</h6>
                            <p style="font-size:13px;">Student profile details will appear here once a QR code is recognized by the camera.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card-panel">
            <div class="panel-header">
                <h5 class="panel-title"><i class="bi bi-graph-up"></i> Monthly Violation Trend</h5>
            </div>
            <div class="panel-body">
                <div class="chart-container"><canvas id="trendChart"></canvas></div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card-panel">
            <div class="panel-header">
                <h5 class="panel-title"><i class="bi bi-pie-chart-fill"></i> By Type</h5>
            </div>
            <div class="panel-body">
                <div class="chart-container"><canvas id="typeChart"></canvas></div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Violations -->
<div class="row g-3">
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
        <div class="card-panel">
            <div class="panel-header">
                <h5 class="panel-title"><i class="bi bi-activity"></i> Recent Activity</h5>
            </div>
            <div class="panel-body">
                <div class="timeline">
                    <?php foreach ($recentActivity as $act): ?>
                    <div class="timeline-item">
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

<?php
$extraJS = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
const primaryColor = "#2e1731";
const accentColor = "#ff5900";
const colors = ["#2e1731","#ff5900","#6b3d70","#dc2626","#4f46e5","#16a34a","#d97706","#0ea5e9"];

// Monthly Trend
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
                borderWidth: 1,
                borderRadius: 6,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } },
                x: { grid: { display: false } }
            }
        }
    });
}

// By Type
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
        { fps: 10, qrbox: { width: 250, height: 250 } },
        (decodedText) => {
            html5QrCode.stop().then(() => {
                lookupStudent(decodedText);
            }).catch(console.error);
        },
        () => {}
    ).catch(err => {
        document.getElementById("qrReader").style.display = "none";
        document.getElementById("startScanBtn").style.display = "inline-block";
        alert("Camera access required to scan QR codes.");
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

function resetScanner() {
    startScanner();
}

// Init scanner
if(navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
    startScanner();
} else {
    document.getElementById("qrReader").innerHTML = "<p class=\'text-muted text-center py-4 border rounded bg-light\'>Camera not supported</p>";
}
</script>';
require_once __DIR__ . '/../includes/footer.php';
?>
