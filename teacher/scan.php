<?php
/**
 * SVMS - Teacher: QR Code Scanner
 */
$pageTitle = 'Scan QR Code';
require_once __DIR__ . '/../includes/layout.php';
$breadcrumbs = ['Dashboard' => BASE_PATH.'/teacher/index.php', 'Scan QR' => null];
require_once __DIR__ . '/../includes/header.php';
requireRole('teacher');

?>

<!-- Scanner Container -->
<div id="scannerCard" class="scanner-fullview">
    <!-- Camera feed -->
    <div id="qrReader" class="scanner-video-feed"></div>
    <style>#qrReader video { transform: scaleX(-1); }</style>
    
    <!-- Scanner overlay -->
    <div class="scanner-overlay">
        <!-- Top bar -->
        <div class="scanner-topbar">
            <a href="<?= BASE_PATH ?>/teacher/index.php" class="scanner-back-btn">
                <i class="bi bi-arrow-left"></i>
            </a>
            <span class="scanner-title">Scan Student QR</span>
            <div style="width:40px;"></div>
        </div>
        
        <!-- Viewfinder frame -->
        <div class="scanner-frame-wrap">
            <div class="scanner-frame">
                <!-- Corner brackets -->
                <span class="scanner-corner tl"></span>
                <span class="scanner-corner tr"></span>
                <span class="scanner-corner bl"></span>
                <span class="scanner-corner br"></span>
                <!-- Scan line -->
                <div class="scanner-line"></div>
            </div>
            <p class="scanner-hint">Position the QR code inside the frame</p>
        </div>
        
        <!-- Bottom actions -->
        <div class="scanner-actions">
            <label class="scanner-action-btn">
                <i class="bi bi-upload"></i>
                <span>Upload QR</span>
                <input type="file" accept="image/*" id="qrGalleryInput" style="display:none;">
            </label>
            <button type="button" class="scanner-action-btn" onclick="showSearchModal()">
                <i class="bi bi-person-lines-fill"></i>
                <span>Search Student</span>
            </button>
        </div>
    </div>
</div>

<!-- Search Modal -->
<div class="modal fade" id="searchModal" tabindex="-1">
    <div class="modal-dialog" style="max-width:400px; margin:16px auto;">
        <div class="modal-content" style="border-radius:24px; border:none; overflow:hidden;">
            <div class="modal-header" style="border-bottom:none; padding:24px 24px 10px;">
                <h5 class="modal-title" style="font-weight:700; font-size:18px;">Search Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:0 24px 24px;">
                <form id="searchForm" onsubmit="event.preventDefault();">
                    <div class="input-group mb-2" style="border-radius:12px; overflow:hidden; border:1.5px solid #ede9ee;">
                        <span class="input-group-text bg-white" style="border:none;"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="searchInput" class="form-control" style="border:none; padding-left:0; font-size:15px; box-shadow:none;" autocomplete="off" placeholder="Name or student number">
                    </div>
                    <ul id="searchResults" class="w-100 shadow-sm bg-white" style="display:none; list-style:none; margin:0; padding:0; border-radius:12px; border:1px solid #ede9ee; max-height:250px; overflow-y:auto;"></ul>
                    <div class="text-center text-muted mt-2 mb-2" style="font-size:13px;" id="searchHint"><i class="bi bi-keyboard"></i> Type to search...</div>
                </form>
            </div>
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

<!-- Pass Verification Result Section -->
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
    document.getElementById("scannerCard").style.display = "block";
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

                    document.getElementById("passStudentName").textContent = data.student_name || "N/A";
                    document.getElementById("passStudentNumber").textContent = data.student_number || "N/A";
                    document.getElementById("passGradeSection").textContent = (data.grade_level || "") + " - " + (data.section || "");
                    document.getElementById("passReason").textContent = data.reason || "N/A";
                    document.getElementById("passValidDate").textContent = data.valid_date_formatted || "N/A";
                    document.getElementById("passIssuedBy").textContent = data.issued_by || "N/A";

                    document.getElementById("scannerCard").style.display = "none";
                    document.getElementById("passResultSection").style.display = "block";
                } else {
                    document.getElementById("errorMsg").textContent = data.message || "Invalid pass code.";
                    document.getElementById("scannerCard").style.display = "none";
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
            document.getElementById("scannerCard").style.display = "none";
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

function showSearchModal() {
    new bootstrap.Modal(document.getElementById('searchModal')).show();
    setTimeout(() => document.getElementById('searchInput').focus(), 500);
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
            const hint = document.getElementById('searchHint');
            if (hint) hint.style.display = 'block';
            return;
        }
        
        searchTimeout = setTimeout(() => {
            const hint = document.getElementById('searchHint');
            if (hint) hint.style.display = 'none';
            
            fetch("<?= BASE_PATH ?>/api/search_students.php?q=" + encodeURIComponent(query))
                .then(r => r.json())
                .then(data => {
                    resultsEl.innerHTML = '';
                    if (data.success && data.results.length > 0) {
                        data.results.forEach(s => {
                            const li = document.createElement('li');
                            let av = s.avatar_html || '';
                            av = av.replace(/width:\s*48px/, 'width: 32px').replace(/height:\s*48px/, 'height: 32px').replace(/font-size:\s*18px/, 'font-size: 13px');
                            
                            li.innerHTML = `
                                <a class="d-flex align-items-center gap-3 py-2 px-3 border-bottom" href="#" style="cursor:pointer; white-space:normal; text-decoration:none; color:inherit; display:flex;">
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
    
    document.addEventListener('click', function(e) {
        if (!inputEl.contains(e.target) && !resultsEl.contains(e.target)) {
            resultsEl.style.display = 'none';
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    bindAutocomplete('searchInput', 'searchResults');
});

function selectStudent(studentObj) {
    // Hide modal
    const bsModal = bootstrap.Modal.getInstance(document.getElementById('searchModal'));
    if (bsModal) bsModal.hide();
    
    if (html5QrCode && html5QrCode.isScanning) html5QrCode.stop().catch(()=>{});
    
    const payload = JSON.stringify({ success: true, student: studentObj });
    handleStudentResultResponse(payload);
}

function handleStudentResultResponse(text) {
    try {
        const data = safeParseJSON(text);
        if (data.success) {
            const s = data.student;
            const avatarEl = document.getElementById("resultAvatar");
            const avatarHtml = s.avatar_html || '';
            
            if (avatarHtml) {
                avatarEl.innerHTML = avatarHtml;
                if (avatarHtml.includes('<img')) {
                    avatarEl.style.background = 'transparent';
                }
            } else {
                avatarEl.innerHTML = '<span>' + (s.first_name[0] + s.last_name[0]).toUpperCase() + '</span>';
            }

            document.getElementById("resultName").textContent = s.first_name + " " + s.last_name;
            document.getElementById("resultNumber").textContent = s.student_number;
            document.getElementById("resultGrade").textContent = s.grade_level + " - " + s.section;
            document.getElementById("resultContact").textContent = s.contact || "N/A";
            document.getElementById("resultGuardian").textContent = s.guardian_name || "N/A";
            document.getElementById("resultViolations").textContent = s.violation_count;
            
            document.getElementById("reportBtn").href = "<?= BASE_PATH ?>/teacher/report.php?student_id=" + s.id;
            
            document.getElementById("scannerCard").style.display = "none";
            document.getElementById("resultSection").style.display = "block";
            document.getElementById("errorSection").style.display = "none";
        } else {
            document.getElementById("errorMsg").textContent = data.message || "No student found.";
            document.getElementById("scannerCard").style.display = "none";
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
    document.getElementById("scannerCard").style.display = "none";
    document.getElementById("errorSection").style.display = "block";
}

function resetScanner() {
    document.getElementById("passResultSection").style.display = "none";
    
    const searchInput = document.getElementById('searchInput');
    if (searchInput) searchInput.value = '';
    
    startScanner();
}

const urlParams = new URLSearchParams(location.search);
const urlQr = urlParams.get("qr");
const actionParam = urlParams.get("action");

if (urlQr) { 
    handleScan(urlQr); 
} else if (actionParam === "search") {
    setTimeout(() => showSearchModal(), 150);
} else { 
    startScanner(); 
}

const processFile = async function(file) {
    if (!file) return;
    
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
        
        const result = await html5QrCode.scanFile(file, false);
        handleScan(result);
    } catch (e) {
        console.error("QR Scan Error:", e);
        showScanError("Could not read QR code from image. Please try a clearer or brighter photo.", e);
        
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
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
