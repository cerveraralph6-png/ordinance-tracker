// --- PAGE LOAD HANDLER ---
window.addEventListener('load', () => {
    const loader = document.getElementById('page-loader');
    if (loader) {
        loader.classList.add('loader-hidden');
        setTimeout(() => {
            loader.style.display = 'none';
        }, 500); 
    }
});

// --- NEW: PORTAL VIEW TOGGLE LOGIC ---
function openTracker(type, title) {
    // Set the hidden category input
    const activeCatInput = document.getElementById('active-category');
    if (activeCatInput) activeCatInput.value = type;

    // Update the UI title
    const dynamicTitle = document.getElementById('dynamic-tracker-title');
    if (dynamicTitle) dynamicTitle.innerText = title;

    // Switch Views
    document.getElementById('selection-view').style.display = 'none';
    document.getElementById('search-view').style.display = 'block';
    
    // Clear any previous results
    clearTrackSearch();
    
    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function closeTracker() {
    document.getElementById('selection-view').style.display = 'block';
    document.getElementById('search-view').style.display = 'none';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

document.addEventListener('DOMContentLoaded', () => {
    console.log("BCH System Active...");

    // --- 1. AJAX LOGIN ---
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault(); 
            const errorDiv = document.getElementById('error-msg');
            const formData = new FormData(this);
            fetch('login_process.php', { method: 'POST', body: formData })
            .then(res => res.json()).then(data => {
                if (data.status === 'success') { window.location.href = data.redirect; } 
                else { errorDiv.innerText = data.message || "Invalid Passkey!"; }
            }).catch(err => console.error("Login Error:", err));
        });
    }

    const logoutBtn = document.getElementById('logout');
    if (logoutBtn) { logoutBtn.addEventListener('click', () => { window.location.href = '/logout.php'; }); }

    // --- 2. CSV UPLOAD LOADER ---
    const importForm = document.querySelector('#modal-import form');
    if (importForm) {
        importForm.addEventListener('submit', () => { document.getElementById('upload-loader').style.display = 'flex'; });
    }

    // --- 3. SELECTIVE DOWNLOAD ---
    const selectAll = document.getElementById('select-all-rows');
    const exportBtn = document.getElementById('btn-export-selected');
    const countDisplay = document.getElementById('selected-count');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            document.querySelectorAll('.record-checkbox').forEach(cb => cb.checked = this.checked);
            updateExportButtonState();
        });
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('record-checkbox')) updateExportButtonState();
        });
        function updateExportButtonState() {
            const checkedCount = document.querySelectorAll('.record-checkbox:checked').length;
            if(countDisplay) countDisplay.innerText = checkedCount;
            if (exportBtn) {
                exportBtn.style.setProperty('opacity', checkedCount > 0 ? '1' : '0.5', 'important');
                exportBtn.style.setProperty('pointer-events', checkedCount > 0 ? 'auto' : 'none', 'important');
            }
        }
    }

    // --- 4. SMART REAL-TIME SYNC ---
    const pagePath = window.location.pathname;
    let moduleName = "";
    if (pagePath.includes('table.php')) moduleName = "records";
    else if (pagePath.includes('events.php')) moduleName = "events";

    if (moduleName !== "") {
        let lastSyncTime = Math.floor(Date.now() / 1000);
        setInterval(() => {
            if (document.hidden) return;
            const root = window.location.origin;
            const syncUrl = `${root}/tools/sync_check.php`;
            fetch(`${syncUrl}?module=${moduleName}&last_sync=${lastSyncTime}&v=${Date.now()}`, {
                method: 'GET',
                credentials: 'include',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.update_needed) {
                    refreshPageFragments();
                    lastSyncTime = data.server_time;
                }
            }).catch(err => console.log("Sync standby..."));
        }, 5000);
    }
});

/**
 * Background Fragment Swapping
 */
function refreshPageFragments() {
    fetch(window.location.href, { credentials: 'include' })
    .then(response => response.text())
    .then(html => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const containers = ['tbody', '.table-pagination', '.pagination', '.stats-badges', '.badge-row', '.stats-info'];
        containers.forEach(selector => {
            const newData = doc.querySelector(selector);
            const currentArea = document.querySelector(selector);
            if (newData && currentArea) { currentArea.innerHTML = newData.innerHTML; }
        });
    });
}

// --- GLOBAL POPUP FUNCTIONS ---
let currentRecordIndex = -1;
let allPageRecords = [];
let currentRecordData = null;

function openDetailByIndex(index) {
    const stores = document.querySelectorAll('.json-store');
    allPageRecords = Array.from(stores).map(s => JSON.parse(s.innerText));
    currentRecordIndex = index;
    const modal = document.getElementById('modal-view-details');
    if (modal) { updateModalContent(allPageRecords[index]); modal.showModal(); }
}

function navRecord(step) {
    let nextIdx = currentRecordIndex + step;
    if (nextIdx >= 0 && nextIdx < allPageRecords.length) {
        currentRecordIndex = nextIdx;
        const editBtn = document.getElementById('btn-enable-edit');
        const saveBtn = document.getElementById('btn-trigger-verify');
        if(editBtn) editBtn.style.setProperty('display', 'inline-flex', 'important');
        if(saveBtn) saveBtn.style.setProperty('display', 'none', 'important');
        updateModalContent(allPageRecords[currentRecordIndex]);
    }
}

function updateModalContent(data) {
    currentRecordData = data;
    const title = document.getElementById('view-subject-title');
    if(title) title.innerText = data.Subject || data.Title || "Details";
    const idField = document.getElementById('view-record-id');
    if(idField) idField.value = data.id;
    renderDetails(false); 
}

function parseTimeline(dataString) {
    if (!dataString || dataString === "-none-" || dataString === "") {
        return "<p style='color:#94a3b8; font-size:0.85rem; font-style:italic;'>No history recorded yet.</p>";
    }
    const entries = dataString.split('|||').filter(e => e.trim() !== "");
    let html = '<div class="timeline-log">';
    entries.forEach(entry => {
        const parts = entry.split('@@@');
        const text = parts[1] || entry;
        html += `<div class="timeline-entry"><p class="timeline-text">${text}</p></div>`;
    });
    html += '</div>';
    return html;
}

function renderDetails(isEditMode) {
    const content = document.getElementById('details-content');
    const data = currentRecordData;
    let html = '';
    let fields = {};

    // --- 1. DYNAMIC FIELD MAPPING ---
if (data.Type_of_law !== undefined) {
    fields = {
        "Type_of_law": "Type of Law",
        "Number": "Number",
        "Series": "Year / Series",
        "Title": "Title of Ordinance",
        "Author": "Author / Proponent",
        "Implementing_department": "Implementing Department",
        "Hyperlink": "Main Link",
        "Other_Attachment": "Attachment Info",
        "Hyperlink1": "Extra Link 1",
        "Hyperlink2": "Extra Link 2",
        "Hyperlink3": "Extra Link 3"
    };
}
    else {
        fields = {
            "Date_Received": "Date Received", "Time_Received": "Time Received", "Ref_No": "Ref No.", 
            "Type": "Type", "Proponent": "Proponent", "Subject": "Subject", 
            "Action_Taken": "Action History Tracking", "Subject_Description": "Description", 
            "Subject_Notation": "Notation", "Committee_Referred": "Committee Referred", 
            "Indorsement1": "Indorsement 1", "Date_Indorsed1": "Date Indorsed 1", 
            "Com_Rep_Nr": "Com Rep Nr", "Com_Rep": "Com Rep", "Com_Rep_Date_Received": "Date Rec (Rep)", 
            "Item_Nr": "Item Nr", "Agenda_Date": "Agenda Date", "Indorsement2": "Indorsement 2", 
            "Indorsement2_Date": "Date Ind 2", "Remarks": "Remarks", "Folder": "Folder"
        };
    }

    // --- 2. GENERATE THE HTML ---
    for (const [key, label] of Object.entries(fields)) {
        let value = data[key] || "";
        const isLong = label.includes("Description") || label.includes("Remarks") || 
                       key === "Subject" || key === "Title" || key === "Action_Taken" || 
                       key.includes("Hyperlink") || key === "Other_Attachment";
        const containerClass = isLong ? "detail-item full-width" : "detail-item";

        if (isEditMode) {
            if (key === "Action_Taken") {
                html += `
                    <div class="${containerClass}">
                        <div class="new-update-area">
                            <span class="new-update-label">➕ Add Progress Update</span>
                            <textarea name="new_action_note" placeholder="Enter update..."></textarea>
                            <div class="history-preview-box">${parseTimeline(value)}</div>
                            <input type="hidden" name="Action_Taken" value="${value}">
                        </div>
                    </div>`;
            } else {
                let inputField = isLong ? `<textarea name="${key}" rows="2">${value}</textarea>` : `<input type="text" name="${key}" value="${value}">`;
                if(key.toLowerCase().includes('date')) inputField = `<input type="date" name="${key}" value="${value}">`;
                html += `<div class="${containerClass}"><label class="detail-label">${label}</label>${inputField}</div>`;
            }
        } else {
            const displayVal = (value === "" || value === "-none-" || value === "0000-00-00") ? '<span class="none-text">-none-</span>' : value;
            let finalOutput = displayVal;
            if (key.includes("Hyperlink") && value !== "" && value !== "-none-") {
                finalOutput = `<a href="${value}" target="_blank" style="color:#2563eb; text-decoration:underline; font-weight:700;">Click to Open Link</a>`;
            }
            html += `<div class="${containerClass}"><label class="detail-label">${label}</label><div class="detail-value">${key === "Action_Taken" ? parseTimeline(value) : finalOutput}</div></div>`;
        }
    }
    content.innerHTML = html;
}

function enableEditMode() {
    renderDetails(true);
    const label = document.getElementById('modal-mode-label');
    if(label) { label.innerText = "⚠️ EDITING MODE"; label.style.color = "#ef4444"; }
    const editBtn = document.getElementById('btn-enable-edit');
    const saveBtn = document.getElementById('btn-trigger-verify');
    if (editBtn) editBtn.style.setProperty('display', 'none', 'important');
    if (saveBtn) saveBtn.style.setProperty('display', 'inline-flex', 'important');
}

// --- NEW/RESTORED: EDIT VERIFICATION FUNCTIONS ---

function openVerifyModal() { 
    const vModal = document.getElementById('modal-verify-passkey');
    if (vModal) vModal.showModal(); 
}

function submitEditWithPasskey() {
    const passInput = document.getElementById('popup-passkey-input');
    const hiddenPasskey = document.getElementById('hidden-passkey');
    const editForm = document.getElementById('edit-detail-form');
    
    if (!passInput || !passInput.value) { 
        alert("Please enter your passkey."); 
        return; 
    }

    hiddenPasskey.value = passInput.value;
    const formData = new FormData(editForm);

    // FIX: Adding specific headers to bypass free hosting firewall
    fetch('edit_record_logic.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest' // This is the critical line
        }
    })
    .then(async res => {
        const text = await res.text();
        
        // DEBUG: If the server sends HTML instead of JSON, the firewall blocked us
        if (text.includes('<script') || text.includes('<html>')) {
            console.error("Firewall Blocked Request:", text);
            throw new Error("Security Check: Please refresh the page and try again.");
        }

        try {
            return JSON.parse(text);
        } catch (e) {
            console.error("Invalid JSON:", text);
            throw new Error("Server returned an invalid response.");
        }
    })
    .then(data => {
        if (data.status === 'success') {
            document.getElementById('modal-verify-passkey').close();
            closeDetailModal();
            window.location.reload(); 
            alert("Record updated successfully!");
        } else {
            alert("Error: " + data.message);
        }
    })
    .catch(err => {
        alert(err.message);
    });
}

function closeDetailModal() {
    document.getElementById('modal-view-details').close();
    const editBtn = document.getElementById('btn-enable-edit');
    const saveBtn = document.getElementById('btn-trigger-verify');
    if(editBtn) editBtn.style.setProperty('display', 'inline-flex', 'important');
    if(saveBtn) saveBtn.style.setProperty('display', 'none', 'important');
}

// --- REST OF YOUR LOGIC ---

function trackOrdinance() {
    const query = document.getElementById('public-search-query').value;
    const filter = document.getElementById('public-filter-by').value;
    const statusFilter = document.getElementById('public-status-filter').value;
    const propType = document.getElementById('public-proponent-type').value; 
    const activeCat = document.getElementById('active-category').value; 
    const resultContainer = document.getElementById('ordinance-result-container');
    
    if (!query && statusFilter === "All" && propType === "All") {
        alert("Please enter search criteria."); 
        return;
    }

    resultContainer.innerHTML = "<div style='text-align:center; padding:20px; color:white;'>Searching records...</div>";

    fetch(`tools/public_track.php?query=${encodeURIComponent(query)}&filter=${filter}&status=${statusFilter}&prop_type=${propType}&cat=${activeCat}`)
    .then(response => response.json())
    .then(data => {
        resultContainer.innerHTML = "";
        publicSearchResults = data.results || [];

        if (data.found && data.results.length > 0) {
            resultContainer.innerHTML = `<div class="results-counter">FOUND ${data.results.length} MATCHING RECORDS</div>`;
            data.results.forEach((item, index) => {
                const actionRaw = item.Action_Taken || "";
                const historyEntries = actionRaw.split('|||');
                const latestEntryParts = historyEntries[0].split('@@@');
                let latestDesc = latestEntryParts[1] ? latestEntryParts[1].trim() : (latestEntryParts[0] || "No history yet");
                let sClass = "status-pending", sLabel = "PENDING...";
                if (actionRaw.toLowerCase().includes("approved")) { sClass = "status-approved"; sLabel = ""; } 
                else if (actionRaw.toLowerCase().includes("disapproved")) { sClass = "status-disapproved"; sLabel = "REJECTED"; }

                resultContainer.innerHTML += `
                    <div class="result-box ${sClass}-border" onclick="openPublicDetail(${index})" style="cursor:pointer; margin-top:20px;">
                        <div class="result-header">
                            <span class="status-pill ${sClass}"><strong>STATUS:</strong> ${sLabel}  ${latestDesc}</span>
                            <span class="result-rec">Rec: ${item.Date_Received}</span>
                        </div>
                        <h3 class="result-subject" style="color:var(--text-dark); margin: 15px 0;">${item.Subject}</h3>
                        <div class="result-footer">
                            <div class="footer-grid">
                                <div><strong>Ordinance Number</strong><span>${item.Ref_No || '-none-'}</span></div>
                                <div><strong>Proponent</strong><span>${item.Proponent || 'N/A'}</span></div>
                            </div>
                        </div>
                    </div>`;
            });
        } else {
            resultContainer.innerHTML = `<div style="background:white; padding:30px; border-radius:15px; text-align:center; color:#64748b;">No matching records found.</div>`;
        }
    }).catch(err => console.error(err));
}

function openPublicDetail(index) {
    const data = publicSearchResults[index];
    const container = document.getElementById('public-details-content');
    document.getElementById('public-view-title').innerText = data.Subject;
    let html = '';
    if (data.db_type === 'women') {
        const fields = { "Type": "Type of Law", "Ref_No": "Number", "Series": "Series", "Proponent": "Author", "Implementing_department": "Implementing Department", "Hyperlink": "Direct Link" };
        for (const [key, label] of Object.entries(fields)) {
            let value = data[key] || "-none-";
            if (key === 'Hyperlink' && value !== "-none-") value = `<a href="${value}" target="_blank" style="color:blue; text-decoration:underline;">View Document</a>`;
            html += `<div class="detail-item"><label class="detail-label">${label}</label><div class="detail-value">${value}</div></div>`;
        }
    } else {
        const fields = { "Date_Received": "Date Received", "Ref_No": "Ordinance No.", "Type": "Type", "Proponent": "Proponent", "Action_Taken": "Action History" };
        for (const [key, label] of Object.entries(fields)) {
            let value = data[key] || "-none-";
            const val = (key === "Action_Taken") ? parseTimeline(value) : value;
            html += `<div class="detail-item"><label class="detail-label">${label}</label><div class="detail-value">${val}</div></div>`;
        }
    }
    container.innerHTML = html;
    document.getElementById('modal-public-view').showModal();
}

function clearTrackSearch() {
    const q = document.getElementById('public-search-query');
    if (q) { q.value = ""; q.focus(); }
    const c = document.getElementById('ordinance-result-container');
    if (c) c.innerHTML = "";
}

const backToTopBtn = document.getElementById("btn-back-to-top");
if (backToTopBtn) {
    window.onscroll = () => { backToTopBtn.style.display = (document.documentElement.scrollTop > 500) ? "flex" : "none"; };
    backToTopBtn.onclick = () => window.scrollTo({ top: 0, behavior: "smooth" });
}

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => { navigator.serviceWorker.register('/sw.js').catch(err => console.log('SW failed', err)); });
}

function openEditEventModal(id, name, cat, date, time, loc, desc) {
    document.getElementById('edit-event-id').value = id;
    document.getElementById('edit-event-name').value = name;
    document.getElementById('edit-event-category').value = cat;
    document.getElementById('edit-event-date').value = date;
    document.getElementById('edit-event-time').value = time;
    document.getElementById('edit-event-location').value = loc;
    document.getElementById('edit-event-desc').value = desc;
    document.getElementById('edit-event-modal').showModal();
}

// Variable to track if we are deleting or editing
let isDeletionMode = false;

function requestDeleteRecord() {
    if (confirm("Are you sure you want to PERMANENTLY delete this record? This cannot be undone.")) {
        isDeletionMode = true;
        openVerifyModal(); // Use your existing passkey modal
    }
}

// We need to modify your existing submitEditWithPasskey or create a shared handler.
// Let's update the logic inside your existing submit function in main.js:

function submitEditWithPasskey() {
    const passInput = document.getElementById('popup-passkey-input');
    const hiddenPasskey = document.getElementById('hidden-passkey');
    const editForm = document.getElementById('edit-detail-form');
    
    if (!passInput || !passInput.value) { 
        alert("Please enter your passkey."); 
        return; 
    }

    hiddenPasskey.value = passInput.value;
    const formData = new FormData(editForm);

    // Determine which logic file to hit
    const targetFile = isDeletionMode ? 'delete_record_logic.php' : 'edit_record_logic.php';

    fetch(targetFile, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(async res => {
        const text = await res.text();
        try { return JSON.parse(text); } catch (e) { throw new Error("Server Error."); }
    })
    .then(data => {
        if (data.status === 'success') {
            alert(isDeletionMode ? "Record deleted successfully!" : "Record updated successfully!");
            location.reload();
        } else {
            alert("Error: " + data.message);
        }
    })
    .catch(err => alert("Connection Error."));
    
    // Reset mode
    isDeletionMode = false;
}

document.addEventListener('DOMContentLoaded', () => {
    const searchInp = document.getElementById('staffSearchInput');
    const resultsDiv = document.getElementById('staffSearchResults');

    searchInp.addEventListener('input', function() {
        const query = this.value.trim();
        if (query.length < 2) {
            resultsDiv.style.display = 'none';
            return;
        }

        fetch(`../tools/user_search_logic.php?query=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                resultsDiv.innerHTML = '';
                if (data.found) {
                    data.results.forEach(user => {
                        const pic = user.profile_pic ? `../uploads/profiles/${user.profile_pic}` : '../uploads/profiles/default-avatar.png';
                        const item = document.createElement('div');
                        item.style = "padding: 12px 20px; display: flex; align-items: center; gap: 12px; cursor: pointer; border-bottom: 1px solid #f1f5f9;";
                        item.innerHTML = `
                            <img src="${pic}" style="width: 35px; height: 35px; border-radius: 50%; object-fit: cover;">
                            <div>
                                <div style="font-weight: 700; font-size: 0.9rem;">${user.full_name}</div>
                                <div style="font-size: 0.7rem; color: #94a3b8; text-transform: uppercase;">${user.role}</div>
                            </div>
                        `;
                        item.onclick = () => showStaffProfile(user);
                        resultsDiv.appendChild(item);
                    });
                    resultsDiv.style.display = 'block';
                } else {
                    resultsDiv.style.display = 'none';
                }
            });
    });

    // Close results when clicking outside
    document.addEventListener('click', (e) => {
        if (!searchInp.contains(e.target) && !resultsDiv.contains(e.target)) {
            resultsDiv.style.display = 'none';
        }
    });
});

function showStaffProfile(user) {
    const modal = document.getElementById('user-profile-modal');
    const pic = user.profile_pic ? `../uploads/profiles/${user.profile_pic}` : '../uploads/profiles/default-avatar.png';
    
    // Fill Data
    document.getElementById('view-user-pic').src = pic;
    document.getElementById('view-user-name').innerText = user.full_name;
    document.getElementById('view-user-role').innerText = user.role.toUpperCase();
    document.getElementById('view-user-bio').innerText = user.bio || "This user hasn't provided a description yet.";
    
    // NEW: Handle Message Button Click
    const msgBtn = document.getElementById('view-user-msg-btn');
    msgBtn.onclick = () => {
        // Redirect to messenger with the 'with' parameter
        window.location.href = `../tools/messenger.php?with=${user.id}`;
    };
    
    document.getElementById('staffSearchResults').style.display = 'none';
    document.getElementById('staffSearchInput').value = '';
    modal.showModal();
}

// --- SECURITY GUARD: PREVENT DEVELOPER TOOLS / INSPECT ---

(function() {
    // Helper function to wipe the page DOM cleanly
    function triggerBlockPage() {
        document.body.innerHTML = `
            <div style="
                position: fixed; 
                inset: 0; 
                background: #0f172a; 
                color: white; 
                display: flex; 
                flex-direction: column; 
                align-items: center; 
                justify-content: center; 
                z-index: 99999999; 
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                text-align: center;
                padding: 20px;
            ">
                <div style="font-size: 5rem; margin-bottom: 20px;">🛡️</div>
                <h1 style="color: #ef4444; font-size: 2.2rem; font-weight: 800; margin-bottom: 15px;">Access Restricted</h1>
                <p style="color: #94a3b8; font-size: 1.15rem; max-width: 500px; line-height: 1.6; margin: 0 auto 30px auto;">
                    <strong>We Got you!!!</strong> Inspect is prohibited and not permissible on this portal.
                </p>
                <button onclick="window.location.reload()" style="
                    background: #ef4444; 
                    color: white; 
                    border: none; 
                    padding: 12px 28px; 
                    border-radius: 8px; 
                    font-weight: 700; 
                    cursor: pointer;
                    font-size: 1rem;
                    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
                    transition: 0.2s;
                ">Reload Portal</button>
            </div>
        `;
        throw new Error("Inspect prohibited."); 
    }

    // 1. Boundary dimension checks (docked inspectors)
    const checkDevTools = function() {
        const threshold = 160;
        const widthDiff = window.outerWidth - window.innerWidth;
        const heightDiff = window.outerHeight - window.innerHeight;

        if (widthDiff > threshold || heightDiff > threshold) {
            triggerBlockPage();
        }
    };

    // RUN INSTANTLY ON LOAD (Catches docked inspect opened before visiting)
    checkDevTools();

    // 2. Disable Right-Click Context Menu
    document.addEventListener('contextmenu', e => e.preventDefault());

    // 3. Disable Developer Tool Keyboard Shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.keyCode === 123) { // F12
            e.preventDefault();
            triggerBlockPage();
            return false;
        }
        if (e.ctrlKey && e.shiftKey && e.keyCode === 73) { // Ctrl+Shift+I
            e.preventDefault();
            triggerBlockPage();
            return false;
        }
        if (e.ctrlKey && e.shiftKey && e.keyCode === 74) { // Ctrl+Shift+J
            e.preventDefault();
            triggerBlockPage();
            return false;
        }
        if (e.ctrlKey && e.keyCode === 85) { // Ctrl+U (View Source)
            e.preventDefault();
            triggerBlockPage();
            return false;
        }
        if (e.metaKey && e.altKey && e.keyCode === 73) { // Cmd+Opt+I (Mac Safari)
            e.preventDefault();
            triggerBlockPage();
            return false;
        }
    });

    // 4. Run active high-speed loops (Checks docked inspectors every 250ms)
    setInterval(checkDevTools, 2500);
    window.addEventListener('resize', checkDevTools);

    // 5. High-Speed Debugger Timing Check (Catches detached/separate window inspect on load)
    setInterval(function() {
        const startTime = performance.now();

        const endTime = performance.now();
        
        // If pause lag is detected, developer tools are active
        if (endTime - startTime > 100) {
            triggerBlockPage();
        }
    }, 2500);
})();