<?php
// Prevent any accidental output before headers
ob_start();
session_start();

// Get the category from the URL (default to main)
$cat = isset($_GET['cat']) ? $_GET['cat'] : 'main';

if ($cat === 'women') {
    $filename = "BCH_Template_Women_Ordinances.csv";
    $headers = [
        'ID', 
        'Type of law', 
        'Number', 
        'Series', 
        'Title', 
        'Author', 
        'Implementing department', 
        'Hyperlink', 
        'Other Attachment', 
        'Hyperlink 1', 
        'Hyperlink 2', 
        'Hyperlink 3'
    ];
} elseif ($cat === 'wofad') {
    $filename = "BCH_Template_Wofad.csv";
    $headers = ['Proponent', 'Subject', 'Status'];
} elseif ($cat === 'others') {
    $filename = "BCH_Template_Others.csv";
    $headers = ['Date', 'Title', 'Category', 'Remarks', 'Hyperlink'];
} else {
    $filename = "BCH_Template_General_Registry.csv";
    $headers = [
        'Date_Received', 'Time_Received', 'Ref_No', 'Type', 'Proponent', 
        'Subject', 'Subject_Description', 'Subject_Notation', 'Committee_Referred', 
        'Indorsement1', 'Date_Indorsed1', 'Com_Rep_Nr', 'Com_Rep', 
        'Com_Rep_Date_Received', 'Item_Nr', 'Agenda_Date', 'Action_Taken', 
        'Indorsement2', 'Indorsement2_Date', 'Remarks', 'Folder'
    ];
}

// Set browser headers to force download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

// Open the output stream
$output = fopen('php://output', 'w');

// Fix for Excel to recognize UTF-8 (BOM)
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write the headers to the CSV
fputcsv($output, $headers);

fclose($output);
exit();
?>
