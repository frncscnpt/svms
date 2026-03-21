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
            <button type="button" class="m-scanner-btn" onclick="showManualSearch()" style="background:none; border:none; color:white;">
                <i class="bi bi-person-lines-fill"></i>
                <span>Search Student</span>
            </button>
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

<!-- Pass Verification Result Section (Mobile) -->
<div id="passResultSection" style="display:none;">
    <div class="m-card mb-3">
        <div id="passResultHeader" style="padding:28px;text-align:center;color:white;border-radius:18px 18px 0 0;">
            <i id="passResultIcon" style="font-size:48px;display:block;margin-bottom:10px;"></i>
            <h5 style="color:white;margin:0;" id="passResultTitle"></h5>
            <small style="color:rgba(255,255,255,0.7);" id="passResultStatus"></small>
        </div>
        <div style="padding:0;">
            <div class="m-detail-row"><span class="m-detail-label">Student</span><strong id="passStudentName"></strong></div>
            <div class="m-detail-row"><span class="m-detail-label">Student No.</span><strong id="passStudentNumber"></strong></div>
            <div class="m-detail-row"><span class="m-detail-label">Grade & Section</span><strong id="passGradeSection"></strong></div>
            <div class="m-detail-row"><span class="m-detail-label">Reason</span><strong id="passReason"></strong></div>
            <div class="m-detail-row"><span class="m-detail-label">Valid Date</span><strong id="passValidDate"></strong></div>
            <div class="m-detail-row"><span class="m-detail-label">Issued By</span><strong id="passIssuedBy"></strong></div>
        </div>
    </div>
    <button onclick="resetScanner()" class="m-submit-btn" style="width:100%;justify-content:center;padding:14px;">
        <i class="bi bi-arrow-repeat me-1"></i> Scan Another
    </button>
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

<!-- Search Section (New) -->
<div class="card-panel mb-3">
    <div class="panel-header d-flex justify-content-between align-items-center">
        <h5 class="panel-title mb-0"><i class="bi bi-search"></i> Manual Search</h5>
    </div>
    <div class="panel-body">
        <form id="desktopSearchForm" class="d-flex gap-2 position-relative" onsubmit="event.preventDefault();">
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="desktopSearchInput" class="form-control border-start-0 ps-0" placeholder="Enter name or student number..." autocomplete="off">
            </div>
            <ul id="desktopSearchResults" class="dropdown-menu w-100 shadow-sm" style="display:none; position:absolute; top:100%; left:0; max-height:250px; overflow-y:auto; z-index:1050; margin-top:4px; padding:0;"></ul>
            <button type="submit" class="btn-primary-custom px-4" style="visibility:hidden; width:0; padding:0!important; margin:0;" tabindex="-1"></button>
        </form>
    </div>
</div>

<div id="scannerSection">
    <div class="card-panel mb-3">
        <div class="panel-header"><h5 class="panel-title"><i class="bi bi-qr-code-scan"></i> QR Scanner</h5></div>
        <div class="panel-body">
            <div id="qrReader" style="min-height:280px;border-radius:12px;overflow:hidden;"></div>
            <div class="text-center mt-3">
                <label class="btn btn-outline-secondary btn-sm" style="border-radius:10px;padding:8px 16px;cursor:pointer;">
                    <i class="bi bi-upload me-1"></i> Upload QR Code Image
                    <input type="file" accept="image/*" id="qrGalleryInputDesktop" style="display:none;">
                </label>
            </div>
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
<div id="passResultSection" style="display:none;">
    <div class="card-panel mb-3">
        <div id="passResultHeader" style="padding:28px;text-align:center;color:white;border-radius:18px 18px 0 0;">
            <i id="passResultIcon" style="font-size:48px;display:block;margin-bottom:10px;"></i>
            <h5 style="color:white;margin:0;" id="passResultTitle"></h5>
            <small style="color:rgba(255,255,255,0.7);" id="passResultStatus"></small>
        </div>
        <div style="padding:0;">
            <div class="d-flex justify-content-between py-3 px-4 border-bottom" style="font-size:13px;"><span class="text-muted">Student</span><strong id="passStudentName"></strong></div>
            <div class="d-flex justify-content-between py-3 px-4 border-bottom" style="font-size:13px;"><span class="text-muted">Student No.</span><strong id="passStudentNumber"></strong></div>
            <div class="d-flex justify-content-between py-3 px-4 border-bottom" style="font-size:13px;"><span class="text-muted">Grade & Section</span><strong id="passGradeSection"></strong></div>
            <div class="d-flex justify-content-between py-3 px-4 border-bottom" style="font-size:13px;"><span class="text-muted">Reason</span><strong id="passReason"></strong></div>
            <div class="d-flex justify-content-between py-3 px-4 border-bottom" style="font-size:13px;"><span class="text-muted">Valid Date</span><strong id="passValidDate"></strong></div>
            <div class="d-flex justify-content-between py-3 px-4" style="font-size:13px;"><span class="text-muted">Issued By</span><strong id="passIssuedBy"></strong></div>
        </div>
    </div>
    <button class="btn-primary-custom w-100" onclick="resetScanner()" style="padding:12px;"><i class="bi bi-arrow-repeat me-1"></i> Scan Another</button>
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

<!-- Scan Error Modal -->
<div class="modal fade" id="scanErrorModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:360px;">
        <div class="modal-content" style="border-radius:18px;border:1px solid #ede9ee;">
            <div class="modal-body text-center" style="padding:32px 24px 20px;">
                <div style="width:52px;height:52px;background:rgba(220,53,69,0.1);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                    <i class="bi bi-qr-code" style="font-size:24px;color:#dc3545;"></i>
                </div>
                <h6 style="font-weight:700;margin-bottom:6px;">QR Code Not Detected</h6>
                <p id="scanErrorMsg" style="font-size:13px;color:var(--text-muted);margin-bottom:0;">Could not read QR code from image.</p>
            </div>
            <div class="modal-footer" style="border:none;padding:0 24px 24px;">
                <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<!-- Mobile Manual Search Modal -->
<div class="modal fade" id="mobileSearchModal" tabindex="-1">
    <div class="modal-dialog" style="max-width:400px; margin:16px auto;">
        <div class="modal-content" style="border-radius:24px; border:none; overflow:hidden;">
            <div class="modal-header" style="border-bottom:none; padding:24px 24px 10px;">
                <h5 class="modal-title" style="font-weight:700; font-size:18px;">Search Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:0 24px 24px;">
                <form id="mobileSearchForm" onsubmit="event.preventDefault();">
                    <div class="input-group mb-2" style="border-radius:12px; overflow:hidden; border:1.5px solid #ede9ee;">
                        <span class="input-group-text bg-white" style="border:none;"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="mobileSearchInput" class="form-control" style="border:none; padding-left:0; font-size:15px; box-shadow:none;" autocomplete="off" placeholder="Name or student number">
                    </div>
                    <!-- Make the list statically positioned so it scrolls natively inside the modal -->
                    <ul id="mobileSearchResults" class="w-100 shadow-sm bg-white" style="display:none; list-style:none; margin:0; padding:0; border-radius:12px; border:1px solid #ede9ee; max-height:250px; overflow-y:auto; overscroll-behavior:contain;"></ul>
                    <div class="text-center text-muted mt-2 mb-2" style="font-size:13px;" id="mobileSearchHint"><i class="bi bi-keyboard"></i> Type to search...</div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php $extraJS = ''; ?>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
let html5QrCode;

// Strip server-injected HTML (e.g. InfinityFree ads) before parsing JSON
function safeParseJSON(text) {
    const start = Math.min(
        text.indexOf('{') === -1 ? Infinity : text.indexOf('{'),
        text.indexOf('[') === -1 ? Infinity : text.indexOf('[')
    );
    if (start === Infinity) throw new SyntaxError("No JSON found in response");
    return JSON.parse(text.substring(start));
}

function showScanError(msg, detail = null) {
    const errorText = detail ? `${msg}\n\nError: ${detail}` : msg;
    document.getElementById("scanErrorMsg").textContent = errorText;
    var modal = document.getElementById("scanErrorModal");
    if (modal) { 
        new bootstrap.Modal(modal).show(); 
    } else { 
        alert(errorText); 
    }
}

function startScanner() {
    document.getElementById("scannerSection").style.display = "block";
    document.getElementById("resultSection").style.display = "none";
    document.getElementById("errorSection").style.display = "none";

    html5QrCode = new Html5Qrcode("qrReader");
    html5QrCode.start(
        { facingMode: "environment" },
        { fps: 10, qrbox: { width: 240, height: 240 } },
        (decodedText) => {
            html5QrCode.stop().then(() => handleScan(decodedText)).catch(console.error);
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

function handleScan(qrData) {
    if (qrData.startsWith("TUP-")) {
        verifyPass(qrData);
    } else {
        lookupStudent(qrData);
    }
}

function verifyPass(code) {
    fetch("<?= BASE_PATH ?>/api/verify_pass.php?code=" + encodeURIComponent(code), {
        headers: { "Bypass-Tunnel-Reminder": "true" }
    })
        .then(r => r.text())
        .then(text => {
            try {
                const data = safeParseJSON(text);
                if (data.success) {
                    const isValid = data.valid;
                    const header = document.getElementById("passResultHeader");
                    const icon = document.getElementById("passResultIcon");
                    const title = document.getElementById("passResultTitle");
                    const status = document.getElementById("passResultStatus");

                    if (isValid) {
                        header.style.background = "linear-gradient(135deg, #065f46, #059669)";
                        icon.className = "bi bi-check-circle-fill";
                        title.textContent = "PASS VALID";
                    } else {
                        header.style.background = "linear-gradient(135deg, #7f1d1d, #dc2626)";
                        icon.className = "bi bi-x-circle-fill";
                        title.textContent = data.status === "revoked" ? "PASS REVOKED" : "PASS EXPIRED";
                    }
                    status.textContent = data.status_message;

                    document.querySelectorAll("[id=passStudentName]").forEach(el => el.textContent = data.student_name || "N/A");
                    document.querySelectorAll("[id=passStudentNumber]").forEach(el => el.textContent = data.student_number || "N/A");
                    document.querySelectorAll("[id=passGradeSection]").forEach(el => el.textContent = (data.grade_level || "") + " - " + (data.section || ""));
                    document.querySelectorAll("[id=passReason]").forEach(el => el.textContent = data.reason || "N/A");
                    document.querySelectorAll("[id=passValidDate]").forEach(el => el.textContent = data.valid_date_formatted || "N/A");
                    document.querySelectorAll("[id=passIssuedBy]").forEach(el => el.textContent = data.issued_by || "N/A");

                    document.getElementById("scannerSection").style.display = "none";
                    document.getElementById("passResultSection").style.display = "block";
                } else {
                    document.getElementById("errorMsg").textContent = data.message || "Invalid pass code.";
                    document.getElementById("scannerSection").style.display = "none";
                    document.getElementById("errorSection").style.display = "block";
                }
            } catch (err) {
                alert("Server returned invalid response: " + text.substring(0, 100));
                throw err;
            }
        })
        .catch((e) => {
            alert("Error in verifyPass: " + e);
            document.getElementById("errorMsg").textContent = "Network error. Please try again.";
            document.getElementById("scannerSection").style.display = "none";
            document.getElementById("errorSection").style.display = "block";
        });
}

function lookupStudent(qrData) {
    if (html5QrCode && html5QrCode.isScanning) html5QrCode.stop().catch(()=>{});
    
    fetch("<?= BASE_PATH ?>/api/qr_lookup.php?qr=" + encodeURIComponent(qrData), {
        headers: { "Bypass-Tunnel-Reminder": "true" }
    })
        .then(r => r.text())
        .then(text => handleStudentResultResponse(text))
        .catch(handleStudentResultError);
}

function showManualSearch() {
    new bootstrap.Modal(document.getElementById('mobileSearchModal')).show();
    setTimeout(() => document.getElementById('mobileSearchInput').focus(), 500);
}

let searchTimeout = null;

function bindAutocomplete(inputId, resultsId) {
    const inputEl = document.getElementById(inputId);
    const resultsEl = document.getElementById(resultsId);
    if (!inputEl || !resultsEl) return;
    
    inputEl.addEventListener('input', function() {
        const query = this.value;
        if (searchTimeout) clearTimeout(searchTimeout);
        
        if (!query.trim() || query.length < 2) {
            resultsEl.style.display = 'none';
            if(resultsId === 'mobileSearchResults') document.getElementById('mobileSearchHint').style.display = 'block';
            return;
        }
        
        searchTimeout = setTimeout(() => {
            if(resultsId === 'mobileSearchResults') document.getElementById('mobileSearchHint').style.display = 'none';
            fetch("<?= BASE_PATH ?>/api/search_students.php?q=" + encodeURIComponent(query))
                .then(r => r.json())
                .then(data => {
                    resultsEl.innerHTML = '';
                    if (data.success && data.results.length > 0) {
                        data.results.forEach(s => {
                            const li = document.createElement('li');
                            // Ensure avatar is properly sized for dropdown
                            let av = s.avatar_html || '';
                            av = av.replace(/width:\s*48px/, 'width: 32px').replace(/height:\s*48px/, 'height: 32px').replace(/font-size:\s*18px/, 'font-size: 13px');
                            
                            // Adjust class for mobile vs desktop if needed, though dropdown-item works if hover effects are desired
                            const isMobile = resultsId === 'mobileSearchResults';
                            const itemClass = isMobile ? 'd-flex align-items-center gap-3 py-2 px-3 border-bottom' : 'dropdown-item d-flex align-items-center gap-3 py-2 border-bottom';
                            
                            li.innerHTML = `
                                <a class="${itemClass}" href="#" style="cursor:pointer; white-space:normal; text-decoration:none; color:inherit; display:flex;">
                                    <div style="flex-shrink:0;">${av}</div>
                                    <div style="flex-grow:1; min-width:0;">
                                        <div style="font-weight:600; font-size:14px; text-overflow:ellipsis; overflow:hidden;">${s.first_name} ${s.last_name}</div>
                                        <div style="font-size:12px; color:var(--text-muted);">${s.student_number} &middot; ${s.grade_level}</div>
                                    </div>
                                </a>
                            `;
                            
                            li.onclick = (e) => {
                                e.preventDefault();
                                resultsEl.style.display = 'none';
                                inputEl.value = s.first_name + ' ' + s.last_name;
                                selectStudent(s);
                            };
                            resultsEl.appendChild(li);
                        });
                        resultsEl.style.display = 'block';
                    } else {
                        resultsEl.innerHTML = '<li class="px-3 py-3 text-muted text-center" style="font-size:13px;">No students found matching that name.</li>';
                        resultsEl.style.display = 'block';
                    }
                })
                .catch(err => console.error(err));
        }, 300);
    });
    
    // Hide dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!inputEl.contains(e.target) && !resultsEl.contains(e.target)) {
            resultsEl.style.display = 'none';
        }
    });
}

// Bind live search
document.addEventListener('DOMContentLoaded', () => {
    bindAutocomplete('desktopSearchInput', 'desktopSearchResults');
    bindAutocomplete('mobileSearchInput', 'mobileSearchResults');
});

function selectStudent(studentObj) {
    // Hide modal if on mobile
    const bsModal = bootstrap.Modal.getInstance(document.getElementById('mobileSearchModal'));
    if (bsModal) bsModal.hide();
    
    if (html5QrCode && html5QrCode.isScanning) html5QrCode.stop().catch(()=>{});
    
    // Create a mock payload to reuse the existing result rendering logic
    const payload = JSON.stringify({ success: true, student: studentObj });
    handleStudentResultResponse(payload);
}

function handleStudentResultResponse(text) {
    try {
        const data = safeParseJSON(text);
        if (data.success) {
            // Reusing the same UI for both QR lookup and manual search
            const s = data.student;
            const avatarHtml = s.avatar_html || '';
            const avatarEl = document.getElementById("resultAvatar");
            if (avatarHtml.includes('<img')) {
                avatarEl.innerHTML = avatarHtml;
                avatarEl.style.background = 'transparent';
            } else {
                avatarEl.innerHTML = '<span>' + (s.first_name[0] + s.last_name[0]).toUpperCase() + '</span>';
            }
            // Clear previous initials if we appended HTML
            if (avatarHtml) avatarEl.innerHTML = avatarHtml;

            document.getElementById("resultName").textContent = s.first_name + " " + s.last_name;
            document.getElementById("resultNumber").textContent = s.student_number;
            document.getElementById("resultGrade").textContent = s.grade_level + " - " + s.section;
            document.getElementById("resultContact").textContent = s.contact || "N/A";
            document.getElementById("resultGuardian").textContent = s.guardian_name || "N/A";
            document.getElementById("resultViolations").textContent = s.violation_count;
            
            // Set the report button to point to report.php with this specific student_id
            document.getElementById("reportBtn").href = "<?= BASE_PATH ?>/teacher/report.php?student_id=" + s.id;
            
            document.getElementById("scannerSection").style.display = "none";
            document.getElementById("resultSection").style.display = "block";
            document.getElementById("errorSection").style.display = "none";
        } else {
            document.getElementById("errorMsg").textContent = data.message || "No student found.";
            document.getElementById("scannerSection").style.display = "none";
            document.getElementById("errorSection").style.display = "block";
            document.getElementById("resultSection").style.display = "none";
        }
    } catch (err) {
        alert("Server returned invalid response.");
        console.error(err, text);
        handleStudentResultError(err);
    }
}

function handleStudentResultError(e) {
    console.error("Lookup error:", e);
    document.getElementById("errorMsg").textContent = "Network error or student not found.";
    document.getElementById("scannerSection").style.display = "none";
    document.getElementById("errorSection").style.display = "block";
}

function resetScanner() {
    document.getElementById("passResultSection").style.display = "none";
    
    // Clear desktop search input if it exists
    const dtSearch = document.getElementById('desktopSearchInput');
    if (dtSearch) dtSearch.value = '';
    
    const mbSearch = document.getElementById('mobileSearchInput');
    if (mbSearch) mbSearch.value = '';
    
    startScanner();
}

const urlParams = new URLSearchParams(location.search);
const urlQr = urlParams.get("qr");
const actionParam = urlParams.get("action");

if (urlQr) { 
    handleScan(urlQr); 
} else { 
    startScanner(); 
    
    // Auto-open search if requested via URL
    if (actionParam === "search") {
        const tryOpenSearch = () => {
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', tryOpenSearch);
            } else {
                setTimeout(() => {
                    if (window.innerWidth < 992) {
                        showManualSearch();
                    } else {
                        const dtInput = document.getElementById('desktopSearchInput');
                        if (dtInput) dtInput.focus();
                    }
                }, 150);
            }
        };
        tryOpenSearch();
    }
}

const processFile = async function(file) {
    if (!file) return;
    
    // Check if camera is currently scanning (state 2 or isScanning property)
    let isScanning = false;
    try {
        isScanning = html5QrCode && (html5QrCode.isScanning || html5QrCode.getState() === 2);
    } catch (e) {
        isScanning = false;
    }
    
    try {
        if (isScanning) {
            await html5QrCode.stop();
        }
        
        // Use instance scanFile with showImage=false
        const result = await html5QrCode.scanFile(file, false);
        handleScan(result);
    } catch (e) {
        console.error("QR Scan Error:", e);
        showScanError("Could not read QR code from image. Please try a clearer or brighter photo.", e);
        
        // If it was scanning before, restart it so the user isn't stuck with a blank screen
        if (isScanning) {
            startScanner();
        }
    }
};

const galleryInput = document.getElementById("qrGalleryInput");
if (galleryInput) {
    galleryInput.addEventListener("change", e => {
        processFile(e.target.files[0]);
        e.target.value = "";
    });
}

const galleryInputDesktop = document.getElementById("qrGalleryInputDesktop");
if (galleryInputDesktop) {
    galleryInputDesktop.addEventListener("change", e => {
        processFile(e.target.files[0]);
        e.target.value = "";
    });
}
</script>
<?php
if (IS_MOBILE) {
    require_once __DIR__ . '/../includes/mobile_footer.php';
} else {
    require_once __DIR__ . '/../includes/footer.php';
}
?>
