<?php
session_start();
include_once '../auth_check.php';
include '../db.php';

// Force PHP to show database errors
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
date_default_timezone_set('Asia/Manila');

function safeJson($data) {
    foreach ($data as $key => $value) {
        if (is_string($value)) {
            // Fix encoding and remove invalid characters
            $data[$key] = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        }
    }
    // Return encoded with flags to handle quotes safely
    return htmlspecialchars(json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');
}

// 1. INPUTS
$limit = 50; 
$page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$offset = ($page - 1) * $limit;

$selected_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'Date_Received';
$order = (isset($_GET['order']) && strtolower($_GET['order']) == 'asc') ? 'ASC' : 'DESC';

// 2. ADMINISTRATIVE TERM LOGIC (July 01 - June 30)
$start_date = "$selected_year-07-01";
$end_date = ($selected_year + 3) . "-06-30";

$where_clauses = ["(Date_Received BETWEEN ? AND ?)"];
$params = [$start_date, $end_date];
$types = "ss";

if (!empty($search)) {
    $where_clauses[] = "(Subject LIKE ? OR Proponent LIKE ? OR Folder LIKE ? OR Item_Nr LIKE ?)";
    $s_term = "%$search%";
    $params[] = $s_term; $params[] = $s_term; $params[] = $s_term; $params[] = $s_term;
    $types .= "ssss";
}

$where_sql = "WHERE " . implode(" AND ", $where_clauses);

// 3. GET TOTAL COUNT
$count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM city_records $where_sql");
$count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_rows = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// 4. FETCH DATA
$data_sql = "SELECT * FROM city_records $where_sql ORDER BY $sort $order LIMIT ? OFFSET ?";
$stmt_records = $conn->prepare($data_sql);
$data_types = $types . "ii";
$data_params = array_merge($params, [$limit, $offset]);
$stmt_records->bind_param($data_types, ...$data_params);
$stmt_records->execute();
$records_result = $stmt_records->get_result();

/** DISPLAY HELPERS **/
function cleanDisplay($v) { $v = trim($v); return (empty($v) || $v === "-none-") ? "<span style='color: #94a3b8; font-style: italic;'>-none-</span>" : htmlspecialchars($v); }
function formatTableDate($d) { if (empty($d) || $d == '0000-00-00') return "<span style='color: #94a3b8;'>-none-</span>"; return date('M d, Y', strtotime($d)); }
function formatTableTime($t) { if (empty($t)) return "<span style='color: #94a3b8;'>-none-</span>"; return date('h:i A', strtotime($t)); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../CSS/tools.css">
    <title>BCH | Records</title>
    <style>
        .wide-table-container { width: 100%; overflow-x: auto; border-radius: 12px; border: 1.5px solid #e2e8f0; background: #fff; margin-bottom: 20px;}
        table { min-width: 3500px; border-collapse: separate; border-spacing: 0; }
        th { position: sticky; top: 0; z-index: 10; background: #f8fafc; padding: 16px; border-bottom: 2px solid #e2e8f0; white-space: nowrap; font-size: 0.75rem; text-transform: uppercase; color: #64748b;}
        td { white-space: nowrap; font-size: 0.85rem; padding: 12px 16px; border-bottom: 1px solid #f1f5f9; color: #334155;}
        .folder-tag { background: #eff6ff; color: #1e40af; padding: 4px 10px; border-radius: 6px; font-weight: 700; border: 1px solid #bfdbfe; }
        .pagination { display: flex; justify-content: center; gap: 8px; margin: 30px 0; align-items: center; flex-wrap: wrap; }
        .page-link { padding: 8px 16px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; text-decoration: none; color: #475569; font-weight: 600; }
        .page-link.active { background: #3b82f6; color: #fff; border-color: #3b82f6; }
        .detail-label { display:block; font-size: 0.7rem; text-transform: uppercase; color: #64748b; font-weight: 800; margin-bottom: 4px;}
    </style>
</head>
<body>
    <nav>
        <img src="../Imag3s/baguio-logo-gov.png" alt="logo">
        <h1>Baguio City Database</h1>
        <div class="nav-actions">
            <button class="btn-nav btn-back-archive" onclick="window.location.href='table.php'">← Back</button>
            <button class="btn-nav logout" id="logout">Logout</button>
        </div>
    </nav>

    <main>
        <div id="container1">
            <div class="table-header">
                <h1>Term Records: <?php echo "$selected_year - " . ($selected_year+3); ?></h1>
                <div class="action-group">
                    <button id="add" style="background: #10b981;" onclick="document.getElementById('modal-add-manual').showModal()">+ Add Record</button>
                    <button id="add" style="background: #3b82f6;" onclick="document.getElementById('modal-import-year').showModal()">📥 Import CSV</button>
                </div>
            </div>

            <form method="GET" class="search-filter-section">
                <input type="hidden" name="year" value="<?php echo $selected_year; ?>">
                <div class="search-field">
                    <span class="search-icon">🔍</span>
                    <input type="text" name="search" placeholder="Search Subject, Proponent..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="filter-field">
                    <select name="sort" onchange="this.form.submit()">
                        <option value="Date_Received" <?php if($sort == 'Date_Received') echo 'selected'; ?>>Sort: Date</option>
                        <option value="Subject" <?php if($sort == 'Subject') echo 'selected'; ?>>Sort: Subject</option>
                    </select>
                </div>
            </form>

            <div class="wide-table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Date Received</th><th>Time Received</th><th>Type</th><th>Proponent</th><th>Subject</th>
                            <th>Description</th><th>Notation</th><th>Committee</th><th>Indorsement 1</th><th>Date Ind 1</th>
                            <th>Com Rep Nr</th><th>Com Rep</th><th>Date Rec (Rep)</th><th>Time Rec (Rep)</th>
                            <th>Item Nr</th><th>Agenda Date</th><th>Action Taken</th><th>Indorsement 2</th>
                            <th>Date Ind 2</th><th>Remarks</th><th>Folder</th>
                        </tr>
                    </thead>
<tbody>
    <?php
    if ($records_result->num_rows > 0) {
        while($row = $records_result->fetch_assoc()) {
            // Use our new safeJson function to prepare the data
            $json_data = safeJson($row);
            
            echo "<tr>";
                echo "<td>" . formatTableDate($row['Date_Received']) . "</td>";
                echo "<td>" . formatTableTime($row['Time_Received']) . "</td>";
                echo "<td>" . cleanDisplay($row['Type']) . "</td>";
                echo "<td>" . cleanDisplay($row['Proponent']) . "</td>";
                
                // CLICKABLE SUBJECT: Uses the cleaned json_data
                echo "<td style='font-weight:600;'>
                        <a href='javascript:void(0)' onclick='viewRecordDetails($json_data)' style='color: #3b82f6; text-decoration: none;'>
                            " . cleanDisplay($row['Subject']) . "
                        </a>
                      </td>";

                echo "<td>" . cleanDisplay($row['Subject_Description']) . "</td>";
                echo "<td>" . cleanDisplay($row['Subject_Notation']) . "</td>";
                echo "<td>" . cleanDisplay($row['Committee_Referred']) . "</td>";
                echo "<td>" . cleanDisplay($row['Indorsement1']) . "</td>";
                echo "<td>" . formatTableDate($row['Date_Indorsed1']) . "</td>";
                echo "<td>" . cleanDisplay($row['Com_Rep_Nr']) . "</td>";
                echo "<td>" . cleanDisplay($row['Com_Rep']) . "</td>";
                echo "<td>" . formatTableDate($row['Com_Rep_Date_Received']) . "</td>";
                echo "<td>" . formatTableTime($row['Com_Rep_Time_Received']) . "</td>";
                echo "<td>" . cleanDisplay($row['Item_Nr']) . "</td>";
                echo "<td>" . formatTableDate($row['Agenda_Date']) . "</td>";
                echo "<td>" . cleanDisplay($row['Action_Taken']) . "</td>";
                echo "<td>" . cleanDisplay($row['Indorsement2']) . "</td>";
                echo "<td>" . formatTableDate($row['Indorsement2_Date']) . "</td>";
                echo "<td>" . cleanDisplay($row['Remarks']) . "</td>";
                echo "<td><span class='folder-tag'>" . cleanDisplay($row['Folder']) . "</span></td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='21' style='text-align:center; padding:60px;'>No records found for this period.</td></tr>";
    }
    ?>
</tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- 1. DETAIL VIEW & EDIT MODAL -->
    <dialog id="modal-view-details" style="max-width: 900px; width: 95%; border: none; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.4); padding: 0; overflow: hidden;">
        <form action="edit_record_logic.php" method="POST" id="edit-detail-form">
            <div style="padding: 24px 30px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
                <div>
                    <small id="modal-mode-label" style="text-transform: uppercase; color: #3b82f6; font-weight: 700; letter-spacing: 0.05em; font-size: 0.7rem;">Document Details</small>
                    <h2 id="view-subject-title" style="margin: 0; color: #0f172a; font-size: 1.4rem; font-weight: 800;">Subject Title</h2>
                </div>
                <button type="button" onclick="closeDetailModal()" style="background: #f1f5f9; border: none; width: 36px; height: 36px; border-radius: 50%; font-size: 1.2rem; cursor: pointer; color: #64748b;">&times;</button>
            </div>
            
            <input type="hidden" name="record_id" id="view-record-id">
            <input type="hidden" name="term_id" value="<?php echo $term_id; ?>">
            <input type="hidden" name="verify_passkey" id="hidden-passkey">

            <div id="details-content" style="padding: 30px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px; max-height: 60vh; overflow-y: auto;"></div>

            <div style="padding: 20px 30px; background: #f8fafc; border-top: 1px solid #f1f5f9; text-align: right;">
                <div class="action-group" style="justify-content: flex-end;">
                    <button type="button" id="btn-enable-edit" class="btn-action" onclick="enableEditMode()" style="color: #3b82f6; border-color: #bfdbfe;">📝 Edit Record</button>
                    <button type="button" id="btn-trigger-verify" class="btn-save" style="display: none;" onclick="openVerifyModal()">Save Changes</button>
                    <button type="button" class="btn-nav" onclick="closeDetailModal()" style="background:#ef4444; color:#fff; border:none;">Close</button>
                </div>
            </div>
        </form>
    </dialog>

    <!-- 2. PASSKEY VERIFICATION MODAL -->
    <dialog id="modal-verify-passkey" style="border: none; border-radius: 16px; padding: 25px; width: 350px; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
        <div style="text-align: center;">
            <h3 style="margin-bottom: 10px;">Security Verification</h3>
            <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 20px;">Enter your Admin Passkey to save changes.</p>
            <input type="password" id="popup-passkey-input" placeholder="Enter Passkey" style="text-align: center; border: 2px solid #3b82f6; margin-bottom: 20px; width:100%; padding:10px; border-radius:8px;">
            <div style="display: flex; gap: 10px;">
                <button type="button" class="btn-save" onclick="submitEditWithPasskey()" style="flex: 1;">Authorize</button>
                <button type="button" class="btn-nav" onclick="document.getElementById('modal-verify-passkey').close()" style="flex: 1; background: #94a3b8; border:none; color:#fff;">Cancel</button>
            </div>
        </div>
    </dialog>

    <!-- 3. MANUAL ADD MODAL -->
    <dialog id="modal-add-manual" style="max-width: 900px; width: 95%;">
        <form action="add_record_logic.php" method="POST" style="padding: 30px;">
            <h2>Add New Record</h2>
            <input type="hidden" name="redirect_id" value="<?php echo $term_id; ?>">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; max-height: 60vh; overflow-y: auto;">
                <!-- Column 1 -->
                <div class="form-group"><label>Date Received</label><input type="date" name="Date_Received" required></div>
                <div class="form-group"><label>Time Received</label><input type="time" name="Time_Received"></div>
                <div class="form-group"><label>Type</label><input type="text" name="Type"></div>
                <div class="form-group"><label>Proponent</label><input type="text" name="Proponent"></div>
                <!-- Spans -->
                <div class="form-group" style="grid-column: span 2;"><label>Subject</label><input type="text" name="Subject" required></div>
                <div class="form-group" style="grid-column: span 2;"><label>Description</label><textarea name="Subject_Description" rows="2"></textarea></div>
                <!-- Folder last -->
                <div class="form-group" style="grid-column: span 2;"><label>Folder</label><input type="text" name="Folder"></div>
            </div>
            <div class="modal-actions" style="margin-top: 25px;">
                <button type="submit" class="btn-save">Save Record</button>
                <button type="button" class="btn-nav" onclick="document.getElementById('modal-add-manual').close()">Cancel</button>
            </div>
        </form>
    </dialog>

    <!-- 4. IMPORT MODAL -->
    <dialog id="modal-import-year">
        <form action="import_logic.php" method="POST" enctype="multipart/form-data">
            <h2>Import CSV</h2>
            <input type="hidden" name="target_year" value="<?php echo $selected_year; ?>">
            <input type="hidden" name="redirect_source" value="year_view"> 
            <input type="file" name="csv_file" accept=".csv" required style="margin-bottom:20px;">
            <div class="modal-actions">
                <button type="submit" class="btn-save">Upload</button>
                <button type="button" class="btn-nav" onclick="document.getElementById('modal-import-year').close()">Cancel</button>
            </div>
        </form>
    </dialog>

    <script src="../scripts/main.js"></script>
</body>
</html>