<?php
require_once 'config.php';
require_login();

// ---- Stats ----
$stats = [];
$r = $mysqli->query("SELECT status, COUNT(*) as c FROM reports GROUP BY status");
while ($row = $r->fetch_assoc()) {
    $stats[strtolower($row['status'])] = (int)$row['c'];
}
$stats['total']      = array_sum($stats);
$stats['pending']    = $stats['pending']    ?? 0;
$stats['responding'] = $stats['responding'] ?? 0;
$stats['resolved']   = $stats['resolved']   ?? 0;

// ---- Active incidents ----
$reports_q = $mysqli->query("SELECT r.*, u.username FROM reports r JOIN users u ON r.user_id = u.id WHERE r.status IN ('Pending','Responding') ORDER BY r.timestamp DESC LIMIT 8");

// ---- Latest alerts ----
$alerts_q = $mysqli->query("SELECT a.*, u.username as posted_by_name FROM alerts a LEFT JOIN users u ON a.posted_by = u.id WHERE a.is_active = 1 ORDER BY a.timestamp DESC LIMIT 5");

// ---- Chart data: last 7 days ----
$trend_labels = [];
$trend_values = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('M j', strtotime("-{$i} days"));
    $trend_labels[] = $date;
    $r = $mysqli->query("SELECT COUNT(*) FROM reports WHERE DATE(timestamp) = CURDATE() - INTERVAL {$i} DAY");
    $trend_values[] = (int)$r->fetch_row()[0];
}

// ---- Chart data: by category ----
$cat_q = $mysqli->query("SELECT category, COUNT(*) as c FROM reports GROUP BY category");
$cat_labels = [];
$cat_values = [];
while ($c = $cat_q->fetch_assoc()) {
    $cat_labels[] = $c['category'];
    $cat_values[] = (int)$c['c'];
}

require_once 'header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Dashboard</h1>
        <p>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name'] ?: $_SESSION['username']); ?>!</p>
    </div>
    <div class="page-header-actions">
        <?php if (is_user()): ?>
            <a href="report_emergency.php" class="btn btn-primary">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Report Emergency
            </a>
        <?php endif; ?>
        <?php if (is_responder()): ?>
            <a href="responder_hub.php" class="btn btn-primary">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Responder Hub
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- ---- Stats ---- -->
<div class="stats-grid">
    <div class="stat-card red">
        <div class="stat-icon">🔴</div>
        <div class="stat-info">
            <div class="stat-value" id="stat-pending"><?php echo $stats['pending']; ?></div>
            <div class="stat-label">Pending</div>
        </div>
    </div>
    <div class="stat-card amber">
        <div class="stat-icon">🟠</div>
        <div class="stat-info">
            <div class="stat-value" id="stat-responding"><?php echo $stats['responding']; ?></div>
            <div class="stat-label">Responding</div>
        </div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon">✅</div>
        <div class="stat-info">
            <div class="stat-value" id="stat-resolved"><?php echo $stats['resolved']; ?></div>
            <div class="stat-label">Resolved</div>
        </div>
    </div>
    <div class="stat-card blue">
        <div class="stat-icon">📊</div>
        <div class="stat-info">
            <div class="stat-value" id="stat-total"><?php echo $stats['total']; ?></div>
            <div class="stat-label">Total Reports</div>
        </div>
    </div>
</div>

<!-- ---- CTA (user only) ---- -->
<?php if (is_user()): ?>
<div class="cta-card mb-4">
    <div>
        <h2>🚨 Need Emergency Assistance?</h2>
        <p>Report an emergency and local responders will be notified immediately.</p>
    </div>
    <a href="report_emergency.php" class="btn btn-lg">Report Now</a>
</div>
<?php endif; ?>

<!-- ---- Main Grid ---- -->
<div class="dashboard-grid">

    <!-- Active Incidents -->
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Active Incidents</div>
                <div class="card-subtitle">Pending &amp; Responding</div>
            </div>
            <?php if ($stats['pending'] > 0): ?>
                <span class="badge badge-pending badge-live"><?php echo $stats['pending']; ?> Pending</span>
            <?php endif; ?>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if ($reports_q && $reports_q->num_rows > 0): ?>
                <?php while($report = $reports_q->fetch_assoc()): ?>
                <div class="feed-item" style="padding: 14px 22px;">
                    <div class="feed-item-body">
                        <div class="gap-8">
                            <span class="feed-item-title"><?php echo htmlspecialchars($report['title'] ?: $report['category']); ?></span>
                            <span class="badge badge-<?php echo strtolower($report['status']); ?>"><?php echo $report['status']; ?></span>
                        </div>
                        <div class="feed-item-meta">
                            <?php echo htmlspecialchars($report['zone']); ?> &bull;
                            <?php echo date('M j, g:ia', strtotime($report['timestamp'])); ?> &bull;
                            by <?php echo htmlspecialchars($report['username']); ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">✅</div>
                    <h3>All Clear</h3>
                    <p>No active incidents at this time</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Announcements -->
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Official Announcements</div>
                <div class="card-subtitle">Latest alerts &amp; advisories</div>
            </div>
            <?php if (is_admin()): ?>
                <a href="post_alert.php" class="btn btn-sm btn-ghost">+ Post</a>
            <?php endif; ?>
        </div>
        <div class="card-body" style="padding: 0 22px;">
            <?php if ($alerts_q && $alerts_q->num_rows > 0): ?>
                <?php while($alert = $alerts_q->fetch_assoc()): ?>
                <div class="feed-item level-<?php echo $alert['level']; ?>" style="gap: 12px; align-items: stretch;">
                    <div class="alert-indicator"></div>
                    <div class="feed-item-body">
                        <div class="gap-8">
                            <span class="feed-item-title"><?php echo htmlspecialchars($alert['title']); ?></span>
                            <span class="badge badge-<?php echo strtolower($alert['level']); ?>"><?php echo $alert['level']; ?></span>
                        </div>
                        <div class="text-sm text-muted" style="margin-top:4px;">
                            <?php echo htmlspecialchars(substr($alert['details'], 0, 100)); ?>...
                        </div>
                        <div class="feed-item-meta"><?php echo date('M j, g:ia', strtotime($alert['timestamp'])); ?></div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state" style="padding: 32px 0;">
                    <div class="empty-icon">📢</div>
                    <h3>No Announcements</h3>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 7-Day Trend Chart -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">7-Day Incident Trend</div>
        </div>
        <div class="card-body">
            <div class="chart-wrap"><canvas id="trend-chart"></canvas></div>
        </div>
    </div>

    <!-- Category Chart -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Incidents by Category</div>
        </div>
        <div class="card-body">
            <div class="chart-wrap"><canvas id="cat-chart"></canvas></div>
        </div>
    </div>

</div><!-- /.dashboard-grid -->

<script>
document.addEventListener('DOMContentLoaded', () => {
    DashboardCharts.trendChart('trend-chart', {
        labels: <?php echo json_encode($trend_labels); ?>,
        values: <?php echo json_encode($trend_values); ?>
    });
    DashboardCharts.categoryChart('cat-chart', {
        labels: <?php echo json_encode($cat_labels ?: ['No Data']); ?>,
        values: <?php echo json_encode($cat_values ?: [0]); ?>
    });
});
</script>

<?php require_once 'footer.php'; ?>