<?php
$host = "sql208.hstn.me";
$user = "mseet_41951522";
$pass = "aqvE2DOTysUk";
$dbname = "mseet_41951522_bch_db_management_1990"; 

$conn = new mysqli($host, $user, $pass, $dbname);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>