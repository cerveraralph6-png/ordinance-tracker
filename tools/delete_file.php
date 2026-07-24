<?php
session_start();
include '../db.php';

$file_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Check if user is the original uploader
$stmt = $conn->prepare("SELECT file_path FROM files WHERE id = ? AND uploader_id = ?");
$stmt->bind_param("ii", $file_id, $user_id);
$stmt->execute();
$res = $stmt->get_result();

if ($f = $res->fetch_assoc()) {
    $fullPath = "../" . $f['file_path']; // Result is ../uploads/filename
    if (file_exists($fullPath)) unlink($fullPath); 

    $conn->query("DELETE FROM files WHERE id = $file_id");
    header("Location: files.php?success=deleted");
} else {
    // If they aren't the owner, maybe just remove the transfer link?
    $conn->query("DELETE FROM file_transfers WHERE file_id = $file_id AND receiver_id = $user_id");
    header("Location: files.php?success=removed_from_view");
}

?>