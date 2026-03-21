<?php
/**
 * SVMS - Settings / Account
 */
$pageTitle = 'My Profile';
$breadcrumbs = ['My Profile' => null];
require_once __DIR__ . '/includes/header.php';
requireLogin();

$pdo = getDBConnection();

// Handle avatar upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // Change password
    if ($_POST['action'] === 'change_password') {
        $current  = $_POST['current_password'] ?? '';
        $new      = $_POST['new_password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        $stmt = $pdo->prepare("SELECT password FROM users WHERE id=?");
        $stmt->execute([$_SESSION['user_id']]);
        $hash = $stmt->fetchColumn();

        if (!password_verify($current, $hash)) {
            setFlash('danger', 'Current password is incorrect.');
        } elseif (strlen($new) < 6) {
            setFlash('danger', 'New password must be at least 6 characters.');
        } elseif ($new !== $confirm) {
            setFlash('danger', 'Passwords do not match.');
        } else {
            $pdo->prepare("UPDATE users SET password=? WHERE id=?")
                ->execute([password_hash($new, PASSWORD_DEFAULT), $_SESSION['user_id']]);
            setFlash('success', 'Password updated successfully.');
        }
        header('Location: ' . BASE_PATH . '/settings.php');
        exit;
    }
}
?>

<div class="row g-4">

    <!-- Left: Profile card -->
    <div class="col-lg-4">
        <div class="card-panel">
            <div class="panel-body text-center" style="padding:32px 24px;">
                <label for="avatarUpload" style="cursor:pointer;display:inline-block;position:relative;">
                    <?= getAvatarHtml($currentUser['avatar'], $currentUser['full_name'], 'user-avatar', 'width:80px;height:80px;font-size:28px;margin:0 auto 12px;') ?>
                    <div style="position:absolute;bottom:12px;right:-4px;background:#fff;border:2px solid var(--primary);border-radius:50%;width:26px;height:26px;display:flex;align-items:center;justify-content:center;font-size:12px;color:var(--primary);">
                        <i class="bi bi-camera-fill"></i>
                    </div>
                </label>
                <input type="file" id="avatarUpload" accept="image/png,image/jpeg,image/webp" style="display:none;">
                <div style="font-weight:700;font-size:18px;color:var(--primary);margin-top:4px;"><?= sanitize($currentUser['full_name']) ?></div>
                <div style="font-size:13px;color:var(--text-muted);"><?= ucwords(str_replace('_', ' ', $currentUser['role'])) ?></div>
                <div style="font-size:12px;color:var(--text-muted);margin-top:4px;"><?= sanitize($currentUser['email'] ?? '') ?></div>
            </div>
        </div>
    </div>

    <!-- Right: Settings panels -->
    <div class="col-lg-8">

        <!-- Account Info -->
        <div class="card-panel mb-4">
            <div class="panel-header">
                <h5 class="panel-title"><i class="bi bi-person-fill"></i> Account Information</h5>
            </div>
            <div class="panel-body">
                <div class="row g-3">
                    <?php $fields = [
                        'Full Name' => sanitize($currentUser['full_name']),
                        'Username'  => sanitize($currentUser['username']),
                        'Email'     => sanitize($currentUser['email'] ?? '—'),
                        'Role'      => ucwords(str_replace('_', ' ', $currentUser['role'])),
                    ];
                    foreach ($fields as $label => $val): ?>
                    <div class="col-sm-6">
                        <div style="font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.6px;margin-bottom:4px;"><?= $label ?></div>
                        <div style="font-size:14px;font-weight:500;color:var(--text-primary);"><?= $val ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Change Password -->
        <div class="card-panel">
            <div class="panel-header">
                <h5 class="panel-title"><i class="bi bi-shield-lock-fill"></i> Change Password</h5>
            </div>
            <div class="panel-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="change_password">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" style="font-size:13px;font-weight:600;color:var(--text-muted);">Current Password</label>
                            <input type="password" name="current_password" class="form-control" required placeholder="Enter current password">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" style="font-size:13px;font-weight:600;color:var(--text-muted);">New Password</label>
                            <input type="password" name="new_password" class="form-control" required placeholder="Min. 6 characters">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" style="font-size:13px;font-weight:600;color:var(--text-muted);">Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control" required placeholder="Repeat new password">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn-primary-custom" style="padding:10px 24px;">
                                <i class="bi bi-check-lg"></i> Update Password
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<?php
$extraJS = '
<script>
document.getElementById("avatarUpload").addEventListener("change", function() {
    if (!this.files || !this.files[0]) return;
    const formData = new FormData();
    formData.append("avatar", this.files[0]);
    fetch("' . BASE_PATH . '/api/upload_avatar.php", { method:"POST", body:formData })
        .then(r => r.json())
        .then(data => { if (data.success) window.location.reload(); else alert(data.message || "Upload failed"); });
});
</script>';
require_once __DIR__ . '/includes/footer.php';
?>
