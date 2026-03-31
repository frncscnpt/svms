<?php
/**
 * SVMS - Student: Uniform Pass
 */
$pageTitle = 'Uniform Pass';

require_once __DIR__ . '/../includes/layout.php';

$breadcrumbs = ['Dashboard' => BASE_PATH.'/student/index.php', 'Uniform Pass' => null];

require_once __DIR__ . '/../includes/header.php';

requireRole('student');

$pdo = getDBConnection();
$studentId = $_SESSION['student_id'];

if (!$studentId) {
    setFlash('danger', 'Student profile not linked. Please contact admin.');
    header('Location: ' . BASE_PATH . '/index.php');
    exit;
}

// Auto-expire old passes
$pdo->prepare("UPDATE uniform_passes SET status = 'expired' WHERE status = 'active' AND valid_date < ?")->execute([date('Y-m-d')]);

// Get active pass for today
$stmt = $pdo->prepare("
    SELECT up.*, u.full_name AS issued_by_name
    FROM uniform_passes up
    JOIN users u ON up.issued_by = u.id
    WHERE up.student_id = ? AND up.status = 'active' AND up.valid_date >= ?
    ORDER BY up.created_at DESC LIMIT 1
");
$stmt->execute([$studentId, date('Y-m-d')]);
$activePass = $stmt->fetch();

// Get pass history (exclude today's active pass — already shown above)
$history = $pdo->prepare("
    SELECT up.*, u.full_name AS issued_by_name
    FROM uniform_passes up
    JOIN users u ON up.issued_by = u.id
    WHERE up.student_id = ?
      AND NOT (up.status = 'active' AND up.valid_date >= ?)
    ORDER BY up.created_at DESC LIMIT 10
");
$phToday = date('Y-m-d');
$history->execute([$studentId, $phToday]);
$passHistory = $history->fetchAll();

?>

<?php if ($activePass): ?>
<!-- Active Pass Card -->
<div class="m-section">
    <div class="m-section-header">
        <span class="m-section-title">Active Pass</span>
        <span class="badge badge-soft-success">Valid Today</span>
    </div>
    
    <div class="m-card" style="overflow:hidden;">
        <!-- Pass Header -->
        <div style="background:linear-gradient(135deg, #065f46, #059669); padding:24px; text-align:center; color:white;">
            <i class="bi bi-check-circle-fill" style="font-size:36px; display:block; margin-bottom:8px;"></i>
            <h5 style="color:white; margin:0; font-size:18px; font-weight:700;">Temporary Uniform Pass</h5>
            <small style="color:rgba(255,255,255,0.7);">Lyceum of Subic Bay</small>
        </div>
        
        <!-- QR Code -->
        <div style="padding:24px; text-align:center; background:#f8fdf8;">
            <div id="passQRCode" style="display:inline-block; padding:12px; background:white; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.08);"></div>
            <p style="font-size:11px; color:#7e747c; margin-top:10px; margin-bottom:0;">Show this QR code to your teacher for verification</p>
        </div>
        
        <!-- Pass Details -->
        <div style="padding:0;">
            <div class="m-detail-row"><span class="m-detail-label">Pass Code</span><strong style="font-size:11px;font-family:monospace;text-align:right;"><?= sanitize($activePass['pass_code']) ?></strong></div>
            <div class="m-detail-row"><span class="m-detail-label">Reason</span><strong style="text-align:right;max-width:55%;"><?= sanitize($activePass['reason']) ?></strong></div>
            <div class="m-detail-row"><span class="m-detail-label">Valid Date</span><strong><?= formatDate($activePass['valid_date']) ?></strong></div>
            <div class="m-detail-row"><span class="m-detail-label">Issued By</span><strong><?= sanitize($activePass['issued_by_name']) ?></strong></div>
            <div class="m-detail-row"><span class="m-detail-label">Issued At</span><strong><?= formatDateTime($activePass['created_at']) ?></strong></div>
        </div>
    </div>
</div>
<?php else: ?>
<!-- No Active Pass -->
<div class="m-section">
    <div class="m-card">
        <div class="m-empty">
            <i class="bi bi-card-checklist" style="color:#ede9ee;"></i>
            <h5>No Active Pass</h5>
            <p>You don't have an active uniform pass for today. If you need one, please visit the discipline officer's office.</p>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Pass History -->
<?php if (!empty($passHistory)): ?>
<div class="m-section">
    <div class="m-section-header">
        <span class="m-section-title">Pass History</span>
    </div>
    <div class="m-card">
        <?php foreach ($passHistory as $ph): ?>
        <?php
            $phStatus = $ph['status'];
            if ($phStatus === 'active' && $ph['valid_date'] < date('Y-m-d')) $phStatus = 'expired';
            $iconClass = $phStatus === 'active' ? 'bi-check-circle-fill' : ($phStatus === 'revoked' ? 'bi-x-circle-fill' : 'bi-clock-fill');
            $iconColor = $phStatus === 'active' ? '#059669' : ($phStatus === 'revoked' ? '#dc2626' : '#9ca3af');
        ?>
        <div class="m-list-item">
            <div style="width:36px;height:36px;border-radius:10px;background:<?= $phStatus === 'active' ? '#ecfdf5' : ($phStatus === 'revoked' ? '#fef2f2' : '#f3f4f6') ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="bi <?= $iconClass ?>" style="font-size:16px;color:<?= $iconColor ?>;"></i>
            </div>
            <div class="m-list-content">
                <div class="m-list-title"><?= sanitize($ph['reason']) ?></div>
                <div class="m-list-sub"><?= formatDate($ph['valid_date']) ?> · <?= sanitize($ph['issued_by_name']) ?></div>
            </div>
            <span class="badge <?= $phStatus === 'active' ? 'badge-soft-success' : ($phStatus === 'revoked' ? 'badge-soft-danger' : 'badge-soft-secondary') ?>"><?= ucfirst($phStatus) ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php
$extraJS = '';
if ($activePass) {
    $extraJS = '<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script>
    new QRCode(document.getElementById("passQRCode"), {
        text: ' . json_encode($activePass['pass_code']) . ',
        width: 200,
        height: 200,
        colorDark: "#000000",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });
    </script>';
}
require_once __DIR__ . '/../includes/footer.php';
?>
