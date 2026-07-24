// --- 1. GLOBAL VARIABLES ---
let currentRecordIndex = -1;
let allPageRecords = [];
let currentRecordData = null;
let publicSearchResults = [];
let isDeletionMode = false;

// --- 2. PAGE LOAD HANDLER ---
window.addEventListener('load', () => {
    const loader = document.getElementById('page-loader');
    if (loader) {
        loader.classList.add('loader-hidden');
        setTimeout(() => { loader.style.display = 'none'; }, 500); 
    }
});

// --- 3. PORTAL VIEW TOGGLE LOGIC ---
function openTracker(type, title) {
    const activeCatInput = document.getElementById('active-category');
    if (activeCatInput) activeCatInput.value = type;

    const dynamicTitle = document.getElementById('dynamic-tracker-title');
    if (dynamicTitle) dynamicTitle.innerText = title;

    const selView = document.getElementById('selection-view');
    const searchView = document.getElementById('search-view');
    if(selView) selView.style.display = 'none';
    if(searchView) searchView.style.display = 'block';
    
    clearTrackSearch();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function closeTracker() {
    const selView = document.getElementById('selection-view');
    const searchView = document.getElementById('search-view');
    if(selView) selView.style.display = 'block';
    if(searchView) searchView.style.display = 'none';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// --- 4. REGISTRY MANAGEMENT FUNCTIONS (Globally Accessible) ---

function openDetailByIndex(index) {
    const stores = document.querySelectorAll('.json-store');
    allPageRecords = Array.from(stores).map(s => JSON.parse(s.innerText));
    currentRecordIndex = index;
    const modal = document.getElementById('modal-view-details');
    if (modal) { 
        updateModalContent(allPageRecords[index]); 
        modal.showModal(); 
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

function renderDetails(isEditMode) {
    const content = document.getElementById('details-content');
    const data = currentRecordData;
    if (!content || !data) return;

    let html = '';
    let fields = {};

    if (data.Type_of_law !== undefined) {
        fields = {
            "Type_of_law": "Type of Law", "Number": "Number", "Series": "Series",
            "Title": "Title", "Author": "Author", "Implementing_department": "Implementing Dept",
            "Hyperlink": "Direct Link", "Other_Attachment": "Attachment",
            "Hyperlink1": "Link 1", "Hyperlink2": "Link 2", "Hyperlink3": "Link 3"
        };
    } else {
        fields = {
            "Date_Received": "Date Received", "Time_Received": "Time Received", "Ref_No": "Ref No.", 
            "Type": "Type", "Proponent": "Proponent", "Subject": "Subject", 
            "Action_Taken": "Action History", "Subject_Description": "Description", 
            "Remarks": "Remarks", "Folder": "Folder"
        };
    }

    for (const [key, label] of Object.entries(fields)) {
        let value = data[key] || "";
        const isLong = label.includes("Description") || label.includes("Remarks") || key === "Subject" || key === "Title" || key === "Action_Taken";
        const containerClass = isLong ? "detail-item full-width" : "detail-item";

        if (isEditMode) {
            if (key === "Action_Taken") {
                html += `<div class="${containerClass}"><div class="new-update-area"><span class="new-update-label">➕ Add Progress Update</span><textarea name="new_action_note"></textarea><div class="history-preview-box">${parseTimeline(value)}</div><input type="hidden" name="Action_Taken" value="${value}"></div></div>`;
            } else {
                let inputField = isLong ? `<textarea name="${key}" rows="2">${value}</textarea>` : `<input type="text" name="${key}" value="${value}">`;
                if(key.toLowerCase().includes('date')) inputField = `<input type="date" name="${key}" value="${value}">`;
                html += `<div class="${containerClass}"><label class="detail-label">${label}</label>${inputField}</div>`;
            }
        } else {
            const displayVal = (value === "" || value === "0000-00-00") ? '<span class="none-text">-none-</span>' : value;
            let finalOutput = displayVal;
            if (key.includes("Hyperlink") && value !== "" && value !== "-none-") {
                finalOutput = `<a href="${value}" target="_blank" style="color:#2563eb; text-decoration:underline; font-weight:700;">Click to Open Link</a>`;
            }
            html += `<div class="${containerClass}"><label class="detail-label">${label}</label><div class="detail-value">${key === "Action_Taken" ? parseTimeline(value) : finalOutput}</div></div>`;
        }
    }
    content.innerHTML = html;
}

function parseTimeline(dataString) {
    if (!dataString || dataString === "-none-" || dataString === "") return "<p>No history recorded yet.</p>";
    const entries = dataString.split('|||').filter(e => e.trim() !== "");
    let html = '<div class="timeline-log">';
    entries.forEach(entry => {
        const parts = entry.split('@@@');
        html += `<div class="timeline-entry"><p class="timeline-text">${parts[1] || entry}</p></div>`;
    });
    return html + '</div>';
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

function openVerifyModal() { 
    const vModal = document.getElementById('modal-verify-passkey');
    if (vModal) vModal.showModal(); 
}

function requestDeleteRecord() {
    if (confirm("Are you sure you want to PERMANENTLY delete this record? This cannot be undone.")) {
        isDeletionMode = true;
        openVerifyModal(); 
    }
}

function submitEditWithPasskey() {
    const passInput = document.getElementById('popup-passkey-input');
    const hiddenPasskey = document.getElementById('hidden-passkey');
    const editForm = document.getElementById('edit-detail-form');
    
    if (!passInput || !passInput.value) { alert("Please enter your passkey."); return; }

    hiddenPasskey.value = passInput.value;
    const formData = new FormData(editForm);
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
            location.reload();
        } else {
            alert("Error: " + data.message);
        }
    })
    .catch(err => alert(err.message));
}

function closeDetailModal() {
    const modal = document.getElementById('modal-view-details');
    if(modal) modal.close();
    const editBtn = document.getElementById('btn-enable-edit');
    const saveBtn = document.getElementById('btn-trigger-verify');
    if(editBtn) editBtn.style.setProperty('display', 'inline-flex', 'important');
    if(saveBtn) saveBtn.style.setProperty('display', 'none', 'important');
}

function exportSelectedRecords() {
    const checked = document.querySelectorAll('.record-checkbox:checked');
    const ids = Array.from(checked).map(cb => cb.value);
    if (ids.length > 0) {
        window.location.href = `export_selected_csv.php?ids=${ids.join(',')}`;
    } else {
        alert("Please select records to export.");
    }
}

function updateExportButtonState() {
    const exportBtn = document.getElementById('btn-export-selected');
    const countDisplay = document.getElementById('selected-count');
    const checkedCount = document.querySelectorAll('.record-checkbox:checked').length;
    if(countDisplay) countDisplay.innerText = checkedCount;
    if (exportBtn) {
        exportBtn.style.setProperty('opacity', checkedCount > 0 ? '1' : '0.5', 'important');
        exportBtn.style.setProperty('pointer-events', checkedCount > 0 ? 'auto' : 'none', 'important');
    }
}

// --- 5. DOM LISTENERS ---
document.addEventListener('DOMContentLoaded', () => {
    // Ajax Login
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault(); 
            const formData = new FormData(this);
            fetch('login_process.php', { method: 'POST', body: formData })
            .then(res => res.json()).then(data => {
                if (data.status === 'success') { window.location.href = data.redirect; } 
                else { document.getElementById('error-msg').innerText = data.message; }
            });
        });
    }

    // Checkbox Listeners
    const selectAll = document.getElementById('select-all-rows');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            document.querySelectorAll('.record-checkbox').forEach(cb => cb.checked = this.checked);
            updateExportButtonState();
        });
    }

    document.addEventListener('change', (e) => {
        if (e.target.classList.contains('record-checkbox')) updateExportButtonState();
    });
});

// --- 6. PUBLIC SEARCH & UTILITIES ---
function trackOrdinance() {
    const query = document.getElementById('public-search-query').value;
    const activeCat = document.getElementById('active-category').value; 
    const resultContainer = document.getElementById('ordinance-result-container');
    if (!query) return;

    fetch(`tools/public_track.php?query=${encodeURIComponent(query)}&cat=${activeCat}`)
    .then(res => res.json()).then(data => {
        if(!resultContainer) return;
        resultContainer.innerHTML = ""; 
        publicSearchResults = data.results || [];
        if (data.found) {
            data.results.forEach((item, index) => {
                resultContainer.innerHTML += `<div class="result-box" onclick="openPublicDetail(${index})"><h3>${item.Subject}</h3></div>`;
            });
        }
    });
}

function clearTrackSearch() {
    const q = document.getElementById('public-search-query');
    if (q) q.value = "";
    const c = document.getElementById('ordinance-result-container');
    if (c) c.innerHTML = "";
}