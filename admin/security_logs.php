<?php
    
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once '../auth_check.php';
include '../db.php';

// Security check: only admins
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../user/user.php");
    exit();
}

// Fetch logs
$logs = $conn->query("SELECT * FROM login_logs ORDER BY last_attempt DESC LIMIT 100");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../Imag3s/baguio-logo-gov.webp" type="image/png">
    <link rel="stylesheet" href="../CSS/tools.css">
    <title>City Council | Security Logs</title>
</head>
<body>
    
    <!-- For subfolder pages (table.php, events.php, etc.) -->
<div id="page-loader">
    <div class="loader-wrapper">
           <?php $homePath = ($_SESSION['role'] === 'admin') ? '/admin/admin.php' : '/user/user.php'; ?>
            <button class="btn-home" onclick="window.location.href='<?php echo $homePath; ?>'" style="background: none; border: none;"><img src="../Imag3s/baguio-logo-gov.png" alt="logo" style="height: 45px;"></button>
        <div class="loader-circle"></div>
    </div>
</div>
    <nav>
        <img src="/Imag3s/baguio-logo-gov.png" alt="logo">
        <h1>Security Monitor</h1>
        <div class="nav-actions">
            <button class="btn-nav" onclick="window.location.href='admin.php'">Home</button>
            <button class="btn-nav logout" id="logout">Logout</button>
        </div>
    </nav>

    <main>
        <div id="container1">
            <div class="table-header">
                <h1>Failed Login Attempts</h1>
                <button id="add" style="background: #64748b;" onclick="if(confirm('Clear all logs?')) window.location.href='clear_logs.php'">🗑️ Clear History</button>
            </div>

        <!-- Update the table in security_logs.php -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>IP Address</th>
                        <th>Attempts</th> <!-- New Column -->
                        <th>Last Failure</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $logs = $conn->query("SELECT * FROM login_logs ORDER BY last_attempt DESC");
                    if ($logs->num_rows > 0): 
                        while($row = $logs->fetch_assoc()): 
                    ?>
                        <tr>
                            <td style="font-family: monospace; font-weight: 600; color: #ef4444;">
                                <?php echo $row['ip_address']; ?>
                            </td>
                            <td style="font-weight: 700; text-align: center;">
                                <?php echo $row['attempts']; ?>
                            </td>
                            <td><?php echo date('M d, Y | h:i:s A', strtotime($row['last_attempt'])); ?></td>
                            <td>
                                <a href="https://whois.domaintools.com/<?php echo $row['ip_address']; ?>" target="_blank" class="btn-nav" style="font-size: 0.75rem; padding: 5px 10px;">Trace IP ↗</a>
                            </td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="4" style="text-align:center; padding: 40px; color: #94a3b8;">No security threats detected.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script src="../scripts/main.js"></script>
</body>
</html>