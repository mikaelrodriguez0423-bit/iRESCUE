<?php
require_once '../Core/config.php';
require_once '../Core/log_activity.php';

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

// Must be logged in
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// CSRF check for mutating operations
$action = sanitize($_REQUEST['action'] ?? '');

function json_response(bool $success, string $message = '', array $data = []): void {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

// =============================================
// GET: Live stats
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'stats') {
    global $mysqli;
    $stats = [];
    $r = $mysqli->query("SELECT status, COUNT(*) as c FROM reports GROUP BY status");
    while ($row = $r->fetch_assoc()) {
        $stats[strtolower($row['status'])] = (int)$row['c'];
    }
    $stats['total'] = array_sum($stats);
    json_response(true, '', $stats);
}

// =============================================
// POST: Update report status
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update_status') {
    if (!csrf_verify()) {
        json_response(false, 'CSRF token mismatch.');
    }
    if (!is_responder()) {
        http_response_code(403);
        json_response(false, 'Forbidden.');
    }

    $report_id  = (int)($_POST['report_id'] ?? 0);
    $new_status = sanitize($_POST['status'] ?? '');
    $allowed    = ['Pending', 'Responding', 'Resolved', 'Closed'];

    if (!$report_id || !in_array($new_status, $allowed)) {
        json_response(false, 'Invalid parameters.');
    }

    // Get old status
    $r   = $mysqli->query("SELECT status FROM reports WHERE id = {$report_id}");
    $old = $r ? $r->fetch_row()[0] : '';

    $resolved = $new_status === 'Resolved' ? ", resolved_at = NOW()" : '';
    $stmt     = $mysqli->prepare("UPDATE reports SET status = ? {$resolved} WHERE id = ?");
    $stmt->bind_param('si', $new_status, $report_id);

    if ($stmt->execute()) {
        // History
        $hist = $mysqli->prepare("INSERT INTO incident_history (report_id, changed_by, old_status, new_status) VALUES (?,?,?,?)");
        $hist->bind_param('iiss', $report_id, $_SESSION['id'], $old, $new_status);
        $hist->execute();
        $hist->close();

        log_activity($mysqli, $_SESSION['id'], $_SESSION['username'], 'STATUS_UPDATED',
            "Report #{$report_id}: {$old} → {$new_status}");

        json_response(true, "Status updated to {$new_status}.");
    } else {
        json_response(false, 'Database error. Please try again.');
    }
}

// =============================================
// Fallthrough
// =============================================
http_response_code(400);
json_response(false, 'Unknown action.');