<?php
include_once '../auth_check.php';
include '../db.php';

if (isset($_GET['id'])) {
    $file_id = $_GET['id'];
    $current_user = $_SESSION['user_id'];
    $role = $_SESSION['role'];

    // 1. Fetch the file path and uploader info first
    $stmt = $conn->prepare("SELECT file_path, uploader_id FROM resources WHERE id = ?");
    $stmt->bind_param("i", $file_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $filePath = "../" . $row['file_path'];
        $uploaderId = $row['uploader_id'];

        // 2. SECURITY CHECK: Only Admin OR the person who uploaded it can delete
        if ($role === 'admin' || $current_user == $uploaderId) {
            
            // 3. Delete physical file from folder
// Inside delete_resource.php, find the file_exists block and change to:
        if ($row['category'] !== 'link') {
            $filePath = "../" . $row['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
// Proceed to delete from database...

            // 4. Delete record from database
            $delStmt = $conn->prepare("DELETE FROM resources WHERE id = ?");
            $delStmt->bind_param("i", $file_id);
            
            if ($delStmt->execute()) {
                // Success - redirect back to resources
                header("Location: resources.php?msg=deleted");
                exit();
            } else {
                echo "Error deleting database record.";
            }
        } else {
            echo "Unauthorized access. You cannot delete this file.";
        }
    } else {
        echo "File not found.";
    }
} else {
    header("Location: resources.php");
}
?>