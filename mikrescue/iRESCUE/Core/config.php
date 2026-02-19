<?php
// =============================================
// iRescue v2.0 - Core Configuration
// =============================================

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => true,
]);

// --- Database ---
define('DB_SERVER',   'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME',     'irescue_db');

// --- App Settings ---
define('APP_NAME',    'iRescue');
define('APP_URL',     'http://localhost/irescue');
define('APP_VERSION', '2.0.0');

// --- Email (Gmail SMTP) ---
define('SMTP_HOST',     'smtp.gmail.com');
define('SMTP_PORT',     587);
define('SMTP_USERNAME', 'your_email@gmail.com');   // <-- Replace
define('SMTP_PASSWORD', 'your_app_password');       // <-- Replace (App Password)
define('SMTP_FROM',     'your_email@gmail.com');
define('SMTP_FROM_NAME', 'iRescue Emergency System');

// --- Security ---
define('BCRYPT_COST',       12);
define('TOKEN_EXPIRY_HOURS', 2);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_MINUTES',    1);
define('SESSION_LIFETIME',   3600); // 1 hour

// --- File Upload ---
define('UPLOAD_DIR',      'uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_TYPES',   ['image/jpeg', 'image/png', 'image/webp']);

// --- Database Connection ---
$mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($mysqli->connect_errno) {
    http_response_code(500);
    die(json_encode(['error' => 'Database connection failed.']));
}
$mysqli->set_charset('utf8mb4');

// =============================================
// CSRF Protection
// =============================================
function csrf_generate(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(): bool {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_generate()) . '">';
}

// =============================================
// Role Helpers
// =============================================
function is_logged_in(): bool {
    return isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
}

function is_admin(): bool {
    return is_logged_in() && $_SESSION['role'] === 'admin';
}

function is_responder(): bool {
    return is_logged_in() && in_array($_SESSION['role'], ['admin', 'responder']);
}

function is_user(): bool {
    return is_logged_in() && $_SESSION['role'] === 'user';
}

function require_login(string $redirect = '../Auth/login.php'): void {
    if (!is_logged_in()) {
        header("Location: $redirect"); exit;
    }
}

function require_admin(): void {
    if (!is_admin()) {
        header("Location: ../Assets/index.php"); exit;
    }
}

function require_responder(): void {
    if (!is_responder()) {
        header("Location: ../Assets/index.php"); exit;
    }
}

// =============================================
// Rate Limiting
// =============================================
function check_rate_limit(mysqli $db, string $ip, string $username): bool {
    // Clean old attempts
    $db->query("DELETE FROM login_attempts WHERE attempted_at < NOW() - INTERVAL " . LOCKOUT_MINUTES . " MINUTE");

    $stmt = $db->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempted_at > NOW() - INTERVAL " . LOCKOUT_MINUTES . " MINUTE");
    $stmt->bind_param('s', $ip);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return $count < MAX_LOGIN_ATTEMPTS;
}

function record_login_attempt(mysqli $db, string $ip, string $username): void {
    $stmt = $db->prepare("INSERT INTO login_attempts (ip_address, username) VALUES (?, ?)");
    $stmt->bind_param('ss', $ip, $username);
    $stmt->execute();
    $stmt->close();
}

function remaining_attempts(mysqli $db, string $ip): int {
    $stmt = $db->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempted_at > NOW() - INTERVAL " . LOCKOUT_MINUTES . " MINUTE");
    $stmt->bind_param('s', $ip);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return max(0, MAX_LOGIN_ATTEMPTS - $count);
}

// =============================================
// Email (PHPMailer-compatible wrapper)
// If PHPMailer is not installed, falls back to mail()
// =============================================
function send_email(string $to, string $subject, string $html_body): bool {
    // Try PHPMailer if available
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = SMTP_PORT;
            $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = email_template($subject, $html_body);
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Email error: " . $e->getMessage());
            return false;
        }
    }
    // Fallback: PHP mail()
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">\r\n";
    return @mail($to, $subject, email_template($subject, $html_body), $headers);
}

function email_template(string $title, string $content): string {
    return "<!DOCTYPE html><html><head><meta charset='UTF-8'><style>
        body{font-family:Arial,sans-serif;background:#f4f6f9;margin:0;padding:20px;}
        .wrap{max-width:600px;margin:0 auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,.1);}
        .head{background:#e63946;color:#fff;padding:24px 32px;}
        .head h1{margin:0;font-size:20px;}
        .body{padding:32px;}
        .btn{display:inline-block;background:#e63946;color:#fff;padding:12px 28px;border-radius:50px;text-decoration:none;font-weight:700;margin:16px 0;}
        .foot{background:#f4f6f9;padding:16px 32px;font-size:12px;color:#6b7280;text-align:center;}
    </style></head><body>
    <div class='wrap'>
        <div class='head'><h1>🚨 " . APP_NAME . "</h1></div>
        <div class='body'>{$content}</div>
        <div class='foot'>&copy; " . date('Y') . " " . APP_NAME . " · Emergency Response System</div>
    </div></body></html>";
}

// =============================================
// Sanitization Helpers
// =============================================
function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validate_email(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// =============================================
// Session Regeneration (anti-fixation)
// =============================================
if (is_logged_in()) {
    // Regenerate every 30 min
    if (!isset($_SESSION['last_regen']) || time() - $_SESSION['last_regen'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['last_regen'] = time();
    }
    // Absolute timeout
    if (isset($_SESSION['login_time']) && time() - $_SESSION['login_time'] > SESSION_LIFETIME) {
        session_unset();
        session_destroy();
        header('Location: ../Auth/login.php?expired=1');
        exit;
    }
}
?>