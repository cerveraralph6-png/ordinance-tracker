<?php
session_start();
include_once '../auth_check.php';

include '../db.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['event_id'];
    $name = $_POST['event_name'];
    $category = $_POST['category']; // New field
    $date = $_POST['event_date'];
    $time = $_POST['event_time'];
    $location = $_POST['location'];
    $description = $_POST['description'];

    // Updated SQL with category and 7 placeholders (6 strings + 1 ID integer)
    $stmt = $conn->prepare("UPDATE events SET event_name=?, category=?, event_date=?, event_time=?, location=?, description=? WHERE id=?");
    $stmt->bind_param("ssssssi", $name, $category, $date, $time, $location, $description, $id);

    if ($stmt->execute()) {
        // Redirect back to the specific category view
        header("Location: events.php?cat=" . urlencode($category) . "&success=updated");
        exit();
    } else {
        echo "Error updating record: " . $conn->error;
    }
    // After event is successfully saved:
$conn->query("UPDATE system_sync SET last_update = CURRENT_TIMESTAMP WHERE module_name = 'events'");

    $stmt->close();
    $conn->close();
}
?>