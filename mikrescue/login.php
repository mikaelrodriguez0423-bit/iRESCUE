<?php
require_once 'config.php';
require_once 'log_activity.php';

if (is_logged_in()) { header('Location: index.php'); exit; }

$error = $success = '';
$ip    = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

if (isset($_GET['expired'])) {
    $success = 'Your session has expired. Please log in again.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Security token mismatch. Please refresh and try again.';
    } else {
        $username = sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Username and password are required.';
        } elseif (!check_rate_limit($mysqli, $ip, $username)) {
            $error = 'Too many failed attempts. Please wait ' . LOCKOUT_MINUTES . ' minutes and try again.';
        } else {
            $stmt = $mysqli->prepare("SELECT id, username, password, role, is_verified, full_name FROM users WHERE username = ? LIMIT 1");
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($user && password_verify($password, $user['password'])) {
                if (!$user['is_verified']) {
                    $error = 'Your account is not verified yet. Please check your email.';
                } else {
                    session_regenerate_id(true);
                    $_SESSION['loggedin']    = true;
                    $_SESSION['id']          = $user['id'];
                    $_SESSION['username']    = $user['username'];
                    $_SESSION['role']        = $user['role'];
                    $_SESSION['full_name']   = $user['full_name'];
                    $_SESSION['login_time']  = time();
                    $_SESSION['last_regen']  = time();

                    // Update last login
                    $mysqli->query("UPDATE users SET last_login = NOW() WHERE id = {$user['id']}");

                    log_activity($mysqli, $user['id'], $user['username'], 'USER_LOGIN', "Login from IP: $ip");
                    header('Location: index.php'); exit;
                }
            } else {
                record_login_attempt($mysqli, $ip, $username);
                $left  = remaining_attempts($mysqli, $ip);
                $error = "Invalid username or password." . ($left <= 2 ? " <strong>{$left} attempt(s) remaining.</strong>" : '');
                log_activity($mysqli, null, $username, 'LOGIN_FAILED', "Failed login from IP: $ip");
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
    <title>Login — iRescue</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap">
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">

<div id="toast-container"></div>

<div class="auth-card">
    <div class="auth-logo">
        <div class="auth-logo-icon">🚨</div>
        <h1>iRescue</h1>
        <p>Emergency Response System</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span><?php echo $error; ?></span>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-warning">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span><?php echo $success; ?></span>
        </div>
    <?php endif; ?>

    <form id="login-form" method="POST" action="login.php" novalidate>
        <?php echo csrf_field(); ?>

        <div class="form-group">
            <label class="form-label" for="username">Username</label>
            <input type="text" id="username" name="username" class="form-control"
                   placeholder="Enter your username" autocomplete="username"
                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
        </div>

        <div class="form-group">
            <label class="form-label" for="password">Password</label>
            <input type="password" id="password" name="password" class="form-control"
                   placeholder="••••••••" autocomplete="current-password" required>
            <a href="forgot_password.php" class="form-hint" style="color:var(--red);float:right">Forgot password?</a>
        </div>

        <div class="form-group" style="margin-top: 8px;">
            <button type="submit" class="btn btn-primary btn-full btn-lg">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                Sign In
            </button>
        </div>
    </form>

    <div class="auth-footer-link">
        Don't have an account? <a href="register.php">Create one here</a>
    </div>
</div>

<script src="script.js"></script>
</body>
</html>