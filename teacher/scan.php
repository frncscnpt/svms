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

<div class="<?= IS_MOBILE ? '' : 'row justify-content-center' ?>">
<div class="<?= IS_MOBILE ? '' : 'col-lg-6' ?>">

<!-- Scanner Area -->
<div id="scannerSection">
    <div class="<?= IS_MOBILE ? 'm-card' : 'card-panel' ?> mb-3">
        <?php if (!IS_MOBILE): ?>
        <div class="panel-header"><h5 class="panel-title"><i class="bi bi-qr-code-scan"></i> QR Scanner</h5></div>
        <?php endif; ?>
        <div class="<?= IS_MOBILE ? 'm-card-body' : 'panel-body' ?>">
            <div class="<?= IS_MOBILE ? 'm-qr-container' : 'qr-scanner-container' ?>" id="qrReader" style="min-height:280px;border-radius:12px;overflow:hidden;"></div>
            <div class="<?= IS_MOBILE ? 'm-qr-hint' : 'qr-scanner-hint mt-3 text-center' ?>">
                <i class="bi bi-qr-code-scan" style="font-size:22px;color:var(--primary);display:block;margin-bottom:6px;"></i>
                Position the QR code within the frame to scan
            </div>
        </div>
    </div>
</div>

<!-- Result Section (hidden initially) -->
<div id="resultSection" style="display:none;">
    <div class="<?= IS_MOBILE ? 'm-card' : 'card-panel' ?> mb-3">
        <div style="background:linear-gradient(135deg,#130117,#2e1731);padding:28px;text-align:center;color:white;border-radius:<?= IS_MOBILE ? '18px 18px 0 0' : '18px 18px 0 0' ?>;">
            <div class="<?= IS_MOBILE ? 'm-profile-avatar' : 'user-avatar' ?>" id="resultAvatar" style="<?= IS_MOBILE ? '' : 'width:64px;height:64px;font-size:22px;' ?>margin:0 auto 12px;background:rgba(255,255,255,0.15);"></div>
            <h5 style="color:white;margin:0;" id="resultName"></h5>
            <small style="color:rgba(255,255,255,0.6);" id="resultNumber"></small>
        </div>
        <div class="<?= IS_MOBILE ? '' : 'panel-body' ?>" style="padding:0;">
            <div class="<?= IS_MOBILE ? 'm-detail-row' : 'd-flex justify-content-between py-3 px-4 border-bottom' ?>" style="font-size:13px;"><span class="<?= IS_MOBILE ? 'm-detail-label' : 'text-muted' ?>">Grade & Section</span><strong id="resultGrade"></strong></div>
            <div class="<?= IS_MOBILE ? 'm-detail-row' : 'd-flex justify-content-between py-3 px-4 border-bottom' ?>" style="font-size:13px;"><span class="<?= IS_MOBILE ? 'm-detail-label' : 'text-muted' ?>">Contact</span><strong id="resultContact"></strong></div>
            <div class="<?= IS_MOBILE ? 'm-detail-row' : 'd-flex justify-content-between py-3 px-4 border-bottom' ?>" style="font-size:13px;"><span class="<?= IS_MOBILE ? 'm-detail-label' : 'text-muted' ?>">Guardian</span><strong id="resultGuardian"></strong></div>
            <div class="<?= IS_MOBILE ? 'm-detail-row' : 'd-flex justify-content-between py-3 px-4' ?>" style="font-size:13px;"><span class="<?= IS_MOBILE ? 'm-detail-label' : 'text-muted' ?>">Total Violations</span><strong><span class="badge badge-soft-danger" id="resultViolations"></span></strong></div>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="#" id="reportBtn" class="<?= IS_MOBILE ? 'm-submit-btn' : 'btn-primary-custom' ?> flex-fill justify-content-center" style="padding:12px;"><i class="bi bi-exclamation-triangle me-1"></i> File Violation Report</a>
        <button class="<?= IS_MOBILE ? '' : 'btn-outline-custom' ?>" onclick="resetScanner()" style="padding:12px 20px;<?= IS_MOBILE ? 'background:#f7f2f8;border:1.5px solid #ede9ee;border-radius:14px;font-size:18px;' : '' ?>"><i class="bi bi-arrow-repeat"></i></button>
    </div>
</div>

<!-- Error Section -->
<div id="errorSection" style="display:none;">
    <div class="<?= IS_MOBILE ? 'm-card' : 'card-panel' ?>">
        <div class="<?= IS_MOBILE ? 'm-empty' : 'panel-body text-center py-5' ?>">
            <i class="bi bi-exclamation-circle" style="font-size:48px;color:var(--danger);<?= IS_MOBILE ? 'display:block;margin-bottom:14px;' : '' ?>"></i>
            <h5 style="<?= IS_MOBILE ? 'font-size:16px;font-weight:700;color:#4c444b;margin-bottom:6px;' : 'margin-top:12px;color:var(--text-primary);' ?>">Student Not Found</h5>
            <p id="errorMsg" style="<?= IS_MOBILE ? 'font-size:13px;color:#7e747c;' : 'color:var(--text-muted);font-size:13px;' ?>">No student linked to this QR code.</p>
            <button class="<?= IS_MOBILE ? 'm-submit-btn mt-2' : 'btn-primary-custom mt-2' ?>" onclick="resetScanner()"><i class="bi bi-arrow-repeat me-1"></i> Try Again</button>
        </div>
    </div>
</div>

</div></div>

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
if (IS_MOBILE) {
    require_once __DIR__ . '/../includes/mobile_footer.php';
} else {
    require_once __DIR__ . '/../includes/footer.php';
}
?>
