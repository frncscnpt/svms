<?php
/**
 * SVMS - Student: Profile
 */
$pageTitle = 'My Profile';
$pageSubtitle = 'Student Information';
require_once __DIR__ . '/../includes/mobile_header.php';
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
?>

<div class="mobile-profile-header">
    <label for="avatarUpload" style="cursor: pointer; display: block; position: relative;">
        <?= getAvatarHtml($student['photo'], $student['first_name'].' '.$student['last_name'], 'mobile-profile-avatar') ?>
        <div class="edit-badge" style="position: absolute; bottom: 0px; right: 50%; transform: translateX(30px); background: var(--bg-card); color: var(--text-primary); width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; box-shadow: var(--shadow-sm); border: 2px solid var(--primary);">
            <i class="bi bi-camera-fill"></i>
        </div>
    </label>
    <input type="file" id="avatarUpload" accept="image/png, image/jpeg, image/webp" style="display: none;">
    <div style="font-size:20px;font-weight:600;margin-top:8px;"><?= sanitize($student['first_name'].' '.$student['last_name']) ?></div>
    <div style="font-size:13px;color:rgba(255,255,255,0.7);margin-top:2px;"><?= sanitize($student['student_number']) ?></div>
</div>

<!-- Info Card -->
<div class="mobile-card mb-3">
    <div class="card-header"><h6><i class="bi bi-info-circle me-2"></i>Personal Information</h6></div>
    <div class="violation-detail-mobile" style="box-shadow:none;margin:0;">
        <div class="detail-row"><span class="detail-label">Full Name</span><span class="detail-value"><?= sanitize($student['first_name'].' '.($student['middle_name'] ? $student['middle_name'].' ' : '').$student['last_name']) ?></span></div>
        <div class="detail-row"><span class="detail-label">Gender</span><span class="detail-value"><?= sanitize($student['gender'] ?? '-') ?></span></div>
        <div class="detail-row"><span class="detail-label">Grade & Section</span><span class="detail-value"><?= sanitize($student['grade_level'].' - '.$student['section']) ?></span></div>
        <div class="detail-row"><span class="detail-label">Contact</span><span class="detail-value"><?= sanitize($student['contact'] ?? 'N/A') ?></span></div>
        <div class="detail-row"><span class="detail-label">Email</span><span class="detail-value"><?= sanitize($student['email'] ?? 'N/A') ?></span></div>
        <div class="detail-row"><span class="detail-label">Guardian</span><span class="detail-value"><?= sanitize($student['guardian_name'] ?? 'N/A') ?></span></div>
        <div class="detail-row"><span class="detail-label">Guardian Contact</span><span class="detail-value"><?= sanitize($student['guardian_contact'] ?? 'N/A') ?></span></div>
    </div>
</div>

<!-- Stats -->
<div class="mobile-card mb-3">
    <div class="card-header"><h6><i class="bi bi-bar-chart me-2"></i>Record Summary</h6></div>
    <div class="card-body text-center">
        <div class="d-inline-block mx-3">
            <div style="font-family:Poppins;font-size:36px;font-weight:700;color:<?= $violationCount > 0 ? 'var(--danger)' : 'var(--success)' ?>;"><?= $violationCount ?></div>
            <div style="font-size:11px;color:var(--text-muted);font-weight:600;text-transform:uppercase;">Total Violations</div>
        </div>
    </div>
</div>

<!-- QR Code -->
<?php if ($qrData): ?>
<div class="mobile-card mb-3">
    <div class="card-header"><h6><i class="bi bi-qr-code me-2"></i>My QR Code</h6></div>
    <div class="card-body text-center d-flex flex-column align-items-center">
        <div id="studentQR"></div>
        <div style="margin-top:12px;font-size:12px;color:var(--text-muted);">Show this to your teacher for quick identification</div>
    </div>
</div>
<?php endif; ?>

<?php
$extraJS = '';
if ($qrData) {
    $extraJS .= '<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script>new QRCode(document.getElementById("studentQR"),{text:"'.$qrData.'",width:180,height:180,colorDark:"#2e1731",colorLight:"#ffffff"});</script>';
}
$extraJS .= '
<script>
document.getElementById("avatarUpload").addEventListener("change", function(e) {
    if (!this.files || !this.files[0]) return;
    
    const formData = new FormData();
    formData.append("avatar", this.files[0]);
    
    // Show uploading state (optional visual feedback)
    const avatarContainer = document.querySelector(".mobile-profile-avatar");
    avatarContainer.style.opacity = "0.5";
    
    fetch("'.BASE_PATH.'/api/upload_avatar.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload(); // Quickest way to reflect changes everywhere
        } else {
            alert(data.message || "Upload failed");
            avatarContainer.style.opacity = "1";
        }
    })
    .catch(error => {
        console.error("Error:", error);
        alert("An error occurred during upload");
        avatarContainer.style.opacity = "1";
    });
});
</script>';

require_once __DIR__ . '/../includes/mobile_footer.php';
?>
