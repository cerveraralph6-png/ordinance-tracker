<?php
session_set_cookie_params(86400); 
session_start();
header('Content-Type: application/json');
include 'db.php';

// Helper function to get the real IP address
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

$ip_address = getUserIP();
$max_attempts = 5;
$lockout_duration = 60; // Increased to 60s for better security

// 1. Check if user is currently locked out
if (isset($_SESSION['lockout_time'])) {
    $seconds_left = ($_SESSION['lockout_time'] + $lockout_duration) - time();
    if ($seconds_left > 0) {
        echo json_encode(['status' => 'error', 'message' => "Too many failed attempts. Wait $seconds_left seconds."]);
        exit();
    } else {
        unset($_SESSION['lockout_time']);
        $_SESSION['failed_attempts'] = 0;
    }
}

$passkey = $_POST['passkey'] ?? '';
if (empty($passkey)) {
    echo json_encode(['status' => 'error', 'message' => 'No passkey provided']);
    exit();
}

$authenticated_row = null;

// --- 2. STEP A: CHECK MASTER ADMIN (OR DIRECT RESET CODE MATCH) ---
// We check if the passkey matches exactly (for Reset Codes or Master Admin)
$direct_stmt = $conn->prepare("SELECT id, passkey, role, status, full_name, password_changed FROM users WHERE passkey = ? AND status = 'active' LIMIT 1");
$direct_stmt->bind_param("s", $passkey);
$direct_stmt->execute();
$direct_res = $direct_stmt->get_result();

if ($row = $direct_res->fetch_assoc()) {
    $authenticated_row = $row;
} else {
    // --- 3. STEP B: CHECK HASHED PASSWORDS ---
    $stmt = $conn->prepare("SELECT id, passkey, role, status, full_name, password_changed FROM users WHERE status = 'active'");
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        if (password_verify($passkey, $row['passkey'])) {
            $authenticated_row = $row;
            break; 
        }
    }
}

// 4. AUTHENTICATION RESULT
if ($authenticated_row) {
    if ($authenticated_row['status'] == 'archived') {
        echo json_encode(['status' => 'error', 'message' => 'This account is archived.']);
        exit();
    }
    
    $_SESSION['failed_attempts'] = 0;
    $_SESSION['user_id'] = $authenticated_row['id'];
    $_SESSION['role'] = $authenticated_row['role'];
    $_SESSION['full_name'] = $authenticated_row['full_name'];
    
    // Set the password status (0 means force reset, 1 means normal)
    $_SESSION['password_changed'] = (int)$authenticated_row['password_changed'];
    
    $_SESSION['last_activity'] = time(); 
    
    // Determine path: If 0, go to change password. If 1, go to dashboard.
    $redirect = ($_SESSION['password_changed'] === 0) ? 'change_password.php' : (($_SESSION['role'] == 'admin') ? 'admin/admin.php' : 'user/user.php');
    
    echo json_encode(['status' => 'success', 'redirect' => $redirect]);
    exit();

} else {
    // --- FAILURE LOGIC ---
    $log_stmt = $conn->prepare("INSERT INTO login_logs (ip_address, attempts) VALUES (?, 1) ON DUPLICATE KEY UPDATE attempts = attempts + 1, last_attempt = CURRENT_TIMESTAMP");
    $log_stmt->bind_param("s", $ip_address);
    $log_stmt->execute();

    if (!isset($_SESSION['failed_attempts'])) { $_SESSION['failed_attempts'] = 0; }
    $_SESSION['failed_attempts']++;

    if ($_SESSION['failed_attempts'] >= $max_attempts) {
        $_SESSION['lockout_time'] = time();
        echo json_encode(['status' => 'error', 'message' => "Too many attempts. Lockout active."]);
    } else {
        $remaining = $max_attempts - $_SESSION['failed_attempts'];
        echo json_encode(['status' => 'error', 'message' => "Invalid Passkey. $remaining attempts left."]);
    }
}
?>