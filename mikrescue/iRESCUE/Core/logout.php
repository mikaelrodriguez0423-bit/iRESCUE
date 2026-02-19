<?php
require_once '../Core/config.php';
require_once '../Core/log_activity.php';

if (is_logged_in()) {
    log_activity($mysqli, $_SESSION['id'], $_SESSION['username'], 'USER_LOGOUT', '');
}
session_unset();
session_destroy();
header('Location: ../Auth/login.php');
exit;