<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://bch-database.zya.me'); 
header('Access-Control-Allow-Credentials: true');

// Ensure DB path is correct based on project tree
include '../db.php'; 

$module = $_GET['module'] ?? '';
$client_ts = intval($_GET['last_sync'] ?? 0);

if (!$module || !$conn) {
    echo json_encode(['update_needed' => false]);
    exit;
}

// Ultra-fast query
$res = $conn->query("SELECT UNIX_TIMESTAMP(last_update) as ts FROM system_sync WHERE module_name = '$module' LIMIT 1");
$row = $res->fetch_assoc();
$server_ts = intval($row['ts'] ?? 0);

// Close connection immediately to free up server resources
$conn->close();

echo json_encode([
    'update_needed' => ($server_ts > $client_ts),
    'server_time' => $server_ts
]);

?>