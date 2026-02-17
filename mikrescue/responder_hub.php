<?php
require_once 'config.php';
require_once 'log_activity.php';
require_responder();

$msg_type = $msg = '';

// Handle status update via traditional POST (fallback)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_id']) && !isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
    if (csrf_verify()) {
        $report_id  = (int)$_POST['report_id'];
        $new_status = sanitize($_POST['status'] ?? '');
        $note       = sanitize($_POST['note'] ?? '');
        $allowed    = ['Pending', 'Responding', 'Resolved', 'Closed'];

        if (in_array($new_status, $allowed)) {
            // Get old status
            $r = $mysqli->query("SELECT status FROM reports WHERE id = {$report_id}");
            $old_status = $r->fetch_row()[0] ?? '';

            $upd = $mysqli->prepare("UPDATE reports SET status = ?" . ($new_status === 'Resolved' ? ", resolved_at = NOW()" : "") . " WHERE id = ?");
            $upd->bind_param('si', $new_status, $report_id);
            if ($upd->execute()) {
                // Write to history
                $hist = $mysqli->prepare("INSERT INTO incident_history (report_id, changed_by, old_status, new_status, note) VALUES (?,?,?,?,?)");
                $hist->bind_param('iisss', $report_id, $_SESSION['id'], $old_status, $new_status, $note);
                $hist->execute();
                $hist->close();
                log_activity($mysqli, $_SESSION['id'], $_SESSION['username'], 'STATUS_UPDATED', "Report #{$report_id}: {$old_status} → {$new_status}");
                $msg_type = 'success';
                $msg = "Report #{$report_id} updated to <strong>{$new_status}</strong>.";
            }
            $upd->close();
        }
    }
}

// Filters
$status_filter   = sanitize($_GET['status'] ?? '');
$category_filter = sanitize($_GET['category'] ?? '');
$where = [];
if ($status_filter)   $where[] = "r.status = '" . $mysqli->real_escape_string($status_filter) . "'";
if ($category_filter) $where[] = "r.category = '" . $mysqli->real_escape_string($category_filter) . "'";
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$reports_q = $mysqli->query("
    SELECT r.*, u.username as reporter, u2.username as assignee
    FROM reports r
    JOIN users u ON r.user_id = u.id
    LEFT JOIN users u2 ON r.assigned_to = u2.id
    {$where_sql}
    ORDER BY FIELD(r.status,'Pending','Responding','Resolved','Closed'), r.timestamp DESC
");

// Responder list for assignment
$responders_q = $mysqli->query("SELECT id, username, full_name FROM users WHERE role IN ('admin','responder') ORDER BY username");
$responders = [];
while ($resp = $responders_q->fetch_assoc()) $responders[] = $resp;

require_once 'header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Responder Hub</h1>
        <p>Manage all incoming emergency reports</p>
    </div>
    <div class="page-header-actions">
        <?php if (is_admin()): ?>
        <a href="post_alert.php" class="btn btn-primary">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
            Post Alert
        </a>
        <?php endif; ?>
        <a href="export.php?type=reports" class="btn btn-secondary">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Export
        </a>
    </div>
</div>

<?php if ($msg): ?>
<div class="alert alert-<?php echo $msg_type; ?> mb-4"><?php echo $msg; ?></div>
<?php endif; ?>

<div class="card">
    <!-- Toolbar -->
    <div class="table-toolbar">
        <div class="search-input-wrap">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" class="search-input" placeholder="Search reports…"
                   data-table="incidents-table" data-search>
        </div>
        <select class="filter-select" data-table="incidents-table" data-filter-col="0">
            <option value="">All Statuses</option>
            <option value="pending"   <?php echo $status_filter==='Pending'   ? 'selected':''; ?>>Pending</option>
            <option value="responding"<?php echo $status_filter==='Responding'? 'selected':''; ?>>Responding</option>
            <option value="resolved"  <?php echo $status_filter==='Resolved'  ? 'selected':''; ?>>Resolved</option>
            <option value="closed"    <?php echo $status_filter==='Closed'    ? 'selected':''; ?>>Closed</option>
        </select>
        <select class="filter-select" data-table="incidents-table" data-filter-col="2">
            <option value="">All Categories</option>
            <?php foreach (['Fire','Flood','Medical','Crime','Accident','Other'] as $cat): ?>
            <option value="<?php echo strtolower($cat); ?>"><?php echo $cat; ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Table -->
    <div style="overflow-x:auto;">
        <table id="incidents-table">
            <thead>
                <tr>
                    <th data-sort="0">Status</th>
                    <th data-sort="1">Severity</th>
                    <th data-sort="2">Category</th>
                    <th data-sort="3">Title / Details</th>
                    <th>Location</th>
                    <th>Reporter</th>
                    <th data-sort="6">Time</th>
                    <th>Update Status</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($reports_q && $reports_q->num_rows > 0): ?>
                <?php while ($r = $reports_q->fetch_assoc()): ?>
                <tr>
                    <td><span class="badge badge-<?php echo strtolower($r['status']); ?>"><?php echo $r['status']; ?></span></td>
                    <td><span class="badge badge-<?php echo strtolower($r['severity']); ?>"><?php echo $r['severity']; ?></span></td>
                    <td><?php echo htmlspecialchars($r['category']); ?></td>
                    <td>
                        <div class="fw-600 truncate" style="max-width:220px;" title="<?php echo htmlspecialchars($r['details']); ?>">
                            <?php echo htmlspecialchars($r['title'] ?: $r['category']); ?>
                        </div>
                        <div class="text-sm text-muted truncate" style="max-width:220px;">
                            <?php echo htmlspecialchars(substr($r['details'],0,60)); ?>…
                        </div>
                    </td>
                    <td class="text-sm"><?php echo $r['address'] ? htmlspecialchars($r['address']) : htmlspecialchars($r['zone']); ?></td>
                    <td class="text-sm"><?php echo htmlspecialchars($r['reporter']); ?></td>
                    <td class="text-sm text-muted" style="white-space:nowrap;">
                        <?php echo date('M j, g:ia', strtotime($r['timestamp'])); ?>
                    </td>
                    <td>
                        <select class="status-select" data-report-id="<?php echo $r['id']; ?>">
                            <?php foreach (['Pending','Responding','Resolved','Closed'] as $st): ?>
                            <option value="<?php echo $st; ?>" <?php echo $r['status']===$st?'selected':''; ?>><?php echo $st; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="8"><div class="empty-state"><div class="empty-icon">📋</div><h3>No Reports Found</h3></div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'footer.php'; ?>