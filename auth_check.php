<?php
session_set_cookie_params(86400); 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- CRITICAL: PREVENT BROWSER FROM CACHING PROTECTED PAGES ---
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); 

// 1. Check if user is logged in
if (!isset($_SESSION['role'])) {
    header("Location: /index.php");
    exit();
}

$current_page = basename($_SERVER['PHP_SELF']);
$allowed_pages = ['change_password.php', 'logout.php', 'update_password_logic.php'];

// 2. FORCE SECURITY SETUP LOGIC (From reset codes)
if (isset($_SESSION['password_changed']) && $_SESSION['password_changed'] == 0) {
    if (!in_array($current_page, $allowed_pages)) {
        header("Location: /change_password.php");
        exit();
    }
}

// 3. ADDITIONAL CHECK (must_change_password flag)
if (isset($_SESSION['must_change_password']) && $_SESSION['must_change_password'] === true) {
    if (!in_array($current_page, $allowed_pages)) {
        header("Location: /change_password.php");
        exit();
    }
}

// 4. Inactivity Check (600 seconds / 10 minutes)
$timeout_duration = 600; 
if (isset($_SESSION['last_activity'])) {
    $elapsed_time = time() - $_SESSION['last_activity'];
    if ($elapsed_time >= $timeout_duration) {
        session_unset();
        session_destroy();
        header("Location: /index.php?reason=timeout");
        exit();
    }
}

// 5. Update the timestamp to 'now'
$_SESSION['last_activity'] = time();
?>