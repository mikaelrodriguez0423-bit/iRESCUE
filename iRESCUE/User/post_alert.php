<?php
require_once '../Core/config.php';
require_once '../Core/log_activity.php';
require_admin();

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Security token mismatch.';
    } else {
        $title   = sanitize($_POST['title']   ?? '');
        $details = sanitize($_POST['details'] ?? '');
        $level   = sanitize($_POST['level']   ?? 'Info');
        $allowed_levels = ['Info', 'Warning', 'Critical'];

        if (empty($title) || empty($details)) {
            $error = 'All fields are required.';
        } elseif (!in_array($level, $allowed_levels)) {
            $error = 'Invalid alert level.';
        } else {
            $stmt = $mysqli->prepare("INSERT INTO alerts (level, title, details, posted_by) VALUES (?,?,?,?)");
            $stmt->bind_param('sssi', $level, $title, $details, $_SESSION['id']);
            if ($stmt->execute()) {
                $stmt->close();
                log_activity($mysqli, $_SESSION['id'], $_SESSION['username'], 'ALERT_POSTED', "'{$level}' alert: {$title}");
                header('Location: ../User/responder_hub.php?toast=success&msg=Alert+posted+successfully.'); exit;
            } else {
                $error = 'Failed to post alert.';
            }
        }
    }
}

require_once '../Assets/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Post Announcement</h1>
        <p>Create a public alert visible to all users on the dashboard.</p>
    </div>
</div>

<div class="card" style="max-width: 640px;">
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form id="post-alert-form" method="POST">
            <?php echo csrf_field(); ?>
            <div class="form-group">
                <label class="form-label">Alert Title <span class="required">*</span></label>
                <input type="text" name="title" class="form-control" placeholder="e.g. Typhoon Signal #2 Raised" required
                       value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Alert Level</label>
                <select name="level" class="form-control">
                    <option value="Info">ℹ️ Info</option>
                    <option value="Warning">⚠️ Warning</option>
                    <option value="Critical">🚨 Critical</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Details <span class="required">*</span></label>
                <textarea name="details" class="form-control" rows="6" required
                          placeholder="Write the full announcement text here…"><?php echo htmlspecialchars($_POST['details'] ?? ''); ?></textarea>
            </div>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <button type="submit" class="btn btn-primary btn-lg">📢 Post Announcement</button>
                <a href="../User/Admin/responder_hub.php" class="btn btn-ghost btn-lg">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../Assets/header.php';
