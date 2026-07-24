<?php
include_once '../auth_check.php';
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['resource_file'])) {
    $uploader_id = $_SESSION['user_id'];
    $file = $_FILES['resource_file'];
    $fileName = basename($file['name']);
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    // --- AUTOMATED CATEGORIZATION ---
    $imgExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $vidExts = ['mp4', 'mov', 'avi', 'mkv'];
    
    if (in_array($fileExt, $imgExts)) {
        $category = 'image';
    } elseif (in_array($fileExt, $vidExts)) {
        $category = 'video';
    } else {
        $category = 'document';
    }

    $fileSize = round($file['size'] / (1024 * 1024), 2) . ' MB';
    if ($file['size'] < 1048576) {
        $fileSize = round($file['size'] / 1024, 2) . ' KB';
    }

    $targetDir = "../uploads/resources/";
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
    
    $uniqueName = time() . "_" . $fileName;
    $targetPath = $targetDir . $uniqueName;
    $dbPath = "uploads/resources/" . $uniqueName;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $stmt = $conn->prepare("INSERT INTO resources (uploader_id, file_name, file_path, file_size, category) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $uploader_id, $fileName, $dbPath, $fileSize, $category);
        $stmt->execute();
    }
    header("Location: resources.php?msg=success");
}

?>