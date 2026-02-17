<?php
require_once 'config.php';
require_once 'log_activity.php';
require_admin();

$action = sanitize($_GET['action'] ?? '');
$error  = '';

// Toggle role
if ($action === 'setrole' && isset($_GET['id']) && isset($_GET['role'])) {
    $id      = (int)$_GET['id'];
    $role    = sanitize($_GET['role']);
    $allowed = ['user', 'responder', 'admin'];
    if ($id !== (int)$_SESSION['id'] && in_array($role, $allowed)) {
        $stmt = $mysqli->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->bind_param('si', $role, $id);
        $stmt->execute();
        $stmt->close();
        log_activity($mysqli, $_SESSION['id'], $_SESSION['username'], 'ROLE_CHANGED', "User #{$id} set to {$role}");
    }
    header('Location: users.php?toast=success&msg=Role+updated.'); exit;
}

// Toggle verified
if ($action === 'verify' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $mysqli->query("UPDATE users SET is_verified = 1 WHERE id = $id");
    log_activity($mysqli, $_SESSION['id'], $_SESSION['username'], 'USER_VERIFIED', "Manually verified user #{$id}");
    header('Location: users.php?toast=success&msg=User+verified.'); exit;
}

// Delete user
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($id !== (int)$_SESSION['id']) {
        $uname = $mysqli->query("SELECT username FROM users WHERE id = $id")->fetch_row()[0] ?? '';
        $mysqli->query("DELETE FROM users WHERE id = $id");
        log_activity($mysqli, $_SESSION['id'], $_SESSION['username'], 'USER_DELETED', "Deleted: {$uname}");
    }
    header('Location: users.php?toast=success&msg=User+deleted.'); exit;
}

$users_q = $mysqli->query("SELECT id, username, email, full_name, role, is_verified, last_login, created_at FROM users ORDER BY created_at DESC");

$toast_type = sanitize($_GET['toast'] ?? '');
$toast_msg  = sanitize($_GET['msg']   ?? '');

require_once 'header.php';
?>
<?php if ($toast_type && $toast_msg): ?>
<div data-toast="<?php echo $toast_type; ?>" data-message="<?php echo $toast_msg; ?>"></div>
<?php endif; ?>

<div class="page-header">
    <div class="page-header-left">
        <h1>User Management</h1>
        <p>View, verify, and manage all system users.</p>
    </div>
</div>

<div class="card">
    <div class="table-toolbar">
        <div class="search-input-wrap">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" class="search-input" placeholder="Search users…" data-table="users-table" data-search>
        </div>
        <select class="filter-select" data-table="users-table" data-filter-col="3">
            <option value="">All Roles</option>
            <option value="admin">Admin</option>
            <option value="responder">Responder</option>
            <option value="user">User</option>
        </select>
    </div>
    <div style="overflow-x:auto;">
        <table id="users-table">
            <thead>
                <tr>
                    <th data-sort="0">Username</th>
                    <th data-sort="1">Full Name</th>
                    <th data-sort="2">Email</th>
                    <th data-sort="3">Role</th>
                    <th>Verified</th>
                    <th data-sort="5">Last Login</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($u = $users_q->fetch_assoc()): ?>
            <tr>
                <td class="fw-600"><?php echo htmlspecialchars($u['username']); ?></td>
                <td><?php echo htmlspecialchars($u['full_name'] ?: '—'); ?></td>
                <td class="text-sm"><?php echo htmlspecialchars($u['email']); ?></td>
                <td><span class="badge badge-<?php echo $u['role']; ?>"><?php echo ucfirst($u['role']); ?></span></td>
                <td>
                    <?php if ($u['is_verified']): ?>
                        <span class="badge badge-resolved">✓ Verified</span>
                    <?php else: ?>
                        <a href="users.php?action=verify&id=<?php echo $u['id']; ?>" class="badge badge-pending" style="cursor:pointer;">✗ Unverified</a>
                    <?php endif; ?>
                </td>
                <td class="text-sm text-muted">
                    <?php echo $u['last_login'] ? date('M j, Y', strtotime($u['last_login'])) : 'Never'; ?>
                </td>
                <td class="actions" style="display:flex;gap:6px;flex-wrap:wrap;">
                    <?php if ($u['id'] != $_SESSION['id']): ?>
                    <select class="filter-select" style="font-size:.75rem;" onchange="location='users.php?action=setrole&id=<?php echo $u['id']; ?>&role='+this.value">
                        <option value="">Set Role</option>
                        <option value="user">User</option>
                        <option value="responder">Responder</option>
                        <option value="admin">Admin</option>
                    </select>
                    <a href="users.php?action=delete&id=<?php echo $u['id']; ?>" class="btn btn-sm btn-danger btn-delete">Delete</a>
                    <?php else: ?>
                    <span class="text-muted text-sm">(You)</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'footer.php'; ?>