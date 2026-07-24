<?php
session_start();
include 'db.php';

// Force JSON response
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_pass = $_POST['new_pass'] ?? '';
    $confirm_pass = $_POST['confirm_pass'] ?? '';
    $user_id = $_SESSION['user_id'] ?? null;

    if (!$user_id) {
        echo json_encode(['status' => 'error', 'message' => 'Session expired. Log in again.']);
        exit;
    }

    if ($new_pass !== $confirm_pass) {
        echo json_encode(['status' => 'error', 'message' => 'Passkeys do not match!']);
        exit;
    }

    // HASH THE PASSKEY
    $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE users SET passkey = ?, password_changed = 1 WHERE id = ?");
    $stmt->bind_param("si", $hashed_pass, $user_id);

    if ($stmt->execute()) {
	    $_SESSION['password_changed'] = 1; // UPDATE SESSION STATUS
        // Determine redirect path in PHP, but let JS handle the actual jump
        $role = $_SESSION['role'];
        $target = ($role == 'admin') ? 'admin/admin.php' : 'user/user.php';
        
        echo json_encode(['status' => 'success', 'redirect' => $target]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database update failed.']);
    }

    $stmt->close();
    $conn->close();
}
?>