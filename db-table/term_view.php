<?php
session_start();
include_once '../auth_check.php';
include '../db.php';

date_default_timezone_set('Asia/Manila');

// 1. SETTINGS & INPUTS
$limit = 50; 
$page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$offset = ($page - 1) * $limit;

$term_id = isset($_GET['id']) ? intval($_GET['id']) : die("Invalid Term ID");
$term_stmt = $conn->prepare("SELECT * FROM archive_terms WHERE id = ?");
$term_stmt->bind_param("i", $term_id);
$term_stmt->execute();
$term = $term_stmt->get_result()->fetch_assoc();
if (!$term) die("Term not found.");

$start_term = $term['start_date'];
$end_term   = $term['end_date'];

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort   = isset($_GET['sort']) ? $_GET['sort'] : 'Date_Received';
$order  = (isset($_GET['order']) && strtolower($_GET['order']) == 'asc') ? 'ASC' : 'DESC';

// NEW: Date Range Logic
$date_from = isset($_GET['df']) ? $_GET['df'] : '';
$date_to   = isset($_GET['dt']) ? $_GET['dt'] : '';

// 2. CONSTRUCT QUERY
// If user provided custom dates, use them. Otherwise, use the Term's dates.
$query_start = !empty($date_from) ? $date_from : $start_term;
$query_end   = !empty($date_to) ? $date_to : $end_term;

$where_clauses = ["(Date_Received BETWEEN ? AND ?)"];
$params = [$query_start, $query_end];
$types = "ss";

if (!empty($search)) {
    $where_clauses[] = "(Subject LIKE ? OR Proponent LIKE ? OR Folder LIKE ? OR Type LIKE ? OR Remarks LIKE ? OR Subject_Description LIKE ?)";
    $st = "%$search%";
    for($i=0; $i<6; $i++) { $params[] = $st; $types .= "s"; }
}

$where_sql = "WHERE " . implode(" AND ", $where_clauses);

// 3. GET COUNTERS
// Count for this filtered view
$count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM city_records $where_sql");
$count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_rows = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// General Total Counter (All time)
$gen_res = $conn->query("SELECT COUNT(*) as total FROM city_records");
$general_total = $gen_res->fetch_assoc()['total'];

// 4. FETCH DATA
$allowed_cols = ['Date_Received', 'Time_Received', 'Type', 'Proponent', 'Subject', 'Folder', 'Item_Nr', 'Agenda_Date'];
if (!in_array($sort, $allowed_cols)) { $sort = 'Date_Received'; }

$data_sql = "SELECT * FROM city_records $where_sql ORDER BY $sort $order LIMIT ? OFFSET ?";
$stmt_records = $conn->prepare($data_sql);
$data_types = $types . "ii";
$data_params = array_merge($params, [$limit, $offset]);
$stmt_records->bind_param($data_types, ...$data_params);
$stmt_records->execute();
$records_result = $stmt_records->get_result();

/** HELPERS **/
function safeJson($data) {
    foreach ($data as $key => $value) { if (is_string($value)) { $data[$key] = mb_convert_encoding($value, 'UTF-8', 'UTF-8'); } }
    return htmlspecialchars(json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');
}
function shortenText($text, $limit = 20) {
    $text = trim($text);
    if (empty($text) || $text === "-none-") return "<span style='color:#94a3b8; font-style:italic;'>-none-</span>";
    return (mb_strlen($text) > $limit) ? htmlspecialchars(mb_substr($text, 0, $limit)) . "..." : htmlspecialchars($text);
}
function cleanDisplay($v) { $v = trim($v); return (empty($v) || $v === "-none-") ? "<span style='color:#94a3b8; font-style:italic;'>-none-</span>" : htmlspecialchars($v); }
function formatTableDate($d) { if (empty($d) || $d == '0000-00-00') return "<span style='color:#94a3b8;'>-none-</span>"; return htmlspecialchars($d); }
function formatTableTime($t) { if (empty($t)) return "<span style='color:#94a3b8;'>-none-</span>"; return htmlspecialchars($t); }
function getSortURL($col, $currSort, $currOrder, $term_id, $search, $df, $dt) {
    $newOrder = ($col == $currSort && $currOrder == 'DESC') ? 'asc' : 'desc';
    return "?id=$term_id&sort=$col&order=$newOrder&search=" . urlencode($search) . "&df=$df&dt=$dt";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../CSS/tools.css">
    <title>BCH | Records</title>
    <style>
        .wide-table-container { width: 100%; overflow-x: auto; border: 1.5px solid #e2e8f0; border-radius: 12px; background: #fff; margin-bottom: 20px;}
        table { min-width: 4500px; border-collapse: separate; border-spacing: 0; table-layout: fixed;}
        th { position: sticky; top: 0; background: #f8fafc; z-index: 10; border-bottom: 2px solid #e2e8f0; white-space: nowrap; font-size: 0.75rem; text-transform: uppercase; color: #64748b; padding: 0; }
        th a { display: block; padding: 15px; text-decoration: none; color: inherit; width: 100%; height: 100%; transition: 0.2s; }
        td { padding: 12px 15px; border-bottom: 1px solid #f1f5f9; white-space: nowrap; font-size: 0.82rem; color: #334155; overflow: hidden; text-overflow: ellipsis;}
        .folder-tag { background: #eff6ff; color: #1e40af; padding: 4px 10px; border-radius: 6px; font-weight: 700; border: 1px solid #bfdbfe; font-size: 0.75rem; }
        .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin: 30px 0; flex-wrap: wrap; }
        .page-link { padding: 8px 16px; border-radius: 8px; background: #fff; border: 1.5px solid #e2e8f0; color: #475569; text-decoration: none; font-weight: 600; white-space: nowrap; }
        .page-link.active { background: #3b82f6; color: #fff; border-color: #3b82f6; }
        .stats-info { background: #f8fafc; padding: 10px 20px; border-radius: 8px; font-size: 0.85rem; color: #64748b; border: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <nav>
        <img src="../Imag3s/baguio-logo-gov.webp" alt="logo">
        <h1>Term Archives</h1>
        <div class="nav-actions">
            <button class="btn-nav back-btn" onclick="window.location.href='table.php'">← Back</button>
            <button class="btn-nav logout" id="logout">Logout</button>
        </div>
    </nav>

    <main>
        <div id="container1">
            <div class="table-header">
                <h1><?php echo $term['term_label']; ?></h1>
                <div class="action-group">
                    <button class="btn-action" style="background:#6366f1; color:#fff; border:none;" onclick="window.print()">🖨️ Print Report</button>
                    <a href="download_template.php" class="btn-action">📥 Template</a>
                    <button id="btn-export-selected" class="btn-action" style="color: #059669; border-color: #a7f3d0; opacity: 0.5; pointer-events: none;" onclick="exportSelectedRecords()">📄 Selected (<span id="selected-count">0</span>)</button>
                    <button id="add" style="background: #10b981;" onclick="document.getElementById('modal-add-manual').showModal()">+ Add Record</button>
                    <button id="add" style="background: #3b82f6;" onclick="document.getElementById('modal-import').showModal()">📂 Import CSV</button>
                </div>
            </div>

            <!-- Global Counters -->
            <div style="display:flex; gap:10px; margin-bottom:15px;">
                <div class="stats-info">General File Count: <strong><?php echo number_format($general_total); ?></strong></div>
                <div class="stats-info" style="border-color:#3b82f6;">Matching this Filter: <strong><?php echo number_format($total_rows); ?></strong></div>
            </div>

            <!-- SEARCH & DATE RANGE FORM -->
            <form method="GET" class="search-filter-section" id="filterForm">
                <input type="hidden" name="id" value="<?php echo $term_id; ?>">
                <div class="search-field">
                    <span class="search-icon">🔍</span>
                    <input type="text" name="search" placeholder="Search Subject, Proponent..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="filter-field" style="display: flex; gap: 8px; align-items:center;">
                    <label style="margin:0; font-size:0.7rem;">FROM:</label>
                    <input type="date" name="df" value="<?php echo $date_from; ?>" style="width:135px; margin:0; padding:8px;">
                    <label style="margin:0; font-size:0.7rem;">TO:</label>
                    <input type="date" name="dt" value="<?php echo $date_to; ?>" style="width:135px; margin:0; padding:8px;">
                    
                    <select name="sort" onchange="this.form.submit()" style="width: 100px; margin:0;">
                        <option value="Date_Received" <?php if($sort == 'Date_Received') echo 'selected'; ?>>Sort: Date</option>
                        <option value="Subject" <?php if($sort == 'Subject') echo 'selected'; ?>>Sort: Subject</option>
                    </select>
                    <button type="submit" class="btn-save" style="height:40px; padding:0 15px;">Filter</button>
                </div>
            </form>

            <div class="wide-table-container">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px; text-align: center;"><input type="checkbox" id="select-all-rows"></th>
                            <?php 
                            $hdrs = ["Date Received"=>"Date_Received", "Time Received"=>"Time_Received", "Type"=>"Type", "Proponent"=>"Proponent", "Subject"=>"Subject", "Description"=>"Subject_Description", "Notation"=>"Subject_Notation", "Committee"=>"Committee_Referred", "Indorsement 1"=>"Indorsement1", "Date Ind 1"=>"Date_Indorsed1", "Com Rep Nr"=>"Com_Rep_Nr", "Com Rep"=>"Com_Rep", "Date Rec (Rep)"=>"Com_Rep_Date_Received", "Time Rec (Rep)"=>"Com_Rep_Time_Received", "Item Nr"=>"Item_Nr", "Agenda Date"=>"Agenda_Date", "Action Taken"=>"Action_Taken", "Indorsement 2"=>"Indorsement2", "Date Ind 2"=>"Indorsement2_Date", "Remarks"=>"Remarks", "Folder"=>"Folder"];
                            foreach($hdrs as $l => $c) {
                                $icon = ($sort == $c) ? ($order == 'ASC' ? '▲' : '▼') : '↕';
                                echo "<th><a href='".getSortURL($c, $sort, $order, $term_id, $search, $date_from, $date_to)."'>$l <span class='sort-icon'>$icon</span></a></th>";
                            }
                            ?>
                        </tr>
                    </thead>
<tbody>
    <?php if ($records_result->num_rows > 0): $idx = 0; ?>
        <?php while($row = $records_result->fetch_assoc()): $rowData = safeJson($row); ?>
        <tr class="event-row">
            <td style="text-align: center;"><input type="checkbox" class="record-checkbox" value="<?php echo $row['id']; ?>"></td>
            <td><?php echo formatTableDate($row['Date_Received']); ?></td>
            <td><?php echo formatTableTime($row['Time_Received']); ?></td>
            <td><?php echo cleanDisplay($row['Type']); ?></td>
            <td><?php echo cleanDisplay($row['Proponent']); ?></td>
            
            <!-- Clickable Subject with data hidden inside the same cell -->
            <td style="font-weight:600;">
                <a href="javascript:void(0)" onclick="openDetailByIndex(<?php echo $idx; ?>)" style="color:#3b82f6; text-decoration:none;">
                    <?php echo shortenText($row['Subject'], 30); ?>
                </a>
                <div class="json-store" style="display:none;"><?php echo $rowData; ?></div>
            </td>

            <!-- Shorten the remaining columns -->
            <td><?php echo shortenText($row['Subject_Description'], 25); ?></td>
            <td><?php echo shortenText($row['Subject_Notation'], 20); ?></td>
            <td><?php echo cleanDisplay($row['Committee_Referred']); ?></td>
            <td><?php echo shortenText($row['Indorsement1'], 20); ?></td>
            <td><?php echo formatTableDate($row['Date_Indorsed1']); ?></td>
            <td><?php echo cleanDisplay($row['Com_Rep_Nr']); ?></td>
            <td><?php echo shortenText($row['Com_Rep'], 20); ?></td>
            <td><?php echo formatTableDate($row['Com_Rep_Date_Received']); ?></td>
            <td><?php echo formatTableTime($row['Com_Rep_Time_Received']); ?></td>
            <td><?php echo cleanDisplay($row['Item_Nr']); ?></td>
            <td><?php echo formatTableDate($row['Agenda_Date']); ?></td>
            <td><?php echo shortenText($row['Action_Taken'], 20); ?></td>
            <td><?php echo shortenText($row['Indorsement2'], 20); ?></td>
            <td><?php echo formatTableDate($row['Indorsement2_Date']); ?></td>
            <td><?php echo shortenText($row['Remarks'], 20); ?></td>
            <td><span class="folder-tag"><?php echo cleanDisplay($row['Folder']); ?></span></td>
        </tr>
        <?php $idx++; endwhile; ?>
    <?php else: ?>
        <tr><td colspan="22" style="text-align:center; padding:60px;">No records found.</td></tr>
    <?php endif; ?>
</tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            <?php if ($total_pages > 1): $base = "?id=$term_id&search=".urlencode($search)."&df=$date_from&dt=$date_to&sort=$sort&order=".strtolower($order); ?>
            <div class="pagination">
                <a href="<?php echo $base; ?>&p=1" class="page-link"><<</a>
                <?php for($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                    <a href="<?php echo $base; ?>&p=<?php echo $i; ?>" class="page-link <?php if($i == $page) echo 'active'; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
                <a href="<?php echo $base; ?>&p=<?php echo $total_pages; ?>" class="page-link">>></a>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Detail View Modal with Prev/Next -->
    <dialog id="modal-view-details" style="max-width: 900px; width: 95%; border: none; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.4); padding: 0; overflow: hidden;">
        <form action="edit_record_logic.php" method="POST" id="edit-detail-form">
            <div style="padding: 24px 30px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
                <div><small id="modal-mode-label" style="text-transform: uppercase; color: #3b82f6; font-weight: 700; font-size: 0.7rem;">Document Details</small><h2 id="view-subject-title" style="margin: 0; color: #0f172a; font-size: 1.4rem; font-weight: 800;">Subject Title</h2></div>
                <button type="button" onclick="closeDetailModal()" style="background: #f1f5f9; border: none; width: 36px; height: 36px; border-radius: 50%; cursor: pointer;">&times;</button>
            </div>
            <input type="hidden" name="record_id" id="view-record-id">
            <input type="hidden" name="term_id" value="<?php echo $term_id; ?>">
            <input type="hidden" name="verify_passkey" id="hidden-passkey">
            <div id="details-content" style="padding: 30px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px; max-height: 60vh; overflow-y: auto;"></div>
            
            <div style="padding: 20px 30px; background: #f8fafc; border-top: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <button type="button" class="btn-nav" onclick="navRecord(-1)" style="background:#fff; color:#475569; border:1px solid #e2e8f0;">« Prev</button>
                    <button type="button" class="btn-nav" onclick="navRecord(1)" style="background:#fff; color:#475569; border:1px solid #e2e8f0;">Next »</button>
                </div>
                <div class="action-group">
                    <button type="button" id="btn-enable-edit" class="btn-action btn-edit-blue" onclick="enableEditMode()">📝 Edit Record</button>
                    <button type="button" id="btn-trigger-verify" class="btn-save" style="display: none;" onclick="openVerifyModal()">💾 Save Changes</button>
                    <button type="button" class="btn-danger" onclick="closeDetailModal()">Close</button>
                </div>
            </div>
        </form>
    </dialog>

    <!-- Verification Modal -->
    <dialog id="modal-verify-passkey" style="border: none; border-radius: 16px; padding: 25px; width: 380px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); margin: auto;">
        <div style="text-align: center;"><h3>Security Verification</h3><input type="password" id="popup-passkey-input" placeholder="Passkey" style="text-align:center; border:2px solid #3b82f6; margin:20px 0; padding:12px; border-radius:10px; width:100%;"><div style="display:flex; gap:10px;"><button type="button" class="btn-save" onclick="submitEditWithPasskey()" style="flex:1;">Authorize</button><button type="button" class="btn-nav" onclick="document.getElementById('modal-verify-passkey').close()" style="flex:1; background:#94a3b8; color:#fff;">Cancel</button></div></div>
    </dialog>

    <!-- Manual Add Modal (21 Fields) -->
    <dialog id="modal-add-manual" style="max-width: 900px; width: 95%;">
        <form action="add_record_logic.php" method="POST" style="padding: 30px;">
            <h2>Add New Record</h2><input type="hidden" name="redirect_id" value="<?php echo $term_id; ?>">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; max-height: 60vh; overflow-y: auto; padding-right:10px;">
                <div><label>Date Received</label><input type="date" name="Date_Received" required></div>
                <div><label>Time Received</label><input type="time" name="Time_Received"></div>
                <div><label>Type</label><input type="text" name="Type"></div>
                <div><label>Proponent</label><input type="text" name="Proponent"></div>
                <div style="grid-column: span 2;"><label>Subject</label><input type="text" name="Subject" required></div>
                <div style="grid-column: span 2;"><label>Description</label><textarea name="Subject_Description" rows="2"></textarea></div>
                <div><label>Notation</label><input type="text" name="Subject_Notation"></div>
                <div><label>Committee Referred</label><input type="text" name="Committee_Referred"></div>
                <div style="grid-column: span 2;"><label>Indorsement 1</label><textarea name="Indorsement1" rows="2"></textarea></div>
                <div><label>Date Ind 1</label><input type="date" name="Date_Indorsed1"></div>
                <div><label>Com Rep Nr</label><input type="text" name="Com_Rep_Nr"></div>
                <div style="grid-column: span 2;"><label>Com Rep Content</label><textarea name="Com_Rep" rows="2"></textarea></div>
                <div><label>Date Rec (Rep)</label><input type="date" name="Com_Rep_Date_Received"></div>
                <div><label>Time Rec (Rep)</label><input type="time" name="Com_Rep_Time_Received"></div>
                <div><label>Item Nr</label><input type="text" name="Item_Nr"></div>
                <div><label>Agenda Date</label><input type="date" name="Agenda_Date"></div>
                <div style="grid-column: span 2;"><label>Action Taken</label><textarea name="Action_Taken" rows="2"></textarea></div>
                <div style="grid-column: span 2;"><label>Indorsement 2</label><textarea name="Indorsement2" rows="2"></textarea></div>
                <div><label>Date Ind 2</label><input type="date" name="Indorsement2_Date"></div>
                <div style="grid-column: span 2;"><label>Remarks</label><textarea name="Remarks" rows="2"></textarea></div>
                <div style="grid-column: span 2;"><label>Folder</label><input type="text" name="Folder"></div>
            </div>
            <div class="modal-actions" style="margin-top:25px;"><button type="submit" class="btn-save">Save Record</button><button type="button" class="btn-nav" onclick="document.getElementById('modal-add-manual').close()">Cancel</button></div>
        </form>
    </dialog>

    <!-- Import Modal -->
    <dialog id="modal-import">
        <form action="import_logic.php" method="POST" enctype="multipart/form-data" onsubmit="return confirmImport()">
            <input type="hidden" name="redirect_id" value="<?php echo $term_id; ?>">
            <h2>Import CSV Data</h2>
            <div style="background:#fff7ed; border:1px solid #ffedd5; padding:15px; border-radius:10px; margin-bottom:20px;"><p style="color:#9a3412; font-size:0.85rem; font-weight:600;">⚠️ <strong>IMPORTANT:</strong> Records must be between <?php echo date('M d, Y', strtotime($start_term)); ?> and <?php echo date('M d, Y', strtotime($end_term)); ?>.</p></div>
            <div class="file-upload-wrapper"><input type="file" name="csv_file" accept=".csv" required></div>
            <div class="modal-actions"><button type="submit" class="btn-save">Import Now</button><button type="button" class="btn-nav" onclick="document.getElementById('modal-import').close()">Cancel</button></div>
        </form>
    </dialog>

    <script src="../scripts/main.js"></script>
</body>
</html>