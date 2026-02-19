<?php
// header.php — Included at the top of every authenticated page
$current = basename($_SERVER['PHP_SELF']);

// Build breadcrumb label
$page_labels = [
    'index.php'              => 'Dashboard',
    'dashboard.php'          => 'Dashboard',
    'responder_hub.php'      => 'Responder Hub',
    'report_emergency.php'   => 'Report Emergency',
    'hotlines.php'           => 'Hotlines',
    'locator.php'            => 'Safety Locator',
    'activity_log.php'       => 'Activity Log',
    'post_alert.php'         => 'Post Alert',
    'manage_hotlines.php'    => 'Manage Hotlines',
    'manage_locations.php'   => 'Manage Locations',
    'users.php'              => 'Users',
    'profile.php'            => 'Profile',
    'export.php'             => 'Export',
];
$page_title = $page_labels[$current] ?? 'iRescue';

// Count pending reports for badge
$pending_count = 0;
if (isset($mysqli)) {
    $r = $mysqli->query("SELECT COUNT(*) FROM reports WHERE status='Pending'");
    if ($r) $pending_count = $r->fetch_row()[0] ?? 0;
}

$csrf = csrf_generate();
$user_initial = strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1));
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $csrf; ?>">
    <title><?php echo $page_title; ?> — iRescue</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap">
    <link rel="stylesheet" href="../Assets/style.css">
</head>
<body>

<!-- Sidebar Overlay (mobile) -->
<div id="sidebar-overlay" class="sidebar-overlay"></div>

<!-- Toast container -->
<div id="toast-container"></div>

<div class="app-shell">

<!-- ========================= -->
<!-- SIDEBAR                   -->
<!-- ========================= -->
<aside class="sidebar" id="sidebar">

    <!-- Logo -->
    <div class="sidebar-logo">
        <a href="../Assets/index.php">
            <div class="logo-icon">🚨</div>
            iRescue
        </a>
    </div>

    <!-- Main Nav -->
    <div class="sidebar-section">
        <div class="sidebar-section-label">Main</div>
        <nav class="sidebar-nav">
            <ul>
                <li>
                    <a href="../Assets/index.php" class="<?php echo in_array($current, ['index.php','dashboard.php']) ? 'active' : ''; ?>">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        Dashboard
                    </a>
                </li>
                <?php if (is_responder()): ?>
                <li>
                    <a href="../User/responder_hub.php" class="<?php echo $current === '../User/responder_hub.php' ? 'active' : ''; ?>">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                        Responder Hub
                        <?php if ($pending_count > 0): ?>
                            <span class="nav-badge"><?php echo $pending_count; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (is_user()): ?>
                <li>
                    <a href="../User/report_emergency.php" class="<?php echo $current === '../User/report_emergency.php' ? 'active' : ''; ?>">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        Report Emergency
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>

    <div class="sidebar-divider"></div>

    <!-- Emergency Info -->
    <div class="sidebar-section">
        <div class="sidebar-section-label">Emergency Info</div>
        <nav class="sidebar-nav">
            <ul>
                <li>
                    <a href="../User/locator.php" class="<?php echo $current === '../User/locator.php' ? 'active' : ''; ?>">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Safety Locator
                    </a>
                </li>
                <li>
                    <a href="../User/hotlines.php" class="<?php echo $current === '../User/hotlines.php' ? 'active' : ''; ?>">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        Hotlines
                    </a>
                </li>
            </ul>
        </nav>
    </div>

    <?php if (is_admin()): ?>
    <div class="sidebar-divider"></div>
    <div class="sidebar-section">
        <div class="sidebar-section-label">Administration</div>
        <nav class="sidebar-nav">
            <ul>
                <li>
                    <a href="../Admin/users.php" class="<?php echo $current === '../Admin/users.php' ? 'active' : ''; ?>">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        Users
                    </a>
                </li>
                <li>
                    <a href="../Admin/manage_locations.php" class="<?php echo $current === '../Admin/manage_locations.php' ? 'active' : ''; ?>">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        Manage Locations
                    </a>
                </li>
                <li>
                    <a href="../Admin/manage_hotlines.php" class="<?php echo $current === '../Admin/manage_hotlines.php' ? 'active' : ''; ?>">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Manage Hotlines
                    </a>
                </li>
                <li>
                    <a href="../Admin/activity_log.php" class="<?php echo $current === '../Admin/activity_log.php' ? 'active' : ''; ?>">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        Activity Log
                    </a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>

    <!-- User info at bottom -->
    <div class="sidebar-user">
        <div class="sidebar-user-avatar"><?php echo $user_initial; ?></div>
        <div class="sidebar-user-info">
            <div class="sidebar-user-name"><?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></div>
            <div class="sidebar-user-role"><?php echo htmlspecialchars($_SESSION['role'] ?? ''); ?></div>
        </div>
        <a href="../Core/logout.php" class="sidebar-logout-btn" title="Logout">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        </a>
    </div>

</aside>

<!-- ========================= -->
<!-- MAIN CONTENT AREA         -->
<!-- ========================= -->
<div class="main-content">

    <!-- Top Bar -->
    <div class="topbar">
        <div class="topbar-left">
            <button class="mobile-toggle" id="mobile-toggle" aria-label="Toggle menu">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <div class="breadcrumb">
                <span>iRescue</span>
                <span class="sep">/</span>
                <span class="current"><?php echo $page_title; ?></span>
            </div>
        </div>
        <div class="topbar-right">
            <!-- Theme Toggle -->
            <button class="topbar-btn" id="theme-toggle" title="Toggle theme">
                <svg class="theme-icon-moon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                <svg class="theme-icon-sun" style="display:none" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </button>
            <!-- Notifications -->
            <?php if (is_responder() && $pending_count > 0): ?>
            <button class="topbar-btn" title="<?php echo $pending_count; ?> pending reports">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                <span class="notif-dot"></span>
            </button>
            <?php endif; ?>
            <!-- Profile -->
            <a href="profile.php" class="topbar-btn" title="Profile">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            </a>
        </div>
    </div>

    <!-- Page Body starts here -->
    <div class="page-body">