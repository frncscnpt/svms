<?php
/**
 * SVMS - Admin: Formal Print Report
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole(['admin', 'discipline_officer']);

$pdo = getDBConnection();

$dateFrom = $_GET['from'] ?? date('Y-m-01');
$dateTo = $_GET['to'] ?? date('Y-m-d');

// Fetch the full report data
$export = $pdo->prepare("
    SELECT s.student_number, CONCAT(s.first_name,' ',s.last_name) as name, s.grade_level, s.section, 
           vt.name as violation, vt.severity, v.status, v.date_occurred, u.full_name as reporter 
    FROM violations v 
    JOIN students s ON v.student_id=s.id 
    JOIN violation_types vt ON v.violation_type_id=vt.id 
    JOIN users u ON v.reported_by=u.id 
    WHERE v.date_occurred BETWEEN ? AND ? 
    ORDER BY v.date_occurred DESC
");
$export->execute([$dateFrom, $dateTo . ' 23:59:59']);
$records = $export->fetchAll();

$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Violations Report - SVMS</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Times+New+Roman&display=swap');
        
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background: #525659; /* PDF viewer background color */
        }
        
        /* Letter Page (8.5 x 11 inches) */
        .page {
            width: 215.9mm;
            min-height: 279.4mm;
            padding: 20mm;
            margin: 10mm auto;
            border: 1px #D3D3D3 solid;
            border-radius: 5px;
            background: white;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
            position: relative;
            box-sizing: border-box;
        }

        /* Formal Header */
        .header {
            text-align: center;
            border-bottom: 2px solid #2e1731;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        .school-logo {
            width: 55px;
            height: auto;
            margin-bottom: 8px;
        }
        .header h1 {
            font-family: 'Times New Roman', serif;
            font-size: 24px;
            color: #2e1731;
            margin: 0 0 5px 0;
            text-transform: uppercase;
        }
        .header h2 {
            font-size: 14px;
            color: #555;
            margin: 0 0 15px 0;
            font-weight: normal;
        }
        .header .report-title {
            font-size: 18px;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
        }
        .header .report-period {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        /* Table Styling */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin-bottom: 40px;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px 6px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
            -webkit-print-color-adjust: exact;
            color: #000;
        }
        .text-center { text-align: center; }
        
        .badge-minor { color: #d97706; }
        .badge-major { color: #ea580c; font-weight: bold;}
        .badge-critical { color: #dc2626; font-weight: bold;}

        /* Signature Block */
        .signatures {
            margin-top: 50px;
            width: 100%;
            page-break-inside: avoid;
        }
        .signature-box {
            float: left;
            width: 45%;
        }
        .signature-box.right {
            float: right;
        }
        .signature-line {
            width: 80%;
            border-bottom: 1px solid #000;
            margin-bottom: 5px;
            height: 40px;
        }
        .signature-name {
            font-weight: bold;
            font-size: 12px;
            text-transform: uppercase;
        }
        .signature-title {
            font-size: 11px;
            color: #555;
        }

        /* Clearfix */
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }

        /* Print Specific adjustments */
        @media print {
            body { background: white; margin: 0; }
            .page {
                margin: 0;
                border: initial;
                border-radius: initial;
                width: initial;
                min-height: initial;
                box-shadow: initial;
                background: initial;
                page-break-after: always;
            }
            .no-print { display: none; }
        }
        
        .back-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background: #555;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-decoration: none;
            font-size: 14px;
            z-index: 100;
        }
        .back-btn:hover { background: #666; color: white; }

        /* Loading overlay */
        .loading-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            font-family: 'Arial', sans-serif;
            color: #2e1731;
        }
        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #2e1731;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 15px;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
    <!-- Include html2pdf.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</head>
<body>
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
        <h3>Generating PDF Report...</h3>
        <p>Please wait while your document is being prepared for download.</p>
    </div>

    <a class="back-btn no-print" href="javascript:window.close();">Close Tab</a>
    
    <div class="page">
        <div class="header">
            <img src="<?= BASE_PATH ?>/assets/img/logo.png" alt="Logo" class="school-logo">
            <h1>Lyceum of Subic Bay</h1>
            <h2>Student Violation Management System (SVMS)</h2>
            <div class="report-title">Official Violations Report</div>
            <div class="report-period">
                Period: <?= date('F d, Y', strtotime($dateFrom)) ?> to <?= date('F d, Y', strtotime($dateTo)) ?>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th width="15%">Date</th>
                    <th width="25%">Student</th>
                    <th width="10%">Grade</th>
                    <th width="25%">Violation</th>
                    <th width="10%">Severity</th>
                    <th width="10%">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($records)): ?>
                <tr>
                    <td colspan="7" class="text-center" style="padding: 20px;">No violations found for this period.</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($records as $i => $r): ?>
                    <tr>
                        <td class="text-center"><?= $i + 1 ?></td>
                        <td><?= date('M d, Y h:i A', strtotime($r['date_occurred'])) ?></td>
                        <td>
                            <strong><?= htmlspecialchars($r['name']) ?></strong><br>
                            <span style="font-size:9px; color:#555;"><?= htmlspecialchars($r['student_number']) ?></span>
                        </td>
                        <td class="text-center"><?= htmlspecialchars($r['grade_level']) ?></td>
                        <td><?= htmlspecialchars($r['violation']) ?></td>
                        <td class="text-center badge-<?= htmlspecialchars($r['severity']) ?>"><?= ucfirst(htmlspecialchars($r['severity'])) ?></td>
                        <td class="text-center"><?= ucfirst(htmlspecialchars($r['status'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="signatures clearfix">
            <div class="signature-box">
                <div style="font-size:11px; margin-bottom: 20px;">Generated By:</div>
                <div class="signature-line"></div>
                <div class="signature-name"><?= htmlspecialchars($currentUser['full_name']) ?></div>
                <div class="signature-title"><?= ucwords(str_replace('_', ' ', $currentUser['role'])) ?></div>
                <div class="signature-title" style="margin-top: 5px;">Date: <?= date('F d, Y') ?></div>
            </div>
            
            <div class="signature-box right">
                <div style="font-size:11px; margin-bottom: 20px;">Noted By:</div>
                <div class="signature-line"></div>
                <div class="signature-name">_____________________________</div>
                <div class="signature-title">Head of Discipline/Principal</div>
            </div>
        </div>
    </div>
    
    <script>
        window.onload = function() {
            const page = document.querySelector('.page');
            const overlay = document.getElementById('loadingOverlay');
            
            const filename = `SVMS_Overall_Report_<?= date('Y-m-d') ?>.pdf`;
            
            const opt = {
                margin:       0,
                filename:     filename,
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true },
                jsPDF:        { unit: 'mm', format: 'letter', orientation: 'portrait' }
            };

            // Generate and download PDF
            html2pdf().set(opt).from(page).save().then(function() {
                // Hide loading overlay when done
                overlay.style.display = 'none';
            }).catch(function(err) {
                console.error("PDF generation error: ", err);
                overlay.innerHTML = `<h3>Error generating PDF</h3><p>Please try again or use the browser's print function.</p>
                                     <button onclick="window.print()" style="padding:10px; margin-top:10px;">Print Using Browser</button>`;
            });
        };
    </script>
</body>
</html>
