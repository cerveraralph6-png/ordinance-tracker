<?php
include_once '../auth_check.php';
include '../db.php';
header('Content-Type: application/json');

$query = $_GET['query'] ?? '';
$me = $_SESSION['user_id'];

if (empty($query)) {
    echo json_encode(['found' => false, 'results' => []]);
    exit;
}

$searchTerm = "%$query%";
// Fetch Name, Role, Pic, and Bio
$stmt = $conn->prepare("SELECT id, full_name, role, profile_pic, bio FROM users WHERE full_name LIKE ? AND status = 'active' AND id != ? LIMIT 5");
$stmt->bind_param("si", $searchTerm, $me);
$stmt->execute();
$res = $stmt->get_result();

$results = [];
while ($row = $res->fetch_assoc()) { 
    $results[] = $row; 
}

echo json_encode(['found' => (count($results) > 0), 'results' => $results]);

?>