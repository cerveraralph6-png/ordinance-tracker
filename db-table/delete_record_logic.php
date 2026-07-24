<?php
ob_start();
session_start();
error_reporting(0);
include '../db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $record_id = $_POST['record_id'] ?? '';
    $category = $_POST['cat'] ?? 'main';
    $input_passkey = $_POST['verify_passkey'] ?? '';
    $my_id = $_SESSION['user_id'];

    // 1. SECURITY CHECK: Verify passkey of the logged-in user
    $stmt = $conn->prepare("SELECT passkey FROM users WHERE id = ?");
    $stmt->bind_param("i", $my_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user || !password_verify($input_passkey, $user['passkey']) && $input_passkey !== $user['passkey']) {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Authorization failed: Incorrect passkey.']);
        exit;
    }

    // 2. DEFINE TABLE
    $table = "city_records";
    if ($category === 'women') $table = "women_records";
    if ($category === 'others') $table = "other_records";

    // 3. EXECUTE DELETE
    $del_stmt = $conn->prepare("DELETE FROM $table WHERE id = ?");
    $del_stmt->bind_param("i", $record_id);

    if ($del_stmt->execute()) {
        ob_clean();
        echo json_encode(['status' => 'success']);
    } else {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
    }
    exit;
}

?>