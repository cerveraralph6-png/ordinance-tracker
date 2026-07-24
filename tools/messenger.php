<?php
include_once '../auth_check.php';
include '../db.php';
$me = $_SESSION['user_id'];
$with = isset($_GET['with']) ? (int)$_GET['with'] : 0;
if ($with > 0) { $conn->query("UPDATE messages SET is_read = 1 WHERE sender_id = $with AND receiver_id = $me"); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../Imag3s/baguio-logo-gov.png" type="image/png">
    <link rel="stylesheet" href="../CSS/messenger.css">
    <title>City Council | SP Messenger</title>
    <style>
        /* This ensures the mobile hiding works perfectly without removing any logic */
        @media (max-width: 768px) {
            .mobile-hide {
                display: none !important;
            }
            .msg-grid {
                grid-template-columns: 1fr !important;
            }
        }

        /* --- ADDED: PROFILE PIC STYLES --- */
        .user-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 15px;
        }
        .list-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e2e8f0;
            background: #f1f5f9;
            flex-shrink: 0;
        }
        .active .list-avatar {
            border-color: var(--primary);
        }
        .user-name-wrapper {
            flex: 1;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
            font-weight: 700;
            font-size: 0.9rem;
            color: #1e293b;
        }
    </style>
</head>
<body class="messenger-body">
    <nav class="table-nav">
        <div style="display: flex; align-items: center; gap: 15px;">
           <?php $homePath = ($_SESSION['role'] === 'admin') ? '/admin/admin.php' : '/user/user.php'; ?>
            <button class="btn-home" onclick="window.location.href='<?php echo $homePath; ?>'" style="background: none; border: none;"><img src="../Imag3s/baguio-logo-gov.png" alt="logo" style="height: 45px;"></button>
            <div>
                <h1 style="font-size: 1rem; font-weight: 800; color: #0f172a; line-height: 1;">Sangguniang Panlungsod</h1>
                <span style="font-size: 0.7rem; font-weight: 600; color: #64748b; text-transform: uppercase;">City Council</span>
            </div>
        </div>
        <div class="nav-actions">
            <?php $homePath = ($_SESSION['role'] === 'admin') ? '/admin/admin.php' : '/user/user.php'; ?>
            <button class="btn-home-style" onclick="window.location.href='<?php echo $homePath; ?>'">Home</button>
            <button class="btn-logout-style" onclick="window.location.href='../logout.php'">Logout</button>
        </div>
    </nav>

    <main class="messenger-main">
        <div class="messenger-header">
            <?php if($with > 0): ?>
                <!-- On Mobile, this goes back to the list. On Desktop, it functions similarly -->
                <button class="btn-nav" onclick="window.location.href='messenger.php'">← Back to List</button>
            <?php else: ?>
                <!-- If no chat selected, go back to dashboard -->
                <button class="btn-nav" onclick="window.location.href='<?php echo $homePath; ?>'">← Dashboard</button>
            <?php endif; ?>
            <h2 style="font-size: 1.1rem; font-weight: 800; color:#64748b; ">SP Messenger</h2>
        </div>

<div class="msg-grid">
    <!-- User List -->
    <div class="user-list <?php echo ($with > 0) ? 'mobile-hide' : ''; ?>">
        <?php
        // UPDATED: Added profile_pic to the SELECT query
        $users = $conn->query("SELECT id, full_name, profile_pic FROM users WHERE id != $me AND status='active'");
        while($u = $users->fetch_assoc()):
            $unread = $conn->query("SELECT COUNT(*) as c FROM messages WHERE sender_id={$u['id']} AND receiver_id=$me AND is_read=0")->fetch_assoc()['c'];
            
            // Handle profile picture fallback
            $userPic = !empty($u['profile_pic']) ? $u['profile_pic'] : 'default-avatar.png';
        ?>
            <div class="user-item <?php echo ($with == $u['id']) ? 'active' : ''; ?>" onclick="window.location.href='?with=<?php echo $u['id']; ?>'">
                <!-- RESTORED: Profile Picture logic replacing emoji -->
                <img src="../uploads/profiles/<?php echo $userPic; ?>" class="list-avatar" onerror="this.src='../uploads/profiles/default-avatar.png'">
                <span class="user-name-wrapper"><?php echo $u['full_name']; ?></span>
                <?php if($unread > 0): ?><span class="badge" style="background:var(--danger); color:white; padding: 2px 8px; border-radius: 10px; font-size: 0.7rem;"><?php echo $unread; ?></span><?php endif; ?>
            </div>
        <?php endwhile; ?>
    </div>

    <!-- Message Pane -->
    <div class="msg-pane <?php echo ($with == 0) ? 'mobile-hide' : ''; ?>">
        <?php if($with > 0): 
            $other = $conn->query("SELECT full_name FROM users WHERE id = $with")->fetch_assoc();
        ?>
            <div class="chat-header">Conversation with <?php echo $other['full_name']; ?></div>
            
            <div class="msg-history" id="history">
                <?php
                $msgs = $conn->query("SELECT m1.*, m2.message_text as quoted FROM messages m1 LEFT JOIN messages m2 ON m1.reply_to_id = m2.id WHERE (m1.sender_id=$me AND m1.receiver_id=$with) OR (m1.sender_id=$with AND m1.receiver_id=$me) ORDER BY m1.sent_at ASC");
                while($m = $msgs->fetch_assoc()):
                    $class = ($m['sender_id'] == $me) ? 'sent' : 'received';
                ?>
                    <div class="bubble-wrapper <?php echo $class; ?>">
                        <div class="bubble" onclick="setReply(<?php echo $m['id']; ?>, '<?php echo addslashes(htmlspecialchars($m['message_text'])); ?>')">
                            <?php if($m['quoted']): ?><div class="reply-quote" style="background:rgba(0,0,0,0.05); padding:5px; border-left:2px solid blue; font-size:0.7rem;"><?php echo htmlspecialchars($m['quoted']); ?></div><?php endif; ?>
                            <?php echo htmlspecialchars($m['message_text']); ?>
                        </div>
                        <span class="reply-link" onclick="setReply(<?php echo $m['id']; ?>, '<?php echo addslashes(htmlspecialchars($m['message_text'])); ?>')">Reply</span>
                    </div>
                <?php endwhile; ?>
            </div>

            <div id="reply-preview" style="display:none; background: #f8fafc; padding: 10px 20px; border-top: 1px solid var(--border); flex-shrink: 0;">
                <div style="display:flex; justify-content: space-between;">
                    <small style="color: var(--primary); font-weight: 800;">REPLYING TO</small>
                    <button onclick="cancelReply()" style="background:none; border:none; cursor:pointer;">&times;</button>
                </div>
                <p id="reply-text" style="font-size: 0.8rem; color: var(--text-muted); overflow: hidden; white-space: nowrap; text-overflow: ellipsis;"></p>
            </div>

            <form action="msg_logic.php" method="POST" class="msg-form" target="hidden_post_frame" onsubmit="showSending()">
                <input type="hidden" name="to_id" value="<?php echo $with; ?>">
                <input type="hidden" name="reply_to_id" id="reply-id-input" value="">
                <input type="text" name="message" id="msg-input" placeholder="Type message..." required autocomplete="off">
                <button type="submit" class="btn-save" style="background:var(--primary); color:white; border:none; padding: 10px 20px; border-radius: 8px; font-weight:700;">Send</button>
            </form>
        <?php else: ?>
            <div style="margin:auto; text-align:center; color:#94a3b8;">
                <span style="font-size:4rem; opacity:0.2;">💬</span>
                <p>Select a person to start chatting</p>
            </div>
        <?php endif; ?>
    </div>
</div>
    </main>

    <iframe name="hidden_post_frame" style="display:none;"></iframe>
    <?php if($with > 0): ?><iframe src="chat_view.php?with=<?php echo $with; ?>" style="display:none;" id="chat-data-frame"></iframe><?php endif; ?>
    
    <script>
        const hb = document.getElementById('history');
        if(hb) hb.scrollTop = hb.scrollHeight;
        function setReply(id, text) { 
            document.getElementById('reply-id-input').value = id; 
            document.getElementById('reply-text').innerText = text; 
            document.getElementById('reply-preview').style.display = 'block'; 
        }
        function cancelReply() { 
            document.getElementById('reply-id-input').value = ''; 
            document.getElementById('reply-preview').style.display = 'none'; 
        }
        function syncDisplay(html) {
            if(!hb) return;
            if(hb.innerHTML.trim() !== html.trim()) { window.location.reload(); }
        }
        function showSending() { 
            const b = document.querySelector('.btn-save'); b.innerText = "Wait..."; b.disabled = true; 
            setTimeout(() => { document.getElementById('msg-input').value = ''; }, 10);
        }
    </script>
</body>
</html>