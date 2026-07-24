<?php
include_once '../auth_check.php';
include '../db.php';

if ($_SESSION['role'] === 'admin') {
    $conn->query("TRUNCATE TABLE login_logs");
}

header("Location: security_logs.php");
exit();
?>