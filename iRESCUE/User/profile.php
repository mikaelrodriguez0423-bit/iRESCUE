<?php
require_once '../Core/config.php';
require_once '../Core/log_activity.php';
require_login();

$error = $success = '';
$user_q = $mysqli->prepare("SELECT id, username, email, full_name, role, is_verified, last_login, created_at FROM users WHERE id = ?");
$user_q->bind_param('i', $_SESSION['id']);
$user_q->execute();
$user = $user_q->get_result()->fetch_assoc();
$user_q->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $full_name = sanitize($_POST['full_name'] ?? '');
    $email     = sanitize($_POST['email']     ?? '');
    $cur_pass  = $_POST['current_password']   ?? '';
    $new_pass  = $_POST['new_password']       ?? '';

    if (!validate_email($email)) {
        $error = 'Invalid email address.';
    } else {
        $updates = "full_name = ?, email = ?";
        $params  = [$full_name, $email];
        $types   = 'ss';

        // Password change
        if (!empty($new_pass)) {
            if (strlen($new_pass) < 8) {
                $error = 'New password must be at least 8 characters.';
            } else {
                // Verify current
                $ph = $mysqli->query("SELECT password FROM users WHERE id = {$user['id']}")->fetch_row()[0];
                if (!password_verify($cur_pass, $ph)) {
                    $error = 'Current password is incorrect.';
                } else {
                    $hashed = password_hash($new_pass, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
                    $updates .= ", password = ?";
                    $params[]  = $hashed;
                    $types    .= 's';
                }
            }
        }

        if (!$error) {
            $updates .= " WHERE id = ?";
            $params[]  = $user['id'];
            $types    .= 'i';
            $stmt      = $mysqli->prepare("UPDATE users SET $updates");
            $stmt->bind_param($types, ...$params);
            if ($stmt->execute()) {
                $_SESSION['full_name'] = $full_name;
                log_activity($mysqli, $user['id'], $user['username'], 'PROFILE_UPDATED', 'Profile updated.');
                $success = 'Profile updated successfully.';
                // Refresh user data
                $user['full_name'] = $full_name;
                $user['email']     = $email;
            } else {
                $error = 'Update failed. Email may already be in use.';
            }
            $stmt->close();
        }
    }
}

require_once '../Assets/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>My Profile</h1>
        <p>Manage your account settings</p>
    </div>
</div>

<div class="card" style="max-width: 560px;">
    <div class="card-header">
        <div class="card-title">Account Information</div>
        <span class="badge badge-<?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span>
    </div>
    <div class="card-body">
        <?php if ($error):   ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>

        <form method="POST">
            <?php echo csrf_field(); ?>
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                <span class="form-hint">Username cannot be changed.</span>
            </div>
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-control"
                       value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control"
                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>

            <div style="border-top: 1px solid var(--border); padding-top: 18px; margin-top: 8px;">
                <p class="fw-600" style="margin-bottom:12px;">Change Password</p>
                <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-control" placeholder="Leave blank to keep current">
                </div>
                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" id="password" name="new_password" class="form-control" placeholder="Min. 8 characters">
                    <div class="strength-bar-wrap"><div class="strength-bar" id="strength-bar"></div></div>
                    <span class="strength-label" id="strength-label"></span>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
    </div>
    <div class="card-footer">
        <small class="text-muted">
            Member since <?php echo date('F Y', strtotime($user['created_at'])); ?> &bull;
            Last login: <?php echo $user['last_login'] ? date('M j, Y g:ia', strtotime($user['last_login'])) : 'N/A'; ?>
        </small>
    </div>
</div>

<?php require_once '../Assets/footer.php'; ?>