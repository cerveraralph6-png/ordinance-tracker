<?php
include '../db.php';
date_default_timezone_set('Asia/Manila');
$now = time();
$cat = $_GET['cat'] ?? 'Others';

$stmt = $conn->prepare("SELECT * FROM events WHERE category = ? ORDER BY event_date ASC, event_time ASC");
$stmt->bind_param("s", $cat);
$stmt->execute();
$result = $stmt->get_result();

$events = [];
while($row = $result->fetch_assoc()) {
    // Apply the same status logic we built earlier
    $event_ts = strtotime($row['event_date'] . ' ' . $row['event_time']);
    $day_ts = strtotime($row['event_date']);
    $today_ts = strtotime(date('Y-m-d'));

    if ($row['status'] == 'done' || $day_ts < $today_ts) { $status = "Ended"; $class = "archived"; }
    elseif ($day_ts == $today_ts) {
        $status = ($now >= $event_ts) ? "Ongoing" : "Today";
        $class = ($now >= $event_ts) ? "active" : "role-tag";
    } else { $status = "Pending"; $class = "role-tag"; }

    $row['dynamic_status'] = $status;
    $row['dynamic_class'] = $class;
    $events[] = $row;
}

header('Content-Type: application/json');
echo json_encode($events);