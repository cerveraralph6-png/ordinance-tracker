<?php
include_once '../auth_check.php';
include '../db.php';

if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
    $handle = fopen($_FILES['csv_file']['tmp_name'], "r");
    fgetcsv($handle); // Skip the header row

    // Prepare for 11 columns (we skip ID index 0 because DB creates it automatically)
    $stmt = $conn->prepare("INSERT INTO women_records (Type_of_law, Number, Series, Title, Author, Implementing_department, Hyperlink, Other_Attachment, Hyperlink1, Hyperlink2, Hyperlink3) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        // data[0] is ID from Excel, we start from data[1]
        $stmt->bind_param("sssssssssss", 
            $data[1], // Type of law
            $data[2], // Number
            $data[3], // Series
            $data[4], // Title
            $data[5], // Author
            $data[6], // Implementing department
            $data[7], // Hyperlink
            $data[8], // Other Attachment
            $data[9], // Hyperlink 1
            $data[10],// Hyperlink 2
            $data[11] // Hyperlink 3
        );
        $stmt->execute();
    }
    fclose($handle);
    header("Location: table.php?cat=women&success=imported");
}
?>