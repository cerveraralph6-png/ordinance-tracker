<?php
session_start();
include '../db.php';
$me = $_SESSION['user_id'];
$with = (int)$_GET['with'];

if(!$me || $with <= 0) exit;

// --- CLEAR NOTIFICATIONS IN REAL-TIME ---
// As long as this hidden iframe is running, new messages are marked as "seen"
$conn->query("UPDATE messages SET is_read = 1 WHERE sender_id = $with AND receiver_id = $me");

$msgs = $conn->query("SELECT m1.*, m2.message_text as quoted_text 
                      FROM messages m1 
                      LEFT JOIN messages m2 ON m1.reply_to_id = m2.id 
                      WHERE (m1.sender_id=$me AND m1.receiver_id=$with) 
                      OR (m1.sender_id=$with AND m1.receiver_id=$me) 
                      ORDER BY m1.sent_at ASC");

$html = '';
while($m = $msgs->fetch_assoc()) {
    $class = ($m['sender_id'] == $me) ? 'sent' : 'received';
    $quote = $m['quoted_text'] ? "<div class='reply-quote' style='background:rgba(0,0,0,0.1); padding:5px; border-left:2px solid blue; font-size:0.7rem;'>".htmlspecialchars($m['quoted_text'])."</div>" : "";
    $text = htmlspecialchars($m['message_text']);
    $html .= "<div class='bubble-wrapper $class'><div class='bubble $class'>$quote $text</div></div>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <!-- Refresh every 8 seconds -->
    <meta http-equiv="refresh" content="8">
</head>
<body onload="if(parent && parent.syncDisplay) parent.syncDisplay(document.getElementById('data').innerHTML)">
    <div id="data">
        <?php echo $html; ?>
    </div>
</body>
</html>