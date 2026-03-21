<?php
/**
 * SVMS - Admin: Import Students
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

// Handle Template Download
if (isset($_GET['download_template'])) {
    require_once __DIR__ . '/../includes/SimpleXLSXGen.php';
    $books = [
        ['<b><style font-size="12" color="#ffffff" bgcolor="#2e1731"><center>student_number</center></style></b>', 
         '<b><style font-size="12" color="#ffffff" bgcolor="#2e1731"><center>first_name</center></style></b>',
         '<b><style font-size="12" color="#ffffff" bgcolor="#2e1731"><center>last_name</center></style></b>',
         '<b><style font-size="12" color="#ffffff" bgcolor="#2e1731"><center>middle_name</center></style></b>',
         '<b><style font-size="12" color="#ffffff" bgcolor="#2e1731"><center>gender</center></style></b>',
         '<b><style font-size="12" color="#ffffff" bgcolor="#2e1731"><center>date_of_birth</center></style></b>',
         '<b><style font-size="12" color="#ffffff" bgcolor="#2e1731"><center>grade_level</center></style></b>',
         '<b><style font-size="12" color="#ffffff" bgcolor="#2e1731"><center>section</center></style></b>',
         '<b><style font-size="12" color="#ffffff" bgcolor="#2e1731"><center>status</center></style></b>',
         '<b><style font-size="12" color="#ffffff" bgcolor="#2e1731"><center>address</center></style></b>',
         '<b><style font-size="12" color="#ffffff" bgcolor="#2e1731"><center>contact</center></style></b>',
         '<b><style font-size="12" color="#ffffff" bgcolor="#2e1731"><center>email</center></style></b>',
         '<b><style font-size="12" color="#ffffff" bgcolor="#2e1731"><center>guardian_name</center></style></b>',
         '<b><style font-size="12" color="#ffffff" bgcolor="#2e1731"><center>guardian_contact</center></style></b>'],
        ['<center>2024-0001</center>', 'Juan', 'Dela Cruz', 'Santos', 'Male', '2005-08-15', 'Grade 11', 'Rizal', 'active', 'Manila', "\0" . '09123456789', 'juan@example.com', 'Maria Dela Cruz', "\0" . '09123456789']
    ];
    $xlsx = Shuchkin\SimpleXLSXGen::fromArray($books);
    $widths = [20, 20, 20, 20, 14, 18, 16, 16, 14, 30, 20, 25, 25, 20];
    foreach($widths as $index => $w) {
        $xlsx->setColWidth($index + 1, $w);
    }
    $xlsx->downloadAs('svms_student_import_template.xlsx');
    exit;
}

$pageTitle = 'Import Students';
$breadcrumbs = ['Dashboard' => '/admin/dashboard.php', 'Students' => '/admin/students.php', 'Import' => null];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card-panel">
            <div class="panel-header d-flex justify-content-between align-items-center">
                <h5 class="panel-title mb-0"><i class="bi bi-file-earmark-spreadsheet-fill"></i> Import Students</h5>
                <a href="<?= BASE_PATH ?>/admin/students_import.php?download_template=1" class="btn-outline-custom" style="font-size:12px;padding:6px 14px;">
                    <i class="bi bi-download"></i> Excel Template (.xlsx)
                </a>
            </div>
            <div class="panel-body">
                <p class="text-muted" style="font-size:14px; margin-bottom: 20px;">
                    Upload an Excel (.xlsx) file containing student data. If the <strong>Student Number</strong> already exists, their information (Grade, Section, Status, etc.) will be updated. Otherwise, a new student account will be created.
                </p>
                
                <form id="importForm">
                    <div style="border:2px dashed #ede9ee; padding:40px 20px; text-align:center; border-radius:12px; background:#faf9fa; margin-bottom: 24px;">
                        <i class="bi bi-cloud-arrow-up" style="font-size:48px; color:var(--primary); margin-bottom:12px; display:inline-block;"></i>
                        <h6 style="font-weight:700; color:#130117; margin-bottom:8px;">Select an Excel file to upload</h6>
                        <p style="font-size:13px; color:var(--text-muted); margin-bottom:20px;">Must be a standard .xlsx format. Max 5MB.</p>
                        
                        <input type="file" id="csvFile" name="excel_file" accept=".xlsx" style="display:none;" onchange="updateFileName()">
                        <button type="button" class="btn-outline-custom" onclick="document.getElementById('csvFile').click()">
                            Browse Files
                        </button>
                        <div id="fileNameDisplay" style="margin-top:16px; font-size:15px; font-weight:600; color:var(--primary); display:none;"></div>
                    </div>
                    
                    <button type="submit" class="btn-primary-custom w-100" id="uploadBtn" style="padding:14px; font-size:15px; justify-content:center;" disabled>
                        <i class="bi bi-upload"></i> Process Import
                    </button>
                </form>
                
                <div id="importResults" style="display:none; margin-top:40px; border-top:1px solid #ede9ee; padding-top:24px;">
                    <h5 style="font-weight:700; color:#130117; margin-bottom:20px;">Import Results Summary</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-4">
                            <div style="background:rgba(5, 150, 105, 0.1); padding:16px; border-radius:12px; text-align:center;">
                                <div style="font-size:32px; font-weight:800; color:#065f46; line-height:1;" id="resCreated">0</div>
                                <div style="font-size:12px; font-weight:600; color:#065f46; text-transform:uppercase; margin-top:8px;">Created</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div style="background:rgba(37, 99, 235, 0.1); padding:16px; border-radius:12px; text-align:center;">
                                <div style="font-size:32px; font-weight:800; color:#1d4ed8; line-height:1;" id="resUpdated">0</div>
                                <div style="font-size:12px; font-weight:600; color:#1d4ed8; text-transform:uppercase; margin-top:8px;">Updated</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div style="background:rgba(220, 38, 38, 0.1); padding:16px; border-radius:12px; text-align:center;">
                                <div style="font-size:32px; font-weight:800; color:#b91c1c; line-height:1;" id="resFailed">0</div>
                                <div style="font-size:12px; font-weight:600; color:#b91c1c; text-transform:uppercase; margin-top:8px;">Failed</div>
                            </div>
                        </div>
                    </div>
                    
                    <div id="errorListContainer" style="display:none;">
                        <h6 style="font-weight:700; color:#b91c1c; margin-bottom:12px;">Error Details</h6>
                        <ul id="errorList" style="font-size:13px; color:#991b1b; background:#fef2f2; border:1px solid #fee2e2; border-radius:8px; padding:16px 16px 16px 32px; max-height:200px; overflow-y:auto; list-style-type:disc;"></ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card-panel">
            <div class="panel-header"><h5 class="panel-title"><i class="bi bi-info-circle"></i> Instructions</h5></div>
            <div class="panel-body" style="font-size:14px; color:var(--text-muted); line-height:1.6;">
                <p>Ensure your Excel file strictly follows the required format. Download the template to see exact column headers.</p>
                
                <h6 style="font-weight:600; color:#130117; margin-top:20px; font-size:13px; text-transform:uppercase; letter-spacing:0.04em;">Required Columns</h6>
                <ul style="padding-left:16px; margin-bottom:16px;">
                    <li><code>student_number</code></li>
                    <li><code>first_name</code></li>
                    <li><code>last_name</code></li>
                </ul>
                
                <h6 style="font-weight:600; color:#130117; margin-top:20px; font-size:13px; text-transform:uppercase; letter-spacing:0.04em;">Special Behavior</h6>
                <ul style="padding-left:16px; margin-bottom:0;">
                    <li class="mb-2"><strong>Status</strong>: Accepts <code>active</code>, <code>inactive</code>, or <code>graduated</code>. If blank when importing a new student, it defaults to <code>active</code>.</li>
                    <li><strong>No Duplicates</strong>: If a student number already exists in the system, its data will be updated based on the CSV row. Their existing photo and violations remain completely intact.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
function updateFileName() {
    const fileInput = document.getElementById('csvFile');
    const display = document.getElementById('fileNameDisplay');
    const btn = document.getElementById('uploadBtn');
    
    if (fileInput.files.length > 0) {
        display.textContent = fileInput.files[0].name;
        display.style.display = 'block';
        btn.disabled = false;
        
        // Hide previous results
        document.getElementById('importResults').style.display = 'none';
        document.getElementById('errorListContainer').style.display = 'none';
    } else {
        display.style.display = 'none';
        btn.disabled = true;
    }
}

document.getElementById('importForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const fileInput = document.getElementById('csvFile');
    if (fileInput.files.length === 0) return;
    
    const btn = document.getElementById('uploadBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="margin-right:8px;"></span> Processing...';
    
    const formData = new FormData();
    formData.append('excel_file', fileInput.files[0]);
    
    fetch('<?= BASE_PATH ?>/api/import_students.php', {
        method: 'POST',
        body: formData,
        headers: { "Bypass-Tunnel-Reminder": "true" }
    })
    .then(r => r.json())
    .then(data => {
        btn.innerHTML = '<i class="bi bi-upload"></i> Process Import';
        
        if (data.success) {
            document.getElementById('resCreated').textContent = data.created;
            document.getElementById('resUpdated').textContent = data.updated;
            document.getElementById('resFailed').textContent = data.failed;
            
            const errContainer = document.getElementById('errorListContainer');
            const errList = document.getElementById('errorList');
            if (data.errors && data.errors.length > 0) {
                errList.innerHTML = '';
                data.errors.forEach(e => {
                    const li = document.createElement('li');
                    li.textContent = `Row ${e.row}: ${e.message}`;
                    errList.appendChild(li);
                });
                errContainer.style.display = 'block';
            } else {
                errContainer.style.display = 'none';
            }
            
            document.getElementById('importResults').style.display = 'block';
            document.getElementById('fileNameDisplay').style.display = 'none';
            fileInput.value = '';
        } else {
            alert(data.error || "An error occurred during import.");
            btn.disabled = false;
        }
    })
    .catch(err => {
        console.error("Import error:", err);
        alert("Network error. Could not process file.");
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-upload"></i> Process Import';
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
