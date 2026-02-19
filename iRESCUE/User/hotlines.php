<?php
require_once '../Core/config.php';
require_login();

$hotlines_q = $mysqli->query("SELECT * FROM hotlines WHERE is_active = 1 ORDER BY category, service");

require_once '../Assets/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Emergency Hotlines</h1>
        <p>Quick access to essential emergency contact numbers.</p>
    </div>
    <?php if (is_admin()): ?>
    <div class="page-header-actions">
        <a href="../Admin/manage_hotlines.php" class="btn btn-secondary">Manage Hotlines</a>
    </div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="table-toolbar">
        <div class="search-input-wrap">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" class="search-input" placeholder="Search hotlines…" data-table="hotlines-table" data-search>
        </div>
    </div>
    <div style="overflow-x:auto;">
        <table id="hotlines-table">
            <thead>
                <tr>
                    <th data-sort="0">Service</th>
                    <th data-sort="1">Category</th>
                    <th data-sort="2">Number</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($hotlines_q && $hotlines_q->num_rows > 0): ?>
                <?php while ($h = $hotlines_q->fetch_assoc()): ?>
                <tr>
                    <td class="fw-600"><?php echo htmlspecialchars($h['service']); ?></td>
                    <td><span class="badge badge-info"><?php echo htmlspecialchars($h['category']); ?></span></td>
                    <td>
                        <a href="tel:<?php echo htmlspecialchars($h['number']); ?>" class="btn btn-sm btn-primary">
                            📞 <?php echo htmlspecialchars($h['number']); ?>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="3"><div class="empty-state"><div class="empty-icon">📞</div><h3>No hotlines available</h3></div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../Assets/footer.php'; ?>