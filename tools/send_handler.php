<?php
session_start();
include '../db.php'; // Path to root db.php

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $sender_id = $_SESSION['user_id'];
    $receiver_id = $_POST['receiver_id'];
    $file_id = $_POST['file_id'];

    // Prevent sending a file to yourself
    if ($sender_id == $receiver_id) {
        header("Location: files.php?error=self_send");
        exit();
    }

    // Insert into the transfer table
    $stmt = $conn->prepare("INSERT INTO file_transfers (file_id, sender_id, receiver_id, is_read) VALUES (?, ?, ?, 0)");
    $stmt->bind_param("iii", $file_id, $sender_id, $receiver_id);

    if ($stmt->execute()) {
        // Redirect back with success message
        header("Location: files.php?success=sent");
        exit();
    } else {
        echo "Database Error: " . $conn->error;
    }
} else {
    // If someone tries to access this file directly
    header("Location: ../index.php");
    exit();
}
?>