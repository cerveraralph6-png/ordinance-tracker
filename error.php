<?php
$code = $_GET['code'] ?? '404';

// Define error messages
$errors = [
    '404' => [
        'title' => 'Page Not Found',
        'desc' => 'The administrative record or page you are looking for does not exist or has been moved.'
    ],
    '403' => [
        'title' => 'The page is currently down', #'Access Forbidden'
        'desc' => 'You do not have the required administrative clearance to access this secure directory.'
    ],
    '500' => [
        'title' => 'Internal Server Error',
        'desc' => 'The system encountered an unexpected glitch. Our technical team has been notified.'
    ],
    '503' => [
        'title' => 'Service Unavailable',
        'desc' => 'The server is currently overloaded or down for maintenance. Please try again in a few minutes.'
    ]
];

// Fallback if code isn't in list
$error = $errors[$code] ?? $errors['404'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BCH | Error <?php echo $code; ?></title>
    <link rel="stylesheet" href="CSS/tools.css">
    <style>
        body {
            background-color: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            text-align: center;
        }
        .error-card {
            background: white;
            margin-top: -100px;
            padding: 60px 40px;
            border-radius: 24px;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 90%;
            border: 1px solid #e2e8f0;
        }
        .error-code {
            font-size: 5rem;
            font-weight: 900;
            color: black;
            line-height: 1;
            margin-bottom: 10px;
        }
        .error-icon { font-size: 3rem; margin-bottom: 20px; display: block; }
        h1 { color: #0f172a; font-size: 1.8rem; font-weight: 800; margin-bottom: 15px; }
        p { color: #64748b; line-height: 1.6; margin-bottom: 30px; font-size: 1rem; }
        
        .btn-back {
            background: #2563eb;
            color: white;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 12px;
            font-weight: 700;
            display: inline-block;
            transition: 0.2s;
        }
        .btn-back:hover { background: #1d4ed8; transform: translateY(-2px); }
        
                /* --- MOBILE BREAKPOINTS --- */
        @media (max-width: 480px) {
            .error-card {
                padding: 40px 25px;
                border-radius: 20px;
            }
            .error-icon { font-size: 2.5rem; }
            .btn-back {
                width: 100%; /* Full width button for thumbs */
                padding: 16px 20px;
            }
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

    <div class="error-card">
        <img src="Imag3s/baguio-logo-gov.png" alt="Baguio Logo" style="height: 60px; margin-bottom: 20px;">
        <div class="error-code"><?php echo $code; ?></div>
        <span class="error-icon"><?php echo $error['icon']; ?></span>
        <h1><?php echo $error['title']; ?></h1>
        <p><?php echo $error['desc']; ?></p>
        <a href="/index.php" class="btn-back">Return to Safety</a>
    </div>

</body>
</html>