<?php
session_start();
include_once '../auth_check.php';
include '../db.php';

$conn->set_charset("utf8mb4");
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
date_default_timezone_set('Asia/Manila');

// --- 1. CATEGORY & TABLE MAPPING ---
$active_cat = isset($_GET['cat']) ? $_GET['cat'] : 'main';

// Determine default GAD subcategory grouping (Active if either women or wofad is selected)
$is_gad_active = ($active_cat === 'women' || $active_cat === 'wofad');

if ($active_cat === 'women') {
    $db_table = "women_records";
    $page_title = "Ordinances on Women";
    // MATCH THESE EXACTLY TO THE SQL:
    $allowed_cols = ['id', 'Type_of_law', 'Number', 'Series', 'Title', 'Author']; 
    $search_cols = ['Type_of_law', 'Number', 'Series', 'Title', 'Author', 'Implementing_department'];
    $sort_default = "id";
    $add_action = "add_women_logic.php";
    $import_action = "import_women_logic.php";
} elseif ($active_cat === 'wofad') {
    $db_table = "wofad_records";
    $page_title = "WOFAD Records";
    $allowed_cols = ['id', 'Proponent', 'Subject', 'Status'];
    $search_cols = ['Proponent', 'Subject', 'Status'];
    $sort_default = "id";
    $add_action = "add_wofad_logic.php";
    $import_action = "import_wofad_logic.php";
} elseif ($active_cat === 'others') {
    $db_table = "other_records";
    $page_title = "Other Records";
    $allowed_cols = ['id', 'Date', 'Title', 'Category'];
    $sort_default = "id";
    $add_action = "add_others_logic.php";
    $import_action = "import_others_logic.php";
} else {
    $db_table = "city_records";
    $page_title = "General Registry";
    $allowed_cols = ['Date_Received', 'Ref_No', 'Type', 'Proponent', 'Subject', 'Folder'];
    $sort_default = "Date_Received";
    $add_action = "add_record_logic.php";
    $import_action = "import_logic.php";
}

// --- 2. SETTINGS & INPUTS ---
$limit = 50; 
$page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$offset = ($page - 1) * $limit;

$search      = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort        = isset($_GET['sort']) ? $_GET['sort'] : $sort_default;
$order       = (isset($_GET['order']) && strtolower($_GET['order']) == 'asc') ? 'ASC' : 'DESC';
$date_from   = isset($_GET['df']) ? $_GET['df'] : '';
$date_to     = isset($_GET['dt']) ? $_GET['dt'] : '';
$year_filter = isset($_GET['y']) ? intval($_GET['y']) : ''; 

if (!in_array($sort, $allowed_cols)) { $sort = $sort_default; }

// --- 3. CONSTRUCT DYNAMIC QUERY ---
$where_clauses = ["1=1"];
$params = [];
$types = "";

if ($active_cat === 'main') {
    if (!empty($year_filter)) { $where_clauses[] = "YEAR(Date_Received) = ?"; $params[] = $year_filter; $types .= "i"; }
    if (!empty($date_from) && !empty($date_to)) { $where_clauses[] = "(Date_Received BETWEEN ? AND ?)"; $params[] = $date_from; $params[] = $date_to; $types .= "ss"; }
}

if (!empty($search)) {
    if ($active_cat === 'women') {
        $where_clauses[] = "(Title LIKE ? OR Author LIKE ? OR Number LIKE ? OR Series LIKE ? OR Type_of_law LIKE ?)";
        $st = "%$search%"; 
        for($i=0; $i<5; $i++){ $params[] = $st; $types .= "s"; }
    } elseif ($active_cat === 'wofad') {
        $where_clauses[] = "(Proponent LIKE ? OR Subject LIKE ? OR Status LIKE ?)";
        $st = "%$search%"; 
        for($i=0; $i<3; $i++){ $params[] = $st; $types .= "s"; }
    } elseif ($active_cat === 'others') {
        $where_clauses[] = "(Title LIKE ? OR Category LIKE ? OR Remarks LIKE ?)";
        $st = "%$search%"; for($i=0; $i<3; $i++){ $params[] = $st; $types .= "s"; }
    } else {
        $where_clauses[] = "(Subject LIKE ? OR Proponent LIKE ? OR Folder LIKE ? OR Ref_No LIKE ? OR Item_Nr LIKE ? OR Subject_Description LIKE ?)";
        $st = "%$search%"; for($i=0; $i<6; $i++){ $params[] = $st; $types .= "s"; }
    }
}
$where_sql = "WHERE " . implode(" AND ", $where_clauses);

// --- 4. COUNTERS & FETCH ---
$db_error_message = "";
try {
    $count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM $db_table $where_sql");
    if(!empty($types)) { $count_stmt->bind_param($types, ...$params); }
    $count_stmt->execute();
    $total_rows = $count_stmt->get_result()->fetch_assoc()['total'];
    $total_pages = ceil($total_rows / $limit);

    $stmt_records = $conn->prepare("SELECT * FROM $db_table $where_sql ORDER BY $sort $order LIMIT ? OFFSET ?");
    $data_types = $types . "ii";
    $data_params = array_merge($params, [$limit, $offset]);
    $stmt_records->bind_param($data_types, ...$data_params);
    $stmt_records->execute();
    $records_result = $stmt_records->get_result();
} catch (mysqli_sql_exception $e) {
    $total_rows = 0;
    $total_pages = 0;
    $records_result = false;
    $db_error_message = $e->getMessage();
}

// --- HELPERS ---
function safeJson($data) {
    foreach ($data as $key => $val) { if (is_string($val)) $data[$key] = mb_convert_encoding($val, 'UTF-8', 'UTF-8'); }
    return htmlspecialchars(json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');
}
function shorten($t, $l = 30) {
    $t = trim($t);
    if (empty($t) || $t === "-none-") return "<span class='none-text'>-none-</span>";
    return (mb_strlen($t) > $l) ? htmlspecialchars(mb_substr($t, 0, $l)) . "..." : htmlspecialchars($t);
}
function formatTableDate($d) { if (empty($d) || $d == '0000-00-00') return "<span class='none-text'>-none-</span>"; return date('M d, Y', strtotime($d)); }
function getSortURL($col, $currSort, $currOrder, $search, $df, $dt, $year, $cat) {
    $newOrder = ($col == $currSort && $currOrder == 'DESC') ? 'asc' : 'desc';
    return "?cat=$cat&sort=$col&order=$newOrder&search=".urlencode($search)."&df=$df&dt=$dt&y=$year";
}

$years_res = $conn->query("SELECT DISTINCT YEAR(Date_Received) as y FROM city_records WHERE Date_Received != '0000-00-00' ORDER BY y DESC");
$available_years = [];
while($yr = $years_res->fetch_assoc()) { $available_years[] = $yr['y']; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../Imag3s/baguio-logo-gov.png" type="image/png">
    <link rel="stylesheet" href="../CSS/table.css">
    <title>BCH | Administrative Records</title>
    <style>
        .category-tabs { display: flex; gap: 5px; margin-bottom: -1px; padding-left: 10px; margin-top: 20px;}
        .tab-btn { padding: 12px 25px; border-radius: 12px 12px 0 0; border: 1px solid #e2e8f0; background: #f1f5f9; color: #64748b; font-weight: 700; cursor: pointer; text-decoration: none; font-size: 0.85rem; transition: 0.3s; }
        .tab-btn.active { background: white; color: #1e3a8a; border-bottom-color: white; box-shadow: 0 -4px 10px rgba(0,0,0,0.03); }
        
        /* GAD CORNER SUB-TABS STYLING */
        .gad-subtabs { display: flex; gap: 10px; margin: 25px; border-bottom: 2px solid #e2e8f0; padding-bottom: 15px; width: 100%; }
        .subtab-btn { text-decoration: none; padding: 10px 18px; border-radius: 10px; font-size: 0.85rem; font-weight: 700; border: 1px solid #cbd5e1; background: #f8fafc; color: #475569; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 6px; }
        .subtab-btn:hover:not(.disabled) { background: #eff6ff; color: #2563eb; border-color: #3b82f6; }
        .subtab-btn.active { background: #1e3a8a; color: white; border-color: #1e3a8a; }
        .subtab-btn.disabled { opacity: 0.6; cursor: not-allowed; background: #f1f5f9; border-color: #cbd5e1; color: #64748b; }
        .subtab-badge { font-size: 0.65rem; background: #cbd5e1; color: #334155; padding: 1px 6px; border-radius: 4px; font-weight: 800; text-transform: uppercase; margin-left: 4px;}

        .btn-table-view {
            background: #eff6ff; color: #2563eb; border: 1px solid #dbeafe;
            padding: 6px 12px; border-radius: 6px; font-size: 0.8rem; font-weight: 700;
            cursor: pointer; transition: 0.2s; display: flex; align-items: center; gap: 5px;
        }
        .btn-table-view:hover { background: #2563eb; color: white; }

        .history-tooltip-wrapper { position: relative; display: inline-block; }
        .action-pill { cursor: help; position: relative; }
        .tooltip-box {
            visibility: hidden; width: 280px; background-color: #1e293b; color: #fff; text-align: left;
            border-radius: 10px; padding: 12px; position: absolute; z-index: 100; bottom: 125%; left: 50%;
            margin-left: -140px; opacity: 0; transition: opacity 0.3s; font-size: 0.8rem; line-height: 1.4;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2); pointer-events: none; border: 1px solid #334155;
        }
        .tooltip-box::after { content: ""; position: absolute; top: 100%; left: 50%; margin-left: -5px; border-width: 5px; border-style: solid; border-color: #1e293b transparent transparent transparent; }
        .history-tooltip-wrapper:hover .tooltip-box { visibility: visible; opacity: 1; }
        .tooltip-header { display: block; font-weight: 800; color: #38bdf8; margin-bottom: 5px; text-transform: uppercase; font-size: 0.65rem; border-bottom: 1px solid #334155; padding-bottom: 3px; }
        
        .import-dashed-box { border: 3px dashed #cbd5e1; background: #f8fafc; padding: 50px 20px; border-radius: 25px; cursor: pointer; transition: all 0.3s ease; display: flex; flex-direction: column; align-items: center; }
        .import-dashed-box:hover { border-color: #2563eb; background: #eff6ff; transform: scale(1.02); }
        .modal-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; padding: 30px; max-height: 65vh; overflow-y: auto; }
        .form-group.full { grid-column: span 2; }
        .spinner { width: 60px; height: 60px; border: 6px solid #f3f3f3; border-top: 6px solid #1e3a8a; border-radius: 50%; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body class="table-page-bg">
    
    <div id="page-loader"><div class="loader-wrapper"><img src="../Imag3s/baguio-logo-gov.png" class="loader-logo"><div class="loader-circle"></div></div></div>

    <nav class="table-nav">
        <div class="nav-left">
            <img src="../Imag3s/baguio-logo-gov.png" alt="logo">
            <div class="nav-text"><h1>Sangguniang Panlungsod</h1><span><?php echo ($is_gad_active) ? "GAD Corner" : $page_title; ?></span></div>
        </div>
        <div class="nav-actions">
            <?php $homePath = ($_SESSION['role'] === 'admin') ? '../admin/admin.php' : '../user/user.php'; ?>
            <button class="btn-home" onclick="window.location.href='<?php echo $homePath; ?>'">Home</button>
            <button class="btn-nav logout" onclick="window.location.href='/logout.php'">Logout</button>
        </div>
    </nav>

    <main class="table-main-content">
        <!-- PRIMARY TABS: Exactly 2 Main Category Headers -->
        <div class="category-tabs">
            <a href="?cat=main" class="tab-btn <?php echo ($active_cat == 'main') ? 'active' : ''; ?>">📂 General Registry</a>
            <a href="?cat=women" class="tab-btn <?php echo ($is_gad_active) ? 'active' : ''; ?>">🌈 GAD Corner</a>
        </div>

        <div class="registry-card">
            
            <!-- SECONDARY SUB-TABS (Only displayed inside GAD Corner) -->
            <?php if ($is_gad_active): ?>
                <div class="gad-subtabs">
                    <a href="?cat=women" class="subtab-btn <?php echo ($active_cat === 'women') ? 'active' : ''; ?>">⚖️ Ordinances on Women</a>
                    <a href="?cat=wofad" class="subtab-btn <?php echo ($active_cat === 'wofad') ? 'active' : ''; ?>">🗣️ WOFAD</a>
                    <span class="subtab-btn disabled">📁 <span class="subtab-badge">Soon</span></span>
                </div>
            <?php endif; ?>

            <div class="registry-header">
                <div class="header-titles">
                    <h2><?php echo $page_title; ?></h2>
                    <div class="stats-badges">
                        <span class="stat-badge">Filtered Total: <?php echo number_format($total_rows); ?></span>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="download_template.php?cat=<?php echo $active_cat; ?>" class="btn-action-gray">Template</a>
                    <button id="btn-export-selected" class="btn-action-gray disabled" onclick="exportSelectedRecords()">Export Selected (<span id="selected-count">0</span>)</button>
                    <button class="btn-add-record" onclick="document.getElementById('modal-add-manual').showModal()">+ New Entry</button>
                    <button class="btn-import-csv" onclick="document.getElementById('modal-import').showModal()">📂 Import CSV</button>
                </div>
            </div>

            <form method="GET" class="filter-toolbar">
                <input type="hidden" name="cat" value="<?php echo $active_cat; ?>">
                <div class="search-box"><span class="search-icon">🔍</span><input type="text" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>"></div>
                <div class="filter-controls">
                    <?php if($active_cat == 'main'): ?>
                    <select name="y" onchange="this.form.submit()" class="order-dropdown">
                        <option value="">All Years</option>
                        <?php foreach($available_years as $yr): ?>
                            <option value="<?php echo $yr; ?>" <?php echo ($year_filter == $yr) ? 'selected' : ''; ?>><?php echo $yr; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>
                    <select name="order" onchange="this.form.submit()" class="order-dropdown">
                        <option value="desc" <?php echo $order == 'DESC' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="asc" <?php echo $order == 'ASC' ? 'selected' : ''; ?>>Oldest First</option>
                    </select>
                    <button type="submit" class="btn-apply-filter">Apply</button>
                </div>
            </form>

            <div class="table-viewport">
                <table>
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="select-all-rows"></th>
                            <?php if($active_cat == 'women'): ?>
                                <th>Type of Law</th><th>No.</th><th>Series</th><th>Title</th><th>Author</th>
                            <?php elseif($active_cat == 'wofad'): ?>
                                <th>Proponent</th><th>Subject</th><th>Status</th>
                            <?php elseif($active_cat == 'others'): ?>
                                <th>Date</th><th>Title</th><th>Category</th><th>Remarks</th>
                            <?php else: ?>
                                <th>Date Rec.</th><th>Type</th><th>Proponent</th><th>Subject</th><th>Latest Action</th>
                            <?php endif; ?>
                            <th style="width: 100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($records_result === false): ?>
                            <tr>
                                <td colspan="10" style="text-align:center; padding:50px; color:#ef4444; font-weight:bold;">
                                    ⚠️ Database Table Error: <?php echo htmlspecialchars($db_error_message); ?><br>
                                    <span style="font-size:0.85rem; font-weight:normal; color:#6b7280; display:block; margin-top:10px;">Please ensure that the table <code><?php echo htmlspecialchars($db_table); ?></code> has been created in your MySQL database.</span>
                                </td>
                            </tr>
                        <?php elseif ($records_result->num_rows > 0): $idx = 0; while($row = $records_result->fetch_assoc()): 
                            $rowData = safeJson($row); ?>
                            <tr>
                                <td style="text-align: center;"><input type="checkbox" class="record-checkbox" value="<?php echo $row['id']; ?>"></td>
                                <?php if($active_cat == 'women'): ?>
                                    <td><?php echo $row['Type_of_law']; ?></td><td><?php echo $row['Number']; ?></td><td><?php echo $row['Series']; ?></td><td class="subject-cell"><?php echo shorten($row['Title'], 60); ?></td><td><?php echo $row['Author']; ?></td>
                                <?php elseif($active_cat == 'wofad'): ?>
                                    <td><?php echo htmlspecialchars($row['Proponent'] ?? ''); ?></td><td class="subject-cell"><?php echo shorten($row['Subject'] ?? '', 60); ?></td><td><?php echo htmlspecialchars($row['Status'] ?? ''); ?></td>
                                <?php elseif($active_cat == 'others'): ?>
                                    <td><?php echo formatTableDate($row['Date']); ?></td><td class="subject-cell"><?php echo shorten($row['Title'], 60); ?></td><td><?php echo $row['Category']; ?></td><td><?php echo shorten($row['Remarks'], 40); ?></td>
                                <?php else: ?>
                                    <td class="text-mono"><?php echo formatTableDate($row['Date_Received']); ?></td><td><span class="type-badge"><?php echo $row['Type']; ?></span></td><td><?php echo shorten($row['Proponent'], 25); ?></td><td class="subject-cell"><?php echo shorten($row['Subject'], 60); ?></td>
                                    <td>
                                        <?php 
                                            $history = $row['Action_Taken'] ?? ''; $latestMsg = "Initial Receipt / No specific history.";
                                            if(!empty($history) && $history !== "-none-") {
                                                $historyArr = explode('|||', $history); $latestParts = explode('@@@', $historyArr[0]);
                                                $latestMsg = isset($latestParts[1]) ? trim($latestParts[1]) : trim($latestParts[0]);
                                            }
                                        ?>
                                        <div class="history-tooltip-wrapper"><span class="action-pill status-initial">View History</span><div class="tooltip-box"><span class="tooltip-header">Latest Progress</span><?php echo htmlspecialchars($latestMsg); ?></div></div>
                                    </td>
                                <?php endif; ?>
                                <td><button class="btn-table-view" onclick="openDetailByIndex(<?php echo $idx; ?>)"><span>📝</span> Details</button></td>
                                <div class="json-store" style="display:none;"><?php echo $rowData; ?></div>
                            </tr>
                        <?php $idx++; endwhile; else: ?>
                            <tr><td colspan="10" style="text-align:center; padding:50px;">No records match your filters.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): $base = "?cat=$active_cat&search=".urlencode($search)."&y=$year_filter&sort=$sort&order=".strtolower($order); ?>
            <div class="table-pagination">
                <a href="<?php echo $base; ?>&p=1" class="page-btn">« First</a>
                <div class="page-numbers">
                    <?php for($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                        <a href="<?php echo $base; ?>&p=<?php echo $i; ?>" class="page-btn <?php if($i == $page) echo 'active'; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
                <a href="<?php echo $base; ?>&p=<?php echo $total_pages; ?>" class="page-btn">Last »</a>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal: View & Edit -->
    <dialog id="modal-view-details" class="table-modal">
        <form action="edit_record_logic.php" method="POST" id="edit-detail-form">
            <input type="hidden" name="cat" value="<?php echo $active_cat; ?>">
            <div class="modal-head">
                <div class="modal-title-box"><small id="modal-mode-label">Registry Details</small><h2 id="view-subject-title">Loading...</h2></div>
                <button type="button" class="modal-close-x" onclick="closeDetailModal()">&times;</button>
            </div>
            <input type="hidden" name="record_id" id="view-record-id">
            <input type="hidden" name="verify_passkey" id="hidden-passkey">
            <div id="details-content" class="modal-details-grid"></div>
            <div class="modal-foot">
                <div class="record-actions" style="display:flex; gap:10px; width:100%; justify-content: flex-end;">
    				<button type="button" class="btn-nav-outline" style="color:var(--danger); border-color:#fecaca;" onclick="requestDeleteRecord()">🗑️ Delete Record</button>
                    <button type="button" id="btn-enable-edit" class="btn-nav-outline" onclick="enableEditMode()">📝 Edit</button>
                    <button type="button" id="btn-trigger-verify" class="btn-add-record" style="display: none;" onclick="openVerifyModal()">💾 Save</button>
                    <button type="button" class="btn-close-gray" onclick="closeDetailModal()">Close</button>
                </div>
            </div>
        </form>
    </dialog>

    <dialog id="modal-verify-passkey" class="table-modal" style="max-width:400px;">
        <div style="padding:30px; text-align:center;"><div style="font-size:3rem;">🔐</div><h3>Verify Identity</h3><input type="password" id="popup-passkey-input" placeholder="Passkey" style="width:100%; padding:12px; margin-top:15px; border-radius:10px; border:1px solid #ddd; text-align:center;"></div>
        <div class="modal-foot" style="justify-content:center;"><button type="button" class="btn-add-record" onclick="submitEditWithPasskey()">Verify & Save</button></div>
    </dialog>

    <!-- Modal: Add New Record -->
    <dialog id="modal-add-manual" class="table-modal" style="max-width:1000px;">
        <form action="<?php echo $add_action; ?>" method="POST">
            <input type="hidden" name="category" value="<?php echo $active_cat; ?>">
            <div class="modal-head"><div class="modal-title-box"><small>Manual Entry</small><h2>New Entry: <?php echo $page_title; ?></h2></div><button type="button" class="modal-close-x" onclick="document.getElementById('modal-add-manual').close()">&times;</button></div>
            <div class="modal-form-grid">
                <?php 
                if($active_cat == 'women') {
                    $manual_hdrs = ["Type of law"=>"Type_of_law", "Number"=>"Number", "Series"=>"Series", "Title"=>"Title", "Author"=>"Author", "Implementing Dept"=>"Implementing_department", "Hyperlink"=>"Hyperlink", "Other Attachment"=>"Other_Attachment", "Hyperlink 1"=>"Hyperlink1", "Hyperlink 2"=>"Hyperlink2", "Hyperlink 3"=>"Hyperlink3"];
                } elseif($active_cat == 'wofad') {
                    $manual_hdrs = ["Proponent"=>"Proponent", "Subject"=>"Subject", "Status"=>"Status"];
                } elseif($active_cat == 'others') {
                    $manual_hdrs = ["Date"=>"Date", "Title"=>"Title", "Category"=>"Category", "Remarks"=>"Remarks", "Hyperlink"=>"Hyperlink"];
                } else {
                    $manual_hdrs = ["Date Received"=>"Date_Received", "Time Received"=>"Time_Received", "Ref No" => "Ref_No" ,"Type"=>"Type", "Proponent"=>"Proponent", "Subject"=>"Subject", "Description"=>"Subject_Description", "Notation"=>"Subject_Notation", "Committee"=>"Committee_Referred", "Indorsement 1"=>"Indorsement1", "Date Ind 1"=>"Date_Indorsed1", "Com Rep Nr"=>"Com_Rep_Nr", "Com Rep"=>"Com_Rep", "Date Rec (Rep)"=>"Com_Rep_Date_Received", "Item Nr"=>"Item_Nr", "Agenda Date"=>"Agenda_Date", "Action Taken"=>"Action_Taken", "Indorsement 2"=>"Indorsement2", "Date Ind 2"=>"Indorsement2_Date", "Remarks"=>"Remarks", "Folder"=>"Folder"];
                }
                foreach($manual_hdrs as $l => $c): 
                    $isFull = in_array($c, ['Subject', 'Subject_Description', 'Action_Taken', 'Remarks', 'Title', 'Hyperlink', 'Other_Attachment']);
                ?>
                    <div class="form-group <?php echo $isFull ? 'full' : ''; ?>"><label><?php echo $l; ?></label>
                    <?php if($isFull): ?><textarea name="<?php echo $c; ?>" rows="2"></textarea>
                    <?php else: ?><input type="<?php echo (strpos($c, 'Date') !== false) ? 'date' : 'text'; ?>" name="<?php echo $c; ?>">
                    <?php endif; ?></div>
                <?php endforeach; ?>
            </div>
            <div class="modal-foot" style="justify-content: flex-end; gap: 10px;"><button type="button" class="btn-close-gray" onclick="document.getElementById('modal-add-manual').close()">Discard</button><button type="submit" class="btn-add-record">Save Entry</button></div>
        </form>
    </dialog> 
    
    <div id="upload-loader" style="display:none; position:fixed; inset:0; background:rgba(255,255,255,0.9); z-index:10000; flex-direction:column; align-items:center; justify-content:center; backdrop-filter:blur(5px);"><div class="spinner"></div><p style="margin-top:25px; font-weight:900; color:#1e3a8a; text-align:center;">UPDATING DATABASE...</p></div>
    
    
    <dialog id="modal-import" class="table-modal" style="max-width:500px;">
        <form action="<?php echo $import_action; ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="cat" value="<?php echo $active_cat; ?>">
            <div class="modal-head"><h2>Batch Import (<?php echo $page_title; ?>)</h2><button type="button" class="modal-close-x" onclick="document.getElementById('modal-import').close()">&times;</button></div>
            <div style="padding:40px 30px; text-align:center;"><div class="import-dashed-box" onclick="document.getElementById('csv_inp').click()"><input type="file" name="csv_file" accept=".csv" required id="csv_inp" style="display:none;"><div style="font-size:3.5rem;">📊</div><div style="font-weight:800;">Click to Select CSV</div><div id="csv-name-display" style="margin-top:12px; color:#2563eb; font-weight:700;"></div></div></div>
            <div class="modal-foot" style="justify-content: center; padding-bottom:30px; border:none;"><button type="submit" class="btn-add-record" style="width:80%;" onclick="document.getElementById('modal-import').close()">Execute Import</button></div>
        </form>
    </dialog>



    <script src="../scripts/main.js"></script>
    <script>
        // Display CSV filename on selection
        document.getElementById('csv_inp').onchange = function() { 
            document.getElementById('csv-name-display').textContent = this.files[0] ? this.files[0].name : ""; 
        };

        // --- Checkbox Selection & Count Management ---
        document.addEventListener('DOMContentLoaded', function() {
            const selectAll = document.getElementById('select-all-rows');
            const checkboxes = document.querySelectorAll('.record-checkbox');
            const exportBtn = document.getElementById('btn-export-selected');
            const countSpan = document.getElementById('selected-count');

            function updateSelectionUI() {
                const checkedCount = document.querySelectorAll('.record-checkbox:checked').length;
                countSpan.textContent = checkedCount;
                
                if (checkedCount > 0) {
                    exportBtn.classList.remove('disabled');
                    exportBtn.removeAttribute('disabled');
                } else {
                    exportBtn.classList.add('disabled');
                    exportBtn.setAttribute('disabled', 'true');
                }
            }

            if (selectAll) {
                selectAll.addEventListener('change', function() {
                    checkboxes.forEach(cb => {
                        cb.checked = selectAll.checked;
                    });
                    updateSelectionUI();
                });
            }

            checkboxes.forEach(cb => {
                cb.addEventListener('change', function() {
                    if (selectAll && !this.checked) {
                        selectAll.checked = false;
                    } else if (selectAll && document.querySelectorAll('.record-checkbox:checked').length === checkboxes.length) {
                        selectAll.checked = true;
                    }
                    updateSelectionUI();
                });
            });
        });

        // --- Export Action Handler ---
        function exportSelectedRecords() {
            const selectedCheckboxes = document.querySelectorAll('.record-checkbox:checked');
            const ids = Array.from(selectedCheckboxes).map(cb => cb.value);
            
            if (ids.length === 0) {
                alert('Please select at least one record to export.');
                return;
            }

            const activeCat = "<?php echo $active_cat; ?>";
            const idsParam = encodeURIComponent(ids.join(','));
            
            // Redirects to your export processing script with selected record IDs and current category
            window.location.href = `export_records.php?cat=${encodeURIComponent(activeCat)}&ids=${idsParam}`;
        }

        // --- Overriding Details View logic specifically for WOFAD ---
        document.addEventListener('DOMContentLoaded', function() {
            // Save original function behaviors from main.js
            const originalOpenDetailByIndex = window.openDetailByIndex;

            // 1. OVERRIDE: Open detail view modal
            window.openDetailByIndex = function(idx) {
                const activeCat = "<?php echo $active_cat; ?>";
                if (activeCat === 'wofad') {
                    const stores = document.querySelectorAll('.json-store');
                    if (!stores[idx]) return;
                    const data = JSON.parse(stores[idx].textContent);

                    // Configure Header Texts
                    document.getElementById('modal-mode-label').textContent = "WOFAD Registry Details";
                    document.getElementById('view-subject-title').textContent = data.Proponent || "WOFAD Entry Detail";
                    document.getElementById('view-record-id').value = data.id;

                    // Build dynamic Details layout - starts as view-only (readonly)
                    const container = document.getElementById('details-content');
                    container.innerHTML = `
                        <div class="form-group"><label>Proponent</label><input type="text" name="Proponent" value="${escapeHtml(data.Proponent || '')}" readonly></div>
                        <div class="form-group"><label>Status</label><input type="text" name="Status" value="${escapeHtml(data.Status || '')}" readonly></div>
                        <div class="form-group full"><label>Subject</label><textarea name="Subject" rows="4" readonly>${escapeHtml(data.Subject || '')}</textarea></div>
                    `;

                    // Reset buttons back to original state (Edit visible, Save hidden)
                    const btnEdit = document.getElementById('btn-enable-edit');
                    const btnSave = document.getElementById('btn-trigger-verify');
                    
                    if (btnEdit) btnEdit.style.display = 'inline-block';
                    if (btnSave) btnSave.style.display = 'none';

                    // Clone the Edit button to strip any default click event listeners from main.js
                    if (btnEdit && !btnEdit.dataset.cloned) {
                        const clonedBtn = btnEdit.cloneNode(true);
                        clonedBtn.dataset.cloned = "true"; // Mark it to prevent redundant cloning
                        btnEdit.parentNode.replaceChild(clonedBtn, btnEdit);
                        
                        clonedBtn.addEventListener('click', function(e) {
                            e.preventDefault();
                            
                            // Remove readonly attribute to allow edits on input elements
                            const inputs = container.querySelectorAll('input, textarea');
                            inputs.forEach(input => {
                                input.removeAttribute('readonly');
                                input.style.borderColor = "#3b82f6"; // visually show it's active
                            });

                            // Hide Edit button and show Verification/Save button
                            clonedBtn.style.display = 'none';
                            if (btnSave) btnSave.style.display = 'inline-block';
                        });
                    }

                    // Launch Detail View dialog modal
                    document.getElementById('modal-view-details').showModal();
                } else {
                    // Fall back to original rendering built into main.js for other templates
                    if (typeof originalOpenDetailByIndex === 'function') {
                        originalOpenDetailByIndex(idx);
                    }
                }
            };

            function escapeHtml(text) {
                if (!text) return '';
                return text
                    .toString()
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }
        });
    </script>
</body>
</html>