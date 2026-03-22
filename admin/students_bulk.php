<?php
/**
 * SVMS - Admin: Bulk Student Actions
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pageTitle = 'Bulk Actions';
$breadcrumbs = ['Dashboard' => '/admin/dashboard.php', 'Students' => '/admin/students.php', 'Bulk Actions' => null];
require_once __DIR__ . '/../includes/header.php';

$pdo = getDBConnection();
$grades = $pdo->query("SELECT DISTINCT grade_level FROM students WHERE status='active' ORDER BY grade_level")->fetchAll(PDO::FETCH_COLUMN);
// Fetch sections grouped by grade level for dynamic dropdowns
$sectionsByGrade = [];
$stm = $pdo->query("SELECT DISTINCT grade_level, section FROM students WHERE status='active' ORDER BY grade_level, section");
foreach ($stm as $row) {
    if (!isset($sectionsByGrade[$row['grade_level']])) {
        $sectionsByGrade[$row['grade_level']] = [];
    }
    $sectionsByGrade[$row['grade_level']][] = $row['section'];
}
?>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card-panel">
            <div class="panel-header">
                <h5 class="panel-title"><i class="bi bi-people-fill"></i> Bulk Section Management</h5>
            </div>
            <div class="panel-body">
                <p class="text-muted" style="font-size:14px; margin-bottom: 24px;">
                    Use this tool to promote an entire section to a new grade, or batch-update their status to graduated/inactive.
                </p>
                
                <form id="bulkForm">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight:600; color:#2e1731;">Target Grade Level</label>
                            <select class="form-select" id="targetGrade" required onchange="updateSections()">
                                <option value="">Select Grade...</option>
                                <?php foreach ($grades as $g): ?>
                                    <option value="<?= sanitize($g) ?>"><?= sanitize($g) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight:600; color:#2e1731;">Target Section</label>
                            <select class="form-select" id="targetSection" required>
                                <option value="">Select Grade First...</option>
                            </select>
                        </div>
                    </div>
                    
                    <div style="background:#faf9fa; padding:20px; border-radius:12px; border:1px solid #ede9ee; margin-bottom:24px;">
                        <label class="form-label" style="font-weight:700; color:#130117;">Action to Perform</label>
                        <select class="form-select mb-3" id="bulkAction" required onchange="toggleActionFields()">
                            <option value="">Select Action...</option>
                            <option value="promote">Promote (Change Grade/Section)</option>
                            <option value="graduate">Mark as Graduated</option>
                            <option value="inactive">Mark as Inactive</option>
                            <option value="print_ids">Print ID Cards for Section</option>
                        </select>
                        
                        <div id="promoteFields" style="display:none; padding-top:16px; border-top:1px dashed #ccc;">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">New Grade Level</label>
                                    <select class="form-select" id="newGrade">
                                        <option value="">Select New Grade...</option>
                                        <option value="Grade 7">Grade 7</option>
                                        <option value="Grade 8">Grade 8</option>
                                        <option value="Grade 9">Grade 9</option>
                                        <option value="Grade 10">Grade 10</option>
                                        <option value="Grade 11">Grade 11</option>
                                        <option value="Grade 12">Grade 12</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">New Section Name</label>
                                    <input type="text" class="form-control" id="newSection" placeholder="e.g. Rizal">
                                </div>
                            </div>
                        </div>
                        
                        <div id="statusWarning" style="display:none; padding-top:12px; color:#b91c1c; font-size:13px; font-weight:600;">
                            <i class="bi bi-exclamation-triangle-fill"></i> Warning: This will hide these students from active lists.
                        </div>
                    </div>
                    
                    <button type="button" class="btn btn-outline-secondary w-100 mb-2" id="previewBtn" onclick="previewAction()">
                        Check Affected Students
                    </button>
                    
                    <div id="previewResult" style="display:none; margin-bottom:20px; text-align:center; padding:12px; background:#e0f2fe; color:#0369a1; border-radius:8px; font-weight:600;"></div>
                    
                    <button type="submit" class="btn-primary-custom w-100" id="executeBtn" style="padding:14px; justify-content:center;" disabled>
                        <i class="bi bi-check2-all"></i> Execute Bulk Action
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card-panel">
            <div class="panel-header"><h5 class="panel-title"><i class="bi bi-lightbulb"></i> How it works</h5></div>
            <div class="panel-body" style="font-size:13px; color:var(--text-muted); line-height:1.6;">
                <p><strong>Promote:</strong> Changes the grade level and section of all active students in the selected target group. For example, moving all of "Grade 11 - Rizal" to "Grade 12 - Rizal".</p>
                <p><strong>Mark as Graduated:</strong> Changes their status to 'graduated'. They will no longer appear in standard active lists or the scanner.</p>
                <p><strong>Mark as Inactive:</strong> Changes their status to 'inactive'. Typically used for students who transferred schools.</p>
            </div>
        </div>
    </div>
</div>

<script>
const sectionsData = <?= json_encode($sectionsByGrade) ?>;

function updateSections() {
    const grade = document.getElementById('targetGrade').value;
    const sectionSelect = document.getElementById('targetSection');
    
    sectionSelect.innerHTML = '<option value="">Select Section...</option>';
    if (grade && sectionsData[grade]) {
        sectionsData[grade].forEach(sec => {
            const opt = document.createElement('option');
            opt.value = sec;
            opt.textContent = sec;
            sectionSelect.appendChild(opt);
        });
    }
    
    // Reset preview
    document.getElementById('previewResult').style.display = 'none';
    document.getElementById('executeBtn').disabled = true;
}

function toggleActionFields() {
    const action = document.getElementById('bulkAction').value;
    const promoteFields = document.getElementById('promoteFields');
    const statusWarning = document.getElementById('statusWarning');
    const newGrade = document.getElementById('newGrade');
    const newSection = document.getElementById('newSection');
    
    if (action === 'promote') {
        promoteFields.style.display = 'block';
        statusWarning.style.display = 'none';
        newGrade.required = true;
        newSection.required = true;
    } else if (action === 'graduate' || action === 'inactive') {
        promoteFields.style.display = 'none';
        statusWarning.style.display = 'block';
        newGrade.required = false;
        newSection.required = false;
    } else if (action === 'print_ids') {
        promoteFields.style.display = 'none';
        statusWarning.style.display = 'none';
        newGrade.required = false;
        newSection.required = false;
        document.getElementById('executeBtn').innerHTML = '<i class="bi bi-printer"></i> Generate ID Cards';
    } else {
        promoteFields.style.display = 'none';
        statusWarning.style.display = 'none';
        newGrade.required = false;
        newSection.required = false;
        document.getElementById('executeBtn').innerHTML = '<i class="bi bi-check2-all"></i> Execute Bulk Action';
    }
    
    document.getElementById('previewResult').style.display = 'none';
    document.getElementById('executeBtn').disabled = true;
}

function previewAction() {
    const grade = document.getElementById('targetGrade').value;
    const section = document.getElementById('targetSection').value;
    
    if (!grade || !section) {
        alert("Please select a target Grade Level and Section first.");
        return;
    }
    
    const previewBtn = document.getElementById('previewBtn');
    previewBtn.innerHTML = 'Checking...';
    previewBtn.disabled = true;
    
    // Fetch count from an API
    fetch(`<?= BASE_PATH ?>/api/bulk_students.php?action=count&grade=${encodeURIComponent(grade)}&section=${encodeURIComponent(section)}`)
        .then(r => r.json())
        .then(data => {
            previewBtn.innerHTML = 'Check Affected Students';
            previewBtn.disabled = false;
            
            if (data.success) {
                const resDiv = document.getElementById('previewResult');
                if (data.count === 0) {
                    resDiv.style.background = '#fef2f2';
                    resDiv.style.color = '#991b1b';
                    resDiv.textContent = 'No active students found in this target group.';
                    document.getElementById('executeBtn').disabled = true;
                } else {
                    resDiv.style.background = '#f0fdf4';
                    resDiv.style.color = '#166534';
                    resDiv.textContent = `${data.count} active students will be affected by this action.`;
                    document.getElementById('executeBtn').disabled = false;
                }
                resDiv.style.display = 'block';
            } else {
                alert("Error checking count.");
            }
        })
        .catch(err => {
            console.error(err);
            previewBtn.innerHTML = 'Check Affected Students';
            previewBtn.disabled = false;
        });
}

document.getElementById('bulkForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const action = document.getElementById('bulkAction').value;
    const targetGrade = document.getElementById('targetGrade').value;
    const targetSection = document.getElementById('targetSection').value;
    
    if (action === 'print_ids') {
        // Fetch all student IDs for this grade/section
        fetch(`<?= BASE_PATH ?>/api/bulk_students.php?action=get_ids&grade=${encodeURIComponent(targetGrade)}&section=${encodeURIComponent(targetSection)}`)
            .then(r => r.json())
            .then(data => {
                if (data.success && data.ids.length > 0) {
                    window.open(`<?= BASE_PATH ?>/admin/student_id_card.php?ids=${data.ids.join(',')}`, '_blank');
                } else {
                    alert("No active students found in this section.");
                }
            });
        return;
    }
    
    if (!confirm(`Are you absolutely sure you want to ${action.toUpperCase()} all students in ${targetGrade} - ${targetSection}? This action cannot be easily undone.`)) {
        return;
    }
    
    const btn = document.getElementById('executeBtn');
    btn.disabled = true;
    btn.innerHTML = 'Processing...';
    
    const formData = new FormData();
    formData.append('target_grade', targetGrade);
    formData.append('target_section', targetSection);
    formData.append('action_type', action);
    
    if (action === 'promote') {
        formData.append('new_grade', document.getElementById('newGrade').value);
        formData.append('new_section', document.getElementById('newSection').value);
    }
    
    fetch('<?= BASE_PATH ?>/api/bulk_students.php', {
        method: 'POST',
        body: formData,
        headers: { "Bypass-Tunnel-Reminder": "true" }
    })
    .then(r => r.json())
    .then(data => {
        btn.innerHTML = '<i class="bi bi-check2-all"></i> Execute Bulk Action';
        if (data.success) {
            alert(`Success! ${data.affected} students were updated successfully.`);
            window.location.href = '<?= BASE_PATH ?>/admin/students.php';
        } else {
            alert(data.error || "An error occurred during bulk update.");
            btn.disabled = false;
        }
    })
    .catch(err => {
        console.error(err);
        alert("Network error.");
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check2-all"></i> Execute Bulk Action';
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
