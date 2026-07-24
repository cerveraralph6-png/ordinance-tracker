<?php
session_start();
include '../db.php';

// No spaces before this!
header('Content-Type: application/json');
// Replace with your actual domain
header('Access-Control-Allow-Origin: https://bch-database.zya.me'); 
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

$me = $_SESSION['user_id'] ?? 0;
$action = $_GET['action'] ?? '';

if (!$me || !$conn) { 
    echo json_encode(['error' => 'Unauthorized']); 
    exit; 
}

if ($action == 'send' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $to = (int)$_POST['to_id'];
    $msg = trim($_POST['message']);
    if (!empty($msg) && $to > 0) {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message_text) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $me, $to, $msg);
        $stmt->execute();
        echo json_encode(['status' => 'ok']);
    }
    exit;
}

if ($action == 'load') {
    $with = (int)$_GET['with'];
    $conn->query("UPDATE messages SET is_read = 1 WHERE sender_id = $with AND receiver_id = $me");
    $res = $conn->query("SELECT * FROM messages WHERE (sender_id=$me AND receiver_id=$with) OR (sender_id=$with AND receiver_id=$me) ORDER BY sent_at ASC");
    $data = [];
    while($row = $res->fetch_assoc()) {
        $row['side'] = ($row['sender_id'] == $me) ? 'sent' : 'received';
        $data[] = $row;
    }
    echo json_encode($data);
    exit;
}