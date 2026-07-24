<?php
session_start();
include '../db.php';

// Enable error reporting for debugging (Remove these 2 lines once working)
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // MATCHED EXACTLY TO YOUR table.php FORM FIELDS (21 Total)
    $fields = [
        'Date_Received', 'Time_Received', 'Ref_No', 'Type', 'Proponent', 
        'Subject', 'Subject_Description', 'Subject_Notation', 'Committee_Referred', 
        'Indorsement1', 'Date_Indorsed1', 'Com_Rep_Nr', 'Com_Rep', 
        'Com_Rep_Date_Received', 'Item_Nr', 'Agenda_Date', 'Action_Taken', 
        'Indorsement2', 'Indorsement2_Date', 'Remarks', 'Folder'
    ];
    
    $processed = [];
    $dateFields = ['Date_Received', 'Date_Indorsed1', 'Com_Rep_Date_Received', 'Agenda_Date', 'Indorsement2_Date'];
    $timeFields = ['Time_Received'];

    foreach($fields as $f) {
        // If the field isn't in POST, default to empty string
        $val = isset($_POST[$f]) ? trim($_POST[$f]) : "";
        
        if (empty($val)) {
            // Use NULL for empty dates/times, otherwise use "-none-"
            $processed[] = (in_array($f, $dateFields) || in_array($f, $timeFields)) ? null : "-none-";
        } else {
            $processed[] = $val;
        }
    }

    // Build the SQL query with 21 placeholders
    $sql = "INSERT INTO city_records (" . implode(',', $fields) . ") VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        die("SQL Prepare Error: " . $conn->error);
    }

    // Bind 21 strings (s)
    $stmt->bind_param("sssssssssssssssssssss", ...$processed);

    if ($stmt->execute()) {
        // --- TRIGGER REAL-TIME SYNC ON SUCCESS ---
        $conn->query("UPDATE system_sync SET last_update = CURRENT_TIMESTAMP WHERE module_name = 'records'");
        
        $stmt->close();
        $conn->close();
        
        header("Location: table.php?success=1");
        exit();
    } else {
        echo "Execution Error: " . $stmt->error;
    }
}
?>