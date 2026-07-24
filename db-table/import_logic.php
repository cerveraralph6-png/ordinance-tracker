<?php
session_start();
include '../db.php';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["csv_file"])) {
    $file = fopen($_FILES["csv_file"]["tmp_name"], "r");
    fgetcsv($file); // Skip Header

    $sql = "INSERT INTO city_records (Date_Received, Time_Received, Ref_No, Type, Proponent, Subject, Subject_Description, Subject_Notation, Committee_Referred, Indorsement1, Date_Indorsed1, Com_Rep_Nr, Com_Rep, Com_Rep_Date_Received, Item_Nr, Agenda_Date, Action_Taken, Indorsement2, Indorsement2_Date, Remarks, Folder) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
    $stmt = $conn->prepare($sql);

    while (($row = fgetcsv($file, 10000, ",")) !== FALSE) {
        if (empty(array_filter($row))) continue; 
        $data = array_pad(array_slice($row, 0, 21), 21, "");
        $processed = [];
        $dateIdx = [0, 10, 14, 15, 18]; // Updated indices for date fields

        foreach($data as $idx => $val) {
            $val = trim($val);
            if (empty($val) || $val == "") {
                $processed[] = in_array($idx, $dateIdx) ? null : "";
            } else {
                if(in_array($idx, $dateIdx)){
                   $ts = strtotime($val);
                   $processed[] = $ts ? date('Y-m-d', $ts) : null;
                } else { $processed[] = $val; }
            }
        }
        $stmt->bind_param("sssssssssssssssssssss", ...$processed);
        $stmt->execute();
    }
    fclose($file);
    header("Location: table.php?success=1");
    $conn->query("UPDATE system_sync SET last_update = CURRENT_TIMESTAMP WHERE module_name = 'records'");
}