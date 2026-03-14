<?php
/**
 * SVMS - Teacher: Profile
 */
$pageTitle = 'Account';
$pageSubtitle = 'Your Profile';
require_once __DIR__ . '/../includes/mobile_header.php';
requireRole('teacher');

$pdo = getDBConnection();

// Report stats
$totalReports = $pdo->prepare("SELECT COUNT(*) FROM violations WHERE reported_by=?");
$totalReports->execute([$_SESSION['user_id']]);
$totalReports = $totalReports->fetchColumn();

$resolvedReports = $pdo->prepare("SELECT COUNT(*) FROM violations WHERE reported_by=? AND status='resolved'");
$resolvedReports->execute([$_SESSION['user_id']]);
$resolvedReports = $resolvedReports->fetchColumn();

$pendingReports = $pdo->prepare("SELECT COUNT(*) FROM violations WHERE reported_by=? AND status='pending'");
$pendingReports->execute([$_SESSION['user_id']]);
$pendingReports = $pendingReports->fetchColumn();
?>

<div class="mobile-profile-header">
    <label for="avatarUpload" style="cursor: pointer; display: block; position: relative;">
        <?= getAvatarHtml($currentUser['avatar'], $currentUser['full_name'], 'mobile-profile-avatar') ?>
        <div class="edit-badge" style="position: absolute; bottom: 0px; right: 50%; transform: translateX(30px); background: var(--bg-card); color: var(--text-primary); width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; box-shadow: var(--shadow-sm); border: 2px solid var(--primary);">
            <i class="bi bi-camera-fill"></i>
        </div>
    </label>
    <input type="file" id="avatarUpload" accept="image/png, image/jpeg, image/webp" style="display: none;">
    <div style="font-size:18px;font-weight:600;margin-top:8px;"><?= sanitize($currentUser['full_name']) ?></div>
    <div style="font-size:12px;color:rgba(255,255,255,0.6);margin-top:2px;"><?= ucwords(str_replace('_', ' ', $currentUser['role'])) ?></div>
</div>

<!-- Account Info -->
<div class="mobile-card mb-3">
    <div class="card-header"><h6><i class="bi bi-person me-2"></i>Account Information</h6></div>
    <div class="violation-detail-mobile" style="box-shadow:none;margin:0;border:none;">
        <div class="detail-row"><span class="detail-label">Full Name</span><span class="detail-value"><?= sanitize($currentUser['full_name']) ?></span></div>
        <div class="detail-row"><span class="detail-label">Username</span><span class="detail-value"><?= sanitize($currentUser['username']) ?></span></div>
        <div class="detail-row"><span class="detail-label">Email</span><span class="detail-value"><?= sanitize($currentUser['email'] ?? 'N/A') ?></span></div>
        <div class="detail-row"><span class="detail-label">Role</span><span class="detail-value"><?= ucwords(str_replace('_', ' ', $currentUser['role'])) ?></span></div>
    </div>
</div>

<!-- Report Stats -->
<div class="mobile-card mb-3">
    <div class="card-header"><h6><i class="bi bi-bar-chart me-2"></i>Report Summary</h6></div>
    <div class="card-body">
        <div class="d-flex justify-content-around text-center">
            <div>
                <div style="font-size:28px;font-weight:700;color:var(--text-primary);"><?= $totalReports ?></div>
                <div style="font-size:10px;color:var(--text-muted);font-weight:600;text-transform:uppercase;">Total</div>
            </div>
            <div>
                <div style="font-size:28px;font-weight:700;color:var(--warning);"><?= $pendingReports ?></div>
                <div style="font-size:10px;color:var(--text-muted);font-weight:600;text-transform:uppercase;">Pending</div>
            </div>
            <div>
                <div style="font-size:28px;font-weight:700;color:var(--success);"><?= $resolvedReports ?></div>
                <div style="font-size:10px;color:var(--text-muted);font-weight:600;text-transform:uppercase;">Resolved</div>
            </div>
        </div>
    </div>
</div>

<!-- Logout -->
<a href="<?= BASE_PATH ?>/api/logout.php" class="mobile-card mb-3 d-flex align-items-center gap-3 text-decoration-none" style="padding:16px 18px;color:var(--danger);">
    <i class="bi bi-box-arrow-right" style="font-size:20px;"></i>
    <span style="font-size:14px;font-weight:600;">Sign Out</span>
</a>

<?php require_once __DIR__ . '/../includes/mobile_footer.php'; ?>

<script>
document.getElementById("avatarUpload").addEventListener("change", function(e) {
    if (!this.files || !this.files[0]) return;
    
    const formData = new FormData();
    formData.append("avatar", this.files[0]);
    
    const avatarContainer = document.querySelector(".mobile-profile-avatar");
    avatarContainer.style.opacity = "0.5";
    
    fetch("<?= BASE_PATH ?>/api/upload_avatar.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
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
</script>
