<?php
require_once 'config.php';
require_once 'log_activity.php';
require_admin();

$action = sanitize($_GET['action'] ?? '');
$error  = '';

if ($action === 'delete' && isset($_GET['id'])) {
    $id   = (int)$_GET['id'];
    $name = $mysqli->query("SELECT name FROM locations WHERE id = $id")->fetch_row()[0] ?? 'Unknown';
    $mysqli->query("DELETE FROM locations WHERE id = $id");
    log_activity($mysqli, $_SESSION['id'], $_SESSION['username'], 'LOCATION_DELETED', "Deleted: {$name}");
    header('Location: manage_locations.php?toast=success&msg=Location+deleted.'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $name    = sanitize($_POST['name']    ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $type    = sanitize($_POST['type']    ?? '');
    $contact = sanitize($_POST['contact'] ?? '');
    $cap     = (int)($_POST['capacity']   ?? 0);
    $id      = (int)($_POST['id']         ?? 0);
    $types   = ['Evacuation Center','Hospital','Police Station','Fire Station','Coast Guard'];

    if (empty($name) || empty($address) || !in_array($type, $types)) {
        $error = 'Name, address, and valid type are required.';
    } elseif ($action === 'add') {
        $stmt = $mysqli->prepare("INSERT INTO locations (name, address, type, capacity, contact) VALUES (?,?,?,?,?)");
        $stmt->bind_param('sssis', $name, $address, $type, $cap, $contact);
        $stmt->execute();
        log_activity($mysqli, $_SESSION['id'], $_SESSION['username'], 'LOCATION_ADDED', "Added: {$name}");
        header('Location: manage_locations.php?toast=success&msg=Location+added.'); exit;
    } elseif ($action === 'edit' && $id) {
        $stmt = $mysqli->prepare("UPDATE locations SET name=?, address=?, type=?, capacity=?, contact=? WHERE id=?");
        $stmt->bind_param('sssisi', $name, $address, $type, $cap, $contact, $id);
        $stmt->execute();
        log_activity($mysqli, $_SESSION['id'], $_SESSION['username'], 'LOCATION_UPDATED', "Updated #{$id}: {$name}");
        header('Location: manage_locations.php?toast=success&msg=Location+updated.'); exit;
    }
}

$locs_q = $mysqli->query("SELECT * FROM locations ORDER BY type, name");
$toast_type = sanitize($_GET['toast'] ?? '');
$toast_msg  = sanitize($_GET['msg']   ?? '');

require_once 'header.php';
?>
<?php if ($toast_type && $toast_msg): ?>
<div data-toast="<?php echo $toast_type; ?>" data-message="<?php echo $toast_msg; ?>"></div>
<?php endif; ?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Manage Locations</h1>
        <p>Add and manage evacuation centers, hospitals, and stations.</p>
    </div>
    <div class="page-header-actions">
        <button id="btn-add-new" data-type="Location" class="btn btn-primary">+ Add Location</button>
    </div>
</div>

<?php if ($error): ?><div class="alert alert-danger mb-4"><?php echo $error; ?></div><?php endif; ?>

<div class="card">
    <div class="table-toolbar">
        <div class="search-input-wrap">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" class="search-input" placeholder="Search…" data-table="locations-table" data-search>
        </div>
    </div>
    <div style="overflow-x:auto;">
        <table id="locations-table">
            <thead>
                <tr><th>Name</th><th>Address</th><th>Type</th><th>Capacity</th><th>Contact</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php while ($loc = $locs_q->fetch_assoc()): ?>
            <tr>
                <td class="fw-600"><?php echo htmlspecialchars($loc['name']); ?></td>
                <td class="text-sm"><?php echo htmlspecialchars($loc['address']); ?></td>
                <td><span class="badge badge-info"><?php echo htmlspecialchars($loc['type']); ?></span></td>
                <td class="text-sm"><?php echo $loc['capacity'] ? number_format($loc['capacity']) : '—'; ?></td>
                <td class="text-sm"><?php echo htmlspecialchars($loc['contact'] ?: '—'); ?></td>
                <td class="actions">
                    <button class="btn btn-sm btn-ghost btn-edit-row"
                            data-entity='<?php echo htmlspecialchars(json_encode($loc), ENT_QUOTES); ?>'
                            data-type="Location">Edit</button>
                    <a href="manage_locations.php?action=delete&id=<?php echo $loc['id']; ?>"
                       class="btn btn-sm btn-danger btn-delete">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- CRUD Modal -->
<div id="crud-modal-overlay" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title" id="crud-modal-title">Add Location</span>
            <button class="modal-close" data-modal-close="crud-modal-overlay">&times;</button>
        </div>
        <div class="modal-body">
            <form id="crud-form" method="POST" data-base-action="manage_locations.php" action="manage_locations.php?action=add">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id" id="form-id">
                <div class="form-group">
                    <label class="form-label">Name <span class="required">*</span></label>
                    <input type="text" name="name" id="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Address <span class="required">*</span></label>
                    <input type="text" name="address" id="address" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Type <span class="required">*</span></label>
                    <select name="type" id="type" class="form-control">
                        <option value="Evacuation Center">Evacuation Center</option>
                        <option value="Hospital">Hospital</option>
                        <option value="Police Station">Police Station</option>
                        <option value="Fire Station">Fire Station</option>
                        <option value="Coast Guard">Coast Guard</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Capacity</label>
                    <input type="number" name="capacity" id="capacity" class="form-control" placeholder="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Contact Number</label>
                    <input type="text" name="contact" id="contact" class="form-control" placeholder="02-8xxx-xxxx">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" data-modal-close="crud-modal-overlay">Cancel</button>
            <button type="submit" form="crud-form" class="btn btn-primary">Save</button>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>