<?php
session_start();
include '../db.php';

// Clear any buffers to prevent file corruption
if (ob_get_level()) ob_end_clean();

// 1. Get current filters from URL (Matches new table.php)
$search    = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_from = isset($_GET['df']) ? $_GET['df'] : '';
$date_to   = isset($_GET['dt']) ? $_GET['dt'] : '';
$sort      = isset($_GET['sort']) ? $_GET['sort'] : 'Date_Received';
$order     = (isset($_GET['order']) && strtolower($_GET['order']) == 'asc') ? 'ASC' : 'DESC';

// 2. Construct Query Logic
$where_clauses = ["1=1"];
$params = [];
$types = "";

if (!empty($date_from) && !empty($date_to)) {
    $where_clauses[] = "(Date_Received BETWEEN ? AND ?)";
    $params[] = $date_from; $params[] = $date_to;
    $types .= "ss";
}

if (!empty($search)) {
    $where_clauses[] = "(Subject LIKE ? OR Proponent LIKE ? OR Folder LIKE ? OR Type LIKE ? OR Remarks LIKE ? OR Subject_Description LIKE ?)";
    $st = "%$search%";
    for($i=0; $i<6; $i++) { $params[] = $st; $types .= "s"; }
}

$where_sql = "WHERE " . implode(" AND ", $where_clauses);
$sql = "SELECT * FROM city_records $where_sql ORDER BY $sort $order";

$stmt = $conn->prepare($sql);
if(!empty($types)) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$result = $stmt->get_result();

// 3. Set Headers for Download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="BCH_Full_Export_'.date('Y-m-d').'.csv"');

$output = fopen('php://output', 'w');

// 4. Add UTF-8 BOM for Excel Compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// 5. Write the 21 Column Headers
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