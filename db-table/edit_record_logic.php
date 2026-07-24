<?php
ob_start();
session_start();
error_reporting(0);
include '../db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $record_id = $_POST['record_id'] ?? '';
    $category = $_POST['cat'] ?? 'main';
    $input_passkey = $_POST['verify_passkey'] ?? '';
    $my_id = $_SESSION['user_id'];

    // 1. SECURITY CHECK: Verify logged-in user's passkey
    $stmt = $conn->prepare("SELECT passkey FROM users WHERE id = ?");
    $stmt->bind_param("i", $my_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    $is_valid = ($user && ($input_passkey === $user['passkey'] || password_verify($input_passkey, $user['passkey'])));

    if (!$is_valid) {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Authorization failed: Incorrect passkey.']);
        exit;
    }

    // 2. TIMELINE LOGIC: Process new progress update (ONLY FOR MAIN/GENERAL)
    if ($category === 'main' && !empty($_POST['new_action_note'])) {
        $date = date('M d, Y');
        $new_note = trim($_POST['new_action_note']);
        $existing_history = $_POST['Action_Taken'] ?? '';
        
        // Format: Date @@@ Note
        $new_entry = "$date @@@ $new_note";
        
        // Prepend new entry to old history using ||| separator
        if (empty($existing_history) || $existing_history === "-none-") {
            $_POST['Action_Taken'] = $new_entry;
        } else {
            $_POST['Action_Taken'] = $new_entry . " ||| " . $existing_history;
        }
    }

    // 3. DEFINE TABLE AND COLUMNS
    if ($category === 'women') {
        $table = "women_records";
        $fields = ['Type_of_law', 'Number', 'Series', 'Title', 'Author', 'Implementing_department', 'Hyperlink', 'Other_Attachment', 'Hyperlink1', 'Hyperlink2', 'Hyperlink3'];
    } elseif ($category === 'others') {
        $table = "other_records";
        $fields = ['Date', 'Title', 'Category', 'Remarks', 'Hyperlink'];
    } else {
        $table = "city_records";
        $fields = ['Date_Received', 'Time_Received', 'Ref_No', 'Type', 'Proponent', 'Subject', 'Subject_Description', 'Subject_Notation', 'Committee_Referred', 'Indorsement1', 'Date_Indorsed1', 'Com_Rep_Nr', 'Com_Rep', 'Com_Rep_Date_Received', 'Item_Nr', 'Agenda_Date', 'Action_Taken', 'Indorsement2', 'Indorsement2_Date', 'Remarks', 'Folder'];
    }

    // 4. CONSTRUCT UPDATE QUERY
    $updates = [];
    $values = [];
    $types = "";

    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            $updates[] = "$field = ?";
            $values[] = $_POST[$field];
            $types .= "s";
        }
    }

    if (empty($updates)) {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'No changes detected.']);
        exit;
    }

    $sql = "UPDATE $table SET " . implode(', ', $updates) . " WHERE id = ?";
    $values[] = $record_id;
    $types .= "i";

    $update_stmt = $conn->prepare($sql);
    $update_stmt->bind_param($types, ...$values);

    if ($update_stmt->execute()) {
        ob_clean();
        echo json_encode(['status' => 'success']);
    } else {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => $conn->error]);
    }
    exit;
}

?>