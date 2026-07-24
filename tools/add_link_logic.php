<?php
include_once '../auth_check.php';
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['url'])) {
    $uploader_id = $_SESSION['user_id'];
    $title = htmlspecialchars($_POST['title']);
    $url = $_POST['url'];
    $category = 'link';
    $size = 'External';

    // Simple URL cleanup (adds https if user forgot)
    if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
        $url = "https://" . $url;
    }

    $stmt = $conn->prepare("INSERT INTO resources (uploader_id, file_name, file_path, file_size, category) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $uploader_id, $title, $url, $size, $category);
    
    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error";
    }
}
?>