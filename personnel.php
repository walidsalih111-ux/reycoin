<?php
// AT THE VERY TOP: Validate the Session
session_start();
if (!isset($_SESSION['personnel_logged_in']) || $_SESSION['personnel_logged_in'] !== true) {
    // If not logged in, redirect them immediately to the index page.
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>RECYCOIN - Personnel Admin</title>
<!-- <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"> -->
<!-- <script src="https://cdn.tailwindcss.com"></script> -->

<script src="functions/font.js"></script>
<script src="functions/tailwind.js"></script>

<link rel="icon" type="image/png" sizes="32x32" href="assets/logo.png">
<link rel="shortcut icon" href="assets/logo.png">
<link rel="apple-touch-icon" href="assets/logo.png">
<style>
    :root { --g900: #042c1e; --g700: #1a7f4c; --a500: #BA7517; }
    body { font-family: 'DM Sans', sans-serif; background-color: #042c1e; color: #fff; margin: 0; }
    .app-container { max-width: 480px; margin: 0 auto; background: var(--g900); min-height: 100vh; display: flex; flex-direction: column; }
    .card { background: white; color: #1a1a1a; border-radius: 16px; padding: 24px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); margin-bottom: 16px; }
    .toast { position: fixed; top: 20px; left: 50%; transform: translateX(-50%); background: #111; color: white; padding: 12px 24px; border-radius: 30px; font-size: 14px; opacity: 0; transition: 0.3s; z-index: 100; white-space: nowrap;}
    .toast.show { opacity: 1; }
    
    /* Scrollbar styling for history modal */
    ::-webkit-scrollbar { width: 6px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>
</head>
<body>
<div class="app-container p-6 relative">
    
    <div id="toast" class="toast">Message</div>

    <div class="flex items-center justify-between mb-8 pt-4">
        <div>
            <h1 class="text-2xl font-bold tracking-wide">RECYCOIN Admin</h1>
            <p class="text-sm text-green-200">Barangay Hall Personnel</p>
        </div>
        <div class="flex gap-2">
            <!-- Edit Credentials Icon -->
            <button onclick="openEditCredentialsModal()" class="bg-white/10 p-2 rounded-lg text-white hover:bg-white/20 transition tooltip flex items-center justify-center" title="Edit Credentials">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
            </button>
            
            <!-- Global History Icon -->
            <button onclick="openAdminHistoryModal()" class="bg-white/10 p-2 rounded-lg text-white hover:bg-white/20 transition tooltip flex items-center justify-center" title="Global History">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            </button>
            
            <!-- Logout Icon -->
            <button onclick="logoutPersonnel()" class="bg-white/10 p-2 rounded-lg text-white hover:bg-white/20 transition tooltip flex items-center gap-2 text-sm" title="Logout & Switch to Resident">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
            </button>
        </div>
    </div>

    <!-- Step 1: ID Entry -->
    <div id="step-scan" class="card">
        <h2 class="font-bold text-lg mb-2 text-gray-900">Enter Resident ID</h2>
        <p class="text-sm text-gray-500 mb-6">Type the Resident's unique ID code to process their redemption request.</p>

        <input type="text" id="qr-input" placeholder="e.g. RC-2026-XXXXXX" class="w-full border border-gray-200 p-4 rounded-xl font-mono mb-4 text-center tracking-widest outline-none focus:border-[var(--g700)] focus:ring-2 focus:ring-green-100 transition-all text-gray-800 uppercase text-lg">
        
        <button onclick="verifyQR()" class="w-full bg-[var(--g700)] text-white font-bold py-4 rounded-xl hover:bg-green-800 transition shadow-md">
            Verify Resident
        </button>
    </div>

    <!-- Step 2: Immediate 100 Pt Reward (Hidden initially) -->
    <div id="step-reward" class="card hidden">
        <div class="flex justify-between items-start mb-6">
            <div>
                <p class="text-[13px] text-gray-400 mb-1">Resident Name</p>
                <h3 class="font-bold text-xl text-gray-900 tracking-tight" id="res-name">---</h3>
            </div>
            <div class="text-right">
                <p class="text-[13px] text-gray-400 mb-1">Available</p>
                <h3 class="font-bold text-xl text-[var(--g700)] tracking-tight" id="res-pts">0.00 <span class="text-sm font-semibold">pts</span></h3>
            </div>
        </div>

        <div class="bg-green-50/70 rounded-xl p-5 text-center mb-6">
            <h2 class="font-bold text-[#144d32] text-lg mb-1">Standard Redemption</h2>
            <p class="text-[15px] text-[var(--g700)] mb-3">Cost: <strong class="font-bold">100 points</strong></p>
            <p class="text-[13px] text-gray-500 leading-relaxed px-2">Points will be deducted immediately upon confirmation and recorded in real-time history.</p>
        </div>

        <button id="redeem-btn" disabled onclick="processRedemption()" class="w-full bg-gray-100 text-gray-400 font-bold py-4 rounded-xl transition-all relative flex items-center justify-center cursor-not-allowed mb-4">
            Confirm 100 Pts Deduction
        </button>
        
        <div class="text-center">
            <!-- CANCEL BUTTON: Properly hooked to the resetScan() function -->
            <button onclick="resetScan()" class="text-[15px] text-gray-400 underline font-medium hover:text-gray-600 transition-colors py-2 px-4 rounded-lg active:bg-gray-50">
                Cancel
            </button>
        </div>
    </div>

    <!-- EDIT CREDENTIALS MODAL -->
    <div id="edit-credentials-modal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-sm rounded-2xl p-6 relative shadow-2xl text-gray-800">
            <h2 class="text-xl font-bold text-[var(--g900)] mb-2">Edit Credentials</h2>
            <p class="text-sm text-gray-500 mb-4">Update your admin username and password.</p>
            
            <input type="text" id="new-admin-user" class="w-full border p-4 rounded-xl mb-3 outline-none focus:border-[var(--g700)]" placeholder="New Username">
            
            <div class="relative mb-5">
                <input type="password" id="new-admin-pass" class="w-full border p-4 rounded-xl outline-none focus:border-[var(--g700)] pr-12" placeholder="New Password">
                <!-- Eye Icon Toggle -->
                <button type="button" onclick="togglePasswordVisibility('new-admin-pass', 'eye-icon')" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-[var(--g700)] focus:outline-none transition-colors">
                    <svg id="eye-icon" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                </button>
            </div>

            <div class="flex gap-3">
                <button onclick="closeModal('edit-credentials-modal')" class="flex-1 py-4 bg-gray-100 text-gray-700 rounded-xl font-bold hover:bg-gray-200 transition">Cancel</button>
                <button onclick="saveAdminCredentials()" class="flex-[2] py-4 bg-[var(--g700)] text-white rounded-xl font-bold hover:bg-green-800 transition">Save Changes</button>
            </div>
        </div>
    </div>  

    <!-- GLOBAL HISTORY LOG MODAL -->
    <div id="admin-history-modal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden z-50 flex flex-col items-center justify-end p-0">
        <div class="bg-white w-full max-w-md h-[80vh] rounded-t-3xl p-6 flex flex-col relative shadow-[0_-10px_40px_rgba(0,0,0,0.1)] text-gray-800">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-[var(--g900)]">Transaction History</h2>
                <button onclick="closeAdminHistoryModal()" class="bg-gray-100 p-2 rounded-full text-gray-500 hover:bg-gray-200 transition-colors">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
            
            <div class="flex items-center gap-2 mb-4 bg-green-50 text-[var(--g700)] p-2 rounded-xl text-xs font-bold justify-center border border-green-100">
                <span class="relative flex h-2 w-2">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[var(--g700)] opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2 w-2 bg-[var(--g700)]"></span>
                </span>
                LIVE UPDATES ACTIVE
            </div>

            <div class="overflow-y-auto flex-1 pb-4" id="admin-history-list">
                <!-- History Items injected via JS -->
                <p class="text-center text-sm text-gray-400 mt-10">Loading history...</p>
            </div>
        </div>
    </div>

</div>

<script>
    let currentResident = null;
    let historyPollingInterval = null;

    function showToast(msg) {
        const t = document.getElementById('toast');
        t.innerText = msg;
        t.classList.add('show');
        setTimeout(() => t.classList.remove('show'), 3000);
    }

    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
    }

    // --- LOGOUT LOGIC --- //
    async function logoutPersonnel() {
        try {
            await fetch('api.php?action=logout');
            window.location.href = 'index.php';
        } catch(e) {
            window.location.href = 'index.php';
        }
    }

    // --- CREDENTIALS MANAGEMENT --- //
    function openEditCredentialsModal() {
        document.getElementById('new-admin-user').value = '';
        document.getElementById('new-admin-pass').value = '';
        
        const passInput = document.getElementById('new-admin-pass');
        passInput.type = 'password';
        document.getElementById('eye-icon').innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
        
        document.getElementById('edit-credentials-modal').classList.remove('hidden');
    }

    function togglePasswordVisibility(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.innerHTML = `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>`;
        } else {
            input.type = 'password';
            icon.innerHTML = `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>`;
        }
    }

    async function saveAdminCredentials() {
        const u = document.getElementById('new-admin-user').value.trim();
        const p = document.getElementById('new-admin-pass').value;
        if(!u || !p) return showToast("Enter both new username and password.");

        try {
            let res = await fetch('api.php?action=update_admin', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username: u, password: p })
            });
            let json = await res.json();
            if(json.status === 'success') {
                showToast("Credentials updated successfully!");
                closeModal('edit-credentials-modal');
            } else { showToast("Error: " + json.message); }
        } catch(e) { showToast("Error updating credentials."); }
    }

    // --- REAL-TIME HISTORY LOGIC --- //
    function openAdminHistoryModal() {
        document.getElementById('admin-history-modal').classList.remove('hidden');
        fetchGlobalHistory();
        historyPollingInterval = setInterval(fetchGlobalHistory, 3000);
    }

    function closeAdminHistoryModal() {
        document.getElementById('admin-history-modal').classList.add('hidden');
        clearInterval(historyPollingInterval);
    }

    async function fetchGlobalHistory() {
        try {
            let res = await fetch('api.php?action=get_all_history');
            let json = await res.json();
            if(json.status === 'success') { renderAdminHistory(json.data); }
        } catch(e) { console.error("Admin history fetch error", e); }
    }

    function renderAdminHistory(logs) {
        const container = document.getElementById('admin-history-list');
        if(!logs || logs.length === 0) {
            container.innerHTML = `<p class="text-center text-sm text-gray-400 mt-10">No transactions recorded yet.</p>`;
            return;
        }
        
        container.innerHTML = logs.map(log => {
            const isDeposit = log.type === 'deposit';
            const sign = isDeposit ? '+' : '-';
            const color = isDeposit ? 'text-[var(--g700)]' : 'text-red-500';
            const bgIcon = isDeposit ? 'bg-green-50 text-[var(--g700)]' : 'bg-red-50 text-red-500';
            const icon = isDeposit 
                ? `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>`
                : `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 12v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>`;
            
            const dateStr = new Date(log.created_at).toLocaleString([], {month:'short', day:'numeric', hour:'2-digit', minute:'2-digit'});

            return `
                <div class="flex items-center justify-between py-4 border-b border-gray-100 hover:bg-gray-50/50 transition">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 ${bgIcon}">
                            ${icon}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-gray-900 text-[15px] truncate" title="${log.full_name}">${log.full_name}</p>
                            <p class="text-[12px] text-gray-400 leading-tight mt-0.5">
                                ${dateStr}<br/>
                                <span class="font-medium ${isDeposit ? 'text-[var(--g700)]' : 'text-orange-500'}">${isDeposit ? 'Deposit' : 'Redemption'}</span> • ${log.detail}
                            </p>
                        </div>
                    </div>
                    <div class="font-bold text-right ${color} flex-shrink-0 pl-2 text-[15px]">
                        ${sign}${parseFloat(log.points).toFixed(2)}
                    </div>
                </div>
            `;
        }).join('');
    }

    // --- VERIFICATION & REDEMPTION LOGIC --- //
    async function verifyQR() {
        const qr = document.getElementById('qr-input').value.trim();
        if(!qr) return showToast('Please enter a Resident ID code');

        try {
            // Keep the 'qr' parameter mapping as the API logic expects it, 
            // even though we are sending it manually via input text now.
            let res = await fetch(`api.php?action=get_user&qr=${qr}`);
            let json = await res.json();
            
            if (json.status === 'success') {
                currentResident = json.data;
                document.getElementById('res-name').innerText = currentResident.full_name;
                document.getElementById('res-pts').innerHTML = `${parseFloat(currentResident.total_points).toFixed(2)} <span class="text-sm font-semibold">pts</span>`;
                
                checkRedemptionEligibility(parseFloat(currentResident.total_points));

                document.getElementById('step-scan').classList.add('hidden');
                document.getElementById('step-reward').classList.remove('hidden');
            } else { showToast(json.message); }
        } catch(e) { showToast('Database connection failed'); }
    }

    function checkRedemptionEligibility(points) {
        const btn = document.getElementById('redeem-btn');
        if (points >= 100) {
            btn.disabled = false;
            // PERFECTLY MATCHING THE UI IN THE IMAGE
            btn.className = "w-full bg-[var(--g700)] text-white font-bold py-4 rounded-xl transition-all hover:bg-[#14663c] shadow-md relative flex items-center justify-center active:scale-[0.99] mb-4";
            btn.innerHTML = `
                <div class="absolute left-5">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                </div>
                <span class="text-[15px] tracking-wide">Redeem 100 Points</span>
            `;
        } else {
            btn.disabled = true;
            btn.className = "w-full bg-gray-100 text-gray-400 font-bold py-4 rounded-xl transition-all cursor-not-allowed mb-4";
            btn.innerHTML = `Insufficient Points (Need 100)`;
        }
    }

    async function processRedemption() {
        if (!currentResident) return;

        try {
            let res = await fetch('api.php?action=redeem', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ qr: currentResident.qr_code })
            });
            let json = await res.json();
            
            if (json.status === 'success') {
                showToast('✅ Success! 100 points deducted.');
                setTimeout(resetScan, 2000);
            } else {
                showToast('Error: ' + json.message);
            }
        } catch(e) { showToast('Network Error'); }
    }

    function resetScan() {
        // Reset the form input and memory
        document.getElementById('qr-input').value = '';
        currentResident = null;
        
        // Reset button visual state completely
        const btn = document.getElementById('redeem-btn');
        btn.disabled = true;
        btn.className = "w-full bg-gray-100 text-gray-400 font-bold py-4 rounded-xl transition-all cursor-not-allowed mb-4";
        btn.innerHTML = "Confirm 100 Pts Deduction";
        
        // Switch views back to Step 1
        document.getElementById('step-reward').classList.add('hidden');
        document.getElementById('step-scan').classList.remove('hidden');
    }
</script>
</body>
</html>