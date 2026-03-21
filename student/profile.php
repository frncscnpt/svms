<?php
/**
 * SVMS - Student: Profile
 */
$pageTitle = 'My Profile';

require_once __DIR__ . '/../includes/layout.php';

$breadcrumbs = ['Dashboard' => BASE_PATH.'/student/index.php', 'My Profile' => null];

if (IS_MOBILE) {
    require_once __DIR__ . '/../includes/mobile_header.php';
} else {
    require_once __DIR__ . '/../includes/header.php';
}

requireRole('student');

$pdo = getDBConnection();
$studentId = $_SESSION['student_id'];

$student = $pdo->prepare("SELECT * FROM students WHERE id=?");
$student->execute([$studentId]);
$student = $student->fetch();

$qr = $pdo->prepare("SELECT qr_data FROM qr_codes WHERE student_id=?");
$qr->execute([$studentId]);
$qrData = $qr->fetchColumn();

$violationCount = $pdo->prepare("SELECT COUNT(*) FROM violations WHERE student_id=?");
$violationCount->execute([$studentId]);
$violationCount = $violationCount->fetchColumn();

$profileFields = [
    'Grade & Section'  => sanitize($student['grade_level'].' - '.$student['section']),
    'Gender'           => sanitize($student['gender'] ?? '—'),
    'Contact'          => sanitize($student['contact'] ?? 'N/A'),
    'Email'            => sanitize($student['email'] ?? 'N/A'),
    'Guardian'         => sanitize($student['guardian_name'] ?? 'N/A'),
    'Guardian Contact' => sanitize($student['guardian_contact'] ?? 'N/A'),
];

if (IS_MOBILE):
?>

<!-- Profile Header -->
<div class="m-profile-header">
    <label for="avatarUpload" style="cursor:pointer;display:inline-block;position:relative;">
        <?= getAvatarHtml($student['photo'], $student['first_name'].' '.$student['last_name'], 'm-profile-avatar') ?>
        <div style="position:absolute;bottom:0;right:50%;transform:translateX(28px);background:#fff;border:2px solid #2e1731;border-radius:50%;width:26px;height:26px;display:flex;align-items:center;justify-content:center;font-size:12px;color:#2e1731;">
            <i class="bi bi-camera-fill"></i>
        </div>
    </label>
    <input type="file" id="avatarUpload" accept="image/png,image/jpeg,image/webp" style="display:none;">
    <div style="font-size:20px;font-weight:700;margin-top:10px;"><?= sanitize($student['first_name'].' '.$student['last_name']) ?></div>
    <div style="font-size:13px;color:rgba(255,255,255,0.6);margin-top:2px;"><?= sanitize($student['student_number']) ?></div>
    <div style="margin-top:16px;display:flex;justify-content:center;gap:32px;">
        <div style="text-align:center;">
            <div style="font-size:26px;font-weight:800;color:<?= $violationCount > 0 ? '#fca5a5' : '#86efac' ?>;"><?= $violationCount ?></div>
            <div style="font-size:10px;color:rgba(255,255,255,0.5);font-weight:600;text-transform:uppercase;letter-spacing:0.06em;">Violations</div>
        </div>
    </div>
</div>

<!-- Personal Info -->
<div class="m-section">
    <div class="m-section-header">
        <span class="m-section-title">Personal Information</span>
    </div>
    <div class="m-card">
        <?php foreach ($profileFields as $label => $val): ?>
        <div class="m-detail-row">
            <span class="m-detail-label"><?= $label ?></span>
            <span class="m-detail-value"><?= $val ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- QR Code -->
<?php if ($qrData): ?>
<div class="m-section">
    <div class="m-section-header">
        <span class="m-section-title">My QR Code</span>
    </div>
    <div class="m-card">
        <div class="m-card-body text-center">
            <div id="studentQR" style="display:inline-block;"></div>
            <p style="font-size:12px;color:#7e747c;margin-top:10px;">Show this to your teacher for quick identification</p>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Sign Out -->
<div class="m-section">
    <button onclick="confirmLogout(event)" class="m-card d-flex align-items-center gap-3 w-100 text-decoration-none border-0" style="padding:16px 18px;color:#dc2626;background:#fff;cursor:pointer;">
        <i class="bi bi-box-arrow-right" style="font-size:20px;"></i>
        <span style="font-size:14px;font-weight:700;">Sign Out</span>
    </button>
</div>

<!-- Logout Modal -->
<div id="logoutModal" style="display:none;position:fixed;inset:0;background:rgba(19,1,23,0.45);backdrop-filter:blur(4px);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:20px;padding:28px 24px;max-width:320px;width:88%;box-shadow:0 20px 60px rgba(19,1,23,0.18);text-align:center;">
        <div style="width:52px;height:52px;background:rgba(220,38,38,0.08);border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
            <i class="bi bi-box-arrow-left" style="font-size:22px;color:#dc2626;"></i>
        </div>
        <h5 style="font-size:18px;font-weight:700;color:#130117;margin-bottom:8px;">Log out?</h5>
        <p style="font-size:13px;color:#7e747c;margin-bottom:22px;line-height:1.5;">You'll be signed out of your session.</p>
        <div style="display:flex;gap:10px;">
            <button id="logoutCancel" style="flex:1;padding:11px;border:1.5px solid #ede9ee;border-radius:12px;background:#fff;font-family:'Chillax','Inter',sans-serif;font-size:14px;font-weight:600;color:#4c444b;cursor:pointer;">Cancel</button>
            <a href="<?= BASE_PATH ?>/api/logout.php" style="flex:1;padding:11px;border:none;border-radius:12px;background:#dc2626;font-family:'Chillax','Inter',sans-serif;font-size:14px;font-weight:600;color:#fff;cursor:pointer;text-decoration:none;display:flex;align-items:center;justify-content:center;">Log out</a>
        </div>
    </div>
</div>

<?php
$extraJS = '';
if ($qrData) {
    $extraJS .= '<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script>new QRCode(document.getElementById("studentQR"),{text:"'.$qrData.'",width:160,height:160,colorDark:"#2e1731",colorLight:"#ffffff"});</script>';
}
$extraJS .= '
<script>
document.getElementById("avatarUpload").addEventListener("change", function() {
    if (!this.files || !this.files[0]) return;
    const formData = new FormData();
    formData.append("avatar", this.files[0]);
    fetch("'.BASE_PATH.'/api/upload_avatar.php", { method:"POST", body:formData })
        .then(r => r.json())
        .then(data => { if (data.success) window.location.reload(); else alert(data.message || "Upload failed"); });
});
function confirmLogout(e) {
    e.preventDefault();
    document.getElementById("logoutModal").style.display = "flex";
}
document.getElementById("logoutCancel").addEventListener("click", function() {
    document.getElementById("logoutModal").style.display = "none";
});
document.getElementById("logoutModal").addEventListener("click", function(e) {
    if (e.target === this) this.style.display = "none";
});
</script>';
require_once __DIR__ . '/../includes/mobile_footer.php';

else: // DESKTOP
?>

<div class="row g-4">
    <!-- Left: Avatar + Stats -->
    <div class="col-lg-4">
        <div class="card-panel">
            <div class="panel-body text-center" style="padding:32px 24px;">
                <label for="avatarUpload" style="cursor:pointer;display:inline-block;position:relative;">
                    <?= getAvatarHtml($student['photo'], $student['first_name'].' '.$student['last_name'], 'user-avatar', 'width:80px;height:80px;font-size:28px;margin:0 auto 12px;') ?>
                    <div style="position:absolute;bottom:12px;right:-4px;background:#fff;border:2px solid var(--primary);border-radius:50%;width:26px;height:26px;display:flex;align-items:center;justify-content:center;font-size:12px;color:var(--primary);">
                        <i class="bi bi-camera-fill"></i>
                    </div>
                </label>
                <input type="file" id="avatarUpload" accept="image/png,image/jpeg,image/webp" style="display:none;">
                <div style="font-weight:700;font-size:18px;color:var(--primary);margin-top:4px;"><?= sanitize($student['first_name'].' '.$student['last_name']) ?></div>
                <div style="font-size:13px;color:var(--text-muted);"><?= sanitize($student['student_number']) ?></div>
                <div class="row g-2 mt-3 pt-3" style="border-top:1px solid #f0eff4;">
                    <div class="col-12 text-center">
                        <div style="font-size:28px;font-weight:700;color:<?= $violationCount > 0 ? 'var(--danger)' : 'var(--success)' ?>;"><?= $violationCount ?></div>
                        <div style="font-size:10px;color:var(--text-muted);font-weight:600;text-transform:uppercase;">Total Violations</div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($qrData): ?>
        <div class="card-panel mt-3">
            <div class="panel-header"><h5 class="panel-title"><i class="bi bi-qr-code"></i> My QR Code</h5></div>
            <div class="panel-body text-center">
                <div id="studentQR" style="display:inline-block;"></div>
                <p style="font-size:12px;color:var(--text-muted);margin-top:10px;">Show this to your teacher for quick identification</p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Right: Info -->
    <div class="col-lg-8">
        <div class="card-panel">
            <div class="panel-header"><h5 class="panel-title"><i class="bi bi-person-fill"></i> Personal Information</h5></div>
            <div class="panel-body">
                <div class="row g-3">
                    <?php foreach ($profileFields as $label => $val): ?>
                    <div class="col-sm-6">
                        <div style="font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.6px;margin-bottom:4px;"><?= $label ?></div>
                        <div style="font-size:14px;font-weight:500;color:var(--text-primary);"><?= $val ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$extraJS = '';
if ($qrData) {
    $extraJS .= '<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script>new QRCode(document.getElementById("studentQR"),{text:"'.$qrData.'",width:160,height:160,colorDark:"#2e1731",colorLight:"#ffffff"});</script>';
}
$extraJS .= '
<script>
document.getElementById("avatarUpload").addEventListener("change", function() {
    if (!this.files || !this.files[0]) return;
    const formData = new FormData();
    formData.append("avatar", this.files[0]);
    fetch("'.BASE_PATH.'/api/upload_avatar.php", { method:"POST", body:formData })
        .then(r => r.json())
        .then(data => { if (data.success) window.location.reload(); else alert(data.message || "Upload failed"); });
});
</script>';
require_once __DIR__ . '/../includes/footer.php';
endif; ?>
