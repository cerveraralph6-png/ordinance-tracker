<?php
include_once '../auth_check.php';
include '../db.php';

$current_user = $_SESSION['user_id'];
$conn->query("UPDATE file_transfers SET is_read = 1 WHERE receiver_id = $current_user");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../Imag3s/baguio-logo-gov.png" type="image/png">
    <link rel="stylesheet" href="../CSS/files.css"> <!-- LINKED NEW INDEPENDENT CSS -->
    <title>City Council | File Management</title>
</head>
<body class="files-page">
    
    <!-- Loader Logic -->
    <div id="page-loader">
        <div class="loader-wrapper">
            <img src="../Imag3s/baguio-logo-gov.png" alt="BCH" class="loader-logo">
            <div class="loader-circle"></div>
        </div>
    </div>

    <nav class="table-nav">
        <div style="display: flex; align-items: center; gap: 15px;">
           <?php $homePath = ($_SESSION['role'] === 'admin') ? '/admin/admin.php' : '/user/user.php'; ?>
            <button class="btn-home" onclick="window.location.href='<?php echo $homePath; ?>'" style="background: none; border: none;"><img src="../Imag3s/baguio-logo-gov.png" alt="logo" style="height: 45px;"></button>
            
            <div class="nav-titles">
                <h1 style="font-size: 1.1rem; font-weight: 800; line-height: 1;">Sangguniang Panlungsod</h1>
                <span style="font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase;">City Council (Baguio City)</span>
            </div>
        </div>
        <div class="nav-actions">
            <?php $homePath = ($_SESSION['role'] === 'admin') ? '/admin/admin.php' : '/user/user.php'; ?>
            <button class="btn-home" onclick="window.location.href='<?php echo $homePath; ?>'">Home</button>
            <button class="btn-nav logout" id="logout" onclick="window.location.href='../logout.php'">Logout</button>
        </div>
    </nav>

    <main class="files-main fade-in">
        <?php if(isset($_GET['success'])): ?>
            <div id="success-alert" style="background: #ecfdf5; color: #065f46; padding: 15px; border-radius: 12px; margin-bottom: 20px; font-weight: 700; text-align: center; border: 1px solid #bbf7d0;">
                <?php 
                    if($_GET['success'] == 'deleted') echo "✓ File deleted successfully!";
                    if($_GET['success'] == 'uploaded') echo "✓ File uploaded successfully!";
                    if($_GET['success'] == 'sent') echo "✓ File shared with user!";
                ?>
            </div>
        <?php endif; ?>

        <div class="fm-card">
            <div class="fm-header">
                <h1>File Management</h1>
            </div>

            <!-- Upload Zone -->
            <div class="upload-zone">
                <h2>Upload New Document or Video</h2>
                <form action="upload_handler.php" method="POST" enctype="multipart/form-data">
                    <input type="file" name="my_file" id="actual-btn" accept="video/*,image/*,.pdf,.doc,.docx,.xls,.xlsx" hidden required>
                    <label for="actual-btn" class="btn-upload-trigger">📁 Select from Device</label>
                    <div id="file-chosen" style="margin-top:10px; color:var(--fm-text-muted); font-weight:500;">No file chosen</div>
                    <button type="submit" class="btn-submit-file">Upload to Secure Server</button>
                </form>
            </div>

            <!-- Document List -->
            <h2 class="section-title">📂 My Repository</h2>
            <div class="fm-table-wrapper">
                <table class="fm-table">
                    <thead>
                        <tr>
                            <th>File Details</th>
                            <th>Size</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT f.*, u.full_name as sender_name FROM files f LEFT JOIN file_transfers ft ON f.id = ft.file_id LEFT JOIN users u ON f.uploader_id = u.id WHERE f.uploader_id = $current_user OR ft.receiver_id = $current_user GROUP BY f.id ORDER BY f.upload_date DESC";
                        $myFiles = $conn->query($query);
                        if ($myFiles->num_rows > 0) {
                            while($f = $myFiles->fetch_assoc()) {
                                $ext = strtolower(pathinfo($f['filename'], PATHINFO_EXTENSION));
                                $icon = (in_array($ext, ['mp4', 'mov', 'avi', 'webm'])) ? "🎥" : "📄";
                                $filePath = "../" . $f['file_path'];

                                echo "<tr>
                                    <td>
                                        <div class='file-name'>$icon ".htmlspecialchars($f['filename'])."</div>";
                                        if($f['uploader_id'] != $current_user) {
                                            echo "<span class='shared-label'>Shared by: {$f['sender_name']}</span>";
                                        }
                                echo "</td>
                                    <td style='color:var(--fm-text-muted)'>{$f['file_size']}</td>
                                    <td>" . date('M d, Y', strtotime($f['upload_date'])) . "</td>
                                    <td>
                                        <div class='fm-action-group'>
                                            <a href='{$filePath}' target='_blank' class='btn-fm btn-fm-view'>View</a>
                                            <a href='{$filePath}' download class='btn-fm btn-fm-download'>Download</a>";
                                            if($f['uploader_id'] == $current_user) {
                                                echo "<button class='btn-fm btn-fm-delete' onclick='if(confirm(\"Delete permanently?\")) window.location.href=\"delete_file.php?id={$f['id']}\"'>Delete</button>";
                                            }
                                echo "</div></td></tr>";
                            }
                        } else { echo "<tr><td colspan='4' style='text-align:center; padding:40px; color:var(--fm-text-muted)'>No files found in your repository.</td></tr>"; }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- Share Section -->
            <div class="send-card">
                <h2 class="section-title">🚀 Share Document with Staff</h2>
                <form action="send_handler.php" method="POST" class="send-grid">
                    <div class="send-group">
                        <label>Select Recipient</label>
                        <select name="receiver_id" required>
                            <option value="" disabled selected>Choose staff member...</option>
                            <?php
                            $users = $conn->query("SELECT id, full_name, role FROM users WHERE id != $current_user AND status='active'");
                            while($u = $users->fetch_assoc()) { echo "<option value='{$u['id']}'>{$u['full_name']} (".ucfirst($u['role']).")</option>"; }
                            ?>
                        </select>
                    </div>
                    <div class="send-group">
                        <label>Select File</label>
                        <select name="file_id" required>
                            <option value="" disabled selected>Choose file...</option>
                            <?php
                            $files = $conn->query("SELECT id, filename FROM files WHERE uploader_id = $current_user");
                            while($f = $files->fetch_assoc()) { echo "<option value='{$f['id']}'>{$f['filename']}</option>"; }
                            ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-submit-file" style="margin:0;">Share File</button>
                </form>
            </div>
        </div>
    </main>

    <script src="../scripts/main.js"></script>
    <script>
        // File selection display
        document.getElementById('actual-btn').addEventListener('change', function(){
            document.getElementById('file-chosen').textContent = this.files[0] ? this.files[0].name : "No file chosen";
        });
        // Alert timeout
        setTimeout(() => { if(document.getElementById('success-alert')) document.getElementById('success-alert').style.display = 'none'; }, 4000);
    </script>
</body>
</html>