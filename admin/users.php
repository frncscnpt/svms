<?php
/**
 * SVMS - Admin: User Management
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pdo = getDBConnection();

// Handle delete (before HTML output)
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    if ($_GET['delete'] != $_SESSION['user_id']) {
        $stmt = $pdo->prepare("UPDATE users SET status='inactive' WHERE id=?");
        $stmt->execute([$_GET['delete']]);
        setFlash('success', 'User deactivated.');
    } else {
        setFlash('danger', 'Cannot delete your own account.');
    }
    header('Location: ' . BASE_PATH . '/admin/users.php');
    exit;
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $username = sanitize($_POST['username']);
    $fullName = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email'] ?? '');
    $role = $_POST['role'];
    
    try {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE users SET username=?, full_name=?, email=?, role=? WHERE id=?");
            $stmt->execute([$username, $fullName, $email, $role, $id]);
            if (!empty($_POST['password'])) {
                $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($_POST['password'], PASSWORD_DEFAULT), $id]);
            }
            setFlash('success', 'User updated.');
        } else {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, email, role) VALUES (?,?,?,?,?)");
            $stmt->execute([$username, $password, $fullName, $email, $role]);
            setFlash('success', 'User created.');
        }
    } catch (PDOException $e) {
        setFlash('danger', $e->getCode() == 23000 ? 'Username already exists.' : 'Error: ' . $e->getMessage());
    }
    header('Location: ' . BASE_PATH . '/admin/users.php');
    exit;
}

// Now include header (HTML output starts here)
$pageTitle = 'Users';
$breadcrumbs = ['Dashboard' => '/admin/dashboard.php', 'Users' => null];
require_once __DIR__ . '/../includes/header.php';

$search = $_GET['search'] ?? '';
$role = $_GET['role'] ?? '';
$where = "WHERE status='active' AND role != 'student'";
$params = [];
if ($search) { $where .= " AND (full_name LIKE ? OR username LIKE ? OR email LIKE ?)"; $params = array_merge($params, ["%$search%","%$search%","%$search%"]); }
if ($role) { $where .= " AND role=?"; $params[] = $role; }

$perPage = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users $where");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("SELECT * FROM users $where ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params, [$perPage, $offset]));
$users = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <p class="text-muted mb-0" style="font-size:13px;">Manage system users (Admin, Discipline Officers, Teachers)</p>
</div>

<div class="card-panel">
    <form class="filter-bar d-flex justify-content-between align-items-center" method="GET">
        <div class="d-flex align-items-center gap-2 flex-grow-1">
            <div class="search-box">
                <i class="bi bi-search"></i>
                <input type="text" name="search" placeholder="Search by name, username or email..." value="<?= sanitize($search) ?>">
            </div>
            <select name="role" class="form-select" style="width:auto">
                <option value="">All Roles</option>
                <option value="admin" <?= $role==='admin'?'selected':'' ?>>Administrator</option>
                <option value="discipline_officer" <?= $role==='discipline_officer'?'selected':'' ?>>Discipline Officer</option>
                <option value="teacher" <?= $role==='teacher'?'selected':'' ?>>Teacher</option>
            </select>
            <button type="submit" class="btn-primary-custom"><i class="bi bi-funnel"></i> Filter</button>
            <?php if ($search || $role): ?><a href="<?= BASE_PATH ?>/admin/users.php" class="btn btn-outline-secondary btn-sm">Clear</a><?php endif; ?>
        </div>
        <button type="button" class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#userModal" onclick="resetUserForm()" style="white-space:nowrap;">
            <i class="bi bi-plus-lg"></i> Add User
        </button>
    </form>
    <div class="data-table-wrapper">
        <table class="data-table">
            <thead>
                <tr><th>User</th><th>Username</th><th>Role</th><th>Last Login</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td>
                        <div class="user-cell">
                            <?= getAvatarHtml($u['avatar'], $u['full_name'], 'user-avatar') ?>
                            <div class="user-info">
                                <div class="name"><?= sanitize($u['full_name']) ?></div>
                                <div class="sub"><?= sanitize($u['email'] ?? '') ?></div>
                            </div>
                        </div>
                    </td>
                    <td><code><?= sanitize($u['username']) ?></code></td>
                    <td><span class="badge bg-primary-custom"><?= ucwords(str_replace('_', ' ', $u['role'])) ?></span></td>
                    <td><small class="text-muted"><?= $u['last_login'] ? formatDateTime($u['last_login']) : 'Never' ?></small></td>
                    <td>
                        <div class="dropdown">
                            <button class="action-btn" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="javascript:void(0)" onclick='editUser(<?= json_encode($u) ?>)'><i class="bi bi-pencil"></i> Edit</a></li>
                                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="javascript:void(0)" onclick="confirmDelete('/admin/users.php?delete=<?= $u['id'] ?>','<?= sanitize($u['full_name']) ?>')"><i class="bi bi-trash"></i> Delete</a></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?><tr><td colspan="5" class="text-center text-muted py-4">No users found</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($totalPages > 1): ?>
    <?php
    $baseUrl = strtok($_SERVER['REQUEST_URI'], '?');
    $qp = array_filter(['search' => $search, 'role' => $role]);
    ?>
    <div class="panel-footer">
        <nav><ul class="pagination justify-content-center mb-0">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                <a class="page-link" href="<?= $baseUrl ?>?<?= http_build_query(array_merge($qp, ['page' => $i]), '', '&') ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>
        </ul></nav>
    </div>
    <?php endif; ?>
</div>

<!-- User Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userModalTitle">Add User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="userForm">
                <input type="hidden" name="id" id="userId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Full Name *</label>
                        <input type="text" class="form-control" name="full_name" id="uFullName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username *</label>
                        <input type="text" class="form-control" name="username" id="uUsername" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" id="uEmail">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role *</label>
                        <select class="form-select" name="role" id="uRole" required>
                            <option value="admin">Administrator</option>
                            <option value="discipline_officer">Discipline Officer</option>
                            <option value="teacher">Teacher</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" id="pwLabel">Password *</label>
                        <input type="password" class="form-control" name="password" id="uPassword" minlength="6">
                        <div class="form-text" id="pwHint" style="display:none;">Leave blank to keep current password</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-primary-custom">Save User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function resetUserForm() {
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = '';
    document.getElementById('userModalTitle').textContent = 'Add User';
    document.getElementById('uPassword').required = true;
    document.getElementById('pwLabel').textContent = 'Password *';
    document.getElementById('pwHint').style.display = 'none';
}

function editUser(u) {
    document.getElementById('userId').value = u.id;
    document.getElementById('userModalTitle').textContent = 'Edit User';
    document.getElementById('uFullName').value = u.full_name;
    document.getElementById('uUsername').value = u.username;
    document.getElementById('uEmail').value = u.email || '';
    document.getElementById('uRole').value = u.role;
    document.getElementById('uPassword').required = false;
    document.getElementById('uPassword').value = '';
    document.getElementById('pwLabel').textContent = 'Password';
    document.getElementById('pwHint').style.display = 'block';
    new bootstrap.Modal(document.getElementById('userModal')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
