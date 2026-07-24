<?php
session_start();
include_once '../auth_check.php';
include '../db.php';

// Set correct timezone
date_default_timezone_set('Asia/Manila');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize form inputs
    $proponent = isset($_POST['Proponent']) ? trim($_POST['Proponent']) : '';
    $subject   = isset($_POST['Subject']) ? trim($_POST['Subject']) : '';
    $status    = isset($_POST['Status']) ? trim($_POST['Status']) : '';

    // Validation check: Prevent saving completely blank entries
    if (empty($proponent) && empty($subject) && empty($status)) {
        $_SESSION['error'] = "Cannot save an empty record.";
        header("Location: table.php?cat=wofad");
        exit();
    }

    try {
        // Prepare the insert query
        $stmt = $conn->prepare("INSERT INTO wofad_records (Proponent, Subject, Status) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $proponent, $subject, $status);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Record successfully added to WOFAD.";
        } else {
            $_SESSION['error'] = "Unable to add the WOFAD record.";
        }
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        $_SESSION['error'] = "Database Error: " . $e->getMessage();
    }
}

// Redirect back to the WOFAD tab
header("Location: table.php?cat=wofad");
exit();
?>
