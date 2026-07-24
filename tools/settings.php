<?php
include_once '../auth_check.php';
include '../db.php';

$uid = $_SESSION['user_id'];

// Fetch current user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$profile_pic = !empty($user['profile_pic']) ? $user['profile_pic'] : 'default-avatar.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BCH | Account Settings</title>
    <link rel="icon" href="../Imag3s/baguio-logo-gov.png" type="image/png">
    <link rel="stylesheet" href="../CSS/admin.css">
    <!-- Include Cropper.js CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
    <style>
        .settings-container { max-width: 850px; margin: 50px auto; display: grid; grid-template-columns: 280px 1fr; gap: 30px; }
        .profile-sidebar { background: white; padding: 30px; border-radius: 24px; text-align: center; border: 1px solid #e2e8f0; height: fit-content; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .avatar-preview { width: 140px; height: 140px; border-radius: 50%; object-fit: cover; border: 5px solid #eff6ff; margin-bottom: 15px; background: #f1f5f9; }
        .settings-form { background: white; padding: 40px; border-radius: 24px; border: 1px solid #e2e8f0; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 0.75rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; margin-bottom: 8px; }
        .form-group input, .form-group textarea { width: 100%; padding: 14px; border-radius: 12px; border: 1.5px solid #e2e8f0; outline: none; transition: 0.3s; }
        .form-group input:focus { border-color: var(--primary); }
        .btn-save-settings { width: 100%; background: var(--primary); color: white; border: none; padding: 15px; border-radius: 12px; font-weight: 700; cursor: pointer; transition: 0.3s; }
        .btn-save-settings:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(37, 99, 235, 0.3); }

        /* --- CROPPER MODAL STYLES --- */
        .crop-modal { border: none; border-radius: 24px; padding: 0; width: 90%; max-width: 500px; margin: auto; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); }
        .crop-container { padding: 25px; background: white; }
        .image-wrapper { width: 100%; max-height: 400px; background: #000; overflow: hidden; border-radius: 12px; }
        #image-to-crop { display: block; max-width: 100%; }
        .crop-actions { display: flex; gap: 10px; margin-top: 20px; }
        .btn-crop { flex: 1; padding: 12px; border-radius: 10px; border: none; font-weight: 700; cursor: pointer; }
        .btn-confirm { background: var(--primary); color: white; }
        .btn-cancel { background: #f1f5f9; color: #64748b; }
    </style>
</head>
<body class="dashboard-bg">
    <nav class="glass-nav">
        <div class="nav-left">
            <img src="../Imag3s/baguio-logo-gov.png" alt="logo" class="nav-logo">
            <div class="nav-titles">
                <h1>Account Settings</h1>
                <span>Manage Profile & Identity</span>
            </div>
        </div>
        <div class="nav-actions">
            <?php $dash = ($user['role'] == 'admin') ? 'admin/admin.php' : 'user/user.php'; ?>
            <button class="btn-nav" onclick="window.location.href='../<?php echo $dash; ?>'">Dashboard</button>
        </div>
    </nav>

    <main class="settings-container fade-in">
        <div class="profile-sidebar">
            <img src="../uploads/profiles/<?php echo $profile_pic; ?>" class="avatar-preview" id="avatar-display">
            <h3 style="margin:0;"><?php echo htmlspecialchars($user['full_name']); ?></h3>
            <p style="font-size:0.8rem; color:#64748b; font-weight:700; margin-top:5px;"><?php echo strtoupper($user['role']); ?></p>
            <p style="font-size:0.75rem; color:#94a3b8; margin-top:10px; font-style:italic; line-height:1.4;">"<?php echo htmlspecialchars($user['bio'] ?? 'No bio set'); ?>"</p>
        </div>

        <div class="settings-form">
            <form id="settingsForm" action="settings_logic.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Profile Picture</label>
                    <!-- Trigger file select -->
                    <input type="file" id="fileInput" accept="image/*" style="padding: 10px;">
                    <!-- Hidden input to store the cropped image data -->
                    <input type="hidden" name="cropped_image" id="cropped_image">
                </div>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Role Description / Bio</label>
                    <textarea name="bio" rows="4" placeholder="Describe your responsibilities..."><?php echo htmlspecialchars($user['bio']); ?></textarea>
                </div>
                <button type="submit" class="btn-save-settings">Save Changes</button>
            </form>
        </div>
    </main>

    <!-- Modal for Cropping -->
    <dialog id="cropModal" class="crop-modal">
        <div class="crop-container">
            <h3 style="margin-bottom:15px; color:#1e3a8a;">Customize Photo</h3>
            <div class="image-wrapper">
                <img id="image-to-crop">
            </div>
            <div class="crop-actions">
                <button type="button" class="btn-crop btn-confirm" id="cropButton">Apply Crop</button>
                <button type="button" class="btn-crop btn-cancel" onclick="document.getElementById('cropModal').close()">Cancel</button>
            </div>
        </div>
    </dialog>

    <!-- Include Cropper.js JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    <script>
        let cropper;
        const fileInput = document.getElementById('fileInput');
        const cropModal = document.getElementById('cropModal');
        const imageToCrop = document.getElementById('image-to-crop');
        const cropButton = document.getElementById('cropButton');
        const avatarDisplay = document.getElementById('avatar-display');
        const croppedInput = document.getElementById('cropped_image');

        // When user selects a file
        fileInput.addEventListener('change', function(e) {
            const files = e.target.files;
            if (files && files.length > 0) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    imageToCrop.src = event.target.result;
                    cropModal.showModal();
                    
                    // Initialize or reset cropper
                    if (cropper) {
                        cropper.destroy();
                    }
                    cropper = new Cropper(imageToCrop, {
                        aspectRatio: 1, // Square for circle
                        viewMode: 1,
                        background: false
                    });
                };
                reader.readAsDataURL(files[0]);
            }
        });

        // When user clicks "Apply Crop"
        cropButton.addEventListener('click', function() {
            const canvas = cropper.getCroppedCanvas({
                width: 400,
                height: 400
            });

            // Update visible preview
            const base64Image = canvas.toDataURL('image/jpeg');
            avatarDisplay.src = base64Image;

            // Store the data in hidden input to be sent to PHP
            croppedInput.value = base64Image;

            cropModal.close();
        });
    </script>
</body>
</html>