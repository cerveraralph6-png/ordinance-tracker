<?php
include_once '../auth_check.php';
include '../db.php';

$current_user = $_SESSION['user_id'];
$role = $_SESSION['role'];

// --- SEARCH LOGIC ---
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

// --- NAV BAR DATA ---
$notifQuery = $conn->prepare("SELECT COUNT(*) as unread_count FROM file_transfers WHERE receiver_id = ? AND is_read = 0");
$notifQuery->bind_param("i", $current_user);
$notifQuery->execute();
$unread_count = $notifQuery->get_result()->fetch_assoc()['unread_count'];

$msgQuery = $conn->prepare("SELECT COUNT(*) as unread_msgs FROM messages WHERE receiver_id = ? AND is_read = 0");
$msgQuery->bind_param("i", $current_user);
$msgQuery->execute();
$unread_msgs = $msgQuery->get_result()->fetch_assoc()['unread_msgs'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" href="../Imag3s/baguio-logo-gov.webp" type="image/png">
    <title>City Council | Resources</title>
    <link rel="stylesheet" href="../CSS/<?php echo ($role == 'admin') ? 'admin.css' : 'user.css'; ?>">
    <link rel="stylesheet" href="../CSS/resources.css?v=<?php echo time(); ?>">
</head>
<body class="dashboard-bg">

    <nav class="glass-nav">
        <div class="nav-left">
            <img src="../Imag3s/baguio-logo-gov.png" alt="logo" class="nav-logo">
            <div class="nav-titles">
                <h1>Sangguniang Panlungsod</h1>
                <span>Resources & Media Cloud</span>
            </div>
        </div>
        <div class="nav-actions">
            <button class="btn-icon" onclick="window.location.href='messenger.php'">💬 <?php if($unread_msgs > 0): ?><span class="notif-badge"><?php echo $unread_msgs; ?></span><?php endif; ?></button>
            <button class="btn-icon" onclick="window.location.href='files.php'">🔔 <?php if($unread_count > 0): ?><span class="notif-badge"><?php echo $unread_count; ?></span><?php endif; ?></button>
            <button class="btn-nav logout" onclick="window.location.href='../<?php echo ($role == 'admin') ? 'admin/admin.php' : 'user/user.php'; ?>'">Dashboard</button>
        </div>
    </nav>
    
    <?php if(isset($_GET['msg'])): ?>
    <div id="status-toast" class="toast-container">
        <div class="toast-content <?php echo $_GET['msg'] == 'deleted' ? 'toast-delete' : 'toast-success'; ?>">
            <?php 
                if($_GET['msg'] == 'deleted') echo "🗑️ File permanently removed.";
                if($_GET['msg'] == 'success') echo "✅ Resource processed successfully.";
            ?>
        </div>
    </div>
    <?php endif; ?>

    <main class="container">
        <section class="upload-section">
            <div class="upload-card">
                <div class="upload-info">
                    <h2>Cloud Storage</h2>
                    <p>Search or upload resources (Docs, Images, Videos).</p>
                    <div class="search-container">
                        <form action="" method="GET" style="display: flex; gap: 10px; width: 100%;">
                            <input type="text" name="search" placeholder="Search by filename or uploader..." value="<?php echo htmlspecialchars($searchTerm); ?>" class="search-input">
                            <?php if(!empty($searchTerm)): ?>
                                <button type="button" class="btn-clear" onclick="window.location.href='resources.php'">✕</button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <div class="upload-actions">
                    <button class="custom-upload-btn btn-blue" onclick="openUploadChoice()">📤 Upload File</button>
                    <!-- UPDATED: Now calls openLinkModal instead of addLinkPrompt -->
                    <button class="custom-upload-btn btn-green" onclick="openLinkModal()">🔗 Add Link</button>
                </div>
            </div>
        </section>

        <div id="resource-display" class="resource-grid">
            <?php
            $sql = "SELECT r.*, u.full_name FROM resources r LEFT JOIN users u ON r.uploader_id = u.id";
            if (!empty($searchTerm)) {
                $sql .= " WHERE r.file_name LIKE ? OR u.full_name LIKE ?";
                $stmt = $conn->prepare($sql);
                $likeTerm = "%$searchTerm%";
                $stmt->bind_param("ss", $likeTerm, $likeTerm);
                $stmt->execute();
                $res = $stmt->get_result();
            } else {
                $sql .= " ORDER BY r.uploaded_at DESC";
                $res = $conn->query($sql);
            }

            if ($res && $res->num_rows > 0):
                while($row = $res->fetch_assoc()):
                    $icon = "📄";
                    if($row['category'] == 'image') $icon = "🖼️";
                    elseif($row['category'] == 'video') $icon = "🎬";
                    elseif($row['category'] == 'link') $icon = "🔗";
            ?>
            <div class="res-card" onclick="viewFile('<?php echo $row['file_path']; ?>', '<?php echo htmlspecialchars($row['file_name']); ?>', '<?php echo $row['category']; ?>')">
                <div class="res-icon"><?php echo $icon; ?></div>
                <div class="res-details">
                    <h4 title="<?php echo htmlspecialchars($row['file_name']); ?>"><?php echo htmlspecialchars($row['file_name']); ?></h4>
                    <p>By: <?php echo htmlspecialchars($row['full_name']); ?></p>
                    <small><?php echo $row['file_size']; ?> | <?php echo date('M d, Y', strtotime($row['uploaded_at'])); ?></small>
                </div>
                <div class="res-actions">
                    <!-- Change the href to point to your new downloader script -->
<a href="download_resource.php?id=<?php echo $row['id']; ?>" 
   class="dl-link" 
   onclick="event.stopPropagation()">Download</a>
                    <?php if($role == 'admin' || $row['uploader_id'] == $current_user): ?>
                        <a href="delete_resource.php?id=<?php echo $row['id']; ?>" class="del-link" onclick="event.stopPropagation(); return confirm('Delete permanently?')">Delete</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; else: ?>
                <div class="no-results"><p>📂 No resources found.</p></div>
            <?php endif; ?>
        </div>
    </main>

    <!-- --- UPLOAD CHOICE MODAL --- -->
    <div id="choiceModal" class="modal-overlay" onclick="closeChoiceModal()">
        <div class="modal-content choice-modal" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3>Select Upload Method</h3>
                <button class="close-btn" onclick="closeChoiceModal()">&times;</button>
            </div>
            <div class="modal-body choice-body">
                <div class="choice-grid">
                    <div class="choice-item" onclick="handleExternalProvider('https://app.mediafire.com/folder/myfiles')"><div class="choice-icon">🔥</div><span>MediaFire</span></div>
                    <div class="choice-item" onclick="handleExternalProvider('https://drive.google.com/')"><div class="choice-icon">📁</div><span>Google Drive</span></div>
                    <div class="choice-item" onclick="handleExternalProvider('https://onedrive.live.com/')"><div class="choice-icon">☁️</div><span>OneDrive</span></div>
                    <label for="res_file" class="choice-item local-choice" onclick="closeChoiceModal()"><div class="choice-icon">💻</div><span>This Device</span></label>
                </div>
            </div>
        </div>
    </div>

    <!-- --- LINK DETAILS MODAL --- -->
    <div id="linkDetailsModal" class="modal-overlay" onclick="closeLinkModal()">
        <div class="modal-content choice-modal" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3>Resource Link Details</h3>
                <button class="close-btn" onclick="closeLinkModal()">&times;</button>
            </div>
            <div class="modal-body choice-body">
                <div style="display:flex; flex-direction:column; gap:15px;">
                    <p style="color:#64748b; font-size:13px; margin:0;">Enter the details for your external resource link.</p>
                    <input type="text" id="link-title-input" placeholder="Resource Name (e.g., Project Video)" class="search-input" style="margin:0; background:#f8fafc;">
                    <input type="text" id="link-url-input" placeholder="Paste URL here (https://...)" class="search-input" style="margin:0; background:#f8fafc;">
                    <button class="custom-upload-btn btn-green" onclick="submitExternalLink()" style="width:100%;">💾 Save to BCH Cloud</button>
                </div>
            </div>
        </div>
    </div>

    <!-- --- FILE PREVIEW MODAL --- -->
    <div id="fileModal" class="modal-overlay" onclick="closeModal()">
        <div class="modal-content" onclick="event.stopPropagation()">
            <div class="modal-header"><h3 id="modalTitle">File Preview</h3><button class="close-btn" onclick="closeModal()">&times;</button></div>
            <div id="modalBody" class="modal-body"></div>
        </div>
    </div>

    <!-- Hidden Local Upload Form -->
    <form action="upload_resource_logic.php" method="POST" enctype="multipart/form-data" id="fileForm" style="display:none;">
        <input type="file" name="resource_file" id="res_file" onchange="document.getElementById('fileForm').submit()">
    </form>

<script src="../scripts/main.js"></script>
<script>
    // --- TOAST LOGIC ---
    document.addEventListener('DOMContentLoaded', function() {
        const toast = document.getElementById('status-toast');
        if (toast) {
            setTimeout(() => {
                toast.classList.add('toast-hidden');
                setTimeout(() => {
                    toast.remove();
                    const url = new URL(window.location);
                    url.searchParams.delete('msg');
                    window.history.replaceState({}, '', url);
                }, 600);
            }, 3000);
        }
    });

    let isModalOpen = false;

    // --- VIEW LOGIC ---
    function viewFile(path, name, category) {
        isModalOpen = true; 
        const modal = document.getElementById('fileModal');
        const body = document.getElementById('modalBody');
        const title = document.getElementById('modalTitle');
        const ext = name.split('.').pop().toLowerCase();
        const fullPath = category === 'link' ? path : '../' + path;
        
        title.innerText = name;
        body.innerHTML = ''; 

        if (category === 'link') {
            body.innerHTML = `<div class="unsupported-msg"><div style="font-size: 60px; margin-bottom: 20px;">🔗</div><h2 style="color: white;">External Link</h2><p style="color: #94a3b8; word-break: break-all; padding:0 20px;">${path}</p><a href="${path}" target="_blank" class="custom-upload-btn btn-green" style="text-decoration:none; margin-top:20px;">Open Link</a></div>`;
        } 
        else if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) {
            body.innerHTML = `<img src="${fullPath}" class="preview-img">`;
        } 
        else if (['mp4', 'webm', 'ogg'].includes(ext)) {
            body.innerHTML = `<video controls autoplay class="preview-video"><source src="${fullPath}" type="video/${ext}"></video>`;
        } 
        else if (ext === 'pdf') {
            body.innerHTML = `<iframe src="${fullPath}" class="preview-iframe"></iframe>`;
        } 
        else {
            body.innerHTML = `<div class="unsupported-msg"><div style="font-size: 50px;">📦</div><h2 style="color: white;">No Preview</h2><a href="${fullPath}" download class="custom-upload-btn btn-blue" style="text-decoration:none; margin-top:15px;">Download File</a></div>`;
        }
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        document.getElementById('fileModal').classList.remove('active');
        document.getElementById('modalBody').innerHTML = ''; 
        document.body.style.overflow = 'auto';
        isModalOpen = false;
    }

    // --- CHOICE MODAL FUNCTIONS ---
    function openUploadChoice() { isModalOpen = true; document.getElementById('choiceModal').classList.add('active'); }
    function closeChoiceModal() { document.getElementById('choiceModal').classList.remove('active'); isModalOpen = false; }

    // --- LINK MODAL FUNCTIONS ---
    function openLinkModal() { isModalOpen = true; document.getElementById('linkDetailsModal').classList.add('active'); }
    function closeLinkModal() { 
        document.getElementById('linkDetailsModal').classList.remove('active'); 
        isModalOpen = false;
        document.getElementById('link-title-input').value = '';
        document.getElementById('link-url-input').value = '';
    }

    function handleExternalProvider(siteUrl) {
        window.open(siteUrl, '_blank');
        closeChoiceModal();
        setTimeout(openLinkModal, 500);
    }

    function submitExternalLink() {
        const title = document.getElementById('link-title-input').value;
        const url = document.getElementById('link-url-input').value;
        if (!title || !url) { alert("Please fill in both fields"); return; }

        const formData = new FormData();
        formData.append('url', url);
        formData.append('title', title);

        fetch('add_link_logic.php', { method: 'POST', body: formData })
        .then(() => { closeLinkModal(); window.location.href = "resources.php?msg=success"; });
    }

    // --- REAL-TIME REFRESH ---
    function fetchResources() {
        if (isModalOpen) return;
        const searchInput = document.querySelector('.search-input');
        const search = searchInput ? searchInput.value : '';
        const display = document.getElementById('resource-display');

        fetch(`fetch_resources_logic.php?search=${encodeURIComponent(search)}`)
            .then(res => res.text())
            .then(html => { if (display.innerHTML !== html) display.innerHTML = html; });
    }

    setInterval(fetchResources, 3000);

    document.addEventListener('keydown', e => { 
        if (e.key === "Escape") { closeModal(); closeChoiceModal(); closeLinkModal(); } 
    });
</script>
</body>
</html>