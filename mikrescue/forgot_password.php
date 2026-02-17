<?php
require_once 'config.php';
require_once 'log_activity.php';

if (is_logged_in()) { header('Location: index.php'); exit; }

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { $error = 'Security token mismatch.'; }
    else {
        $email = sanitize($_POST['email'] ?? '');
        if (!validate_email($email)) {
            $error = 'Please enter a valid email address.';
        } else {
            $stmt = $mysqli->prepare("SELECT id, username FROM users WHERE email = ? LIMIT 1");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            // Always show success to prevent user enumeration
            $success = "If that email is registered, a password reset link has been sent.";

            if ($user) {
                $token  = bin2hex(random_bytes(32));
                $expiry = date('Y-m-d H:i:s', strtotime('+' . TOKEN_EXPIRY_HOURS . ' hours'));
                $stmt   = $mysqli->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?");
                $stmt->bind_param('ssi', $token, $expiry, $user['id']);
                $stmt->execute();
                $stmt->close();

                log_activity($mysqli, $user['id'], $user['username'], 'PASSWORD_RESET_REQUEST', "Reset requested for: $email");

                $reset_link = APP_URL . "/reset_password.php?token=" . urlencode($token);
                $body = "
                    <h2>Reset Your Password</h2>
                    <p>Hi {$user['username']},</p>
                    <p>We received a request to reset your iRescue password. Click below to set a new password.</p>
                    <p style='text-align:center'><a href='{$reset_link}' class='btn'>Reset Password</a></p>
                    <p><small>This link expires in " . TOKEN_EXPIRY_HOURS . " hours. If you did not request this, ignore this email.</small></p>
                ";
                send_email($email, 'Reset Your iRescue Password', $body);
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
    <title>Forgot Password — iRescue</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap">
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">
<div class="auth-card">
    <div class="auth-logo">
        <div class="auth-logo-icon">🔑</div>
        <h1>Forgot Password</h1>
        <p>We'll email you a reset link</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><span><?php echo $error; ?></span></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><span><?php echo $success; ?></span></div>
    <?php else: ?>

    <form method="POST" action="forgot_password.php">
        <?php echo csrf_field(); ?>
        <div class="form-group">
            <label class="form-label" for="email">Email Address</label>
            <input type="email" id="email" name="email" class="form-control"
                   placeholder="your@email.com" required>
        </div>
        <button type="submit" class="btn btn-primary btn-full">Send Reset Link</button>
    </form>

    <?php endif; ?>

    <div class="auth-footer-link" style="margin-top:16px;">
        <a href="login.php">&larr; Back to Login</a>
    </div>
</div>
<script src="script.js"></script>
</body>
</html>