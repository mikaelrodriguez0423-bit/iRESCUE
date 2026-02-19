<?php
require_once '../Core/config.php';
require_once '../Core/log_activity.php';
require_login();

if (!csrf_verify() && $_SERVER['REQUEST_METHOD'] === 'POST') {
    die('CSRF error');
}

$type   = sanitize($_GET['type']   ?? 'reports');
$format = sanitize($_GET['format'] ?? 'csv');

// Build dataset
if ($type === 'reports') {
    if (!is_responder()) { header('Location: ../Assets/index.php'); exit; }
    $result = $mysqli->query("SELECT r.id, r.category, r.severity, r.title, r.details, r.zone, r.address, r.status, r.timestamp, u.username as reporter
        FROM reports r JOIN users u ON r.user_id = u.id ORDER BY r.timestamp DESC");
    $filename = 'iRescue_Reports_' . date('Ymd');
    $headers  = ['ID', 'Category', 'Severity', 'Title', 'Details', 'Zone', 'Address', 'Status', 'Timestamp', 'Reporter'];
} elseif ($type === 'logs') {
    if (!is_admin()) { header('Location: ../Assets/index.php'); exit; }
    $result = $mysqli->query("SELECT id, timestamp, username, action, details, ip_address FROM activity_logs ORDER BY timestamp DESC");
    $filename = 'iRescue_ActivityLogs_' . date('Ymd');
    $headers  = ['ID', 'Timestamp', 'Username', 'Action', 'Details', 'IP'];
} else {
    header('Location: ../Assets/index.php'); exit;
}

log_activity($mysqli, $_SESSION['id'], $_SESSION['username'], 'EXPORT', "Exported {$type} as {$format}");

// --- CSV Export ---
if ($format === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    header('Cache-Control: no-cache, no-store, must-revalidate');

    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
    fputcsv($out, $headers);

    while ($row = $result->fetch_row()) {
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

// --- HTML print-friendly (PDF alternative without library) ---
if ($format === 'pdf') {
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8">
    <title>' . htmlspecialchars($filename) . '</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #e63946; color: #fff; padding: 8px; text-align: left; }
        td { padding: 6px 8px; border-bottom: 1px solid #eee; }
        h1 { font-size: 18px; }
        @media print { button { display: none; } }
    </style></head><body>
    <h1>' . htmlspecialchars($filename) . '</h1>
    <p>Generated: ' . date('F j, Y g:i a') . ' | By: ' . htmlspecialchars($_SESSION['username']) . '</p>
    <button onclick="window.print()">🖨 Print / Save as PDF</button>
    <table><thead><tr>';
    foreach ($headers as $h) echo '<th>' . htmlspecialchars($h) . '</th>';
    echo '</tr></thead><tbody>';
    while ($row = $result->fetch_row()) {
        echo '<tr>';
        foreach ($row as $cell) echo '<td>' . htmlspecialchars($cell ?? '') . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table></body></html>';
    exit;
}

header('Location: ../Assets/index.php');