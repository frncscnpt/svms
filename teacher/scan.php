<?php
/**
 * SVMS - Teacher: QR Code Scanner
 */
$pageTitle = 'Scan QR Code';
$pageSubtitle = 'Point camera at student QR code';
require_once __DIR__ . '/../includes/mobile_header.php';
requireRole('teacher');
?>

<!-- Scanner Area -->
<div id="scannerSection">
    <div class="qr-scanner-container" id="qrReader" style="min-height:300px;"></div>
    <div class="qr-scanner-hint">
        <i class="bi bi-qr-code-scan"></i>
        Position the QR code within the frame to scan
    </div>
</div>

<!-- Result Section (hidden initially) -->
<div id="resultSection" style="display:none;">
    <div class="mobile-card">
        <div style="background:linear-gradient(135deg,var(--primary),var(--primary-light));padding:24px;text-align:center;color:white;">
            <div class="mobile-profile-avatar" id="resultAvatar" style="margin:0 auto 12px;"></div>
            <h5 style="color:white;margin:0;" id="resultName"></h5>
            <small style="color:rgba(255,255,255,0.7);" id="resultNumber"></small>
        </div>
        <div class="card-body">
            <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:13px;">
                <span class="text-muted">Grade & Section</span>
                <strong id="resultGrade"></strong>
            </div>
            <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:13px;">
                <span class="text-muted">Contact</span>
                <strong id="resultContact"></strong>
            </div>
            <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:13px;">
                <span class="text-muted">Guardian</span>
                <strong id="resultGuardian"></strong>
            </div>
            <div class="d-flex justify-content-between py-2" style="font-size:13px;">
                <span class="text-muted">Total Violations</span>
                <strong><span class="badge badge-soft-danger" id="resultViolations"></span></strong>
            </div>
        </div>
    </div>

    <div class="d-grid gap-2 mt-3">
        <a href="#" id="reportBtn" class="mobile-form submit-btn text-center text-decoration-none" style="display:block;">
            <i class="bi bi-exclamation-triangle me-1"></i> File Violation Report
        </a>
        <button class="btn btn-outline-secondary" onclick="resetScanner()" style="border-radius:var(--radius-lg);padding:12px;">
            <i class="bi bi-arrow-repeat me-1"></i> Scan Another
        </button>
    </div>
</div>

<!-- Error Section -->
<div id="errorSection" style="display:none;">
    <div class="mobile-empty">
        <i class="bi bi-exclamation-circle" style="color:var(--danger);"></i>
        <h5>Student Not Found</h5>
        <p id="errorMsg">No student linked to this QR code.</p>
        <button class="mobile-form submit-btn" onclick="resetScanner()" style="width:auto;display:inline-block;padding:10px 24px;margin-top:12px;">
            <i class="bi bi-arrow-repeat me-1"></i> Try Again
        </button>
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
            html5QrCode.stop().then(() => {
                lookupStudent(decodedText);
            }).catch(console.error);
        },
        () => {}
    ).catch(err => {
        document.getElementById("scannerSection").innerHTML = `
            <div class="mobile-empty">
                <i class="bi bi-camera-video-off" style="color:var(--danger);"></i>
                <h5>Camera Access Required</h5>
                <p>Please allow camera access to scan QR codes. On mobile, you may need to use HTTPS.</p>
            </div>`;
    });
}

function lookupStudent(qrData) {
    fetch("/api/qr_lookup.php?qr=" + encodeURIComponent(qrData))
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
                document.getElementById("reportBtn").href = "/teacher/report.php?student_id=" + s.id;
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
    startScanner();
}

startScanner();
</script>';
require_once __DIR__ . '/../includes/mobile_footer.php';
?>
