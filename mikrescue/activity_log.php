<?php
require_once 'config.php';
require_admin();

$logs_q = $mysqli->query("SELECT * FROM activity_logs ORDER BY timestamp DESC LIMIT 500");

require_once 'header.php';
?>
<div class="page-header">
    <div class="page-header-left">
        <h1>System Activity Log</h1>
        <p>Full audit trail of all user actions.</p>
    </div>
    <div class="page-header-actions">
        <a href="export.php?type=logs" class="btn btn-secondary">Export CSV</a>
    </div>
</div>

<div class="card">
    <div class="table-toolbar">
        <div class="search-input-wrap">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" class="search-input" placeholder="Search logs…" data-table="logs-table" data-search>
        </div>
    </div>
    <div style="overflow-x:auto;">
        <table id="logs-table">
            <thead>
                <tr>
                    <th data-sort="0">Timestamp</th>
                    <th data-sort="1">User</th>
                    <th data-sort="2">Action</th>
                    <th>Details</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($logs_q && $logs_q->num_rows > 0): ?>
                <?php while ($log = $logs_q->fetch_assoc()): ?>
                <tr>
                    <td class="text-sm text-muted" style="white-space:nowrap;"><?php echo date('M j, Y g:ia', strtotime($log['timestamp'])); ?></td>
                    <td class="fw-600"><?php echo htmlspecialchars($log['username']); ?></td>
                    <td><code style="font-size:.78rem; background:var(--surface-2); padding:2px 6px; border-radius:4px;"><?php echo htmlspecialchars($log['action']); ?></code></td>
                    <td class="text-sm"><?php echo htmlspecialchars($log['details']); ?></td>
                    <td class="text-sm text-muted"><?php echo htmlspecialchars($log['ip_address'] ?? '—'); ?></td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5"><div class="empty-state"><div class="empty-icon">📋</div><h3>No logs yet</h3></div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'footer.php'; ?>