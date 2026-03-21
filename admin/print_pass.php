<?php
/**
 * SVMS - Print Uniform Pass (Admin)
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pdo = getDBConnection();
$passId = $_GET['id'] ?? null;

if (!$passId) {
    die('Pass ID is required.');
}

$stmt = $pdo->prepare("
    SELECT up.*, s.first_name, s.last_name, s.student_number, s.grade_level, s.section,
           u.full_name AS issued_by_name
    FROM uniform_passes up
    JOIN students s ON up.student_id = s.id
    JOIN users u ON up.issued_by = u.id
    WHERE up.id = ?
");
$stmt->execute([$passId]);
$pass = $stmt->fetch();

if (!$pass) {
    die('Pass not found.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Pass - <?= sanitize($pass['pass_code']) ?></title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; margin: 0; padding: 20px; color: #130117; background-color: #f7f2f8; }
        .pass-wrapper { background: #fff; border: 2px solid #2e1731; padding: 30px; width: 400px; margin: 0 auto; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .header { border-bottom: 2px solid #2e1731; padding-bottom: 15px; margin-bottom: 20px; text-align: center; }
        .school-logo { width: 55px; height: auto; margin-bottom: 8px; }
        .school-name { font-size: 14px; font-weight: bold; text-transform: uppercase; margin: 0 0 5px 0; color: #4c444b; }
        .title { font-size: 22px; font-weight: 900; margin: 0; color: #2e1731; letter-spacing: -0.5px; }
        .details { margin-bottom: 25px; }
        .details-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed #ede9ee; font-size: 15px; }
        .details-row:last-child { border-bottom: none; }
        .label { font-weight: 600; color: #7e747c; }
        .value { font-weight: bold; text-align: right; }
        .qr-section { text-align: center; margin: 25px 0; }
        .qr-code { padding: 15px; border: 2px solid #ede9ee; display: inline-block; border-radius: 12px; }
        .code-display { font-family: monospace; font-size: 18px; font-weight: bold; letter-spacing: 1px; margin-top: 10px; color: #2e1731; }
        .footer { font-size: 12px; border-top: 2px solid #2e1731; padding-top: 15px; color: #4c444b; display: flex; flex-direction: column; gap: 5px; }
        
        .no-print { text-align: center; margin-bottom: 30px; }
        .btn-print { background: #2e1731; color: #fff; border: none; padding: 12px 24px; font-size: 16px; border-radius: 8px; cursor: pointer; font-weight: bold; transition: all 0.2s; }
        .btn-print:hover { background: #130117; transform: translateY(-1px); }
        .btn-back { background: #fff; color: #2e1731; border: 1px solid #ede9ee; padding: 12px 24px; font-size: 16px; border-radius: 8px; cursor: pointer; font-weight: bold; text-decoration: none; display: inline-block; margin-right: 10px; }
        
        @media print {
            @page {
                margin: 0;
                size: 80mm auto;
            }
            body { padding: 0; background: #fff; color: #000; width: 76mm; margin: 0 auto; }
            .pass-wrapper { width: 100%; border: auto; padding: 4mm; box-shadow: none; margin: 0; border-radius: 0; box-sizing: border-box; }
            .school-logo { width: 40px; margin-bottom: 5px; }
            .title { font-size: 16px; letter-spacing: 0; }
            .school-name { font-size: 11px; }
            .details-row { flex-direction: column; font-size: 12px; border-bottom: 1px dashed #ccc; padding: 4px 0; }
            .value { text-align: left; margin-top: 2px; font-weight: normal; }
            .qr-code { padding: 5px; border: 1px solid #000; }
            .qr-code canvas, .qr-code img { width: 120px !important; height: 120px !important; margin: 0 auto; }
            .code-display { font-size: 14px; }
            .footer { font-size: 10px; border-top: 1px dashed #000; padding-top: 8px; margin-top: 10px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <a href="<?= BASE_PATH ?>/admin/uniform_passes.php" class="btn-back">Back</a>
    <button class="btn-print" onclick="window.print()">Print Pass</button>
</div>

<div class="pass-wrapper">
    <div class="header">
        <img src="<?= BASE_PATH ?>/assets/img/logo.png" alt="Logo" class="school-logo">
        <h2 class="school-name"><?= SCHOOL_NAME ?></h2>
        <h1 class="title">TEMPORARY UNIFORM PASS</h1>
    </div>
    
    <div class="details">
        <div class="details-row"><span class="label">Student:</span><span class="value"><?= sanitize($pass['first_name'].' '.$pass['last_name']) ?></span></div>
        <div class="details-row"><span class="label">ID Number:</span><span class="value"><?= sanitize($pass['student_number']) ?></span></div>
        <div class="details-row"><span class="label">Grade & Section:</span><span class="value"><?= sanitize($pass['grade_level'].' - '.$pass['section']) ?></span></div>
        <div class="details-row"><span class="label">Reason:</span><span class="value" style="max-width:60%;"><?= sanitize($pass['reason']) ?></span></div>
        <div class="details-row"><span class="label" style="color:#059669;">Valid Date:</span><span class="value" style="color:#059669;"><?= formatDate($pass['valid_date']) ?></span></div>
    </div>
    
    <div class="qr-section">
        <div class="qr-code" id="qrcode"></div>
        <div class="code-display"><?= sanitize($pass['pass_code']) ?></div>
    </div>
    
    <div class="footer">
        <div><strong>Issued By:</strong> <?= sanitize($pass['issued_by_name']) ?></div>
        <div><strong>Issued On:</strong> <?= formatDateTime($pass['created_at']) ?></div>
        <?php if ($pass['status'] === 'revoked' || ($pass['status'] === 'expired' && $pass['valid_date'] < date('Y-m-d'))): ?>
        <div style="color:red; font-weight:bold; margin-top:5px; text-transform:uppercase;">
            STATUS: <?= $pass['status'] === 'revoked' ? 'REVOKED' : 'EXPIRED' ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
    new QRCode(document.getElementById("qrcode"), {
        text: <?= json_encode($pass['pass_code']) ?>,
        width: 140,
        height: 140,
        colorDark: "#130117",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });
</script>
</body>
</html>
