<?php
include '../db.php';
header('Content-Type: application/json');

$empID = $_GET['id'] ?? '';

if (empty($empID)) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

$stmt = $conn->prepare("SELECT passkey FROM users WHERE employee_id = ? AND status = 'active' LIMIT 1");
$stmt->bind_param("s", $empID);
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {
    echo json_encode(['success' => true, 'passkey' => $row['passkey']]);
} else {
    echo json_encode(['success' => false, 'message' => 'Employee ID not found or inactive.']);
}
?>