<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>RECYCOIN - Resident</title>
<link href="functions/font.css" rel="stylesheet">
<script src="functions/tailwind.js"></script>
<link rel="icon" type="image/png" sizes="32x32" href="assets/logo.png">
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
    <div class="header-curved">
        <div class="flex justify-between items-start mb-6">
            <div>
                <div class="flex items-center gap-2">
                    <img src="assets/logo.png" alt="Logo" class="w-8 h-8 rounded-full bg-white p-0.5 shadow-sm object-contain">
                    <h1 class="text-2xl font-bold tracking-tight">RECYCOIN</h1>
                </div>
                <div class="flex items-center gap-2 mt-1">
                    <p class="text-green-400 text-sm opacity-90 font-medium truncate max-w-[150px]" id="user-name">Connecting...</p>
                    <button onclick="openEditNameModal(false)" class="text-green-400 hover:text-white transition tooltip" title="Edit Profile Name">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 20h9"></path>
                            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="text-right">
                <div class="flex justify-end mb-1">
                    <button onclick="openLoginModal()" class="text-xs bg-white/10 hover:bg-white/20 text-white py-1.5 px-3 rounded-full transition flex items-center gap-1.5 font-medium shadow-sm">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                        Personnel
                    </button>
                </div>
                <p class="text-sm text-green-200 font-medium mb-1 mt-1">Balance</p>
                <h2 class="text-4xl font-black tracking-tighter transition-all duration-300" id="total-points">0.00</h2>
            </div>
        </div>
    </div>

    <div class="px-5 -mt-6 relative z-10">
        <div class="card text-center relative overflow-hidden border border-gray-100">
            <h3 class="font-bold text-gray-800 mb-1">My Resident ID</h3>
            <div class="bg-green-50/50 p-4 rounded-xl border-2 border-[var(--green-700)] shadow-inner mb-3">
                <p class="font-mono text-xl font-bold text-[var(--green-900)] tracking-widest" id="resident-id-text">Loading...</p>
            </div>
        </div>
        
        <!-- Modified to include an ID for dynamic disabling based on bin fill level -->
        <button id="insert-bottle-btn" onclick="openDepositModal()" class="w-full bg-[var(--green-700)] text-white font-bold py-4 rounded-xl shadow-lg active:scale-[0.98] transition-all duration-300">
            Insert Bottle
        </button>

        <div class="mt-4 mb-4 bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
            <div class="overflow-hidden rounded-lg border border-gray-100">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600 text-xs uppercase font-bold">
                        <tr>
                            <th class="px-4 py-2 text-center">Bottle Size</th>
                            <th class="px-4 py-2 text-center">Points Earned</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-700 font-medium">
                        <tr>
                            <td class="px-4 py-2 text-center">250ml</td>
                            <td class="px-4 py-2 text-center text-[var(--green-700)]">+0.5 pt</td>
                        </tr>
                        <tr class="bg-gray-50/50">
                            <td class="px-4 py-2 text-center">500ml</td>
                            <td class="px-4 py-2 text-center text-[var(--green-700)]">+1.0 pt</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2 text-center">1L</td>
                            <td class="px-4 py-2 text-center text-[var(--green-700)]">+2.0 pts</td>
                        </tr>
                        <tr class="bg-gray-50/50">
                            <td class="px-4 py-2 text-center">1.5L</td>
                            <td class="px-4 py-2 text-center text-[var(--green-700)]">+3.0 pts</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="toast"></div>

    <!-- PERSONNEL LOGIN MODAL -->
    <div id="login-modal" class="fixed inset-0 modal-overlay hidden z-50 flex-col items-center justify-center p-4">
        <div class="bg-white w-full max-w-sm rounded-2xl p-6 relative shadow-2xl">
            <h2 class="text-xl font-bold text-[var(--green-900)] mb-2">Personnel Login</h2>
            <p class="text-sm text-gray-500 mb-5">Enter credentials to access the admin panel.</p>

            <input type="text" id="admin-user-input" class="w-full border border-gray-300 p-4 rounded-xl outline-none focus:border-[var(--green-700)] focus:ring-2 focus:ring-green-100 transition-all text-gray-800 mb-3" placeholder="Username">
            
            <input type="password" id="admin-pass-input" class="w-full border border-gray-300 p-4 rounded-xl outline-none focus:border-[var(--green-700)] focus:ring-2 focus:ring-green-100 transition-all text-gray-800 mb-1" placeholder="Password">
            
            <p id="login-error-msg" class="text-red-500 text-xs mt-1 mb-3 hidden"></p>

            <div class="flex gap-3 mt-4">
                <button onclick="closeLoginModal()" class="flex-1 py-3 bg-gray-100 text-gray-700 rounded-xl font-bold hover:bg-gray-200 transition">Cancel</button>
                <button onclick="submitAdminLogin()" class="flex-[2] py-3 bg-[var(--green-700)] text-white rounded-xl font-bold shadow-md hover:bg-green-800 transition">Login</button>
            </div>
        </div>
    </div>

    <!-- PROFILE NAME MODAL -->
    <div id="name-modal" class="fixed inset-0 modal-overlay hidden z-50 flex-col items-center justify-center p-4">
        <div class="bg-white w-full max-w-sm rounded-2xl p-6 relative shadow-2xl">
            <h2 class="text-xl font-bold text-[var(--green-900)] mb-2" id="name-modal-title">Complete Profile</h2>
            <p class="text-sm text-gray-500 mb-4" id="name-modal-desc">Please enter your full name to continue.</p>

            <input type="text" id="resident-name-input" class="w-full border border-gray-300 p-4 rounded-xl outline-none focus:border-[var(--green-700)] focus:ring-2 focus:ring-green-100 transition-all text-gray-800" placeholder="e.g. Juan Dela Cruz">
            <p id="name-error" class="text-red-500 text-xs mt-2 hidden"></p>

            <div class="flex gap-3 mt-5">
                <button id="cancel-name-btn" onclick="closeEditNameModal()" class="flex-1 py-3 bg-gray-100 text-gray-700 rounded-xl font-bold hover:bg-gray-200 transition">Cancel</button>
                <button onclick="saveResidentName()" class="flex-[2] py-3 bg-[var(--green-700)] text-white rounded-xl font-bold shadow-md hover:bg-green-800 transition">Save Name</button>
            </div>
        </div>
    </div>

    <!-- RVM DEPOSIT MODAL -->
    <div id="deposit-modal" class="fixed inset-0 modal-overlay hidden z-50 flex-col items-center justify-center p-4">
        <div class="bg-white w-full max-w-md rounded-2xl p-6 relative shadow-2xl">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-[var(--green-900)]">RVM Disposal</h2>
                <div class="bg-red-100 text-red-600 font-mono px-3 py-1 rounded-full text-sm font-bold flex items-center gap-1">
                    <span id="timer-display">01:00</span>
                </div>
            </div>

            <!-- HARDWARE SCANNER ACTIVE INDICATOR -->
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-6 text-center mb-5 flex flex-col items-center justify-center h-48">
                <svg class="animate-pulse w-14 h-14 text-[var(--green-700)] mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                </svg>
                <p class="text-[var(--green-900)] font-bold text-lg mb-1">Scanner Active</p>
                <p class="text-sm text-gray-500 max-w-[200px]">Please look at the machine's display and insert your bottles.</p>
            </div>

            <div class="bg-[var(--green-50)] p-4 rounded-xl mb-5 flex justify-between items-center border border-[var(--green-200)]">
                <div>
                    <span class="block text-sm text-[var(--green-900)] opacity-80">Accumulated Points</span>
                    <span class="text-xs text-[var(--green-700)]" id="session-bottle-count">0 bottles scanned</span>
                </div>
                <span class="text-3xl font-black text-[var(--green-700)]" id="session-total-pts">0.00</span>
            </div>

            <button onclick="confirmBalance()" class="w-full py-3 bg-[var(--green-700)] text-white rounded-xl font-bold shadow-md hover:bg-green-800 transition">Confirm Balance</button>
        </div>
    </div>
</div>

<script>
    let myAssignedQR = null; 
    let timerInterval;
    let yoloPollInterval;
    let syncDataInterval; // Variable for user data sync
    let timeLeft = 60;
    let sessionPoints = 0;
    let sessionBottles = 0;
    let isForceName = false;
    let binIsFull = false; // NEW: Global bin status state

    function showToast(msg) {
        const t = document.getElementById('toast');
        t.innerText = msg;
        t.classList.add('show');
        setTimeout(() => t.classList.remove('show'), 3000);
    }

    async function initApp() {
        try {
            let res = await fetch(`api.php?action=init_session`);
            let json = await res.json();
            if(json.status === 'success') {
                myAssignedQR = json.data.qr_code;
                document.getElementById('user-name').innerText = json.data.full_name;
                document.getElementById('total-points').innerText = parseFloat(json.data.total_points).toFixed(2);
                document.getElementById('resident-id-text').innerText = myAssignedQR;

                if (json.data.is_new || json.data.full_name.startsWith('Resident (')) {
                    openEditNameModal(true);
                }

                // Start synchronizing user data and bin status periodically
                if (!syncDataInterval) {
                    syncDataInterval = setInterval(() => {
                        syncUserData();
                        pollBinStatus(); // Continuously fetch bin level
                    }, 3000);
                    pollBinStatus(); // Initial fetch
                }
            }
        } catch(e) {}
    }

    // --- NEW: POLL RVM BIN LEVEL ---
    async function pollBinStatus() {
        try {
            let res = await fetch('api.php?action=bin_status');
            let json = await res.json();
            if (json.status === 'success') {
                binIsFull = json.is_full;
                
                // Update Insert Button visual state
                const insertBtn = document.getElementById('insert-bottle-btn');
                if (insertBtn) {
                    if (binIsFull) {
                        insertBtn.classList.add('opacity-50', 'bg-red-600', 'cursor-not-allowed');
                        insertBtn.classList.remove('bg-[var(--green-700)]');
                        insertBtn.innerText = "Bin Full - Cannot Insert";
                    } else {
                        insertBtn.classList.remove('opacity-50', 'bg-red-600', 'cursor-not-allowed');
                        insertBtn.classList.add('bg-[var(--green-700)]');
                        insertBtn.innerText = "Insert Bottle";
                    }
                }
            }
        } catch(e) {}
    }

    // --- REAL-TIME DATA SYNCHRONIZATION ---
    async function syncUserData() {
        if (!myAssignedQR) return;
        try {
            let res = await fetch(`api.php?action=get_user&qr=${myAssignedQR}`);
            let json = await res.json();
            if (json.status === 'success') {
                let currentPtsText = document.getElementById('total-points').innerText;
                let newPtsVal = parseFloat(json.data.total_points);
                let newPtsText = newPtsVal.toFixed(2);
                
                // If points changed remotely (e.g. Personnel Redemption)
                if (currentPtsText !== newPtsText) {
                    let ptsEl = document.getElementById('total-points');
                    let diff = newPtsVal - parseFloat(currentPtsText);
                    
                    ptsEl.innerText = newPtsText;
                    
                    // Flash effect to draw attention
                    ptsEl.classList.add('text-green-300', 'scale-110');
                    setTimeout(() => ptsEl.classList.remove('text-green-300', 'scale-110'), 400);

                    // If points decreased, it means a redemption occurred
                    if (diff < 0) {
                        showToast(`Redemption complete! ${Math.abs(diff).toFixed(2)} pts spent.`);
                    }
                }

                // Keep name in sync across devices (if not currently editing)
                if (!isForceName) {
                    let currentName = document.getElementById('user-name').innerText;
                    if (currentName !== json.data.full_name) {
                        document.getElementById('user-name').innerText = json.data.full_name;
                    }
                }
            }
        } catch (e) {
            console.error("Sync error:", e);
        }
    }

    // --- PERSONNEL LOGIN LOGIC ---
    function openLoginModal() {
        document.getElementById('login-modal').classList.remove('hidden');
        document.getElementById('login-error-msg').classList.add('hidden');
        document.getElementById('admin-user-input').value = '';
        document.getElementById('admin-pass-input').value = '';
    }

    function closeLoginModal() {
        document.getElementById('login-modal').classList.add('hidden');
    }

    async function submitAdminLogin() {
        const user = document.getElementById('admin-user-input').value.trim();
        const pass = document.getElementById('admin-pass-input').value;
        const errorMsg = document.getElementById('login-error-msg');

        if (!user || !pass) {
            errorMsg.innerText = "Please enter both username and password.";
            errorMsg.classList.remove('hidden');
            return;
        }

        try {
            let res = await fetch('api.php?action=personnel_login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username: user, password: pass })
            });
            let json = await res.json();

            if (json.status === 'success') {
                window.location.href = 'personnel.php';
            } else {
                errorMsg.innerText = json.message || "Invalid credentials.";
                errorMsg.classList.remove('hidden');
            }
        } catch (e) {
            errorMsg.innerText = "Network error. Please try again.";
            errorMsg.classList.remove('hidden');
        }
    }

    // --- PROFILE NAME LOGIC ---
    function openEditNameModal(force = false) {
        isForceName = force;
        document.getElementById('name-modal').classList.remove('hidden');
        document.getElementById('name-error').classList.add('hidden');
        
        const inputField = document.getElementById('resident-name-input');
        
        if (force) {
            document.getElementById('cancel-name-btn').classList.add('hidden');
            document.getElementById('name-modal-title').innerText = "Welcome to RECYCOIN!";
            document.getElementById('name-modal-desc').innerText = "Please enter your full name to start earning points.";
            inputField.value = ""; 
        } else {
            document.getElementById('cancel-name-btn').classList.remove('hidden');
            document.getElementById('name-modal-title').innerText = "Edit Profile";
            document.getElementById('name-modal-desc').innerText = "Update your displayed name.";
            inputField.value = document.getElementById('user-name').innerText;
        }
    }

    function closeEditNameModal() {
        if (!isForceName) {
            document.getElementById('name-modal').classList.add('hidden');
        }
    }

    async function saveResidentName() {
        if (!myAssignedQR) return showToast("Profile error. Please refresh the page.");
        
        const inputField = document.getElementById('resident-name-input');
        const errorMsg = document.getElementById('name-error');
        const name = inputField.value.trim();

        const nameRegex = /^[A-Za-z0-9 .\-]+$/;
        if (name.length < 3) {
            errorMsg.innerText = "Name must be at least 3 characters long.";
            errorMsg.classList.remove('hidden');
            return;
        }
        if (!nameRegex.test(name)) {
            errorMsg.innerText = "Only letters, numbers, spaces, periods, and hyphens are allowed.";
            errorMsg.classList.remove('hidden');
            return;
        }
        
        errorMsg.classList.add('hidden');

        try {
            let res = await fetch('api.php?action=update_name', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ qr: myAssignedQR, new_name: name })
            });
            let json = await res.json();
            
            if (json.status === 'success') {
                showToast('✅ Profile name updated successfully!');
                document.getElementById('user-name').innerText = json.new_name;
                isForceName = false; 
                closeEditNameModal();
            } else {
                errorMsg.innerText = json.message || "Failed to update name.";
                errorMsg.classList.remove('hidden');
            }
        } catch (e) {
            errorMsg.innerText = "Network error. Please try again.";
            errorMsg.classList.remove('hidden');
        }
    }

    // --- YOLO & RVM MODAL LOGIC ---
    async function openDepositModal() {
        if(!myAssignedQR) return;

        // Block opening of modal if machine is entirely full
        if (binIsFull) {
            showToast("Bin is currently full! Please wait for personnel to empty it.");
            return;
        }
        
        let res = await fetch(`api.php?action=request_lock&qr=${myAssignedQR}`);
        let json = await res.json();
        if(json.status !== 'success') { showToast(json.message); return; }

        document.getElementById('deposit-modal').classList.remove('hidden');
        sessionPoints = 0; sessionBottles = 0; updateSessionUI(); startTimer(); 

        // Start YOLO (Camera feed will pop up natively on the Pi)
        await fetch('api.php?action=yolo_start');
        
        yoloPollInterval = setInterval(pollYoloDetection, 1000);
    }

    async function pollYoloDetection() {
        // Double check while inside loop if it suddenly became full
        if (binIsFull) {
            closeDepositModal();
            showToast("Machine reached full capacity. Session auto-closed.");
            return;
        }

        try {
            let res = await fetch('api.php?action=yolo_poll');
            let json = await res.json();
            if(json.status === 'success') {
                triggerDetection(true, json.size, json.points);
            }
        } catch(e) {} 
    }

    function closeDepositModal() {
        document.getElementById('deposit-modal').classList.add('hidden');
        clearInterval(timerInterval);
        clearInterval(yoloPollInterval);
        
        // Stop YOLO (Will close the native window on the Pi)
        fetch('api.php?action=yolo_stop');

        if(myAssignedQR) { fetch(`api.php?action=release_lock&qr=${myAssignedQR}`); }
    }

    function updateSessionUI() {
        document.getElementById('session-total-pts').innerText = parseFloat(sessionPoints).toFixed(2);
        document.getElementById('session-bottle-count').innerText = `${sessionBottles} bottles scanned`;
    }

    function startTimer() {
        clearInterval(timerInterval);
        timeLeft = 60; 
        document.getElementById('timer-display').innerText = "01:00";
        
        timerInterval = setInterval(() => {
            timeLeft--; 
            let s = timeLeft % 60;
            document.getElementById('timer-display').innerText = `00:${s < 10 ? '0' : ''}${s}`;
            if (timeLeft <= 0) {
                clearInterval(timerInterval); 
                showToast('Time is up! Auto-confirming balance.');
                if(sessionPoints > 0) confirmBalance(); else closeDepositModal();
            }
        }, 1000);
    }

    function triggerDetection(isPet, sizeLabel, pointsValue) {
        if (isPet) {
            sessionPoints += parseFloat(pointsValue); 
            sessionBottles++;
            updateSessionUI(); 
            startTimer(); 
            showToast(`✅ Valid PET Detected (${sizeLabel})! +${pointsValue} pts.`);
            fetch(`api.php?action=extend_lock&qr=${myAssignedQR}`);
        }
    }

    async function confirmBalance() {
        if (sessionPoints <= 0) { closeDepositModal(); return; }
        try {
            let res = await fetch('api.php?action=deposit', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ qr: myAssignedQR, size: `Hardware Detect (${sessionBottles})`, points: sessionPoints })
            });
            let json = await res.json();
            if (json.status === 'success') {
                showToast(`Success! ${sessionPoints} points added.`);
                initApp(); 
                closeDepositModal(); 
            }
        } catch(e) { showToast('Error during checkout.'); }
    }

    window.onload = initApp;
</script>
</body>
</html>