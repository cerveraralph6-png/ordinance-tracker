<?php
include_once '../auth_check.php';
include '../db.php';
header('Content-Type: application/json');

$query = "SELECT id, event_name, event_date, event_time, category, location FROM events";
$result = $conn->query($query);
$events = [];

while($row = $result->fetch_assoc()) {
    // Standardized Color Codes
    $color = '#2563eb'; // Default Blue
    if($row['category'] == 'Seminar') $color = '#10b981'; // Green
    if($row['category'] == 'Committee Level Hearing') $color = '#f59e0b'; // Amber
    if($row['category'] == 'Public Consultation') $color = '#8b5cf6'; // Purple
    if($row['category'] == 'Special Session') $color = '#f43f5e'; // Rose

    $events[] = [
        'id' => $row['id'],
        'title' => $row['event_name'],
        'start' => $row['event_date'] . 'T' . $row['event_time'],
        'backgroundColor' => $color,
        'borderColor' => $color,
        'extendedProps' => [
            'category' => $row['category'],
            'location' => $row['location'],
            'time' => date('h:i A', strtotime($row['event_time']))
        ]
    ];
}
echo json_encode($events);

?>