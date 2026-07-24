<?php
session_start();

// 1. Security Check: Only allow logged-in users
if (!isset($_SESSION['role'])) {
    header("Location: /index.php");
    exit();
}

include '../db.php'; // Database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 2. Get data from the form
    $label = trim($_POST['label']);
    $start = $_POST['start'];
    $end   = $_POST['end'];

    // 3. Insert into the archive_terms table
    $stmt = $conn->prepare("INSERT INTO archive_terms (term_label, start_date, end_date) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $label, $start, $end);

    if ($stmt->execute()) {
        // Success: Redirect back to the archive grid
        header("Location: table.php?success=term_added");
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
    
        // Add this right after a successful SQL execution
$conn->query("UPDATE system_sync SET last_update = NOW() WHERE module_name = 'city_records'");

    $stmt->close();
    $conn->close();
}
?>