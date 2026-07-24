<?php
include 'db.php';

$sql = "
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `employee_id` VARCHAR(50) UNIQUE NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `passkey` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'user') DEFAULT 'user',
    `status` ENUM('active', 'archived') DEFAULT 'active',
    `password_changed` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `city_records` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `Date_Received` DATE, `Time_Received` TIME, `Ref_No` VARCHAR(100),
    `Type` VARCHAR(100), `Proponent` TEXT, `Subject` TEXT,
    `Subject_Description` TEXT, `Subject_Notation` TEXT, `Committee_Referred` TEXT,
    `Indorsement1` TEXT, `Date_Indorsed1` DATE, `Com_Rep_Nr` VARCHAR(100),
    `Com_Rep` TEXT, `Com_Rep_Date_Received` DATE, `Item_Nr` VARCHAR(100),
    `Agenda_Date` DATE, `Action_Taken` LONGTEXT, `Indorsement2` TEXT,
    `Indorsement2_Date` DATE, `Remarks` TEXT, `Folder` VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `sender_id` INT, `receiver_id` INT, `message_text` TEXT,
    `reply_to_id` INT, `sent_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, `is_read` TINYINT(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `files` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `uploader_id` INT, `filename` VARCHAR(255), `file_path` VARCHAR(255),
    `file_size` VARCHAR(50), `upload_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `file_transfers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `file_id` INT, `sender_id` INT, `receiver_id` INT,
    `transfer_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, `is_read` TINYINT(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category` ENUM('Seminar', 'Training', 'Others'),
    `event_name` VARCHAR(255), `event_date` DATE, `event_time` TIME,
    `location` VARCHAR(255), `status` ENUM('pending', 'done') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `system_sync` (
    `module_name` VARCHAR(50) PRIMARY KEY,
    `last_update` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `users` (id, employee_id, full_name, passkey, role, password_changed) 
VALUES (1, 'ADMIN_MASTER', 'System Administrator', 'bch@admin123', 'admin', 1);
";

if ($conn->multi_query($sql)) {
    echo "<h1>Database Setup Successful!</h1>";
    echo "<p>All tables have been created on Railway.</p>";
    echo "<a href='index.php'>Go to Login</a>";
} else {
    echo "Error creating database: " . $conn->error;
}
?>