<?php
include_once '../auth_check.php';
include '../db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$cat = isset($_GET['cat']) ? $_GET['cat'] : 'Others';

if ($id > 0) {
    $conn->query("DELETE FROM events WHERE id = $id");
}
$conn->query("UPDATE system_sync SET last_update = CURRENT_TIMESTAMP WHERE module_name = 'events'");

// After your successful query:
$conn->query("UPDATE system_sync SET last_update = CURRENT_TIMESTAMP WHERE module_name = 'events'");

// Redirect back to the specific category
header("Location: events.php?cat=" . urlencode($cat) . "&success=deleted");
exit();
?>