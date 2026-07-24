<?php
include_once '../auth_check.php';
include '../db.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Fetch file details from DB
    $stmt = $conn->prepare("SELECT file_name, file_path FROM resources WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();

    if ($res) {
        $fullPath = "../" . $res['file_path'];

        if (file_exists($fullPath)) {
            // Clean output buffer to prevent corruption
            if (ob_get_level()) ob_end_clean();

            // Set headers to force download and handle large files
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $res['file_name'] . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($fullPath));
            
            // Set time limit to 0 (infinite) so the server doesn't time out
            set_time_limit(0);

            // Stream the file in 1MB chunks
            $file = fopen($fullPath, "rb");
            while (!feof($file)) {
                echo fread($file, 1048576);
                flush(); // Flush the output to the browser
            }
            fclose($file);
            exit;
        } else {
            die("Error: Physical file not found on server.");
        }
    }
}
header("Location: resources.php");

?>