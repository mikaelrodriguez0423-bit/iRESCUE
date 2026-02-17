<?php
require_once 'config.php';
require_once 'log_activity.php';
require_admin();

$action = sanitize($_GET['action'] ?? '');
$error  = $success = '';

// Delete
if ($action === 'delete' && isset($_GET['id'])) {
    $id  = (int)$_GET['id'];
    $svc = $mysqli->query("SELECT service FROM hotlines WHERE id = $id")->fetch_row()[0] ?? 'Unknown';
    $mysqli->query("DELETE FROM hotlines WHERE id = $id");
    log_activity($mysqli, $_SESSION['id'], $_SESSION['username'], 'HOTLINE_DELETED', "Deleted: {$svc}");
    header('Location: manage_hotlines.php?toast=success&msg=Hotline+deleted.'); exit;
}

// Add / Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $service  = sanitize($_POST['service'] ?? '');
    $number   = sanitize($_POST['number'] ?? '');
    $category = sanitize($_POST['category'] ?? 'General');
    $id       = (int)($_POST['id'] ?? 0);

    if (empty($service) || empty($number)) {
        $error = 'Service name and number are required.';
    } elseif ($action === 'add') {
        $stmt = $mysqli->prepare("INSERT INTO hotlines (service, number, category) VALUES (?,?,?)");
        $stmt->bind_param('sss', $service, $number, $category);
        $stmt->execute();
        log_activity($mysqli, $_SESSION['id'], $_SESSION['username'], 'HOTLINE_ADDED', "Added: {$service}");
        header('Location: manage_hotlines.php?toast=success&msg=Hotline+added.'); exit;
    } elseif ($action === 'edit' && $id) {
        $stmt = $mysqli->prepare("UPDATE hotlines SET service=?, number=?, category=? WHERE id=?");
        $stmt->bind_param('sssi', $service, $number, $category, $id);
        $stmt->execute();
        log_activity($mysqli, $_SESSION['id'], $_SESSION['username'], 'HOTLINE_UPDATED', "Updated #{$id}: {$service}");
        header('Location: manage_hotlines.php?toast=success&msg=Hotline+updated.'); exit;
    }
}

$hotlines_q = $mysqli->query("SELECT * FROM hotlines ORDER BY category, service");

// Handle toast from redirect
$toast_type = sanitize($_GET['toast'] ?? '');
$toast_msg  = sanitize($_GET['msg'] ?? '');

require_once 'header.php';
?>
<?php if ($toast_type && $toast_msg): ?>
<div data-toast="<?php echo $toast_type; ?>" data-message="<?php echo $toast_msg; ?>"></div>
<?php endif; ?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Manage Hotlines</h1>
        <p>Add, edit, or remove emergency contact numbers.</p>
    </div>
    <div class="page-header-actions">
        <button id="btn-add-new" data-type="Hotline" class="btn btn-primary">+ Add Hotline</button>
    </div>
</div>

<?php if ($error): ?>
<div class="alert alert-danger mb-4"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card">
    <div class="table-toolbar">
        <div class="search-input-wrap">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" class="search-input" placeholder="Search…" data-table="hotlines-table" data-search>
        </div>
    </div>
    <div style="overflow-x:auto;">
        <table id="hotlines-table">
            <thead>
                <tr><th>Service</th><th>Category</th><th>Number</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php while ($h = $hotlines_q->fetch_assoc()): ?>
            <tr>
                <td class="fw-600"><?php echo htmlspecialchars($h['service']); ?></td>
                <td><span class="badge badge-info"><?php echo htmlspecialchars($h['category']); ?></span></td>
                <td><?php echo htmlspecialchars($h['number']); ?></td>
                <td class="actions">
                    <button class="btn btn-sm btn-ghost btn-edit-row"
                            data-entity='<?php echo htmlspecialchars(json_encode($h), ENT_QUOTES); ?>'
                            data-type="Hotline">Edit</button>
                    <a href="manage_hotlines.php?action=delete&id=<?php echo $h['id']; ?>"
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
            <span class="modal-title" id="crud-modal-title">Add Hotline</span>
            <button class="modal-close" data-modal-close="crud-modal-overlay">&times;</button>
        </div>
        <div class="modal-body">
            <form id="crud-form" method="POST" data-base-action="manage_hotlines.php" action="manage_hotlines.php?action=add">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id" id="form-id">
                <div class="form-group">
                    <label class="form-label">Service Name <span class="required">*</span></label>
                    <input type="text" name="service" id="service" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Phone Number <span class="required">*</span></label>
                    <input type="text" name="number" id="number" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <input type="text" name="category" id="category" class="form-control" placeholder="e.g. Medical, Fire">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-ghost" data-modal-close="crud-modal-overlay">Cancel</button>
            <button type="submit" form="crud-form" class="btn btn-primary">Save</button>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>