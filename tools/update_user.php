<?php
include_once '../auth_check.php';
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'];
    $full_name = $_POST['full_name'];
    $employee_id = $_POST['employee_id'];
    $new_passkey = $_POST['passkey'];
    $role = $_POST['role'];

    // 1. Get current passkey to see if it changed
    $stmt = $conn->prepare("SELECT passkey FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $current_passkey = $stmt->get_result()->fetch_assoc()['passkey'];

    // 2. If passkey is different, we set password_changed to 0 (Trigger reset)
    // Otherwise, we keep it as 1 (Assuming it was already set)
    $password_status = ($new_passkey !== $current_passkey) ? 0 : 1;

    // 3. Update User
    $update = $conn->prepare("UPDATE users SET full_name = ?, employee_id = ?, passkey = ?, role = ?, password_changed = ? WHERE id = ?");
    $update->bind_param("ssssii", $full_name, $employee_id, $new_passkey, $role, $password_status, $user_id);

    if ($update->execute()) {
        header("Location: manage-accounts.php?success=updated");
    } else {
        echo "Error: " . $conn->error;
    }
}
?>