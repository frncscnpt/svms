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

$users = $pdo->query("SELECT * FROM users WHERE status='active' AND role != 'student' ORDER BY created_at DESC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <p class="text-muted mb-0" style="font-size:13px;">Manage system users (Admin, Discipline Officers, Teachers)</p>
    <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#userModal" onclick="resetUserForm()">
        <i class="bi bi-plus-lg"></i> Add User
    </button>
</div>

<div class="card-panel">
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
                        <div class="action-btns">
                            <button class="action-btn" title="Edit" onclick='editUser(<?= json_encode($u) ?>)'><i class="bi bi-pencil"></i></button>
                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                            <a href="javascript:void(0)" class="action-btn btn-danger" onclick="confirmDelete('/admin/users.php?delete=<?= $u['id'] ?>','<?= sanitize($u['full_name']) ?>')"><i class="bi bi-trash"></i></a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
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
