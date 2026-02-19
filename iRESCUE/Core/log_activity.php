<?php
/**
 * iRescue Activity Logger
 */
function log_activity(mysqli $db, ?int $user_id, string $username, string $action, string $details = ''): void {
    $ip   = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmt = $db->prepare("INSERT INTO activity_logs (user_id, username, action, details, ip_address) VALUES (?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param('issss', $user_id, $username, $action, $details, $ip);
        $stmt->execute();
        $stmt->close();
    }
}
?>