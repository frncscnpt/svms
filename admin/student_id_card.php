<?php
/**
 * SVMS - Student ID Card Generator
 * Formal printable student ID card with QR code and photo
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole(['admin', 'discipline_officer']);

$pdo = getDBConnection();

$ids = $_GET['ids'] ?? '';
$studentIds = array_filter(explode(',', $ids), 'is_numeric');

if (empty($studentIds)) {
    die('At least one Student ID is required.');
}

// Fetch students with their QR codes
$placeholders = implode(',', array_fill(0, count($studentIds), '?'));
$stmt = $pdo->prepare("
    SELECT s.*, q.qr_data 
    FROM students s 
    LEFT JOIN qr_codes q ON s.id = q.student_id 
    WHERE s.id IN ($placeholders)
    ORDER BY s.last_name, s.first_name
");
$stmt->execute($studentIds);
$students = $stmt->fetchAll();

if (empty($students)) {
    die('No valid students found.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student ID Cards - SVMS</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap');

        :root {
            --id-purple: #2e1731;
            --id-accent: #f2e7f5;
            --id-text: #130117;
            --id-width: 85.6mm;
            --id-height: 54mm;
        }

        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 20px;
            background: #f0f2f5;
            color: var(--id-text);
        }

        .no-print {
            text-align: center;
            margin-bottom: 30px;
        }

        .btn-print {
            background: var(--id-purple);
            color: #fff;
            border: none;
            padding: 12px 24px;
            font-size: 16px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-decoration: none;
            display: inline-block;
        }

        /* ID Card Layout */
        .id-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
        }

        .id-card {
            width: var(--id-width);
            height: var(--id-height);
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            position: relative;
            display: flex;
            border: 1px solid #ddd;
            page-break-inside: avoid;
        }

        /* Front Side */
        .id-front {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            background: linear-gradient(135deg, #fff 0%, #fcf9fd 100%);
        }

        .id-header {
            background: var(--id-purple);
            height: 40px;
            display: flex;
            align-items: center;
            padding: 0 15px;
            color: #fff;
        }

        .id-header img {
            height: 28px;
            width: auto;
            margin-right: 10px;
        }

        .id-header h1 {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            margin: 0;
            letter-spacing: 0.5px;
        }

        .id-content {
            flex: 1;
            display: flex;
            padding: 15px;
            gap: 15px;
            align-items: center;
        }

        .id-photo-col {
            width: 85px;
            text-align: center;
        }

        .id-photo-frame {
            width: 80px;
            height: 80px;
            border: 3px solid var(--id-purple);
            border-radius: 50%;
            overflow: hidden;
            background: #f7f2f8;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .id-photo-frame img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .id-info-col {
            flex: 1;
        }

        .id-name {
            font-size: 16px;
            font-weight: 800;
            margin-bottom: 2px;
            color: var(--id-text);
            line-height: 1.2;
        }

        .id-student-no {
            font-size: 12px;
            font-weight: 600;
            color: var(--id-purple);
            margin-bottom: 8px;
            font-family: monospace;
        }

        .id-detail {
            font-size: 10px;
            color: #666;
            margin-bottom: 2px;
        }
        .id-detail strong {
            color: var(--id-text);
        }

        .id-qr-col {
            width: 70px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .id-qr-box {
            background: #fff;
            padding: 5px;
            border: 1px solid #eee;
            border-radius: 6px;
        }

        .id-footer-strip {
            height: 8px;
            background: var(--id-purple);
            width: 100%;
            display: flex;
        }
        .id-footer-strip div {
            flex: 1;
        }
        .id-footer-strip .accent-1 { background: #6b21a8; opacity: 0.8; }
        .id-footer-strip .accent-2 { background: #9333ea; opacity: 0.6; }

        @media print {
            body { 
                background: #fff; 
                padding: 0; 
            }
            .no-print { 
                display: none; 
            }
            .id-container {
                gap: 5mm;
                justify-content: flex-start;
                padding: 10mm;
            }
            .id-card {
                box-shadow: none;
                /* border: 1px solid #000; */
            }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button class="btn-print" onclick="window.print()"><i class="bi bi-printer"></i> Print ID Cards</button>
    <a href="<?= BASE_PATH ?>/admin/students.php" style="margin-left:15px; color:#666; text-decoration:none;">Back to Students</a>
</div>

<div class="id-container">
    <?php foreach ($students as $s): 
        $fullName = $s['first_name'] . ' ' . $s['last_name'];
    ?>
    <div class="id-card">
        <div class="id-front">
            <!-- Header -->
            <div class="id-header">
                <img src="<?= BASE_PATH ?>/assets/img/logo.png" alt="Logo">
                <h1>Lyceum of Subic Bay</h1>
            </div>
            
            <!-- Content -->
            <div class="id-content">
                <!-- Photo -->
                <div class="id-photo-col">
                    <div class="id-photo-frame">
                        <?php if ($s['photo']): 
                            $photoSrc = (strpos($s['photo'], 'http') === 0) ? $s['photo'] : rtrim(BASE_PATH, '/') . '/' . ltrim($s['photo'], '/');
                        ?>
                            <img src="<?= $photoSrc ?>" alt="<?= htmlspecialchars($fullName) ?>">
                        <?php else: ?>
                            <span style="font-size:32px; font-weight:700; color:var(--id-purple); opacity:0.3;">
                                <?= substr($s['first_name'], 0, 1) . substr($s['last_name'], 0, 1) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div style="font-size:8px; font-weight:700; color:var(--id-purple); text-transform:uppercase;">Student</div>
                </div>

                <!-- Info -->
                <div class="id-info-col">
                    <div class="id-name"><?= htmlspecialchars($fullName) ?></div>
                    <div class="id-student-no"><?= htmlspecialchars($s['student_number']) ?></div>
                    
                    <div class="id-detail">Level: <strong><?= htmlspecialchars($s['grade_level']) ?></strong></div>
                    <div class="id-detail">Section: <strong><?= htmlspecialchars($s['section']) ?></strong></div>
                    <div class="id-detail">Gender: <strong><?= htmlspecialchars($s['gender'] ?? 'N/A') ?></strong></div>
                </div>

                <!-- QR -->
                <div class="id-qr-col">
                    <div class="id-qr-box" id="qr-<?= $s['id'] ?>"></div>
                    <div style="font-size:7px; margin-top:4px; font-weight:bold; color:#888;">SVMS Scannable</div>
                </div>
            </div>

            <!-- Footer Strip -->
            <div class="id-footer-strip">
                <div class="accent-1"></div>
                <div class="accent-2"></div>
                <div style="background:var(--id-purple)"></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
    <?php foreach ($students as $s): ?>
    new QRCode(document.getElementById("qr-<?= $s['id'] ?>"), {
        text: <?= json_encode($s['qr_data'] ?? 'N/A') ?>,
        width: 60,
        height: 60,
        colorDark: "#2e1731",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.M
    });
    <?php endforeach; ?>
</script>

</body>
</html>
