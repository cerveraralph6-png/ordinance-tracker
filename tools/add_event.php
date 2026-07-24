<?php
include_once '../auth_check.php';
include '../db.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Collect and trim inputs
    $name = trim($_POST['event_name']);
    $category = trim($_POST['category']); 
    $date = $_POST['event_date'];
    $time = $_POST['event_time'];
    $location = trim($_POST['location']);
    $desc = trim($_POST['description'] ?? '');

    // 2. Prepare the statement
    // Order: event_name, category, event_date, event_time, location, description
    $stmt = $conn->prepare("INSERT INTO events (event_name, category, event_date, event_time, location, description) VALUES (?, ?, ?, ?, ?, ?)");
    
    // 3. Bind the 6 string parameters
    $stmt->bind_param("ssssss", $name, $category, $date, $time, $location, $desc);

    if ($stmt->execute()) {
        // --- 4. TRIGGER REAL-TIME SYNC ---
        // This notifies the system that the events module has new data
        $conn->query("UPDATE system_sync SET last_update = CURRENT_TIMESTAMP WHERE module_name = 'events'");
        
        // 5. Success Redirect back to the specific category tab
        header("Location: events.php?cat=" . urlencode($category) . "&success=1");
        exit(); 
    } else {
        // Error handling for database/constraint issues
        echo "Error saving event: " . $conn->error;
    }

    $stmt->close();
    $conn->close();
} else {
    // Redirect if file is accessed directly
    header("Location: events.php");
    exit();
}
?>