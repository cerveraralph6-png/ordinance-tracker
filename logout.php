<?php
session_start();
session_unset();
session_destroy();

// Prevent back button from seeing the last page after logout
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

header("Location: index.php");
exit();
?>