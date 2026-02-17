<?php
require_once 'config.php';
require_login();

$locations_q = $mysqli->query("SELECT * FROM locations WHERE is_active = 1 ORDER BY type, name");

require_once 'header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Safety Locator</h1>
        <p>Find nearby evacuation centers, hospitals, and emergency services.</p>
    </div>
    <?php if (is_admin()): ?>
    <div class="page-header-actions">
        <a href="manage_locations.php" class="btn btn-secondary">Manage Locations</a>
    </div>
    <?php endif; ?>
</div>

<?php
$type_icons = [
    'Evacuation Center' => '🏫',
    'Hospital'          => '🏥',
    'Police Station'    => '🚔',
    'Fire Station'      => '🚒',
    'Coast Guard'       => '⚓',
];
?>

<div class="card">
    <div class="table-toolbar">
        <div class="search-input-wrap">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" class="search-input" placeholder="Search locations…" data-table="locations-table" data-search>
        </div>
        <select class="filter-select" data-table="locations-table" data-filter-col="2">
            <option value="">All Types</option>
            <?php foreach (array_keys($type_icons) as $t): ?>
            <option value="<?php echo strtolower($t); ?>"><?php echo $t; ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div style="overflow-x:auto;">
        <table id="locations-table">
            <thead>
                <tr>
                    <th data-sort="0">Name</th>
                    <th data-sort="1">Address</th>
                    <th data-sort="2">Type</th>
                    <th>Contact</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($locations_q && $locations_q->num_rows > 0): ?>
                <?php while ($loc = $locations_q->fetch_assoc()): ?>
                <tr>
                    <td class="fw-600">
                        <?php echo ($type_icons[$loc['type']] ?? '📍') . ' ' . htmlspecialchars($loc['name']); ?>
                    </td>
                    <td class="text-sm"><?php echo htmlspecialchars($loc['address']); ?></td>
                    <td><span class="badge badge-info"><?php echo htmlspecialchars($loc['type']); ?></span></td>
                    <td>
                        <?php if ($loc['contact']): ?>
                        <a href="tel:<?php echo htmlspecialchars($loc['contact']); ?>" class="btn btn-sm btn-ghost">
                            📞 <?php echo htmlspecialchars($loc['contact']); ?>
                        </a>
                        <?php else: ?>
                        <span class="text-muted text-sm">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4"><div class="empty-state"><div class="empty-icon">📍</div><h3>No locations found</h3></div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'footer.php'; ?>