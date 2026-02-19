<?php
require_once '../Core/config.php';
require_once '../Core/log_activity.php';

if (is_logged_in()) { header('Location: ../Assets/index.php'); exit; }

$token = sanitize($_GET['token'] ?? '');
$error = $success = '';
$valid_token = false;
$user = null;

if ($token) {
    $stmt = $mysqli->prepare("SELECT id, username FROM users WHERE reset_token = ? AND reset_token_expiry > NOW() LIMIT 1");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $valid_token = (bool) $user;
}

if (!$valid_token && empty($error)) {
    $error = 'This reset link is invalid or has expired. Please request a new one.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    if (!csrf_verify()) { $error = 'Security token mismatch.'; }
    else {
        $pw = $_POST['password'] ?? '';
        $cp = $_POST['confirm_password'] ?? '';
        if (strlen($pw) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif ($pw !== $cp) {
            $error = 'Passwords do not match.';
        } else {
            $hashed = password_hash($pw, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
            $stmt = $mysqli->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?");
            $stmt->bind_param('si', $hashed, $user['id']);
            if ($stmt->execute()) {
                $stmt->close();
                log_activity($mysqli, $user['id'], $user['username'], 'PASSWORD_RESET_SUCCESS', 'Password successfully reset.');
                $success = 'Password updated! <a href="login.php">Log in now &rarr;</a>';
            } else {
                $error = 'Failed to update password. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — iRescue</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap">
    <link rel="stylesheet" href="../Assets/style.css">
</head>
<body class="auth-page">
<div class="auth-card">
    <div class="auth-logo">
        <div class="auth-logo-icon">🔒</div>
        <h1>Reset Password</h1>
        <p>Choose a new secure password</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
        <div class="auth-footer-link"><a href="../Auth/forgot_password.php">Request a new link &rarr;</a></div>
    <?php elseif ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php else: ?>

    <form id="register-form" method="POST">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
        <div class="form-group">
            <label class="form-label">New Password</label>
            <input type="password" id="password" name="password" class="form-control" placeholder="Min. 8 characters" required>
            <div class="strength-bar-wrap"><div class="strength-bar" id="strength-bar"></div></div>
            <span class="strength-label" id="strength-label"></span>
        </div>
        <div class="form-group">
            <label class="form-label">Confirm Password</label>
            <input type="password" name="confirm_password" class="form-control" placeholder="Re-enter password" required>
        </div>
        <button type="submit" class="btn btn-primary btn-full">Set New Password</button>
    </form>

    <?php endif; ?>
</div>
<script src="../Assets/script.js"></script>
</body>
</html>