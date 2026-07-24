<?php
include_once '../auth_check.php';
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect data from the form
    $type_of_law = $_POST['Type_of_law'] ?? '';
    $number = $_POST['Number'] ?? '';
    $series = $_POST['Series'] ?? '';
    $title = $_POST['Title'] ?? '';
    $author = $_POST['Author'] ?? '';
    $dept = $_POST['Implementing_department'] ?? '';
    $hyperlink = $_POST['Hyperlink'] ?? '';
    $other_attach = $_POST['Other_Attachment'] ?? '';
    $h1 = $_POST['Hyperlink1'] ?? '';
    $h2 = $_POST['Hyperlink2'] ?? '';
    $h3 = $_POST['Hyperlink3'] ?? '';

    // Prepare the SQL statement for the women_records table
    $stmt = $conn->prepare("INSERT INTO women_records (
        Type_of_law, 
        Number, 
        Series, 
        Title, 
        Author, 
        Implementing_department, 
        Hyperlink, 
        Other_Attachment, 
        Hyperlink1, 
        Hyperlink2, 
        Hyperlink3
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    // Bind the 11 string parameters
    $stmt->bind_param("sssssssssss", 
        $type_of_law, 
        $number, 
        $series, 
        $title, 
        $author, 
        $dept, 
        $hyperlink, 
        $other_attach, 
        $h1, 
        $h2, 
        $h3
    );

    if ($stmt->execute()) {
        // Redirect back to the women tab with a success message
        header("Location: table.php?cat=women&success=1");
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
} else {
    // If someone tries to access this file directly, send them back
    header("Location: table.php");
    exit();
}
?>