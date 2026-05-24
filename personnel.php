<?php
// AT THE VERY TOP: Validate the Session
session_start();
if (!isset($_SESSION['personnel_logged_in']) || $_SESSION['personnel_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>RECYCOIN - Personnel</title>
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
    
    ::-webkit-scrollbar { width: 6px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>
</head>
<body>
<div class="app-container p-4 md:p-6 relative">
    
    <div id="toast" class="toast">Message</div>

    <!-- FIXED: Header Overlap and Spacing -->
    <div class="flex items-center justify-between mb-6 pt-4">
        <div class="flex-1 min-w-0 pr-2">
            <div class="flex items-center gap-2">
                <img src="assets/logo.png" alt="Logo" class="w-7 h-7 rounded-full bg-white p-0.5 shadow-sm object-contain flex-shrink-0">
                <h1 class="text-xl font-bold tracking-wide truncate">RECYCOIN</h1>
            </div>
            <p class="text-[13px] text-green-200 mt-1 truncate">Barangay Personnel</p>
        </div>
        <div class="flex gap-1.5 items-center flex-shrink-0">
            
            <!-- Bin Status Badge -->
            <div class="bg-black/20 px-2.5 py-1.5 rounded-lg border border-white/10 text-center flex-shrink-0" title="RVM Machine Fill Level">
                <p class="text-[9px] text-green-200 uppercase tracking-wider mb-0.5 opacity-80 leading-none">Bin Level</p>
                <p class="text-[13px] font-bold text-white transition-colors duration-300 leading-none mt-1" id="admin-bin-text">--%</p>
            </div>

            <button onclick="openEditCredentialsModal()" class="bg-white/10 p-2 rounded-lg text-white hover:bg-white/20 transition tooltip flex items-center justify-center flex-shrink-0" title="Edit Credentials">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
            </button>
            
            <button onclick="openAdminHistoryModal()" class="bg-white/10 p-2 rounded-lg text-white hover:bg-white/20 transition tooltip flex items-center justify-center flex-shrink-0" title="Global History">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            </button>
            
            <button onclick="logoutPersonnel()" class="bg-white/10 p-2 rounded-lg text-white hover:bg-white/20 transition tooltip flex items-center justify-center flex-shrink-0" title="Logout">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
            </button>
        </div>
    </div>

    <!-- Warning Banner for Bin Overflow -->
    <div id="admin-bin-warning" class="hidden mb-6 bg-red-500/20 border border-red-500/50 text-red-200 p-4 rounded-xl text-center font-bold text-[13px] animate-pulse shadow-md relative overflow-hidden">
        <div class="absolute inset-0 bg-red-500/10 blur-xl pointer-events-none"></div>
        <span class="relative z-10 flex items-center justify-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            RVM BIN IS FULL! MACHINE IS DISABLED. PLEASE EMPTY.
        </span>
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

    <!-- Step 2: Immediate 100 Pt Reward -->
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
            <button onclick="resetScan()" class="text-[15px] text-gray-400 underline font-medium hover:text-gray-600 transition-colors py-2 px-4 rounded-lg active:bg-gray-50">
                Cancel
            </button>
        </div>
    </div>

    <!-- FIXED: EDIT CREDENTIALS MODAL -->
    <div id="edit-credentials-modal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden z-50">
        <div class="w-full h-full flex items-center justify-center p-4">
            <div class="bg-white w-full max-w-[400px] rounded-2xl p-6 relative shadow-2xl text-gray-800">
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
    </div>  

    <!-- FIXED: GLOBAL HISTORY LOG MODAL -->
    <div id="admin-history-modal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden z-50">
        <div class="w-full h-full flex flex-col items-center justify-end p-0">
            <div class="bg-white w-full max-w-[480px] h-[85vh] rounded-t-3xl p-6 flex flex-col relative shadow-[0_-10px_40px_rgba(0,0,0,0.1)] text-gray-800 m-0 border-none">
                <div class="flex justify-between items-center mb-4 shrink-0">
                    <h2 class="text-xl font-bold text-[var(--g900)]">Transaction History</h2>
                    <button onclick="closeAdminHistoryModal()" class="bg-gray-100 p-2 rounded-full text-gray-500 hover:bg-gray-200 transition-colors">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>
                
                <div class="flex items-center gap-2 mb-4 bg-green-50 text-[var(--g700)] p-2 rounded-xl text-xs font-bold justify-center border border-green-100 shrink-0">
                    <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[var(--g700)] opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-[var(--g700)]"></span>
                    </span>
                    LIVE UPDATES ACTIVE
                </div>

                <div class="overflow-y-auto flex-1 pb-4" id="admin-history-list">
                    <p class="text-center text-sm text-gray-400 mt-10">Loading history...</p>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    let currentResident = null;
    let historyPollingInterval = null;

    // --- NEW: BIN LEVEL POLLING --- //
    async function fetchBinStatus() {
        try {
            let res = await fetch('api.php?action=bin_status');
            let json = await res.json();
            if (json.status === 'success') {
                let fill = parseFloat(json.fill_percent);
                let textEl = document.getElementById('admin-bin-text');
                let warningEl = document.getElementById('admin-bin-warning');
                
                textEl.innerText = fill + '%';
                
                // Color Code Based on Fill Severity
                if (fill >= 90) {
                    textEl.classList.add('text-red-400');
                    textEl.classList.remove('text-yellow-400', 'text-white');
                } else if (fill >= 60) {
                    textEl.classList.add('text-yellow-400');
                    textEl.classList.remove('text-red-400', 'text-white');
                } else {
                    textEl.classList.add('text-white');
                    textEl.classList.remove('text-red-400', 'text-yellow-400');
                }

                if (json.is_full) {
                    warningEl.classList.remove('hidden');
                } else {
                    warningEl.classList.add('hidden');
                }
            }
        } catch(e) {}
    }
    
    // Initialize Bin Polling
    fetchBinStatus();
    setInterval(fetchBinStatus, 3000);

    // --- MISC CONTROLS --- //
    function showToast(msg) {
        const t = document.getElementById('toast');
        t.innerText = msg;
        t.classList.add('show');
        setTimeout(() => t.classList.remove('show'), 3000);
    }

    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
    }

    async function logoutPersonnel() {
        try {
            await fetch('api.php?action=logout');
            window.location.href = 'index.php';
        } catch(e) {
            window.location.href = 'index.php';
        }
    }

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

    async function verifyQR() {
        const qr = document.getElementById('qr-input').value.trim();
        if(!qr) return showToast('Please enter a Resident ID code');

        try {
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
        document.getElementById('qr-input').value = '';
        currentResident = null;
        
        const btn = document.getElementById('redeem-btn');
        btn.disabled = true;
        btn.className = "w-full bg-gray-100 text-gray-400 font-bold py-4 rounded-xl transition-all cursor-not-allowed mb-4";
        btn.innerHTML = "Confirm 100 Pts Deduction";
        
        document.getElementById('step-reward').classList.add('hidden');
        document.getElementById('step-scan').classList.remove('hidden');
    }
</script>
</body>
</html>