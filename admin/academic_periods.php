<?php
/**
 * SVMS - Admin: Academic Period Management
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pdo = getDBConnection();
$pageTitle = "Academic Periods";
$breadcrumbs = ['Dashboard' => '/admin/dashboard.php', 'Academic Periods' => null];

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $name = $_POST['name'] ?? '';
        $start = $_POST['start_date'] ?? '';
        $end = $_POST['end_date'] ?? '';
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $id = $_POST['id'] ?? null;
        
        try {
            if ($isActive) {
                // Deactivate all others if this one is active
                $pdo->exec("UPDATE academic_periods SET is_active = 0");
            }
            
            if ($action === 'add') {
                $stmt = $pdo->prepare("INSERT INTO academic_periods (name, start_date, end_date, is_active) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $start, $end, $isActive]);
                setFlash('success', 'Academic Period added successfully.');
            } else {
                $stmt = $pdo->prepare("UPDATE academic_periods SET name = ?, start_date = ?, end_date = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$name, $start, $end, $isActive, $id]);
                setFlash('success', 'Academic Period updated successfully.');
            }
        } catch (Exception $e) {
            setFlash('danger', 'Error: ' . $e->getMessage());
        }
    } elseif ($action === 'activate') {
        $id = $_POST['id'] ?? null;
        if ($id) {
            try {
                $pdo->exec("UPDATE academic_periods SET is_active = 0");
                $stmt = $pdo->prepare("UPDATE academic_periods SET is_active = 1 WHERE id = ?");
                $stmt->execute([$id]);
                setFlash('success', 'Academic Period activated.');
            } catch (Exception $e) {
                setFlash('danger', 'Error activating period.');
            }
        }
    }
    header("Location: academic_periods.php");
    exit;
}

// Get all periods
$periods = $pdo->query("SELECT * FROM academic_periods ORDER BY start_date DESC")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <p class="text-muted mb-0" style="font-size:13px;">Manage school years and semesters for historical data tracking</p>
    <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#periodModal" onclick="resetForm()">
        <i class="bi bi-plus-lg"></i> Create New Period
    </button>
</div>

<div class="card-panel">
    <div class="data-table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Period Name</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($periods)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4"><i class="bi bi-calendar-x me-2"></i>No academic periods found</td></tr>
                <?php else: ?>
                <?php foreach ($periods as $p): ?>
                <tr>
                    <td>
                        <div class="user-cell">
                            <div class="user-avatar" style="background:linear-gradient(135deg,var(--primary),#4e1456);color:#fff;display:flex;align-items:center;justify-content:center;">
                                <i class="bi bi-calendar-event"></i>
                            </div>
                            <div class="user-info">
                                <div class="name"><?= sanitize($p['name']) ?></div>
                                <div class="sub">Created <?= date('M d, Y', strtotime($p['created_at'])) ?></div>
                            </div>
                        </div>
                    </td>
                    <td><small><?= date('M d, Y', strtotime($p['start_date'])) ?></small></td>
                    <td><small><?= date('M d, Y', strtotime($p['end_date'])) ?></small></td>
                    <td>
                        <?php if ($p['is_active']): ?>
                            <span class="badge badge-soft-success">Active</span>
                        <?php else: ?>
                            <span class="badge badge-soft-secondary">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="dropdown">
                            <button class="action-btn" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="javascript:void(0)" onclick='editPeriod(<?= htmlspecialchars(json_encode($p)) ?>)'><i class="bi bi-pencil"></i> Edit</a></li>
                                <?php if (!$p['is_active']): ?>
                                <li>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="activate">
                                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                        <button type="submit" class="dropdown-item text-success"><i class="bi bi-check-circle"></i> Set Active</button>
                                    </form>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Period Modal -->
<div class="modal fade" id="periodModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow-lg rounded-4">
            <input type="hidden" name="action" id="modalAction" value="add">
            <input type="hidden" name="id" id="modalId">
            
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalTitle">Create New Academic Period</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-4">
                <div class="mb-3">
                    <label class="form-label fw-600">Period Name</label>
                    <div class="input-group">
                        <input type="text" class="form-control rounded-start-3" name="name" id="modalName" placeholder="e.g. SY 2024-2025 1st Sem" required>
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Quick Fill</button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                            <li><h6 class="dropdown-header">Common Formats</h6></li>
                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="quickFillName('1st Sem')">1st Semester</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="quickFillName('2nd Sem')">2nd Semester</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="quickFillName('Summer')">Summer / Midyear</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-primary" href="javascript:void(0)" onclick="autoSchoolYear()">Auto School Year</a></li>
                        </ul>
                    </div>
                    <small class="text-muted" style="font-size:11px;">Select [Auto School Year] to automatically format based on current date.</small>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-600">Start Date</label>
                        <input type="date" class="form-control rounded-3" name="start_date" id="modalStart" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-600">End Date</label>
                        <input type="date" class="form-control rounded-3" name="end_date" id="modalEnd" required>
                    </div>
                </div>
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" name="is_active" id="modalActive">
                    <label class="form-check-label ms-2" for="modalActive">Set as the Current Active Period</label>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn-primary-custom">Save Period</button>
            </div>
        </form>
    </div>
</div>

<script>
function resetForm() {
    document.getElementById('modalTitle').innerText = 'Create New Academic Period';
    document.getElementById('modalAction').value = 'add';
    document.getElementById('modalId').value = '';
    document.getElementById('modalName').value = '';
    document.getElementById('modalStart').value = '';
    document.getElementById('modalEnd').value = '';
    document.getElementById('modalActive').checked = false;
}

function editPeriod(period) {
    document.getElementById('modalTitle').innerText = 'Edit Academic Period';
    document.getElementById('modalAction').value = 'edit';
    document.getElementById('modalId').value = period.id;
    document.getElementById('modalName').value = period.name;
    document.getElementById('modalStart').value = period.start_date;
    document.getElementById('modalEnd').value = period.end_date;
    document.getElementById('modalActive').checked = period.is_active == 1;
    
    var modal = new bootstrap.Modal(document.getElementById('periodModal'));
    modal.show();
}

function quickFillName(suffix) {
    const input = document.getElementById('modalName');
    const start = document.getElementById('modalStart').value;
    let yearPart = '';
    
    if (start) {
        const d = new Date(start);
        const y = d.getFullYear();
        // Scholarly guess: if month < 6, it belongs to previous school year session
        if (d.getMonth() < 5) {
            yearPart = `SY ${y-1}-${y} `;
        } else {
            yearPart = `SY ${y}-${y+1} `;
        }
    } else {
        const now = new Date();
        const y = now.getFullYear();
        if (now.getMonth() < 5) {
            yearPart = `SY ${y-1}-${y} `;
        } else {
            yearPart = `SY ${y}-${y+1} `;
        }
    }
    input.value = yearPart + suffix;
}

function autoSchoolYear() {
    const start = document.getElementById('modalStart').value;
    const now = start ? new Date(start) : new Date();
    const y = now.getFullYear();
    const input = document.getElementById('modalName');
    
    let sy = '';
    let sem = '';
    
    if (now.getMonth() < 5) { // Jan-May
        sy = `SY ${y-1}-${y}`;
        sem = '2nd Sem';
    } else if (now.getMonth() < 10) { // Jun-Oct
        sy = `SY ${y}-${y+1}`;
        sem = '1st Sem';
    } else { // Nov-Dec
        sy = `SY ${y}-${y+1}`;
        sem = '2nd Sem';
    }
    
    input.value = `${sy} ${sem}`;
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
