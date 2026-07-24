<?php
include_once '../auth_check.php';
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $uid = $_SESSION['user_id'];
    $name = $_POST['full_name'];
    $bio = $_POST['bio'];
    
    // 1. Handle Cropped Image Data (Base64 from Cropper.js)
    if (!empty($_POST['cropped_image'])) {
        $targetDir = "../uploads/profiles/";
        
        // Ensure directory exists
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        // The data comes in as "data:image/jpeg;base64,/9j/4AAQSkZJRg..."
        // We need to strip the header and decode the actual image data
        $base64_string = $_POST['cropped_image'];
        $data = explode(',', $base64_string);
        
        if (isset($data[1])) {
            $decoded_image = base64_decode($data[1]);
            
            // Create a unique filename
            $fileName = time() . "_" . $uid . ".jpg";
            $targetPath = $targetDir . $fileName;

            // Save the decoded binary data as a physical file
            if (file_put_contents($targetPath, $decoded_image)) {
                // Update database with the new filename
                $updatePic = $conn->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
                $updatePic->bind_param("si", $fileName, $uid);
                $updatePic->execute();
            }
        }
    }

    // 2. Update Text Data (Name and Bio)
    $stmt = $conn->prepare("UPDATE users SET full_name = ?, bio = ? WHERE id = ?");
    $stmt->bind_param("ssi", $name, $bio, $uid);
    
    if ($stmt->execute()) {
        $_SESSION['full_name'] = $name; // Update session name instantly for the Nav bar
        header("Location: settings.php?success=1");
        exit();
    } else {
        echo "Error updating record: " . $conn->error;
    }
}
?>