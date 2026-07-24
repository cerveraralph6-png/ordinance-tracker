<?php
session_start();
include '../db.php';
include_once '../auth_check.php';

// Only Admins should be able to create users
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Grab and sanitize inputs
    // Note: Ensure the name in your HTML <input> is exactly "employee_id"
    $emp_id    = trim($_POST['employee_id'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $passkey   = trim($_POST['passkey'] ?? '');
    $role      = $_POST['role'] ?? 'user';

    // 2. Check if any fields are empty to prevent the "Cannot be null" error
    if (empty($emp_id) || empty($full_name) || empty($passkey)) {
        header("Location: manage-accounts.php?error=empty_fields");
        exit();
    }

    // 3. HASH THE PASSKEY
    // This turns "123456" into a secure random string
    $hashed_pass = password_hash($passkey, PASSWORD_DEFAULT);

    // 4. Prepare and execute the query
    $stmt = $conn->prepare("INSERT INTO users (employee_id, full_name, passkey, role, status, password_changed) VALUES (?, ?, ?, ?, 'active', 0)");
    
    // Bind the variables
    $stmt->bind_param("ssss", $emp_id, $full_name, $hashed_pass, $role);

    if ($stmt->execute()) {
        header("Location: manage-accounts.php?success=created");
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>