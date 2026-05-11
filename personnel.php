<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>RECYCOIN - Personnel Admin</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/html5-qrcode"></script>

<link rel="icon" type="image/png" sizes="32x32" href="assets/logo.png">
<link rel="shortcut icon" href="assets/logo.png">
<link rel="apple-touch-icon" href="assets/logo.png">
<style>
    :root { --g900: #042c1e; --g700: #0F6E56; --a500: #BA7517; }
    body { font-family: 'DM Sans', sans-serif; background-color: #042c1e; color: #fff; margin: 0; }
    .app-container { max-width: 480px; margin: 0 auto; background: var(--g900); min-height: 100vh; display: flex; flex-direction: column; }
    .card { background: white; color: #1a1a1a; border-radius: 16px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); margin-bottom: 16px; }
    .toast { position: fixed; top: 20px; left: 50%; transform: translateX(-50%); background: #111; color: white; padding: 12px 24px; border-radius: 30px; font-size: 14px; opacity: 0; transition: 0.3s; z-index: 100; white-space: nowrap;}
    .toast.show { opacity: 1; }
    #reader video { border-radius: 12px; object-fit: cover; }
    
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
            
            <!-- Switch to Resident Icon -->
            <a href="index.php" class="bg-white/10 p-2 rounded-lg text-white hover:bg-white/20 transition tooltip flex items-center gap-2 text-sm" title="Switch to Resident">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
            </a>
        </div>
    </div>

    <!-- Step 1: Scan -->
    <div id="step-scan" class="card">
        <h2 class="font-bold text-lg mb-2">Scan Resident ID</h2>
        <p class="text-sm text-gray-500 mb-4">Use the camera to scan the Resident's QR code.</p>
        
        <!-- Scanner Window -->
        <div id="reader" width="100%" class="mb-4 hidden rounded-xl overflow-hidden border-2 border-green-200 bg-gray-50"></div>

        <button id="start-scan-btn" onclick="startScanner()" class="w-full bg-green-50 text-green-800 font-bold py-3 rounded-lg mb-2 border border-green-200 hover:bg-green-100 transition flex justify-center items-center gap-2">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
            Open Camera Scanner
        </button>

        <button id="stop-scan-btn" onclick="stopScanner()" class="w-full bg-red-50 text-red-600 font-bold py-3 rounded-lg mb-2 hidden transition">
            Close Camera
        </button>

        <div class="flex items-center my-4">
            <div class="flex-grow border-t border-gray-200"></div>
            <span class="mx-4 text-gray-400 text-xs">OR ENTER MANUALLY</span>
            <div class="flex-grow border-t border-gray-200"></div>
        </div>

        <input type="text" id="qr-input" placeholder="e.g. RC-2026-00142" class="w-full border p-3 rounded-lg font-mono mb-4 text-center tracking-widest outline-none focus:border-green-600">
        <button onclick="verifyQR()" class="w-full bg-green-700 text-white font-bold py-3 rounded-lg hover:bg-green-800 transition">
            Verify Resident
        </button>
    </div>

    <!-- Step 2: Immediate 100 Pt Reward (Hidden initially) -->
    <div id="step-reward" class="card hidden">
        <div class="flex justify-between items-center mb-4 pb-4 border-b border-gray-100">
            <div>
                <p class="text-xs text-gray-400">Resident Name</p>
                <h3 class="font-bold text-lg" id="res-name">---</h3>
            </div>
            <div class="text-right">
                <p class="text-xs text-gray-400">Available</p>
                <h3 class="font-bold text-xl text-green-700" id="res-pts">0 <span class="text-sm">pts</span></h3>
            </div>
        </div>

        <div class="bg-green-50 border border-green-100 rounded-xl p-4 text-center mb-4">
            <h2 class="font-bold text-green-900 text-lg mb-1">Standard Redemption</h2>
            <p class="text-sm text-green-700">Cost: <strong>100 points</strong></p>
            <p class="text-xs text-gray-500 mt-2">Points will be deducted immediately upon confirmation and recorded in real-time history.</p>
        </div>

        <button id="redeem-btn" disabled onclick="processRedemption()" class="w-full bg-gray-300 text-gray-500 font-bold py-3 rounded-lg transition-all flex items-center justify-center gap-2">
            Confirm 100 Pts Deduction
        </button>
        <button onclick="resetScan()" class="w-full text-center text-sm text-gray-400 mt-4 underline">Cancel</button>
    </div>

    <!-- EDIT CREDENTIALS MODAL -->
    <div id="edit-credentials-modal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-sm rounded-2xl p-6 relative shadow-2xl text-gray-800">
            <h2 class="text-xl font-bold text-[var(--g900)] mb-2">Edit Credentials</h2>
            <p class="text-sm text-gray-500 mb-4">Update your admin username and password.</p>
            
            <input type="text" id="new-admin-user" class="w-full border p-3 rounded-lg mb-3 outline-none focus:border-green-600" placeholder="New Username">
            
            <div class="relative mb-5">
                <input type="password" id="new-admin-pass" class="w-full border p-3 rounded-lg outline-none focus:border-green-600 pr-10" placeholder="New Password">
                <button type="button" onclick="togglePasswordVisibility('new-admin-pass', 'eye-icon')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-[var(--g700)] focus:outline-none transition-colors">
                    <svg id="eye-icon" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                </button>
            </div>

            <div class="flex gap-2">
                <button onclick="closeModal('edit-credentials-modal')" class="flex-1 py-3 bg-gray-100 text-gray-700 rounded-xl font-bold">Cancel</button>
                <button onclick="saveAdminCredentials()" class="flex-[2] py-3 bg-[var(--g700)] text-white rounded-xl font-bold">Save Changes</button>
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
            
            <div class="flex items-center gap-2 mb-4 bg-green-50 text-green-700 p-2 rounded-lg text-xs font-bold justify-center border border-green-100">
                <span class="relative flex h-2 w-2">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
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
    let html5QrcodeScanner = null;
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

    // --- CREDENTIALS MANAGEMENT --- //
    function openEditCredentialsModal() {
        document.getElementById('new-admin-user').value = '';
        document.getElementById('new-admin-pass').value = '';
        
        // Reset password toggle to hidden state
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
            // Eye-off SVG
            icon.innerHTML = `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>`;
        } else {
            input.type = 'password';
            // Eye-on SVG
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
                body: JSON.stringify({ username: u, password: p, id: 1 })
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
        
        // Start AJAX polling every 3 seconds
        historyPollingInterval = setInterval(fetchGlobalHistory, 3000);
    }

    function closeAdminHistoryModal() {
        document.getElementById('admin-history-modal').classList.add('hidden');
        // Stop polling to save resources
        clearInterval(historyPollingInterval);
    }

    async function fetchGlobalHistory() {
        try {
            let res = await fetch('api.php?action=get_all_history');
            let json = await res.json();
            if(json.status === 'success') {
                renderAdminHistory(json.data);
            }
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
            const color = isDeposit ? 'text-green-600' : 'text-red-500';
            const bgIcon = isDeposit ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-500';
            const icon = isDeposit 
                ? `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>`
                : `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 12v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>`;
            
            const dateStr = new Date(log.created_at).toLocaleString([], {month:'short', day:'numeric', hour:'2-digit', minute:'2-digit'});

            return `
                <div class="flex items-center justify-between p-4 border-b border-gray-100 hover:bg-gray-50 transition">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 ${bgIcon}">
                            ${icon}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-gray-800 text-sm truncate" title="${log.full_name}">${log.full_name}</p>
                            <p class="text-[11px] text-gray-400 leading-tight mt-0.5">
                                ${dateStr}<br/>
                                <span class="font-medium ${isDeposit ? 'text-green-600' : 'text-orange-500'}">${isDeposit ? 'Deposit' : 'Redemption'}</span> • ${log.detail}
                            </p>
                        </div>
                    </div>
                    <div class="font-black text-right ${color} flex-shrink-0 pl-2">
                        ${sign}${parseFloat(log.points).toFixed(2)}
                    </div>
                </div>
            `;
        }).join('');
    }

    // --- CAMERA SCANNER LOGIC --- //
    function startScanner() {
        document.getElementById('reader').classList.remove('hidden');
        document.getElementById('start-scan-btn').classList.add('hidden');
        document.getElementById('stop-scan-btn').classList.remove('hidden');

        html5QrcodeScanner = new Html5Qrcode("reader");
        html5QrcodeScanner.start(
            { facingMode: "environment" }, 
            { fps: 10, qrbox: { width: 250, height: 250 } },
            (decodedText, decodedResult) => {
                document.getElementById('qr-input').value = decodedText;
                showToast("QR Code Detected!");
                stopScanner();
                verifyQR(); 
            },
            (errorMessage) => {}
        ).catch((err) => {
            showToast("Camera access denied or unavailable.");
            stopScanner();
        });
    }

    function stopScanner() {
        if (html5QrcodeScanner) {
            html5QrcodeScanner.stop().then(() => {
                document.getElementById('reader').classList.add('hidden');
                document.getElementById('start-scan-btn').classList.remove('hidden');
                document.getElementById('stop-scan-btn').classList.add('hidden');
                html5QrcodeScanner.clear();
            }).catch((err) => { console.error("Failed to stop scanner", err); });
        }
    }

    // --- VERIFICATION & REDEMPTION LOGIC --- //
    async function verifyQR() {
        const qr = document.getElementById('qr-input').value.trim();
        if(!qr) return showToast('Please enter or scan a QR code');

        try {
            let res = await fetch(`api.php?action=get_user&qr=${qr}`);
            let json = await res.json();
            
            if (json.status === 'success') {
                currentResident = json.data;
                document.getElementById('res-name').innerText = currentResident.full_name;
                document.getElementById('res-pts').innerHTML = `${parseFloat(currentResident.total_points).toFixed(2)} <span class="text-sm">pts</span>`;
                
                checkRedemptionEligibility(parseFloat(currentResident.total_points));

                document.getElementById('step-scan').classList.add('hidden');
                document.getElementById('step-reward').classList.remove('hidden');
                showToast('Resident Verified');
            } else { showToast(json.message); }
        } catch(e) { showToast('Database connection failed'); }
    }

    function checkRedemptionEligibility(points) {
        const btn = document.getElementById('redeem-btn');
        if (points >= 100) {
            btn.disabled = false;
            btn.className = "w-full bg-green-700 text-white font-bold py-3 rounded-lg transition-all hover:bg-green-800 shadow-md";
            btn.innerHTML = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg> Redeem 100 Points`;
        } else {
            btn.disabled = true;
            btn.className = "w-full bg-red-100 text-red-500 font-bold py-3 rounded-lg transition-all";
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
        stopScanner(); 
        document.getElementById('qr-input').value = '';
        currentResident = null;
        
        document.getElementById('redeem-btn').disabled = true;
        document.getElementById('redeem-btn').className = "w-full bg-gray-300 text-gray-500 font-bold py-3 rounded-lg transition-all";
        document.getElementById('redeem-btn').innerHTML = "Confirm 100 Pts Deduction";
        
        document.getElementById('step-reward').classList.add('hidden');
        document.getElementById('step-scan').classList.remove('hidden');
    }
</script>
</body>
</html>