<?php
include_once '../auth_check.php';
include '../db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$cat = isset($_GET['cat']) ? $_GET['cat'] : 'Others';

$query = $conn->query("SELECT status FROM events WHERE id = $id");
$row = $query->fetch_assoc();

if ($row) {
    // If it's pending, make it done. If it's done, make it pending.
    $newStatus = ($row['status'] == 'pending') ? 'done' : 'pending';
    $conn->query("UPDATE events SET status = '$newStatus' WHERE id = $id");
}
// After your successful query:
$conn->query("UPDATE system_sync SET last_update = CURRENT_TIMESTAMP WHERE module_name = 'events'");

header("Location: events.php?cat=" . urlencode($cat));
exit();