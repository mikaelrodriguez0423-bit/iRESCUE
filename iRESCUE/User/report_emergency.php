<?php
require_once '../Core/config.php';
require_once '../Core/log_activity.php';
require_login();

if (is_admin()) { header('Location: ../User/responder_hub.php'); exit; }

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Security token mismatch.';
    } else {
        $category = sanitize($_POST['category'] ?? '');
        $severity = sanitize($_POST['severity'] ?? 'Moderate');
        $title    = sanitize($_POST['title'] ?? '');
        $details  = sanitize($_POST['details'] ?? '');
        $address  = sanitize($_POST['address'] ?? '');
        $lat      = is_numeric($_POST['lat'] ?? '') ? floatval($_POST['lat']) : null;
        $lng      = is_numeric($_POST['lng'] ?? '') ? floatval($_POST['lng']) : null;
        $zone     = 'Zone ' . rand(1, 10); // Real: derive from lat/lng
        $user_id  = $_SESSION['id'];

        $allowed_cats  = ['Fire','Flood','Medical','Crime','Accident','Other'];
        $allowed_sevs  = ['Low','Moderate','High','Critical'];

        if (!in_array($category, $allowed_cats)) $error = 'Invalid category.';
        elseif (!in_array($severity, $allowed_sevs)) $error = 'Invalid severity.';
        elseif (empty($details)) $error = 'Please describe the emergency.';
        else {
            // Photo upload
            $photo_path = null;
            if (!empty($_FILES['photo']['name'])) {
                $file     = $_FILES['photo'];
                $ftype    = mime_content_type($file['tmp_name']);
                $fsize    = $file['size'];

                if (!in_array($ftype, ALLOWED_TYPES)) {
                    $error = 'Only JPEG, PNG, or WebP images are accepted.';
                } elseif ($fsize > MAX_UPLOAD_SIZE) {
                    $error = 'Photo must be under 5MB.';
                } else {
                    $ext  = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $name = uniqid('report_', true) . '.' . $ext;
                    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
                    move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $name);
                    $photo_path = UPLOAD_DIR . $name;
                }
            }

            if (!$error) {
                $stmt = $mysqli->prepare("INSERT INTO reports (user_id, category, severity, title, details, location_lat, location_lng, address, zone, photo_path) VALUES (?,?,?,?,?,?,?,?,?,?)");
                $stmt->bind_param('issssddsss', $user_id, $category, $severity, $title, $details, $lat, $lng, $address, $zone, $photo_path);

                if ($stmt->execute()) {
                    $report_id = $stmt->insert_id;
                    $stmt->close();
                    log_activity($mysqli, $user_id, $_SESSION['username'], 'REPORT_SUBMITTED', "New {$category} report #{$report_id}");

                    // Notify admin by email (async-friendly)
                    $admin_q = $mysqli->query("SELECT email FROM users WHERE role='admin' LIMIT 3");
                    while ($adm = $admin_q->fetch_assoc()) {
                        $body = "<h2>⚠️ New Emergency Report #{$report_id}</h2>
                            <p><strong>Category:</strong> {$category} ({$severity})</p>
                            <p><strong>Details:</strong> " . nl2br(htmlspecialchars($details)) . "</p>
                            <p><strong>Zone:</strong> {$zone}</p>
                            <p><a href='" . APP_URL . "../User/responder_hub.php' class='btn'>View in Responder Hub</a></p>";
                        send_email($adm['email'], "[iRescue] New {$category} Report - {$severity}", $body);
                    }

                    $success = "Your emergency report has been submitted. Responders are being notified. Stay safe!";
                } else {
                    $error = 'Failed to submit report. Please try again.';
                }
            }
        }
    }
}

require_once '../Assets/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Report an Emergency</h1>
        <p>Your report will be sent to local responders immediately.</p>
    </div>
</div>

<?php if ($success): ?>
<div class="alert alert-success" style="max-width:640px;">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <span><?php echo $success; ?></span>
</div>
<?php endif; ?>

<div class="card" style="max-width: 640px;">
    <div class="card-body">
        <?php if ($error): ?>
        <div class="alert alert-danger">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span><?php echo $error; ?></span>
        </div>
        <?php endif; ?>

        <form id="report-form" method="POST" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>

            <div class="form-group">
                <label class="form-label">Type of Emergency <span class="required">*</span></label>
                <select name="category" class="form-control" required>
                    <option value="">Select emergency type…</option>
                    <?php foreach(['Fire'=>'🔥','Flood'=>'🌊','Medical'=>'🏥','Crime'=>'🚔','Accident'=>'💥','Other'=>'⚠️'] as $cat => $ico): ?>
                    <option value="<?php echo $cat; ?>" <?php echo ($_POST['category']??'')===$cat?'selected':''; ?>>
                        <?php echo "$ico $cat"; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Severity Level</label>
                <select name="severity" class="form-control">
                    <?php foreach(['Low','Moderate','High','Critical'] as $sev): ?>
                    <option value="<?php echo $sev; ?>" <?php echo ($_POST['severity']??'Moderate')===$sev?'selected':''; ?>><?php echo $sev; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Short Title</label>
                <input type="text" name="title" class="form-control" placeholder="e.g. House Fire on Rizal Ave"
                       value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Details <span class="required">*</span></label>
                <textarea name="details" class="form-control" rows="5" required
                          placeholder="Describe the situation clearly — people involved, immediate danger, what you can see."><?php echo htmlspecialchars($_POST['details'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Address or Landmark</label>
                <input type="text" name="address" class="form-control" placeholder="e.g. 12 Rizal Ave, near SM Mall"
                       value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>">
            </div>

            <div id="location-warning" class="alert alert-warning" style="display:none;">
                <span>⚠️ Could not get your GPS location. Please be specific in the address field.</span>
            </div>

            <input type="hidden" name="lat" id="lat">
            <input type="hidden" name="lng" id="lng">

            <div class="form-group">
                <label class="form-label">Attach a Photo (optional)</label>
                <input type="file" name="photo" class="form-control" accept="image/jpeg,image/png,image/webp">
                <span class="form-hint">Max 5MB, JPEG / PNG / WebP</span>
            </div>

            <button type="submit" class="btn btn-primary btn-full btn-lg">
                🚨 Submit Emergency Report
            </button>
        </form>
    </div>
</div>

<?php require_once '../Assets/footer.php'; ?>