<?php
session_start();
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $from = $_SESSION['user_id'];
    $to = (int)$_POST['to_id'];
    $msg = trim($_POST['message']);
    // Capture the reply ID
    $reply_to = !empty($_POST['reply_to_id']) ? (int)$_POST['reply_to_id'] : null;

    if (!empty($msg) && $to > 0) {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message_text, reply_to_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iisi", $from, $to, $msg, $reply_to);
        $stmt->execute();
        $stmt->close();
    }
}
echo "<script>window.parent.location.reload();</script>";
?>
// ... after your INSERT query in msg_logic.php ...
<script>
    // Refresh the iframe in the parent window
    if(window.parent.document.getElementById('chat-data-frame')) {
        window.parent.document.getElementById('chat-data-frame').src += '';
    }
</script>