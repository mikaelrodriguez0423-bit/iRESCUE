<?php
require_once '../Core/config.php';
require_once '../Core/log_activity.php';

$token = sanitize($_GET['token'] ?? '');
$error = $success = '';

if (!$token) {
    header('Location: ../Auth/login.php'); exit;
}

$stmt = $mysqli->prepare("SELECT id, username, is_verified FROM users WHERE verify_token = ? LIMIT 1");
$stmt->bind_param('s', $token);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    $error = 'Invalid verification link. It may have already been used.';
} elseif ($user['is_verified']) {
    $success = 'Your account is already verified. <a href="../Auth/login.php">Log in &rarr;</a>';
} else {
    $stmt = $mysqli->prepare("UPDATE users SET is_verified = 1, verify_token = NULL WHERE id = ?");
    $stmt->bind_param('i', $user['id']);
    if ($stmt->execute()) {
        $stmt->close();
        log_activity($mysqli, $user['id'], $user['username'], 'EMAIL_VERIFIED', 'Account email verified.');
        $success = "Your account has been verified, <strong>{$user['username']}</strong>! <a href='../Auth/login.php'>Log in now &rarr;</a>";
    } else {
        $error = 'Verification failed. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification — iRescue</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap">
    <link rel="stylesheet" href="../Assets/style.css">
</head>
<body class="auth-page">
<div class="auth-card" style="text-align:center;">
    <div class="auth-logo">
        <div class="auth-logo-icon"><?php echo $error ? '❌' : '✅'; ?></div>
        <h1><?php echo $error ? 'Verification Failed' : 'Account Verified!'; ?></h1>
    </div>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php else: ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
</div>
</body>
</html>