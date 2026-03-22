<?php
/**
 * SVMS - Individual Violation Print Report
 * Formal A4-style printable report for a single violation
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole(['admin', 'discipline_officer']);

$pdo = getDBConnection();
$currentUser = getCurrentUser();

$violationId = intval($_GET['id'] ?? 0);
if (!$violationId) {
    die('Violation ID is required.');
}

// Fetch violation with student and type info
$stmt = $pdo->prepare("
    SELECT v.*, 
           s.student_number, s.first_name, s.last_name, s.middle_name, 
           s.gender, s.grade_level, s.section, s.contact, s.email as student_email,
           s.guardian_name, s.guardian_contact,
           vt.name as violation_name, vt.severity, vt.description as type_description,
           u.full_name as reporter_name
    FROM violations v
    JOIN students s ON v.student_id = s.id
    JOIN violation_types vt ON v.violation_type_id = vt.id
    JOIN users u ON v.reported_by = u.id
    WHERE v.id = ?
");
$stmt->execute([$violationId]);
$violation = $stmt->fetch();

if (!$violation) {
    die('Violation not found.');
}

// Fetch disciplinary actions for this violation
$actionsStmt = $pdo->prepare("
    SELECT da.*, u.full_name as issuer_name
    FROM disciplinary_actions da
    JOIN users u ON da.issued_by = u.id
    WHERE da.violation_id = ?
    ORDER BY da.created_at ASC
");
$actionsStmt->execute([$violationId]);
$actions = $actionsStmt->fetchAll();

// Count total violations for this student
$countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM violations WHERE student_id = ?");
$countStmt->execute([$violation['student_id']]);
$totalViolations = $countStmt->fetch()['total'];

$studentName = htmlspecialchars($violation['first_name'] . ' ' . ($violation['middle_name'] ? $violation['middle_name'][0] . '. ' : '') . $violation['last_name']);
$actionLabels = [
    'warning' => 'Warning',
    'detention' => 'Detention',
    'suspension' => 'Suspension',
    'expulsion' => 'Expulsion',
    'community_service' => 'Community Service',
    'counseling' => 'Counseling'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Violation Report - <?= $studentName ?> - SVMS</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background: #525659;
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
            margin-bottom: 25px;
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
        .header .report-ref {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
        }

        /* Section Title */
        .section-title {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            color: #2e1731;
            border-bottom: 1px solid #2e1731;
            padding-bottom: 4px;
            margin: 25px 0 12px 0;
        }

        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px 30px;
            font-size: 12px;
        }
        .info-row {
            display: flex;
            gap: 5px;
        }
        .info-label {
            font-weight: bold;
            min-width: 120px;
            color: #333;
        }
        .info-value {
            color: #000;
        }

        /* Full-width info row */
        .info-full {
            grid-column: 1 / -1;
        }

        /* Detail box for description */
        .detail-box {
            font-size: 12px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            padding: 10px 12px;
            border-radius: 3px;
            margin-top: 8px;
            line-height: 1.6;
            color: #333;
        }

        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #000;
            padding: 7px 6px;
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
        .badge-major { color: #ea580c; font-weight: bold; }
        .badge-critical { color: #dc2626; font-weight: bold; }

        /* Severity indicator */
        .severity {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
        }
        .severity-minor { background: #fef3c7; color: #92400e; }
        .severity-major { background: #ffedd5; color: #9a3412; }
        .severity-critical { background: #fee2e2; color: #991b1b; }

        /* Status indicator */
        .status {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 3px;
            font-size: 11px;
            text-transform: uppercase;
            font-weight: bold;
        }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-reviewed { background: #dbeafe; color: #1e40af; }
        .status-resolved { background: #d1fae5; color: #065f46; }
        .status-dismissed { background: #e5e7eb; color: #374151; }

        /* No actions message */
        .no-actions {
            font-size: 12px;
            color: #666;
            font-style: italic;
            margin-top: 10px;
        }

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
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }

        /* Footer */
        .page-footer {
            position: absolute;
            bottom: 20mm;
            left: 20mm;
            right: 20mm;
            text-align: center;
            font-size: 9px;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 8px;
        }

        /* Print */
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
            .severity-minor, .severity-major, .severity-critical,
            .status-pending, .status-reviewed, .status-resolved, .status-dismissed {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
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
        <!-- Report Header -->
        <div class="header">
            <img src="<?= BASE_PATH ?>/assets/img/logo.png" alt="Logo" class="school-logo">
            <h1>Lyceum of Subic Bay</h1>
            <h2>Student Violation Management System (SVMS)</h2>
            <div class="report-title">Individual Violation Report</div>
            <div class="report-ref">Reference No: VR-<?= str_pad($violation['id'], 5, '0', STR_PAD_LEFT) ?> | Date Generated: <?= date('F d, Y') ?></div>
        </div>

        <!-- Student Information -->
        <div class="section-title">I. Student Information</div>
        <div class="info-grid">
            <div class="info-row">
                <span class="info-label">Name:</span>
                <span class="info-value"><?= $studentName ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Student No.:</span>
                <span class="info-value"><?= htmlspecialchars($violation['student_number']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Grade & Section:</span>
                <span class="info-value"><?= htmlspecialchars($violation['grade_level']) ?> - <?= htmlspecialchars($violation['section']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Gender:</span>
                <span class="info-value"><?= htmlspecialchars($violation['gender'] ?? 'N/A') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Contact:</span>
                <span class="info-value"><?= htmlspecialchars($violation['contact'] ?? 'N/A') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Guardian:</span>
                <span class="info-value"><?= htmlspecialchars($violation['guardian_name'] ?? 'N/A') ?> <?= $violation['guardian_contact'] ? '(' . htmlspecialchars($violation['guardian_contact']) . ')' : '' ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Total Violations:</span>
                <span class="info-value"><?= $totalViolations ?> record<?= $totalViolations > 1 ? 's' : '' ?> on file</span>
            </div>
        </div>

        <!-- Violation Details -->
        <div class="section-title">II. Violation Details</div>
        <div class="info-grid">
            <div class="info-row">
                <span class="info-label">Violation Type:</span>
                <span class="info-value"><?= htmlspecialchars($violation['violation_name']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Severity:</span>
                <span class="info-value">
                    <span class="severity severity-<?= htmlspecialchars($violation['severity']) ?>"><?= ucfirst(htmlspecialchars($violation['severity'])) ?></span>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Date & Time:</span>
                <span class="info-value"><?= date('F d, Y - h:i A', strtotime($violation['date_occurred'])) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Location:</span>
                <span class="info-value"><?= htmlspecialchars($violation['location'] ?? 'Not specified') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Reported By:</span>
                <span class="info-value"><?= htmlspecialchars($violation['reporter_name']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Status:</span>
                <span class="info-value">
                    <span class="status status-<?= htmlspecialchars($violation['status']) ?>"><?= ucfirst(htmlspecialchars($violation['status'])) ?></span>
                </span>
            </div>
        </div>

        <?php if ($violation['description']): ?>
        <div style="margin-top: 12px; font-size: 12px;">
            <strong>Description / Remarks:</strong>
            <div class="detail-box"><?= nl2br(htmlspecialchars($violation['description'])) ?></div>
        </div>
        <?php endif; ?>

        <!-- Disciplinary Actions -->
        <div class="section-title">III. Disciplinary Action<?= count($actions) !== 1 ? 's' : '' ?></div>
        <?php if (!empty($actions)): ?>
        <table>
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th width="18%">Action Type</th>
                    <th width="30%">Description</th>
                    <th width="15%">Start Date</th>
                    <th width="15%">End Date</th>
                    <th width="10%">Status</th>
                    <th width="15%">Issued By</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($actions as $i => $a): ?>
                <tr>
                    <td class="text-center"><?= $i + 1 ?></td>
                    <td class="text-center"><?= $actionLabels[$a['action_type']] ?? ucfirst($a['action_type']) ?></td>
                    <td><?= htmlspecialchars($a['description'] ?: '-') ?></td>
                    <td class="text-center"><?= $a['start_date'] ? date('M d, Y', strtotime($a['start_date'])) : '-' ?></td>
                    <td class="text-center"><?= $a['end_date'] ? date('M d, Y', strtotime($a['end_date'])) : '-' ?></td>
                    <td class="text-center"><?= ucfirst($a['status']) ?></td>
                    <td><?= htmlspecialchars($a['issuer_name']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="no-actions">No disciplinary actions have been issued for this violation.</div>
        <?php endif; ?>

        <!-- Signatures -->
        <div class="signatures clearfix">
            <div class="signature-box">
                <div style="font-size:11px; margin-bottom: 20px;">Prepared By:</div>
                <div class="signature-line"></div>
                <div class="signature-name"><?= htmlspecialchars($currentUser['full_name']) ?></div>
                <div class="signature-title"><?= ucwords(str_replace('_', ' ', $currentUser['role'])) ?></div>
                <div class="signature-title" style="margin-top: 5px;">Date: <?= date('F d, Y') ?></div>
            </div>

            <div class="signature-box right">
                <div style="font-size:11px; margin-bottom: 20px;">Noted By:</div>
                <div class="signature-line"></div>
                <div class="signature-name">_____________________________</div>
                <div class="signature-title">Head of Discipline / Principal</div>
            </div>
        </div>

        <!-- Page Footer -->
        <div class="page-footer">
            This document was generated by the Student Violation Management System (SVMS) — Lyceum of Subic Bay.
            <br>Printed on <?= date('F d, Y \a\t h:i A') ?> | Confidential
        </div>
    </div>

    <script>
        window.onload = function() {
            const page = document.querySelector('.page');
            const overlay = document.getElementById('loadingOverlay');
            
            // Format filename based on student name and violation ID
            const safeName = "<?= preg_replace('/[^a-zA-Z0-9]+/', '_', $studentName) ?>";
            const filename = `SVMS_Violation_${safeName}_VR<?= str_pad($violation['id'], 5, '0', STR_PAD_LEFT) ?>.pdf`;
            
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
