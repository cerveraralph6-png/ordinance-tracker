<?php
session_start();
include '../db.php';

if (ob_get_level()) ob_end_clean();

if (!isset($_GET['ids']) || empty($_GET['ids'])) {
    die("No records selected.");
}

// 1. Sanitize IDs
$id_array = explode(',', $_GET['ids']);
$clean_ids = array_map('intval', $id_array);
$id_list = implode(',', $clean_ids);

// 2. Query only selected records
$sql = "SELECT * FROM city_records WHERE id IN ($id_list) ORDER BY Date_Received DESC";
$result = $conn->query($sql);

// 3. Set Headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="BCH_Selected_Records_'.date('Y-m-d').'.csv"');

$output = fopen('php://output', 'w');

// 4. Add UTF-8 BOM
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// 5. Write 21 Headers
fputcsv($output, ['Date_Received', 'Time_Received', 'Type', 'Proponent', 'Subject', 'Subject_Description', 'Subject_Notation', 'Committee_Referred', 'Indorsement1', 'Date_Indorsed1', 'Com_Rep_Nr', 'Com_Rep', 'Com_Rep_Date_Received', 'Com_Rep_Time_Received', 'Item_Nr', 'Agenda_Date', 'Action_Taken', 'Indorsement2', 'Indorsement2_Date', 'Remarks', 'Folder']);

// 6. Write Data Rows
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['Date_Received'], $row['Time_Received'], $row['Type'], $row['Proponent'], $row['Subject'],
        $row['Subject_Description'], $row['Subject_Notation'], $row['Committee_Referred'], $row['Indorsement1'], $row['Date_Indorsed1'],
        $row['Com_Rep_Nr'], $row['Com_Rep'], $row['Com_Rep_Date_Received'], $row['Com_Rep_Time_Received'], $row['Item_Nr'],
        $row['Agenda_Date'], $row['Action_Taken'], $row['Indorsement2'], $row['Indorsement2_Date'], $row['Remarks'], $row['Folder']
    ]);
}

fclose($output);
exit();
?>