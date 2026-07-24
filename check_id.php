<?php
include 'db.php';
$id = $_POST['employee_id'] ?? '';

$stmt = $conn->prepare("SELECT passkey FROM users WHERE employee_id = ?");
$stmt->bind_param("s", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo "Passkey: " . $row['passkey'];
} else {
    echo "ID not found. Please try again.";
}
?>