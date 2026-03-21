<?php
/**
 * SVMS - Admin: Student Management
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pdo = getDBConnection();

// Handle delete (must be before any HTML output)
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $stmt = $pdo->prepare("UPDATE students SET status='inactive' WHERE id=?");
        $stmt->execute([$_GET['delete']]);
        setFlash('success', 'Student deactivated successfully.');
    } catch (Exception $e) {
        setFlash('danger', 'Error deactivating student.');
    }
    header('Location: ' . BASE_PATH . '/admin/students.php');
    exit;
}

// Generate missing QR codes for all students
if (isset($_GET['generate_all'])) {
    $stmt = $pdo->query("SELECT s.id FROM students s LEFT JOIN qr_codes q ON s.id=q.student_id WHERE q.id IS NULL AND s.status='active'");
    $missing = $stmt->fetchAll();
    $count = 0;
    foreach ($missing as $m) {
        $qrData = QR_PREFIX . generateUUID();
        $pdo->prepare("INSERT INTO qr_codes (student_id, qr_data) VALUES (?,?)")->execute([$m['id'], $qrData]);
        $count++;
    }
    setFlash('success', "$count QR codes generated.");
    header('Location: ' . BASE_PATH . '/admin/students.php');
    exit;
}

// Handle add/edit (must be before any HTML output)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $data = [
        sanitize($_POST['student_number']),
        sanitize($_POST['first_name']),
        sanitize($_POST['last_name']),
        sanitize($_POST['middle_name'] ?? ''),
        $_POST['gender'] ?? 'Male',
        $_POST['date_of_birth'] ?? null,
        sanitize($_POST['grade_level']),
        sanitize($_POST['section']),
        sanitize($_POST['contact'] ?? ''),
        sanitize($_POST['email'] ?? ''),
        sanitize($_POST['guardian_name'] ?? ''),
        sanitize($_POST['guardian_contact'] ?? ''),
        sanitize($_POST['address'] ?? '')
    ];
    
    try {
        if ($id) {
            $data[] = $id;
            $stmt = $pdo->prepare("UPDATE students SET student_number=?, first_name=?, last_name=?, middle_name=?, gender=?, date_of_birth=?, grade_level=?, section=?, contact=?, email=?, guardian_name=?, guardian_contact=?, address=? WHERE id=?");
            $stmt->execute($data);
            setFlash('success', 'Student updated successfully.');
        } else {
            $stmt = $pdo->prepare("INSERT INTO students (student_number, first_name, last_name, middle_name, gender, date_of_birth, grade_level, section, contact, email, guardian_name, guardian_contact, address) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute($data);
            $studentId = $pdo->lastInsertId();
            
            // Auto-generate QR code
            $qrData = QR_PREFIX . generateUUID();
            $qrStmt = $pdo->prepare("INSERT INTO qr_codes (student_id, qr_data) VALUES (?, ?)");
            $qrStmt->execute([$studentId, $qrData]);
            
            // Create student user account
            $username = sanitize($_POST['student_number']);
            $password = password_hash('student123', PASSWORD_DEFAULT);
            $fullName = sanitize($_POST['first_name'] . ' ' . $_POST['last_name']);
            $userStmt = $pdo->prepare("INSERT INTO users (username, password, full_name, email, role, student_id) VALUES (?,?,?,?,'student',?)");
            $userStmt->execute([$username, $password, $fullName, sanitize($_POST['email'] ?? ''), $studentId]);
            
            setFlash('success', 'Student added successfully. Login: ' . $username . ' / student123');
        }
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            setFlash('danger', 'Student number already exists.');
        } else {
            setFlash('danger', 'Error saving student: ' . $e->getMessage());
        }
    }
    header('Location: ' . BASE_PATH . '/admin/students.php');
    exit;
}

// Now include header (HTML output starts here)
$pageTitle = 'Students';
$breadcrumbs = ['Dashboard' => '/admin/dashboard.php', 'Students' => null];
require_once __DIR__ . '/../includes/header.php';

// Search and filter
$search = $_GET['search'] ?? '';
$grade = $_GET['grade'] ?? '';
$where = "WHERE s.status='active'";
$params = [];

if ($search) {
    $where .= " AND (s.first_name LIKE ? OR s.last_name LIKE ? OR s.student_number LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}
if ($grade) {
    $where .= " AND s.grade_level = ?";
    $params[] = $grade;
}

$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM students s $where");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

$stmt = $pdo->prepare("SELECT s.*, (SELECT COUNT(*) FROM violations WHERE student_id=s.id) as violation_count FROM students s $where ORDER BY s.created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$students = $stmt->fetchAll();

// Get edit data
$editStudent = null;
if (isset($_GET['edit'])) {
    $editStmt = $pdo->prepare("SELECT * FROM students WHERE id=?");
    $editStmt->execute([$_GET['edit']]);
    $editStudent = $editStmt->fetch();
}

// Grade levels for filter
$grades = $pdo->query("SELECT DISTINCT grade_level FROM students WHERE status='active' ORDER BY grade_level")->fetchAll(PDO::FETCH_COLUMN);
?>

<!-- Action Bar -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <p class="text-muted mb-0" style="font-size:13px;">Manage student records and generate QR codes</p>
    </div>
    <div class="d-flex gap-2">
        <a href="?generate_all=1" class="btn-outline-custom" onclick="return confirm('Generate QR codes for all students missing one?')">
            <i class="bi bi-qr-code"></i> Generate Missing QRs
        </a>
        <a href="<?= BASE_PATH ?>/admin/students_import.php" class="btn-outline-custom border-primary text-primary hover-bg-primary hover-text-white">
            <i class="bi bi-cloud-arrow-up"></i> Import CSV
        </a>
        <a href="<?= BASE_PATH ?>/admin/students_bulk.php" class="btn-outline-custom" style="border-color:#130117; color:#130117;">
            <i class="bi bi-people-fill"></i> Bulk Actions
        </a>
        <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#studentModal" onclick="resetForm()">
            <i class="bi bi-plus-lg"></i> Add Student
        </button>
    </div>
</div>

<!-- Filter Bar -->
<div class="card-panel mb-4">
    <form class="filter-bar" method="GET">
        <div class="search-box">
            <i class="bi bi-search"></i>
            <input type="text" name="search" placeholder="Search by name or student number..." value="<?= sanitize($search) ?>">
        </div>
        <select name="grade" class="form-select" style="width:auto;min-width:160px;">
            <option value="">All Grades</option>
            <?php foreach ($grades as $g): ?>
                <option value="<?= sanitize($g) ?>" <?= $grade === $g ? 'selected' : '' ?>><?= sanitize($g) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-primary-custom"><i class="bi bi-funnel"></i> Filter</button>
        <?php if ($search || $grade): ?>
            <a href="<?= BASE_PATH ?>/admin/students.php" class="btn btn-outline-secondary btn-sm">Clear</a>
        <?php endif; ?>
    </form>

    <!-- Table -->
    <div class="data-table-wrapper">
        <table class="data-table" id="studentsTable">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Student No.</th>
                    <th>Grade & Section</th>
                    <th>Contact</th>
                    <th>Violations</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $s): ?>
                <tr>
                    <td>
                        <div class="user-cell">
                            <?= getAvatarHtml($s['photo'], $s['first_name'] . ' ' . $s['last_name'], 'user-avatar') ?>
                            <div class="user-info">
                                <div class="name"><?= sanitize($s['last_name'] . ', ' . $s['first_name']) ?></div>
                                <div class="sub"><?= sanitize($s['gender'] ?? '') ?></div>
                            </div>
                        </div>
                    </td>
                    <td><code><?= sanitize($s['student_number']) ?></code></td>
                    <td><?= sanitize($s['grade_level'] . ' - ' . $s['section']) ?></td>
                    <td><small><?= sanitize($s['contact'] ?? 'N/A') ?></small></td>
                    <td>
                        <?php if ($s['violation_count'] > 0): ?>
                            <span class="badge badge-soft-danger"><?= $s['violation_count'] ?></span>
                        <?php else: ?>
                            <span class="badge badge-soft-success">0</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="action-btns">
                            <?php
                            $qrStmt = $pdo->prepare("SELECT qr_data FROM qr_codes WHERE student_id = ?");
                            $qrStmt->execute([$s['id']]);
                            $qr = $qrStmt->fetch();
                            ?>
                            <?php if ($qr && $qr['qr_data']): ?>
                                <button class="action-btn" title="View QR" onclick="showQR('<?= sanitize($qr['qr_data']) ?>', '<?= sanitize($s['last_name'] . ', ' . $s['first_name']) ?>', '<?= sanitize($s['student_number']) ?>')">
                                    <i class="bi bi-qr-code"></i>
                                </button>
                            <?php endif; ?>
                            <a href="?edit=<?= $s['id'] ?>#studentModal" class="action-btn" title="Edit"
                               onclick="editStudent(<?= htmlspecialchars(json_encode($s)) ?>)">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="javascript:void(0)" class="action-btn btn-danger" title="Delete"
                               onclick="confirmDelete('/admin/students.php?delete=<?= $s['id'] ?>', '<?= sanitize($s['first_name'] . ' ' . $s['last_name']) ?>')">
                                <i class="bi bi-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($students)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No students found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($totalPages > 1): ?>
    <div class="panel-footer">
        <nav><ul class="pagination justify-content-center mb-0">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&grade=<?= urlencode($grade) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul></nav>
    </div>
    <?php endif; ?>
</div>

<!-- Student Modal -->
<div class="modal fade" id="studentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="studentForm">
                <input type="hidden" name="id" id="studentId">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Student Number *</label>
                            <input type="text" class="form-control" name="student_number" id="studentNumber" required placeholder="e.g. 2024-0001">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">First Name *</label>
                            <input type="text" class="form-control" name="first_name" id="firstName" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Last Name *</label>
                            <input type="text" class="form-control" name="last_name" id="lastName" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Middle Name</label>
                            <input type="text" class="form-control" name="middle_name" id="middleName">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Gender</label>
                            <select class="form-select" name="gender" id="gender">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" name="date_of_birth" id="dob">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Grade Level *</label>
                            <select class="form-select" name="grade_level" id="gradeLevel" required>
                                <option value="">Select...</option>
                                <option>Grade 7</option><option>Grade 8</option>
                                <option>Grade 9</option><option>Grade 10</option>
                                <option>Grade 11</option><option>Grade 12</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Section *</label>
                            <input type="text" class="form-control" name="section" id="section" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Contact</label>
                            <input type="text" class="form-control" name="contact" id="contact" placeholder="09XX XXX XXXX">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="email">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Guardian Name</label>
                            <input type="text" class="form-control" name="guardian_name" id="guardianName">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Guardian Contact</label>
                            <input type="text" class="form-control" name="guardian_contact" id="guardianContact">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" id="address" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-primary-custom">Save Student</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function resetForm() {
    document.getElementById('studentForm').reset();
    document.getElementById('studentId').value = '';
    document.getElementById('modalTitle').textContent = 'Add Student';
}

function editStudent(s) {
    document.getElementById('studentId').value = s.id;
    document.getElementById('modalTitle').textContent = 'Edit Student';
    document.getElementById('studentNumber').value = s.student_number;
    document.getElementById('firstName').value = s.first_name;
    document.getElementById('lastName').value = s.last_name;
    document.getElementById('middleName').value = s.middle_name || '';
    document.getElementById('gender').value = s.gender || 'Male';
    document.getElementById('dob').value = s.date_of_birth || '';
    document.getElementById('gradeLevel').value = s.grade_level;
    document.getElementById('section').value = s.section;
    document.getElementById('contact').value = s.contact || '';
    document.getElementById('email').value = s.email || '';
    document.getElementById('guardianName').value = s.guardian_name || '';
    document.getElementById('guardianContact').value = s.guardian_contact || '';
    document.getElementById('address').value = s.address || '';
    new bootstrap.Modal(document.getElementById('studentModal')).show();
}
</script>

<!-- QR Modal -->
<div class="modal fade" id="qrModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Student QR Code</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <div id="qrDisplay" class="mb-3 d-flex justify-content-center"></div>
                <h6 id="qrName" class="mb-1"></h6>
                <small class="text-muted" id="qrStudentNo"></small>
            </div>
            <div class="modal-footer justify-content-center">
                <button class="btn-primary-custom btn-sm" onclick="printCurrentQR()"><i class="bi bi-printer"></i> Print</button>
                <button class="btn-accent btn-sm" onclick="downloadCurrentQR()"><i class="bi bi-download"></i> Download</button>
            </div>
        </div>
    </div>
</div>

<?php 
$extraJS = '<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
let currentQRData = {};

function showQR(data, name, studentNo) {
    currentQRData = { data, name, studentNo };
    document.getElementById("qrName").textContent = name;
    document.getElementById("qrStudentNo").textContent = studentNo;
    const display = document.getElementById("qrDisplay");
    display.innerHTML = "";
    new QRCode(display, { text: data, width: 200, height: 200, colorDark: "#2e1731", colorLight: "#ffffff" });
    new bootstrap.Modal(document.getElementById("qrModal")).show();
}

function printQR(data, name, studentNo) {
    const win = window.open("", "_blank");
    win.document.write(`<html><head><title>QR - ${name}</title>
        <style>body{text-align:center;font-family:Arial;padding:40px;}h2{margin:0;color:#2e1731;}p{color:#666;}</style>
        </head><body><h2>${name}</h2><p>${studentNo}</p><div id="qr" style="display:flex;justify-content:center;margin-top:20px;"></div>
        <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"><\/script>
        <script>new QRCode(document.getElementById("qr"),{text:"${data}",width:250,height:250,colorDark:"#2e1731"});setTimeout(()=>window.print(),800);<\/script></body></html>`);
}

function printCurrentQR() { printQR(currentQRData.data, currentQRData.name, currentQRData.studentNo); }

function downloadCurrentQR() {
    const canvas = document.querySelector("#qrDisplay canvas");
    if (canvas) {
        const a = document.createElement("a");
        a.href = canvas.toDataURL("image/png");
        a.download = `QR_${currentQRData.studentNo}.png`;
        a.click();
    }
}
</script>';
require_once __DIR__ . '/../includes/footer.php'; 
?>
