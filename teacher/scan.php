<?php
/**
 * SVMS - Teacher: QR Code Scanner
 */
$pageTitle = 'Scan QR Code';
require_once __DIR__ . '/../includes/layout.php';
$breadcrumbs = ['Dashboard' => BASE_PATH.'/teacher/index.php', 'Scan QR' => null];
if (IS_MOBILE) {
    require_once __DIR__ . '/../includes/mobile_header.php';
} else {
    require_once __DIR__ . '/../includes/header.php';
}
requireRole('teacher');
?>

<?php if (IS_MOBILE): ?>
<?php
// Hide topbar and bottom nav on full-screen scanner
$extraCSS = '<style>
    .m-topbar, .mobile-content, .m-bottom-nav, .m-nav-fab-wrap { display: none !important; }
    .mobile-app { padding-bottom: 0 !important; background: #000 !important; overflow: hidden; }
</style>';
$hideScanNav = true;
?>

<!-- ── Full-screen scanner UI ── -->
<div id="scannerSection" class="m-scanner-fullscreen">

    <!-- Camera feed -->
    <div id="qrReader" class="m-scanner-feed"></div>

    <!-- Dark overlay with cutout -->
    <div class="m-scanner-overlay">
        <div class="m-scan-shadow-top"></div>
        <div class="m-scan-shadow-bottom"></div>
        <div class="m-scan-shadow-left"></div>
        <div class="m-scan-shadow-right"></div>
        <!-- Top bar -->
        <div class="m-scanner-topbar">
            <a href="<?= BASE_PATH ?>/teacher/index.php" class="m-scanner-back">
                <i class="bi bi-arrow-left"></i>
            </a>
            <span class="m-scanner-title">Scan Student QR</span>
            <div style="width:40px;"></div>
        </div>

        <!-- Viewfinder frame -->
        <div class="m-scanner-frame-wrap">
            <div class="m-scanner-frame" id="scanFrame">
                <!-- Corner brackets -->
                <span class="m-corner tl"></span>
                <span class="m-corner tr"></span>
                <span class="m-corner bl"></span>
                <span class="m-corner br"></span>
                <!-- Scan line -->
                <div class="m-scan-line"></div>
            </div>
            <p class="m-scanner-hint">Position the QR code inside the frame</p>
        </div>

        <!-- Bottom actions -->
        <div class="m-scanner-actions">
            <label class="m-scanner-btn">
                <i class="bi bi-upload"></i>
                <span>Upload QR</span>
                <input type="file" accept="image/*" id="qrGalleryInput" style="display:none;">
            </label>
            <a href="<?= BASE_PATH ?>/teacher/report.php" class="m-scanner-btn">
                <i class="bi bi-person-lines-fill"></i>
                <span>Search Student</span>
            </a>
        </div>
    </div>
</div>

<!-- Result Section -->
<div id="resultSection" style="display:none;">
    <div class="m-card mb-3">
        <div style="background:linear-gradient(135deg,#130117,#2e1731);padding:28px;text-align:center;color:white;border-radius:18px 18px 0 0;">
            <div class="m-profile-avatar" id="resultAvatar" style="margin:0 auto 12px;background:rgba(255,255,255,0.15);"></div>
            <h5 style="color:white;margin:0;" id="resultName"></h5>
            <small style="color:rgba(255,255,255,0.6);" id="resultNumber"></small>
        </div>
        <div style="padding:0;">
            <div class="m-detail-row"><span class="m-detail-label">Grade & Section</span><strong id="resultGrade"></strong></div>
            <div class="m-detail-row"><span class="m-detail-label">Contact</span><strong id="resultContact"></strong></div>
            <div class="m-detail-row"><span class="m-detail-label">Guardian</span><strong id="resultGuardian"></strong></div>
            <div class="m-detail-row"><span class="m-detail-label">Total Violations</span><strong><span class="badge badge-soft-danger" id="resultViolations"></span></strong></div>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="#" id="reportBtn" class="m-submit-btn flex-fill justify-content-center" style="padding:12px;">
            <i class="bi bi-exclamation-triangle me-1"></i> File Violation Report
        </a>
        <button onclick="resetScanner()" style="padding:12px 20px;background:#f7f2f8;border:1.5px solid #ede9ee;border-radius:14px;font-size:18px;cursor:pointer;">
            <i class="bi bi-arrow-repeat"></i>
        </button>
    </div>
</div>

<!-- Error Section -->
<div id="errorSection" style="display:none;">
    <div class="m-card">
        <div class="m-empty">
            <i class="bi bi-exclamation-circle" style="color:#dc2626;"></i>
            <h5>Student Not Found</h5>
            <p id="errorMsg">No student linked to this QR code.</p>
            <button class="m-submit-btn mt-2" onclick="resetScanner()">
                <i class="bi bi-arrow-repeat me-1"></i> Try Again
            </button>
        </div>
    </div>
</div>

<?php else: ?>

<!-- Desktop layout -->
<div class="row justify-content-center">
<div class="col-lg-6">
<div id="scannerSection">
    <div class="card-panel mb-3">
        <div class="panel-header"><h5 class="panel-title"><i class="bi bi-qr-code-scan"></i> QR Scanner</h5></div>
        <div class="panel-body">
            <div id="qrReader" style="min-height:280px;border-radius:12px;overflow:hidden;"></div>
            <div class="qr-scanner-hint mt-3 text-center">
                <i class="bi bi-qr-code-scan" style="font-size:22px;color:var(--primary);display:block;margin-bottom:6px;"></i>
                Position the QR code within the frame to scan
            </div>
        </div>
    </div>
</div>
<div id="resultSection" style="display:none;">
    <div class="card-panel mb-3">
        <div style="background:linear-gradient(135deg,#130117,#2e1731);padding:28px;text-align:center;color:white;border-radius:18px 18px 0 0;">
            <div class="user-avatar" id="resultAvatar" style="width:64px;height:64px;font-size:22px;margin:0 auto 12px;background:rgba(255,255,255,0.15);"></div>
            <h5 style="color:white;margin:0;" id="resultName"></h5>
            <small style="color:rgba(255,255,255,0.6);" id="resultNumber"></small>
        </div>
        <div style="padding:0;">
            <div class="d-flex justify-content-between py-3 px-4 border-bottom" style="font-size:13px;"><span class="text-muted">Grade & Section</span><strong id="resultGrade"></strong></div>
            <div class="d-flex justify-content-between py-3 px-4 border-bottom" style="font-size:13px;"><span class="text-muted">Contact</span><strong id="resultContact"></strong></div>
            <div class="d-flex justify-content-between py-3 px-4 border-bottom" style="font-size:13px;"><span class="text-muted">Guardian</span><strong id="resultGuardian"></strong></div>
            <div class="d-flex justify-content-between py-3 px-4" style="font-size:13px;"><span class="text-muted">Total Violations</span><strong><span class="badge badge-soft-danger" id="resultViolations"></span></strong></div>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="#" id="reportBtn" class="btn-primary-custom flex-fill justify-content-center" style="padding:12px;"><i class="bi bi-exclamation-triangle me-1"></i> File Violation Report</a>
        <button class="btn-outline-custom" onclick="resetScanner()" style="padding:12px 20px;"><i class="bi bi-arrow-repeat"></i></button>
    </div>
</div>
<div id="errorSection" style="display:none;">
    <div class="card-panel">
        <div class="panel-body text-center py-5">
            <i class="bi bi-exclamation-circle" style="font-size:48px;color:var(--danger);"></i>
            <h5 style="margin-top:12px;">Student Not Found</h5>
            <p id="errorMsg" style="color:var(--text-muted);font-size:13px;">No student linked to this QR code.</p>
            <button class="btn-primary-custom mt-2" onclick="resetScanner()"><i class="bi bi-arrow-repeat me-1"></i> Try Again</button>
        </div>
    </div>
</div>
</div></div>

<?php endif; ?>

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
        { fps: 10, qrbox: { width: 240, height: 240 } },
        (decodedText) => {
            html5QrCode.stop().then(() => lookupStudent(decodedText)).catch(console.error);
        },
        () => {}
    ).catch(() => {
        document.getElementById("qrReader").innerHTML = `
            <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:280px;color:rgba(255,255,255,0.6);text-align:center;padding:20px;">
                <i class="bi bi-camera-video-off" style="font-size:48px;margin-bottom:12px;color:#dc2626;"></i>
                <p style="font-size:13px;">Camera access required.<br>Please allow camera permissions.</p>
            </div>`;
    });
}

function lookupStudent(qrData) {
    fetch("<?= BASE_PATH ?>/api/qr_lookup.php?qr=" + encodeURIComponent(qrData))
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const s = data.student;
                document.getElementById("resultAvatar").textContent = s.initials;
                document.getElementById("resultName").textContent = s.first_name + " " + s.last_name;
                document.getElementById("resultNumber").textContent = s.student_number;
                document.getElementById("resultGrade").textContent = s.grade_level + " - " + s.section;
                document.getElementById("resultContact").textContent = s.contact || "N/A";
                document.getElementById("resultGuardian").textContent = s.guardian_name || "N/A";
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

function resetScanner() { startScanner(); }

const urlQr = new URLSearchParams(location.search).get("qr");
if (urlQr) { lookupStudent(urlQr); } else { startScanner(); }

const galleryInput = document.getElementById("qrGalleryInput");
if (galleryInput) {
    galleryInput.addEventListener("change", async function() {
        const file = this.files[0];
        if (!file) return;
        try {
            const result = await Html5Qrcode.scanFile(file, false);
            lookupStudent(result);
        } catch (e) {
            alert("Could not read QR code from image. Please try a clearer photo.");
        }
        this.value = "";
    });
}
</script>';

if (IS_MOBILE) {
    require_once __DIR__ . '/../includes/mobile_footer.php';
} else {
    require_once __DIR__ . '/../includes/footer.php';
}
?>
