<?php
session_start();
include_once '../auth_check.php';
include '../db.php';

// Set database character set
$conn->set_charset("utf8mb4");

// 1. Validate Inputs
if (!isset($_GET['ids']) || empty($_GET['ids'])) {
    die("No records selected for export.");
}

$active_cat = isset($_GET['cat']) ? $_GET['cat'] : 'main';

// Sanitize IDs by mapping them to safe integers to prevent SQL injection
$id_array = array_map('intval', explode(',', $_GET['ids']));
if (empty($id_array)) {
    die("Invalid record IDs.");
}

$placeholders = implode(',', $id_array);

// 2. Map Database Tables, Target Columns, and Header Labels
if ($active_cat === 'women') {
    $db_table = "women_records";
    $filename = "women_ordinances_" . date('Y-m-d') . ".csv";
    $columns = ['id', 'Type_of_law', 'Number', 'Series', 'Title', 'Author', 'Implementing_department', 'Hyperlink', 'Other_Attachment', 'Hyperlink1', 'Hyperlink2', 'Hyperlink3'];
    $headers = ['ID', 'Type of Law', 'Number', 'Series', 'Title', 'Author', 'Implementing Department', 'Hyperlink', 'Other Attachment', 'Hyperlink 1', 'Hyperlink 2', 'Hyperlink 3'];
} elseif ($active_cat === 'others') {
    $db_table = "other_records";
    $filename = "other_records_" . date('Y-m-d') . ".csv";
    $columns = ['id', 'Date', 'Title', 'Category', 'Remarks', 'Hyperlink'];
    $headers = ['ID', 'Date', 'Title', 'Category', 'Remarks', 'Hyperlink'];
} else {
    $db_table = "city_records";
    $filename = "general_registry_" . date('Y-m-d') . ".csv";
    $columns = ['id', 'Date_Received', 'Time_Received', 'Ref_No', 'Type', 'Proponent', 'Subject', 'Subject_Description', 'Subject_Notation', 'Committee_Referred', 'Indorsement1', 'Date_Indorsed1', 'Com_Rep_Nr', 'Com_Rep', 'Com_Rep_Date_Received', 'Item_Nr', 'Agenda_Date', 'Action_Taken', 'Indorsement2', 'Indorsement2_Date', 'Remarks', 'Folder'];
    $headers = ['ID', 'Date Received', 'Time Received', 'Ref No', 'Type', 'Proponent', 'Subject', 'Subject Description', 'Subject Notation', 'Committee Referred', 'Indorsement 1', 'Date Indorsed 1', 'Com Rep Nr', 'Com Rep', 'Com Rep Date Received', 'Item Nr', 'Agenda Date', 'Action Taken', 'Indorsement 2', 'Indorsement 2 Date', 'Remarks', 'Folder'];
}

// 3. Fetch Selected Records
$sql = "SELECT " . implode(', ', $columns) . " FROM $db_table WHERE id IN ($placeholders)";
$result = $conn->query($sql);

if (!$result) {
    die("Query execution failed: " . $conn->error);
}

// 4. Force CSV Download Headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// 5. Open output stream
$output = fopen('php://output', 'w');

// Add UTF-8 Byte Order Mark (BOM) to ensure special characters read correctly in MS Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write CSV headers
fputcsv($output, $headers);

// Write records row-by-row
while ($row = $result->fetch_assoc()) {
    $line = [];
    foreach ($columns as $col) {
        $line[] = $row[$col] ?? '';
    }
    fputcsv($output, $line);
}

fclose($output);
exit();

?>