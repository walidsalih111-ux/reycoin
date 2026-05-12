<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>RECYCOIN - Resident</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script> -->
<script src=functions\qrcode.min.js></script>

<!-- Favicon / Logo for browser tab -->
<link rel="icon" type="image/png" sizes="32x32" href="assets/logo.png">
<link rel="shortcut icon" href="assets/logo.png">
<link rel="apple-touch-icon" href="assets/logo.png">

<style>
    :root {
        --green-900: #042c1e; --green-800: #0a5c46; --green-700: #0F6E56; --green-400: #5DCAA5;
        --bg-color: #f4f3ef;
    }
    body { font-family: 'DM Sans', sans-serif; background-color: var(--bg-color); color: #1a1a1a; margin: 0; padding: 0; }
    .app-container { max-width: 480px; margin: 0 auto; background: var(--bg-color); min-height: 100vh; position: relative; box-shadow: 0 0 20px rgba(0,0,0,0.05); overflow-x: hidden; }
    .header-curved { background: var(--green-900); border-radius: 0 0 24px 24px; padding: 24px 20px 40px; color: white; }
    .card { background: white; border-radius: 16px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); margin-bottom: 16px; }
    
    #toast {
        position: fixed; bottom: 80px; left: 50%; transform: translateX(-50%) translateY(100px);
        background: #1a1a1a; color: white; padding: 12px 24px; border-radius: 30px;
        font-weight: 500; font-size: 14px; opacity: 0; transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        z-index: 100; pointer-events: none; white-space: nowrap; box-shadow: 0 10px 20px rgba(0,0,0,0.2);
    }
    #toast.show { transform: translateX(-50%) translateY(0); opacity: 1; }
    
    .modal-overlay { background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); }
</style>
</head>
<body>

<div class="app-container">
    
    <!-- HEADER -->
    <div class="header-curved">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">RECYCOIN</h1>
                <div class="flex items-center gap-2 mt-1">
                    <p class="text-green-400 text-sm opacity-90 font-medium" id="user-name">Connecting...</p>
                    <button onclick="openEditNameModal()" class="text-green-200 hover:text-white transition p-1">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                    </button>
                </div>
            </div>
            
            <div class="flex gap-2">
                <!-- Log History Icon -->
                <div onclick="openHistoryModal()" class="bg-white/10 p-2 rounded-full backdrop-blur-sm cursor-pointer active:scale-95 transition-transform">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                </div>
                <!-- Personnel Login Icon -->
                <div onclick="openLoginModal()" class="bg-white/10 p-2 rounded-full backdrop-blur-sm cursor-pointer active:scale-95 transition-transform text-orange-300">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                </div>
            </div>
        </div>

        <div class="text-center mt-2">
            <p class="text-sm text-green-200 font-medium mb-1">Total Balance</p>
            <h2 class="text-5xl font-black tracking-tighter" id="total-points">0.00</h2>
            <p class="text-xs text-green-400 mt-1 uppercase tracking-widest">Points</p>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="px-5 -mt-6 relative z-10">
        
        <!-- QR CODE CARD -->
        <div class="card text-center relative overflow-hidden border border-gray-100">
            <div class="absolute top-0 right-0 w-24 h-24 bg-green-50 rounded-bl-full -z-10"></div>
            <h3 class="font-bold text-gray-800 mb-1">My Resident ID</h3>
            <p class="text-xs text-gray-500 mb-4">Scan this to barangay personnel to redeem your 100 points and claim your incentives at the Barangay Tetuan.</p>
            
            <div class="bg-white p-3 rounded-xl inline-block border-2 border-gray-100 shadow-sm mb-2">
                <div id="qrcode"></div>
            </div>
            <p class="font-mono text-sm font-medium text-gray-600 tracking-wider mt-1" id="qr-text">Loading...</p>
        </div>

        <!-- ACTION BUTTON -->
        <button onclick="openDepositModal()" class="w-full bg-[var(--green-700)] text-white font-bold py-4 rounded-xl shadow-lg shadow-green-900/20 active:scale-[0.98] transition-transform flex items-center justify-center gap-2 mb-6">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            Insert Bottle
        </button>

    </div>

    <div id="toast"></div>

    <!-- Modals -->
    <!-- EDIT NAME MODAL -->
    <div id="edit-name-modal" class="fixed inset-0 modal-overlay hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-sm rounded-2xl p-6 relative shadow-2xl">
            <h2 class="text-xl font-bold text-[var(--green-900)] mb-2">Edit Resident Name</h2>
            <p class="text-sm text-gray-500 mb-4">Update your display name here.</p>
            <input type="text" id="new-name-input" class="w-full border p-3 rounded-lg mb-4 outline-none focus:border-[var(--green-700)]" placeholder="Enter Full Name">
            <div class="flex gap-2">
                <button onclick="closeModal('edit-name-modal')" class="flex-1 py-3 bg-gray-100 text-gray-700 rounded-xl font-bold">Cancel</button>
                <button onclick="saveName()" class="flex-[2] py-3 bg-[var(--green-700)] text-white rounded-xl font-bold">Save Name</button>
            </div>
        </div>
    </div>

    <!-- PERSONNEL LOGIN MODAL -->
    <div id="login-modal" class="fixed inset-0 modal-overlay hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-sm rounded-2xl p-6 relative shadow-2xl">
            <div class="flex justify-center mb-2">
                <div class="bg-orange-100 p-3 rounded-full text-orange-600">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                </div>
            </div>
            <h2 class="text-xl font-bold text-center text-gray-800 mb-4">Personnel Login</h2>
            <input type="text" id="login-user" class="w-full border p-3 rounded-lg mb-3 outline-none focus:border-orange-500" placeholder="Username">
            <input type="password" id="login-pass" class="w-full border p-3 rounded-lg mb-5 outline-none focus:border-orange-500" placeholder="Password">
            <div class="flex gap-2">
                <button onclick="closeModal('login-modal')" class="flex-1 py-3 bg-gray-100 text-gray-700 rounded-xl font-bold">Cancel</button>
                <button onclick="processLogin()" class="flex-[2] py-3 bg-orange-600 text-white rounded-xl font-bold">Login</button>
            </div>
        </div>
    </div>

    <!-- HISTORY LOG MODAL -->
    <div id="history-modal" class="fixed inset-0 modal-overlay hidden z-50 flex flex-col items-center justify-end p-0">
        <div class="bg-white w-full max-w-md h-[70vh] rounded-t-3xl p-6 flex flex-col relative shadow-[0_-10px_40px_rgba(0,0,0,0.1)]">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-[var(--green-900)]">Transaction History</h2>
                <button onclick="closeModal('history-modal')" class="bg-gray-100 p-2 rounded-full text-gray-500">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
            <div class="overflow-y-auto flex-1 pb-4" id="history-list">
                <!-- History Items injected via JS -->
                <p class="text-center text-sm text-gray-400 mt-10">Loading history...</p>
            </div>
        </div>
    </div>

    <!-- RVM DEPOSIT MODAL -->
    <div id="deposit-modal" class="fixed inset-0 modal-overlay hidden z-50 flex flex-col items-center justify-center p-4">
        <div class="bg-white w-full max-w-md rounded-2xl p-6 relative shadow-2xl">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-[var(--green-900)]">RVM Disposal</h2>
                <div class="bg-red-100 text-red-600 font-mono px-3 py-1 rounded-full text-sm font-bold flex items-center gap-1">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <span id="timer-display">01:00</span>
                </div>
            </div>

            <div class="bg-black w-full h-56 rounded-xl mb-3 overflow-hidden relative shadow-inner">
                <video id="webcam-feed" autoplay playsinline class="w-full h-full object-cover"></video>
                <div class="absolute inset-0 border-4 border-dashed border-white/20 pointer-events-none rounded-xl m-2"></div>
                <div id="scan-line" class="absolute top-0 left-0 w-full h-1 bg-green-400 shadow-[0_0_10px_#5DCAA5] pointer-events-none opacity-50" style="animation: scan 2s linear infinite;"></div>
            </div>

            <p class="text-xs text-gray-500 text-center mb-4 bg-gray-50 p-2 rounded-lg border border-gray-100">
                <strong class="text-gray-700">Rules:</strong> 1 min to insert bottle. Timer resets on valid PET detection. <br>
                <span class="text-[var(--green-700)] font-medium">Small = 1pt | Medium = 2pts | Large = 3pts</span>
            </p>

            <div class="bg-[var(--green-50)] p-4 rounded-xl mb-5 flex justify-between items-center border border-[var(--green-200)]">
                <div>
                    <span class="block text-sm text-[var(--green-900)] opacity-80">Accumulated Points</span>
                    <span class="text-xs text-[var(--green-700)]" id="session-bottle-count">0 bottles scanned</span>
                </div>
                <span class="text-3xl font-black text-[var(--green-700)]" id="session-total-pts">0</span>
            </div>

            <div class="flex gap-3">
                <button onclick="closeDepositModal()" class="flex-1 py-3 bg-gray-100 text-gray-700 rounded-xl font-bold">Cancel</button>
                <button onclick="confirmBalance()" class="flex-[2] py-3 bg-[var(--green-700)] text-white rounded-xl font-bold shadow-md">Confirm Balance</button>
            </div>

            <div class="mt-6 pt-4 border-t border-gray-200">
                <p class="text-[10px] text-gray-400 text-center mb-2 uppercase tracking-wider">Dev Tools: Simulate Hardware Detection</p>
                <div class="flex justify-center gap-2">
                    <button onclick="triggerDetection(true, 'Small', 1)" class="text-xs bg-green-100 text-green-700 px-3 py-2 rounded-lg font-medium">+ Small (1)</button>
                    <button onclick="triggerDetection(true, 'Large', 3)" class="text-xs bg-green-100 text-green-700 px-3 py-2 rounded-lg font-medium">+ Large (3)</button>
                    <button onclick="triggerDetection(false, 'None', 0)" class="text-xs bg-red-100 text-red-700 px-3 py-2 rounded-lg font-medium">Non-PET</button>
                </div>
            </div>
        </div>
    </div>

</div>

<style>
    @keyframes scan { 0% { top: 0; } 50% { top: 100%; } 100% { top: 0; } }
</style>

<script>
    // --- STATE ---
    let myAssignedQR = null; 
    let currentName = '';
    let timerInterval;
    let timeLeft = 60;
    let sessionPoints = 0;
    let sessionBottles = 0;
    let stream = null;

    function showToast(msg) {
        const t = document.getElementById('toast');
        t.innerText = msg;
        t.classList.add('show');
        setTimeout(() => t.classList.remove('show'), 3000);
    }

    function closeModal(id) { document.getElementById(id).classList.add('hidden'); }
    function openModal(id) { document.getElementById(id).classList.remove('hidden'); }

    // --- INIT ---
    async function initApp() {
        try {
            let res = await fetch(`api.php?action=init_session`);
            let json = await res.json();
            if(json.status === 'success') {
                myAssignedQR = json.data.qr_code;
                currentName = json.data.full_name;
                document.getElementById('user-name').innerText = currentName;
                document.getElementById('total-points').innerText = parseFloat(json.data.total_points).toFixed(2);
                generateQRCode(myAssignedQR);
                fetchHistory(); // Fetch initial history
            }
        } catch(e) { document.getElementById('user-name').innerText = "Connection Error"; }
    }

    function generateQRCode(text) {
        document.getElementById("qrcode").innerHTML = "";
        new QRCode(document.getElementById("qrcode"), { text: text, width: 140, height: 140, colorDark : "#1a1a1a", colorLight : "#ffffff" });
        document.getElementById('qr-text').innerText = text;
    }

    // --- POLLING & SYNC ---
    async function refreshData() {
        if(!myAssignedQR) return;
        try {
            let res = await fetch(`api.php?action=get_user&qr=${myAssignedQR}`);
            let json = await res.json();
            if(json.status === 'success') {
                document.getElementById('total-points').innerText = parseFloat(json.data.total_points).toFixed(2);
                // If history modal is open, refresh it in real-time
                if(!document.getElementById('history-modal').classList.contains('hidden')) fetchHistory();
            }
        } catch(e) {}
    }

    // --- NAME EDITING ---
    function openEditNameModal() {
        document.getElementById('new-name-input').value = currentName;
        openModal('edit-name-modal');
    }

    async function saveName() {
        const newName = document.getElementById('new-name-input').value.trim();
        if(!newName || !myAssignedQR) return;
        
        try {
            let res = await fetch('api.php?action=update_name', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ qr: myAssignedQR, name: newName })
            });
            let json = await res.json();
            if(json.status === 'success') {
                currentName = newName;
                document.getElementById('user-name').innerText = currentName;
                showToast("Name updated successfully!");
                closeModal('edit-name-modal');
            }
        } catch(e) { showToast("Error updating name."); }
    }

    // --- LOGIN ---
    function openLoginModal() {
        document.getElementById('login-user').value = '';
        document.getElementById('login-pass').value = '';
        openModal('login-modal');
    }

    async function processLogin() {
        const u = document.getElementById('login-user').value.trim();
        const p = document.getElementById('login-pass').value;
        if(!u || !p) return showToast("Enter username and password");
        
        try {
            let res = await fetch('api.php?action=login', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username: u, password: p })
            });
            let json = await res.json();
            if(json.status === 'success') {
                window.location.href = 'personnel.php';
            } else { showToast(json.message); }
        } catch(e) { showToast("Login Error."); }
    }

    // --- HISTORY LOG ---
    function openHistoryModal() {
        fetchHistory();
        openModal('history-modal');
    }

    async function fetchHistory() {
        if(!myAssignedQR) return;
        try {
            let res = await fetch(`api.php?action=get_history&qr=${myAssignedQR}`);
            let json = await res.json();
            if(json.status === 'success') {
                renderHistory(json.data);
            }
        } catch(e) { console.error("History fetch error", e); }
    }

    function renderHistory(logs) {
        const container = document.getElementById('history-list');
        if(!logs || logs.length === 0) {
            container.innerHTML = `<p class="text-center text-sm text-gray-400 mt-10">No transactions found.</p>`;
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
                        <div class="w-10 h-10 rounded-full flex items-center justify-center ${bgIcon}">
                            ${icon}
                        </div>
                        <div>
                            <p class="font-bold text-gray-800 text-sm">${isDeposit ? 'RVM Deposit' : 'Reward Redemption'}</p>
                            <p class="text-xs text-gray-400">${dateStr} • ${log.detail}</p>
                        </div>
                    </div>
                    <div class="font-black ${color}">
                        ${sign}${parseFloat(log.points).toFixed(2)}
                    </div>
                </div>
            `;
        }).join('');
    }

    // --- RVM MODAL LOGIC (Untouched core logic) ---
    async function openDepositModal() {
        if(!myAssignedQR) { showToast("Establishing secure connection first..."); return; }
        openModal('deposit-modal');
        sessionPoints = 0; sessionBottles = 0; updateSessionUI(); startTimer(); startWebcam();
    }

    function closeDepositModal() {
        closeModal('deposit-modal');
        clearInterval(timerInterval);
        stopWebcam();
    }

    function updateSessionUI() {
        document.getElementById('session-total-pts').innerText = sessionPoints;
        document.getElementById('session-bottle-count').innerText = `${sessionBottles} bottles scanned`;
    }

    function startTimer() {
        clearInterval(timerInterval);
        timeLeft = 60; updateTimerDisplay();
        timerInterval = setInterval(() => {
            timeLeft--; updateTimerDisplay();
            if (timeLeft <= 0) {
                clearInterval(timerInterval); showToast('Time is up! Session closed.');
                if(sessionPoints > 0) confirmBalance(); else closeDepositModal();
            }
        }, 1000);
    }

    function updateTimerDisplay() {
        let m = Math.floor(timeLeft / 60); let s = timeLeft % 60;
        document.getElementById('timer-display').innerText = `0${m}:${s < 10 ? '0' : ''}${s}`;
    }

    async function startWebcam() {
        try {
            stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
            document.getElementById('webcam-feed').srcObject = stream;
        } catch (err) { console.error("Webcam error:", err); }
    }

    function stopWebcam() { if (stream) { stream.getTracks().forEach(track => track.stop()); stream = null; } }

    function triggerDetection(isPet, sizeLabel, pointsValue) {
        if (isPet) {
            sessionPoints += pointsValue; sessionBottles++;
            updateSessionUI(); startTimer(); 
            showToast(`✅ Valid PET (${sizeLabel})! +${pointsValue} pts.`);
        } else { showToast('❌ Invalid bottle detected.'); }
    }

    async function confirmBalance() {
        if (sessionPoints <= 0) { showToast('No points collected in this session.'); closeDepositModal(); return; }
        try {
            let res = await fetch('api.php?action=deposit', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ qr: myAssignedQR, size: `Bulk Deposit (${sessionBottles} bottles)`, points: sessionPoints })
            });
            let json = await res.json();
            if (json.status === 'success') {
                showToast(`Success! ${sessionPoints} points added to balance.`);
                refreshData(); closeDepositModal();
            } else { showToast('Error saving data.'); }
        } catch(e) { showToast('Network error during checkout.'); }
    }

    window.onload = () => {
        initApp();
        setInterval(refreshData, 5000); 
    };
</script>

</body>
</html>