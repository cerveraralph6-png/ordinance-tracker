<?php
include_once '../auth_check.php';
include '../db.php';

$current_user = $_SESSION['user_id'];
$role = $_SESSION['role'];
$searchTerm = $_GET['search'] ?? '';

$sql = "SELECT r.*, u.full_name FROM resources r LEFT JOIN users u ON r.uploader_id = u.id";
if (!empty($searchTerm)) {
    $sql .= " WHERE r.file_name LIKE ? OR u.full_name LIKE ?";
    $stmt = $conn->prepare($sql);
    $term = "%$searchTerm%";
    $stmt->bind_param("ss", $term, $term);
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $sql .= " ORDER BY r.uploaded_at DESC";
    $res = $conn->query($sql);
}

if ($res && $res->num_rows > 0) {
    while($row = $res->fetch_assoc()) {
        $icon = "📄";
        if($row['category'] == 'image') $icon = "🖼️";
        elseif($row['category'] == 'video') $icon = "🎬";
        elseif($row['category'] == 'link') $icon = "🔗";

        // IMPORTANT: Only the card itself!
        echo '<div class="res-card" onclick="viewFile(\''.$row['file_path'].'\', \''.htmlspecialchars($row['file_name']).'\', \''.$row['category'].'\')">
                <div class="res-icon">'.$icon.'</div>
                <div class="res-details">
                    <h4 title="'.htmlspecialchars($row['file_name']).'">'.htmlspecialchars($row['file_name']).'</h4>
                    <p>By: '.htmlspecialchars($row['full_name']).'</p>
                    <small>'.$row['file_size'].' | '.date('M d, Y', strtotime($row['uploaded_at'])).'</small>
                </div>
                <div class="res-actions">
                    <a href="download_resource.php?id='.$row['id'].'" download class="dl-link" onclick="event.stopPropagation()">Download</a>';
        if($role == 'admin' || $row['uploader_id'] == $current_user) {
            echo '<a href="delete_resource.php?id='.$row['id'].'" class="del-link" onclick="event.stopPropagation(); return confirm(\'Delete permanent?\')">Delete</a>';
        }
        echo '</div></div>';
    }
} else {
    echo '<div class="no-results"><p>📂 No resources found.</p></div>';
}
?>