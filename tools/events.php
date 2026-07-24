<?php
include_once '../auth_check.php';
include '../db.php'; 

date_default_timezone_set('Asia/Manila'); 
$now = new DateTime();

// Get the selected category from the URL
$selected_cat = isset($_GET['cat']) ? $_GET['cat'] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <link rel="icon" href="../Imag3s/baguio-logo-gov.webp" type="image/png">
    <link rel="stylesheet" href="../CSS/events.css">
    <title>City Council | Events Management</title>
</head>
<body class="events-page">
<!-- For subfolder pages (table.php, events.php, etc.) -->
<div id="page-loader">
    <div class="loader-wrapper">
           <?php $homePath = ($_SESSION['role'] === 'admin') ? '/admin/admin.php' : '/user/user.php'; ?>
            <button class="btn-home" onclick="window.location.href='<?php echo $homePath; ?>'" style="background: none; border: none;"><img src="../Imag3s/baguio-logo-gov.png" alt="logo" style="height: 45px;"></button>
        <div class="loader-circle"></div>
    </div>
</div>
    <nav class="events-nav">
        <div class="nav-left">
            <img src="../Imag3s/baguio-logo-gov.png" alt="logo">
                <h1>Sangguniang Panlungsod</h1>
        </div>
<div class="nav-actions">
    <?php 
        // Determine the home path based on session role
        $homePath = ($_SESSION['role'] === 'admin') ? '/admin/admin.php' : '/user/user.php'; 
    ?>
    <button id="Dashboard" class="btn-home" onclick="window.location.href='<?php echo $homePath; ?>'">Home</button>
    <button class="btn-nav logout" id="logout">Logout</button>
</div>
    </nav>

    <main class="fade-in">
        <?php if (!$selected_cat): ?>
            <!-- --- CATEGORY SELECTION CARDS --- -->
            <section class="selection-container">
                <div class="section-header">
                    <h1>Event Categories</h1>
                    <p>Select a category to manage specific schedules and records.</p>
                </div>
<div class="category-grid">
    <a href="?cat=Seminar" class="cat-card-link">
        <div class="cat-card">
            <div class="cat-icon">👥</div>
            <h2>Seminars</h2>
            <p>Description</p>
            <span class="cat-btn">Open Manager →</span>
        </div>
    </a>
    <!-- FIXED: Added 'l' to Consultation -->
    <a href="?cat=Public Consultation" class="cat-card-link">
        <div class="cat-card">
            <div class="cat-icon">👨🏻‍🏫</div>
            <h2>Public Consultation</h2>
            <p>Description</p>
            <span class="cat-btn">Open Manager →</span>
        </div>
    </a>
    
    <!-- FIXED: Added 'm' to Committee -->
    <a href="?cat=Committee Level Hearing" class="cat-card-link">
        <div class="cat-card">
            <div class="cat-icon">🏛️</div>
            <h2>Committee Level Hearing</h2>
            <p>Description</p>
            <span class="cat-btn">Open Manager →</span>
        </div>
    </a>
    
    <a href="?cat=Others" class="cat-card-link">
        <div class="cat-card">
            <div class="cat-icon">✨</div>
            <h2>Special Session</h2>
            <p>General events and special occasions</p>
            <span class="cat-btn">Open Manager →</span>
        </div>
    </a>
</div>
            </section>

        <?php else: ?>
            <!-- --- THE MAIN MANAGEMENT TABLE --- -->
            <div id="container1" class="management-card">
                <div class="table-header">
                    <div class="header-title-group">
                        <button class="btn-back" onclick="window.location.href='events.php'">← Back</button>
                        <h1><?php echo $selected_cat; ?> Management</h1>
                    </div>
                    <button id="add" class="btn-add-event" onclick="document.getElementById('event-modal').showModal()">+ Add <?php echo $selected_cat; ?></button>
                </div>

                <div class="filter-bar">
                    <div class="search-box">
                        <span class="search-icon">🔍</span>
                        <input type="text" id="event-search" placeholder="Search events or locations...">
                    </div>
                    <div class="filter-box">
                        <select id="status-filter" class="status-select">
                            <option value="all">All Status</option>
                            <option value="Pending">Pending</option>
                            <option value="Today">Today</option>
                            <option value="Ongoing">Ongoing</option>
                            <option value="Ended">Ended</option>
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="events-table">
                        <thead>
                            <tr>
                                <th>Event Details</th>
                                <th>Schedule</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="events-table-body">
<?php
$stmt = $conn->prepare("SELECT * FROM events WHERE category = ? ORDER BY event_date ASC, event_time ASC");
$stmt->bind_param("s", $selected_cat);
$stmt->execute();
$result = $stmt->get_result();

while($row = $result->fetch_assoc()) {
    $id = $row['id'];
    $name = htmlspecialchars($row['event_name']);
    $date_raw = $row['event_date'];
    $time_raw = $row['event_time'];
    $loc = htmlspecialchars($row['location']);
    $desc = htmlspecialchars($row['description']);
    $manual_status = $row['status']; // 'pending' or 'done'

    // --- RELIABLE DYNAMIC STATUS LOGIC ---
    $event_start_ts = strtotime($date_raw . ' ' . $time_raw); 
    $event_day_ts   = strtotime($date_raw);                  
    $today_ts       = strtotime(date('Y-m-d'));             
    $now_ts         = time();                                

    if ($manual_status == 'done') {
        $statusText = "Archived";
        $badgeClass = "archived";
    } elseif ($event_day_ts < $today_ts) {
        $statusText = "Archived";
        $badgeClass = "archived";
    } elseif ($event_day_ts == $today_ts) {
        if ($now_ts >= $event_start_ts) {
            $statusText = "Ongoing";
            $badgeClass = "active";
        } else {
            $statusText = "Today";
            $badgeClass = "role-tag";
        }
    } else {
        $statusText = "Pending";
        $badgeClass = "role-tag";
    }

    // --- TABLE ROW OUTPUT ---
   echo "<tr class='event-row' data-name='".strtolower($name)."' data-location='".strtolower($loc)."' data-status='$statusText'>
    <td style='font-weight:600;'>$name</td>
    <td>" . date('M d, Y', strtotime($date_raw)) . "<br><small>" . date('h:i A', strtotime($time_raw)) . "</small></td>
    <td>$loc</td>
    <td><span class='badge $badgeClass'>$statusText</span></td>
    <td>
        <div class='action-group'>
            <button class='btn-action btn-edit-blue' onclick='openEditEventModal($id, \"$name\", \"$selected_cat\", \"$date_raw\", \"$time_raw\", \"$loc\", \"$desc\")'>Edit</button>
            
            <button class='btn-action' onclick=\"window.location.href='toggle_event_status.php?id=$id&cat=" . urlencode($selected_cat) . "'\" style='border-color:#bbf7d0;'>
                " . ($manual_status == 'done' ? '🔓 Reset' : '✅ Done') . "
            </button>
            
            <button class='btn-action' onclick='if(confirm(\"Delete this event?\")) window.location.href=\"delete_event.php?id=$id&cat=" . urlencode($selected_cat) . "\"' style='color:#dc2626; border-color:#fecaca;'>🗑️</button>
        </div>
    </td>
</tr>";
}
?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <!-- CREATE MODAL (Updated with hidden Category) -->
    <dialog id="event-modal">
        <form action="add_event.php" method="POST">
            <h2>Add <?php echo $selected_cat; ?></h2>
            <input type="hidden" name="category" value="<?php echo $selected_cat; ?>">
            <label>Event Name</label>
            <input type="text" name="event_name" placeholder="Name" required>
            <label>Date</label>
            <input type="date" name="event_date" required>
            <label>Time</label>
            <input type="time" name="event_time" required>
            <label>Location</label>
            <input type="text" name="location" placeholder="Location" required>
            <label>Description</label>
            <textarea name="description" placeholder="Description" style="width: 100%; padding: 12px; border: 1.5px solid #e2e8f0; border-radius: 10px; margin-bottom: 18px; min-height: 80px;"></textarea>
            <div class="modal-actions">
                <button type="submit" class="btn-save">Save</button>
                <button type="button" class="btn-cancel" onclick="document.getElementById('event-modal').close()">Cancel</button>
            </div>
        </form>
    </dialog>

    <!-- EDIT MODAL (Updated with Category Select) -->
    <dialog id="edit-event-modal">
        <form action="update_event.php" method="POST">
            <h2>Edit Event</h2>
            <input type="hidden" name="event_id" id="edit-event-id">
            <label>Category</label>
            <select name="category" id="edit-event-category">
                <option value="Seminar">Seminar</option>
                <option value="Public Consultation">Public Consultation</option>
				<option value="Comittee Level Hearing">Comittee Level Hearing</option>
                <option value="Others">Others</option>
            </select>
            <label>Event Name</label>
            <input type="text" name="event_name" id="edit-event-name" required>
            <label>Date</label>
            <input type="date" name="event_date" id="edit-event-date" required>
            <label>Time</label>
            <input type="time" name="event_time" id="edit-event-time" required>
            <label>Location</label>
            <input type="text" name="location" id="edit-event-location" required>
            <textarea name="description" id="edit-event-desc"></textarea>
            <div class="modal-actions">
                <button type="submit" class="btn-save">Update</button>
                <button type="button" class="btn-cancel" onclick="document.getElementById('edit-event-modal').close()">Cancel</button>
            </div>
        </form>
    </dialog>

    <script src="/scripts/main.js"></script>
    <script>

    </script>
</body>
</html>