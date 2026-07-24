<?php
session_start();
include '../db.php';

$id = $_GET['id'];
$user = $conn->query("SELECT status FROM users WHERE id = $id")->fetch_assoc();
$newStatus = ($user['status'] == 'active') ? 'archived' : 'active';

$conn->query("UPDATE users SET status = '$newStatus' WHERE id = $id");
header("Location: manage-accounts.php");
?>