<?php
require_once 'config.php';
require_once 'log_activity.php';

if (is_logged_in()) { header('Location: index.php'); exit; }

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Security token mismatch. Please refresh.';
    } else {
        $username = sanitize($_POST['username'] ?? '');
        $email    = sanitize($_POST['email'] ?? '');
        $fullname = sanitize($_POST['full_name'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        // Validation
        if (empty($username) || empty($email) || empty($password) || empty($confirm)) {
            $error = 'All fields are required.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
            $error = 'Username must be 3–50 characters (letters, numbers, underscores only).';
        } elseif (!validate_email($email)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $error = 'Password must contain at least one uppercase letter and one number.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            // Check duplicates
            $stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->bind_param('ss', $username, $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $error = 'Username or email already taken.';
                $stmt->close();
            } else {
                $stmt->close();
                $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
                $token  = bin2hex(random_bytes(32));

                $stmt = $mysqli->prepare("INSERT INTO users (username, email, password, full_name, role, is_verified) VALUES (?, ?, ?, ?, 'user', 1)");
                $stmt->bind_param('ssss', $username, $email, $hashed, $fullname);

                if ($stmt->execute()) {
                    $new_id = $stmt->insert_id;
                    $stmt->close();
                    log_activity($mysqli, $new_id, $username, 'USER_REGISTERED', "New user registered: $email");


                    $success = "Account created! Please check <strong>{$email}</strong> for a verification link.";
                } else {
                    $error = 'Registration failed. Please try again.';
                }
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
    <title>Register — iRescue</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap">
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">
<div id="toast-container"></div>

<div class="auth-card" style="max-width: 460px;">
    <div class="auth-logo">
        <div class="auth-logo-icon">🚨</div>
        <h1>Create Account</h1>
        <p>Join iRescue Emergency Response</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span><?php echo $error; ?></span>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span><?php echo $success; ?></span>
        </div>
    <?php else: ?>

    <form id="register-form" method="POST" action="register.php" novalidate>
        <?php echo csrf_field(); ?>

        <div class="form-group">
            <label class="form-label" for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name" class="form-control"
                   placeholder="Juan dela Cruz" required
                   value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label class="form-label" for="username">Username <span class="required">*</span></label>
            <input type="text" id="username" name="username" class="form-control"
                   placeholder="juandelacruz" autocomplete="username" required
                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            <span class="form-hint">Letters, numbers and underscores only (3–50 chars)</span>
        </div>

        <div class="form-group">
            <label class="form-label" for="email">Email Address <span class="required">*</span></label>
            <input type="email" id="email" name="email" class="form-control"
                   placeholder="you@example.com" autocomplete="email" required
                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label class="form-label" for="password">Password <span class="required">*</span></label>
            <input type="password" id="password" name="password" class="form-control"
                   placeholder="Min. 8 characters" autocomplete="new-password" required>
            <div class="strength-bar-wrap"><div class="strength-bar" id="strength-bar"></div></div>
            <span class="strength-label" id="strength-label"></span>
        </div>

        <div class="form-group">
            <label class="form-label" for="confirm_password">Confirm Password <span class="required">*</span></label>
            <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                   placeholder="Re-enter your password" autocomplete="new-password" required>
        </div>

        <div class="form-group" style="margin-top: 8px;">
            <button type="submit" class="btn btn-primary btn-full btn-lg">Create Account</button>
        </div>
    </form>

    <?php endif; ?>

    <div class="auth-footer-link">
        Already have an account? <a href="login.php">Sign in here</a>
    </div>
</div>

<script src="script.js"></script>
</body>
</html>