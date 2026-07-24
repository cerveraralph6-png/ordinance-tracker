<?php
session_start();
include '../db.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Check if the total POST size was exceeded (common for videos)
    if (empty($_FILES) && $_SERVER['CONTENT_LENGTH'] > 0) {
        die("Error: The file is too large for this server. Max allowed is likely " . ini_get('upload_max_filesize'));
    }

    if (isset($_FILES['my_file'])) {
        $uploader_id = $_SESSION['user_id'];
        $file = $_FILES['my_file'];

        // Check for specific PHP upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            if ($file['error'] === 1 || $file['error'] === 2) {
                die("Error: File exceeds the server's upload limit.");
            }
            die("Upload failed with error code: " . $file['error']);
        }

        $fileName = basename($file['name']);
        $fileSize = round($file['size'] / 1024, 2) . " KB";
        $targetDir = "../uploads/"; 

        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        // Allowed Extensions
        $allowedTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg', 'mp4', 'mov', 'avi', 'mkv', 'webm'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedTypes)) {
            die("Error: File type not supported.");
        }

        $cleanFileName = preg_replace("/[^a-zA-Z0-9.]/", "_", $fileName);
        $uniqueName = time() . "_" . $cleanFileName;
        $targetPath = $targetDir . $uniqueName;
        $dbPath = "uploads/" . $uniqueName; 

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $stmt = $conn->prepare("INSERT INTO files (uploader_id, filename, file_path, file_size) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $uploader_id, $fileName, $dbPath, $fileSize);
            
            if ($stmt->execute()) {
                header("Location: files.php?success=uploaded");
                exit();
            } else {
                echo "Database Error: " . $conn->error;
            }
        } else {
            echo "Error: Could not move file. Check folder permissions.";
        }
    } else {
        echo "No file detected in the request.";
    }
}
?>