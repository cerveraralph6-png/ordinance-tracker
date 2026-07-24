<?php
session_start();

$is_android_app = (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'BCH-Portal-App') !== false);

if (isset($_SESSION['role'])) {
    $redirect = ($_SESSION['role'] == 'admin') ? 'admin/admin.php' : 'user/user.php';
    header("Location: $redirect");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <link class="favicon" rel="icon" href="../Imag3s/baguio-logo-gov.png" type="image/png">
    <link rel="stylesheet" href="CSS/index.css?v=<?php echo filemtime('CSS/index.css'); ?>">
    <title>City Council | Ordinance Portal</title>
    
    <style>
/* --- APK DOWNLOAD BUTTON --- */
.btn-download-apk {
    text-decoration: none;
    background: #ffffff;
    color: #1e3a8a;
    padding: 10px 20px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 8px;
    border: 2px solid #1e3a8a;
    transition: all 0.3s ease;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
}

.btn-download-apk:hover {
    background: #f1f5f9;
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.1);
}

/* Ensure Nav Actions stay side-by-side */
.nav-actions { display: flex; align-items: center; gap: 12px; }

@media (max-width: 600px) {
    .btn-download-apk span { display: none; }
    .btn-download-apk, .btn-login-trigger { padding: 8px 12px; font-size: 12px; }
}

/* --- CATEGORY SELECTION STYLES --- */
.portal-selection {
    width: 100%; max-width: 1100px; margin: 0 auto;
    display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px; padding: 20px;
}

.category-card {
    background: white; padding: 50px 30px; border-radius: 30px;
    text-align: center; cursor: pointer; transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    box-shadow: 0 15px 35px rgba(0,0,0,0.1); border: 2px solid transparent;
}

.category-card:hover { transform: translateY(-15px); border-color: #2563eb; box-shadow: 0 25px 50px rgba(37, 99, 235, 0.2); }
.cat-icon { font-size: 4rem; margin-bottom: 20px; display: block; }
.category-card h2 { color: #1e3a8a; font-size: 1.6rem; font-weight: 800; margin-bottom: 10px; }
.category-card p { color: #64748b; font-size: 1rem; line-height: 1.6; }

.btn-back-selection {
    background: rgba(255,255,255,0.2); color: white; border: 1px solid white;
    padding: 8px 16px; border-radius: 20px; cursor: pointer; font-weight: 700; margin-bottom: 20px;
    transition: 0.2s;
}
.btn-back-selection:hover {
    background: rgba(255,255,255,0.3);
}

.badge-soon {
    display: inline-block; background: #e2e8f0; color: #475569; 
    font-size: 0.75rem; font-weight: bold; padding: 3px 10px; 
    border-radius: 9999px; margin-top: 10px;
}

/* RESTORED: Hiding APK button in App */
.is-app .btn-download-apk { display: none !important; }
    </style>

    <script>
        window.history.pushState(null, null, window.location.href);
        window.onpopstate = function () { window.history.go(1); };
    </script>
</head>
<body class="public-bg <?php echo $is_android_app ? 'is-app' : ''; ?>">

    <!-- Page Loader -->
    <div id="page-loader">
        <div class="loader-wrapper">
            <img src="Imag3s/baguio-logo-gov.png" alt="BCH" class="loader-logo">
            <div class="loader-circle"></div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="public-nav">
        <div class="nav-brand">
            <img src="Imag3s/baguio-logo-gov.png" alt="Logo">
            <div class="brand-text">
                <h1>City Council of Baguio</h1>
                <span>Sangguniang Panlungsod Portal</span>
            </div>
        </div>

        <div class="nav-actions">
            <!-- RESTORED: Download APK Button (Conditional) -->
            <button class="btn-login-trigger" onclick="document.getElementById('modal-login').showModal()">
                Administrative Login
            </button>
        </div>
    </nav>

<main class="public-main">
    
    <!-- 1. MAIN SELECTION VIEW -->
    <div id="selection-view" class="fade-in">
        <div style="text-align: center; margin-bottom: 40px;">
            <h1 style="color: white; font-size: 5rem; font-weight: 800; text-shadow: 0 4px 10px rgba(0,0,0,0.3);">Welcome!</h1>
            <p style="color: rgba(255,255,255,0.9); font-size: 1.2rem;">Please select a specialized tracking system</p>
        </div>

        <div class="portal-selection">
            <div class="category-card" onclick="openTracker('main', 'General Ordinance Tracker')">
                <span class="cat-icon">🔎</span>
                <h2>Ordinance Tracker</h2>
                <p>Search all general city records, legislation, and resolutions from the central database.</p>
            </div>

            <div class="category-card" onclick="showGadSelection()">
                <span class="cat-icon">🔎</span>
                <h2>GAD Corner</h2>
                <p>Gender and Development archives, resources, and specialized tracking systems.</p>
            </div>

            <div class="category-card" style="opacity: 0.7; cursor: default;">
                <span class="cat-icon">📁</span>
                <h2>Soon</h2>
                <p>Additional digital archives and specialized trackers will be available soon.</p>
            </div>
        </div>
    </div>

    <!-- 2. GAD CORNER SUB-SELECTION VIEW -->
    <div id="gad-selection-view" style="display: none;" class="fade-in">
        <button class="btn-back-selection" onclick="showMainSelection()">← Back to Main Menu</button>
        <div style="text-align: center; margin-bottom: 40px;">
            <h1 style="color: white; font-size: 5rem; font-weight: 800; text-shadow: 0 4px 10px rgba(0,0,0,0.3);">GAD Corner</h1>
            <p style="color: rgba(255,255,255,0.9); font-size: 1.2rem;">Gender and Development Resources & Trackers</p>
        </div>

        <div class="portal-selection">
            <div class="category-card" onclick="openTracker('women', 'Ordinances on Women Tracker')">
                <span class="cat-icon">⚖️</span>
                <h2>Ordinances on Women</h2>
                <p>Access the dedicated database for gender-responsive legislation and women's rights records.</p>
            </div>

            <div class="category-card" style="opacity: 0.7; cursor: default;">
                <span class="cat-icon">🗣️</span>
                <h2>Gender-Sensitivity Language</h2>
                <p>Resources and terminology standards for gender-sensitive language and communication.</p>
                <span class="badge-soon">Soon</span>
            </div>

            <div class="category-card" style="opacity: 0.7; cursor: default;">
                <span class="cat-icon">📁</span>
                <h2>Soon</h2>
                <p>Additional gender and development digital archives will be available soon.</p>
            </div>
        </div>
    </div>

    <!-- 3. SEARCH VIEW -->
    <div id="search-view" style="display: none; width: 100%; max-width: 850px;">
        <button class="btn-back-selection" onclick="closeTracker()">← Back to Categories</button>
        <div class="search-card fade-in">
            <header class="card-header">
                <img src="Imag3s/baguio-logo-gov.png" alt="City Seal">
                <h1 id="dynamic-tracker-title">Track My Ordinance</h1>
                <p>Search by <strong>Keywords</strong>, <strong>Reference No.</strong>, or <strong>Proponent Name</strong></p>
            </header>

            <div class="search-engine">
                <div class="console-section filters">
                    <select id="public-filter-by" class="console-input">
                        <option value="Subject">Keywords</option>
                        <option value="Ref_No">Ordinance No.</option>
                        <option value="Proponent">Proponent Name</option>
                    </select>
                    <div class="console-divider"></div>
                    <select id="public-proponent-type" class="console-input">
                        <option value="All">Any Proponent</option>
                        <option value="Single">Single</option>
                        <option value="Multiple">Multiple</option>
                    </select>
                    <div class="console-divider"></div>
                    <select id="public-status-filter" class="console-input">
                        <option value="All">All Status</option>
                        <option value="Approved">Approved</option>
                        <option value="Pending">Pending</option>
                    </select>
                </div>

                <div class="search-console">
                    <div class="console-section search-main">
                        <span class="console-icon">🔍</span>
                        <input type="text" id="public-search-query" placeholder="Type here to search city records..." onkeyup="if(event.key === 'Enter') trackOrdinance()">
                    </div>
                    <div class="console-section actions">
                        <button class="btn-search-primary" onclick="trackOrdinance()">Search</button>
                        <button class="btn-search-clear" onclick="clearTrackSearch()">Clear</button>
                    </div>
                </div>
            </div>
            <!-- Results will be injected here and are clickable via JS logic -->
            <div id="ordinance-result-container"></div>
        </div>
    </div>
</main>

    <!-- Modal: Administrative Login -->
    <dialog id="modal-login" class="login-modal">
        <div class="modal-content">
            <button class="close-modal" onclick="document.getElementById('modal-login').close()">&times;</button>
            <h2>Administrative Access</h2>
            <form id="login-form" method="POST">
                <div class="input-box">
                    <label for="passkey">System Passkey</label>
                    <input type="password" name="passkey" id="passkey" placeholder="••••••••" required>
                </div>
                <div id="error-msg"></div>
                <button type="submit" class="btn-submit-login">Enter Dashboard</button>
            </form>
        </div>
    </dialog>

    <!-- RESTORED: Public Detail View Modal (This makes results clickable) -->
    <dialog id="modal-public-view" class="table-modal">
        <div class="modal-content" style="width: 100%; max-width: 800px; padding:0;">
            <div class="modal-head">
                <div class="modal-title-box">
                    <small>Ordinance Details</small>
                    <h2 id="public-view-title">Subject</h2>
                </div>
                <button type="button" class="modal-close-x" onclick="document.getElementById('modal-public-view').close()">&times;</button>
            </div>
            <div id="public-details-content" class="modal-details-grid">
                <!-- Details injected here via main.js openPublicDetail() -->
            </div>
            <div class="modal-foot" style="justify-content: flex-end;">
                <button type="button" class="btn-close-gray" onclick="document.getElementById('modal-public-view').close()">Close</button>
            </div>
        </div>
    </dialog>

    <!-- RESTORED: Back to Top Button -->
    <button id="btn-back-to-top" title="Go to top">↑</button>

    <input type="hidden" id="active-category" value="main">
    <script src="scripts/main.js"></script>
    <script>
        function showGadSelection() {
            document.getElementById('selection-view').style.display = 'none';
            document.getElementById('gad-selection-view').style.display = 'block';
        }

        function showMainSelection() {
            document.getElementById('gad-selection-view').style.display = 'none';
            document.getElementById('selection-view').style.display = 'block';
        }

        function openTracker(type, title) {
            document.getElementById('active-category').value = type;
            document.getElementById('dynamic-tracker-title').innerText = title;
            document.getElementById('selection-view').style.display = 'none';
            document.getElementById('gad-selection-view').style.display = 'none';
            document.getElementById('search-view').style.display = 'block';
            clearTrackSearch(); 
        }

        function closeTracker() {
            const activeType = document.getElementById('active-category').value;
            document.getElementById('search-view').style.display = 'none';
            
            // Return back to the category menu from where the tracker was opened
            if (activeType === 'women') {
                document.getElementById('gad-selection-view').style.display = 'block';
            } else {
                document.getElementById('selection-view').style.display = 'block';
            }
        }

        // RESTORED: Service Worker registration
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(reg => console.log('BCH Offline Guard Active'))
                    .catch(err => console.log('Service Worker failed', err));
            });
        }
    </script>
</body>
</html>