<?php
include '../db.php';
header('Content-Type: application/json');

// Get search parameters
$query = $_GET['query'] ?? '';
$filter = $_GET['filter'] ?? 'Subject';
$status = $_GET['status'] ?? 'All';
$prop_type = $_GET['prop_type'] ?? 'All';
$category = $_GET['cat'] ?? 'main'; // 'main' or 'women'

// Check if any search criteria is provided
if (empty($query) && $status === 'All' && $prop_type === 'All') {
    echo json_encode(['found' => false, 'results' => []]);
    exit;
}

$searchTerm = "%$query%";
$results = [];

// --- BRANCH 1: WOMEN'S ORDINANCE SEARCH ---
if ($category === 'women') {
    // Map Women's headers to standard headers for the frontend
    // Title -> Subject, Number -> Ref_No, Author -> Proponent
    $where_clauses = ["(Title LIKE ? OR Author LIKE ? OR Number LIKE ?)"];
    $params = [$searchTerm, $searchTerm, $searchTerm];
    $types = "sss";

    $sql = "SELECT 
                Title AS Subject, 
                Number AS Ref_No, 
                Author AS Proponent, 
                Type_of_law AS Type, 
                Series, 
                Implementing_department, 
                Hyperlink,
                'women' AS db_type,
                'Approved' AS Action_Taken, -- Default status for women's list
                '---' AS Date_Received
            FROM women_records 
            WHERE " . implode(' AND ', $where_clauses) . " 
            ORDER BY id DESC LIMIT 100";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) { $results[] = $row; }

} 
// --- BRANCH 2: GENERAL ORDINANCE SEARCH ---
else {
    $allowedFilters = ['Subject', 'Ref_No', 'Proponent'];
    if (!in_array($filter, $allowedFilters)) { $filter = 'Subject'; }

    $where_clauses = ["$filter LIKE ?"];
    $params = [$searchTerm];
    $types = "s";

    // Status Filter Logic
    if ($status === 'Approved') {
        $where_clauses[] = "(Action_Taken LIKE '%approved%' OR Folder LIKE '%approved%' OR Action_Taken LIKE '%passed%')";
    } elseif ($status === 'Rejected') {
        $where_clauses[] = "(Action_Taken LIKE '%disapproved%' OR Action_Taken LIKE '%denied%' OR Action_Taken LIKE '%dropped%')";
    } elseif ($status === 'Pending') {
        $where_clauses[] = "(Action_Taken NOT LIKE '%approved%' AND Folder NOT LIKE '%approved%' AND Action_Taken NOT LIKE '%disapproved%')";
    }

    // Proponent Type Logic
    if ($prop_type === 'Single') {
        $where_clauses[] = "Proponent NOT LIKE '%,%' AND Proponent NOT LIKE '% and %' AND Proponent NOT LIKE '% & %'";
    } elseif ($prop_type === 'Multiple') {
        $where_clauses[] = "(Proponent LIKE '%,%' OR Proponent LIKE '% and %' OR Proponent LIKE '% & %')";
    }

    $sql = "SELECT 
                Subject, Ref_No, Folder, Action_Taken, Date_Received, Proponent, 
                'main' AS db_type 
            FROM city_records 
            WHERE " . implode(' AND ', $where_clauses) . " 
            ORDER BY Date_Received DESC LIMIT 100";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) { $results[] = $row; }
}

echo json_encode(['found' => (count($results) > 0), 'results' => $results]);
?>