<?php 
include_once '../auth_check.php';
include '../db.php'; 

$current_user = $_SESSION['user_id'];

// NEW: Get the display name from the session (set during login)
$displayName = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Administrator';

// 1. Count Unread Received Files
$notifQuery = $conn->prepare("SELECT COUNT(*) as unread_count FROM file_transfers WHERE receiver_id = ? AND is_read = 0");
$notifQuery->bind_param("i", $current_user);
$notifQuery->execute();
$unread_count = $notifQuery->get_result()->fetch_assoc()['unread_count'];

// 2. Count Unread Messages
$msgQuery = $conn->prepare("SELECT COUNT(*) as unread_msgs FROM messages WHERE receiver_id = ? AND is_read = 0");
$msgQuery->bind_param("i", $current_user);
$msgQuery->execute();
$unread_msgs = $msgQuery->get_result()->fetch_assoc()['unread_msgs'];

// 3. Fetch current user's profile picture
$userQuery = $conn->prepare("SELECT profile_pic FROM users WHERE id = ?");
$userQuery->bind_param("i", $current_user);
$userQuery->execute();
$userData = $userQuery->get_result()->fetch_assoc();
$my_pic = !empty($userData['profile_pic']) ? $userData['profile_pic'] : 'default-avatar.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="icon" href="../Imag3s/baguio-logo-gov.webp" type="image/png">
    <title>City Council | Admin Dashboard</title>
    
    <link rel="stylesheet" href="../CSS/admin.css">
    <link rel="stylesheet" href="../CSS/chat.css?v=<?php echo filemtime('../CSS/chat.css'); ?>">
    
    <!-- FullCalendar Library -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
</head>
<body class="dashboard-bg">
    
    <div id="page-loader">
        <div class="loader-wrapper">
            <img src="../Imag3s/baguio-logo-gov.png" alt="BCH" class="loader-logo">
            <div class="loader-circle"></div>
        </div>
    </div>

   <nav class="glass-nav">
        <div class="nav-left">
            <img src="../Imag3s/baguio-logo-gov.png" alt="logo" class="nav-logo">
            <div class="nav-titles">
                <h1>Sangguniang Panlungsod</h1>
                <span>City Council (Baguio City)</span>
            </div>
        </div>
        <div class="nav-actions">
            <button class="btn-icon" title="Messages" style="position:relative;" onclick="window.location.href='../tools/messenger.php'">
                💬 <?php if($unread_msgs > 0): ?><span class="notif-badge"><?php echo $unread_msgs; ?></span><?php endif; ?>
            </button>
            <button class="btn-icon" title="Notifications" style="position:relative;" onclick="window.location.href='../tools/files.php'">
                🔔 <?php if($unread_count > 0): ?><span class="notif-badge"><?php echo $unread_count; ?></span><?php endif; ?>
            </button>
            <button class="btn-nav logout" id="logout" onclick="window.location.href='../logout.php'">Logout</button>
        </div>
    </nav>

    <main class="dashboard-layout fade-in">
        <!-- LEFT SIDE -->
        <div class="main-content-area">
            <section class="welcome-section">
                <div class="welcome-content" style="display: flex; align-items: center; gap: 20px;">
                    <!-- USER PROFILE PIC -->
                    <img src="../uploads/profiles/<?php echo $my_pic; ?>" 
                         alt="Profile" 
                         style="width: 75px; height: 75px; border-radius: 50%; object-fit: cover; border: 4px solid white; box-shadow: 0 4px 12px rgba(0,0,0,0.1);"
                         onerror="this.src='../uploads/profiles/default-avatar.png'">
                    
                    <div class="greetings">
                        <!-- UPDATED: Displaying dynamic name -->
                        <h1>Welcome back, <span class="admin-name"><?php echo htmlspecialchars($displayName); ?>!</span></h1>
                        <p>System Overview & Control Panel</p>
                    </div>
                </div>
            </section>
            
            <!-- Search Staff Bar -->
            <div class="search-staff-container" style="margin-bottom: 25px; position: relative;">
                <div style="background: white; padding: 5px; border-radius: 15px; border: 1.5px solid #e2e8f0; display: flex; align-items: center; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
                    <span style="padding: 0 15px; opacity: 0.5;"></span>
                    <input type="text" id="staffSearchInput" placeholder="Search staff or council members..." 
                           style="flex: 1; border: none; outline: none; padding: 12px 0; font-size: 0.95rem; font-weight: 500;">
                </div>
                <div id="staffSearchResults" style="display: none; position: absolute; top: 60px; width: 100%; background: white; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; z-index: 100;">
                </div>
            </div>

            <h2 class="section-label">Management Tools</h2>
            
            <div class="card-grid">
                <a href="../tools/events.php" class="card-link">
                    <div class="tool-card">
                        <div class="icon-wrapper"><img src="../Imag3s/events.webp" alt="events"></div>
                        <div class="tool-info"><h3>Events</h3><p>Seminars & Trainings</p></div>
                    </div>
                </a>

                <a href="../db-table/table.php" class="card-link">
                    <div class="tool-card">
                        <div class="icon-wrapper"><img src="../Imag3s/database.webp" alt="database"></div>
                        <div class="tool-info"><h3>Registry</h3><p>Central Database</p></div>
                    </div>
                </a>

                <a href="../tools/files.php" class="card-link">
                    <div class="tool-card <?php echo ($unread_count > 0) ? 'has-notif' : ''; ?>">
                        <div class="icon-wrapper"><img src="../Imag3s/Files.webp" alt="files"></div>
                        <div class="tool-info"><h3>Cloud Files</h3><p>Storage & Transfer</p></div>
                    </div>
                </a>

                <a href="../tools/manage-accounts.php" class="card-link">
                    <div class="tool-card">
                        <div class="icon-wrapper"><img src="../Imag3s/accounts.webp" alt="accounts"></div>
                        <div class="tool-info"><h3>Accounts</h3><p>User Management</p></div>
                    </div>
                </a>
                
                <a href="../admin/security_logs.php" class="card-link">
                    <div class="tool-card">
                        <div class="icon-wrapper"><img src="../Imag3s/security.webp" alt="security"></div>
                        <div class="tool-info"><h3>Security</h3><p>Login & IP Logs</p></div>
                    </div>
                </a>
                
                <a href="../tools/resources.php" class="card-link">
                    <div class="tool-card">
                        <div class="icon-wrapper"><img src="../Imag3s/resources.webp" alt="resources"></div>
                        <div class="tool-info"><h3>Resources</h3><p>Shared Docs & Media</p></div>
                    </div>
                </a>
                
                <a href="../tools/settings.php" class="card-link">
                    <div class="tool-card">
                        <div class="icon-wrapper"><img src="../Imag3s/settings.webp" alt="settings"></div>
                        <div class="tool-info"><h3>Settings</h3><p>Account & Profile Customization</p></div>
                    </div>
                </a>
            </div>
        </div>

        <!-- RIGHT SIDE: STICKY CALENDAR -->
        <aside class="sidebar-area">
            <div class="calendar-card">
                <div id="dash-calendar"></div>
            </div>
        </aside>
    </main>

    <!-- Event Detail Pop-up -->
    <dialog id="event-detail-modal" class="table-modal" style="max-width: 420px;">
        <div class="modal-content" style="padding: 0;">
            <div class="modal-head">
                <div class="modal-title-box">
                    <small id="pop-event-cat" style="color: #2563eb; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;">Category</small>
                    <h2 id="pop-event-title" style="font-size: 1.25rem; margin-top: 5px;">Event Name</h2>
                </div>
                <button class="modal-close-x" onclick="document.getElementById('event-detail-modal').close()">&times;</button>
            </div>
            
            <div style="padding: 30px 25px;">
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div style="font-size: 1.5rem; background: #f1f5f9; width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; border-radius: 12px;">🕒</div>
                        <div>
                            <small style="display: block; color: #94a3b8; font-weight: 800; font-size: 0.65rem; text-transform: uppercase;">Scheduled Time</small>
                            <span id="pop-event-time" style="font-weight: 700; color: #1e293b;">00:00 AM</span>
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div style="font-size: 1.5rem; background: #f1f5f9; width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; border-radius: 12px;">📍</div>
                        <div>
                            <small style="display: block; color: #94a3b8; font-weight: 800; font-size: 0.65rem; text-transform: uppercase;">Location</small>
                            <span id="pop-event-loc" style="font-weight: 700; color: #1e293b;">Location Name</span>
                        </div>
                    </div>
                </div>

                <button id="btn-go-to-manager" style="width: 100%; margin-top: 35px; padding: 16px; border: none; border-radius: 15px; background: linear-gradient(135deg, #1e3a8a, #2563eb); color: white; font-weight: 800; font-size: 0.95rem; cursor: pointer; box-shadow: 0 10px 20px rgba(37, 99, 235, 0.2); transition: 0.3s;">
                    Open Category Manager →
                </button>
            </div>
        </div>
    </dialog>
    
<!-- User Profile View Modal -->
<dialog id="user-profile-modal" class="table-modal" style="max-width: 400px; border:none; border-radius:28px; padding:0; box-shadow:0 25px 50px rgba(0,0,0,0.3);">
    <div class="modal-content" style="text-align:center; padding:0;">
        <div style="height: 100px; background: linear-gradient(135deg, #1e3a8a, #2563eb); border-radius: 28px 28px 0 0;"></div>
        <div style="padding: 0 30px 30px;">
            <img id="view-user-pic" src="../uploads/profiles/default-avatar.png" 
                 style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 5px solid white; margin-top: -60px; background: white;">
            
            <h2 id="view-user-name" style="margin: 15px 0 5px; color: #1e293b;">Full Name</h2>
            <span id="view-user-role" style="background: #eff6ff; color: #2563eb; padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase;">Role</span>
            
            <div style="margin-top: 25px; text-align: left;">
                <label style="font-size: 0.65rem; font-weight: 800; color: #94a3b8; text-transform: uppercase;">Responsibilities / Bio</label>
                <p id="view-user-bio" style="color: #64748b; font-size: 0.9rem; line-height: 1.6; margin-top: 8px;">No description provided.</p>
            </div>
            
    <!-- NEW: Message Button -->
    <button id="view-user-msg-btn" class="btn-message-profile">
        💬 Send Message
    </button>
            
            <button onclick="document.getElementById('user-profile-modal').close()" 
                    style="width: 100%; margin-top: 25px; padding: 12px; border: 1.5px solid #e2e8f0; border-radius: 12px; background: white; font-weight: 700; cursor: pointer;">
                Close Profile
            </button>
        </div>
    </div>
</dialog>

    <?php include '../tools/chat_widget.php'; ?>

    <script>
        const currentUserId = <?php echo (int)$current_user; ?>;
        
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('dash-calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                height: 'parent',
                headerToolbar: { left: 'prev,next', center: 'title', right: 'today' },
                events: '../tools/get_events_json.php',
                dayMaxEvents: 1,
                eventClick: function(info) {
                    const ev = info.event;
                    const props = ev.extendedProps;
                    const modal = document.getElementById('event-detail-modal');

                    document.getElementById('pop-event-cat').innerText = props.category;
                    document.getElementById('pop-event-title').innerText = ev.title;
                    document.getElementById('pop-event-time').innerText = props.time || 'Check Details';
                    document.getElementById('pop-event-loc').innerText = props.location || 'Baguio City Hall';

                    const managerBtn = document.getElementById('btn-go-to-manager');
                    managerBtn.onclick = function() {
                        window.location.href = `../tools/events.php?cat=${encodeURIComponent(props.category)}`;
                    };

                    modal.showModal();
                }
            });
            calendar.render();
        });
    </script>
    <script src="../scripts/main.js"></script>
</body>
</html>