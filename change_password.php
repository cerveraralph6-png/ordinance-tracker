<?php
session_start();
if (!isset($_SESSION['role'])) { header("Location: index.php"); exit(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BCH | Security Setup</title>
    <link rel="stylesheet" href="CSS/tools.css?v=<?php echo filemtime('CSS/tools.css'); ?>">
    
    <style>
        /* --- SECURITY PAGE SPECIFIC STYLES --- */
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            background-color: #f1f5f9;
            margin: 0;
            padding: 20px;
        }

        .setup-card {
            background: white;
            padding: 50px 40px;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
            max-width: 440px;
            width: 100%;
            text-align: center;
            border: 1px solid #e2e8f0;
            animation: slideUp 0.5s ease-out;
        }

        .setup-icon {
            font-size: 3.5rem;
            margin-bottom: 20px;
            display: block;
        }

        .setup-card h2 {
            font-size: 1.75rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 10px;
            letter-spacing: -0.02em;
        }

        .setup-card p {
            font-size: 0.95rem;
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 35px;
        }

        .user-highlight {
            color: var(--primary);
            font-weight: 700;
        }

        /* Form Layout */
        .form-group {
            text-align: left;
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-size: 0.75rem;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding-left: 4px;
        }

        /* Error Message Styling */
        .error-box {
            background: #fef2f2;
            color: #dc2626;
            padding: 12px;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 20px;
            border: 1px solid #fecaca;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        input{
            padding: 10px;
            border-radius: 8px;
            font-size: 15px;
            border: 1px solid black;
        }
    </style>
</head>
<body class="has-watermark">
    
    <div class="setup-card">
        <span class="setup-icon">🛡️</span>
        <h2>Security Setup</h2>
        
        <p>
            Hi, <span class="user-highlight"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>. 
            To protect your account, please create a new personal passkey.
        </p>

        <!-- Added ID here so the Script can find it -->
        <form id="setup-form">
            <div class="form-group">
                <label>Create New Passkey</label>
                <input type="password" name="new_pass" placeholder="At least 6 characters" required minlength="6" autofocus>
            </div>
            
            <div class="form-group">
                <label>Confirm Your Passkey</label>
                <input type="password" name="confirm_pass" placeholder="Repeat passkey to verify" required>
            </div>

            <!-- Added Error Container here for AJAX response -->
            <div id="setup-error-msg"></div>

            <button type="submit" class="btn-save" style="width: 100%; height: 55px; font-size: 1rem; border-radius: 12px;">
                Secure My Account
            </button>
        </form>
    </div>
    
<script>
document.getElementById('setup-form').addEventListener('submit', function(e) {
    e.preventDefault(); // This stops the raw JSON text from showing
    
    const errorDiv = document.getElementById('setup-error-msg');
    const submitBtn = document.querySelector('.btn-save');
    const formData = new FormData(this);

    submitBtn.innerText = "Processing...";
    submitBtn.disabled = true;

    fetch('update_password_logic.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            // Success! The redirect now happens via JS
            window.location.href = data.redirect;
        } else {
            // Show error inside the card
            errorDiv.innerHTML = `<div class="error-box">⚠️ ${data.message}</div>`;
            submitBtn.innerText = "Secure My Account";
            submitBtn.disabled = false;
        }
    })
    .catch(err => {
        alert("Server error. Please try again.");
        submitBtn.innerText = "Secure My Account";
        submitBtn.disabled = false;
    });
});
    
        // Disable Back Button
    window.history.pushState(null, null, window.location.href);
    window.onpopstate = function () {
        window.history.go(1);
    };
</script>

</body>
</html>