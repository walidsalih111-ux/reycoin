<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>RECYCOIN - Resident</title>
<link href="functions/font.css" rel="stylesheet">
<script src="functions/tailwind.js"></script>
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
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">RECYCOIN</h1>
                <p class="text-green-400 text-sm opacity-90 font-medium" id="user-name">Connecting...</p>
            </div>
            <div class="text-right">
                <p class="text-sm text-green-200 font-medium mb-1">Balance</p>
                <h2 class="text-4xl font-black tracking-tighter" id="total-points">0.00</h2>
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
        <button onclick="openDepositModal()" class="w-full bg-[var(--green-700)] text-white font-bold py-4 rounded-xl shadow-lg active:scale-[0.98] transition-transform">
            Insert Bottle
        </button>

        <!-- RULES MOVED HERE -->
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

    <!-- RVM DEPOSIT MODAL -->
    <div id="deposit-modal" class="fixed inset-0 modal-overlay hidden z-50 flex-col items-center justify-center p-4">
        <div class="bg-white w-full max-w-md rounded-2xl p-6 relative shadow-2xl">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-[var(--green-900)]">RVM Disposal</h2>
                <div class="bg-red-100 text-red-600 font-mono px-3 py-1 rounded-full text-sm font-bold flex items-center gap-1">
                    <span id="timer-display">01:00</span>
                </div>
            </div>

            <!-- LIVE WEBCAM STREAM CONTAINER -->
            <div id="cam-container" class="relative w-full h-48 bg-black rounded-xl mb-4 overflow-hidden border border-[var(--green-200)] flex items-center justify-center">
                <p class="text-white text-xs z-10 animate-pulse">Initializing Camera Feed...</p>
                <img id="rvm-cam" src="" class="absolute inset-0 w-full h-full object-cover z-20 hidden" alt="RVM Camera">
            </div>

            <div class="bg-[var(--green-50)] p-4 rounded-xl mb-5 flex justify-between items-center border border-[var(--green-200)]">
                <div>
                    <span class="block text-sm text-[var(--green-900)] opacity-80">Accumulated Points</span>
                    <span class="text-xs text-[var(--green-700)]" id="session-bottle-count">0 bottles scanned</span>
                </div>
                <span class="text-3xl font-black text-[var(--green-700)]" id="session-total-pts">0.00</span>
            </div>

            <button onclick="confirmBalance()" class="w-full py-3 bg-[var(--green-700)] text-white rounded-xl font-bold shadow-md">Confirm Balance</button>
        </div>
    </div>
</div>

<script>
    let myAssignedQR = null; 
    let timerInterval;
    let yoloPollInterval;
    let timeLeft = 60;
    let sessionPoints = 0;
    let sessionBottles = 0;

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
            }
        } catch(e) {}
    }

    // --- YOLO & RVM MODAL LOGIC ---
    async function openDepositModal() {
        if(!myAssignedQR) return;
        
        let res = await fetch(`api.php?action=request_lock&qr=${myAssignedQR}`);
        let json = await res.json();
        if(json.status !== 'success') { showToast(json.message); return; }

        document.getElementById('deposit-modal').classList.remove('hidden');
        sessionPoints = 0; sessionBottles = 0; updateSessionUI(); startTimer(); 

        // Start Hardware Camera via PHP
        await fetch('api.php?action=yolo_start');
        
        // Point the img src directly to the Python Stream (running on port 5000)
        const camImg = document.getElementById('rvm-cam');
        camImg.src = `http://${window.location.hostname}:5000/video_feed`;
        camImg.classList.remove('hidden');

        // Start checking for detected bottles from Python every 1 second
        yoloPollInterval = setInterval(pollYoloDetection, 1000);
    }

    async function pollYoloDetection() {
        try {
            let res = await fetch('api.php?action=yolo_poll');
            let json = await res.json();
            // If Python successfully queued a detection
            if(json.status === 'success') {
                triggerDetection(true, json.size, json.points);
            }
        } catch(e) {} // Fail silently if camera is still booting
    }

    function closeDepositModal() {
        document.getElementById('deposit-modal').classList.add('hidden');
        clearInterval(timerInterval);
        clearInterval(yoloPollInterval);
        
        // Stop the Camera script
        fetch('api.php?action=yolo_stop');
        document.getElementById('rvm-cam').src = '';
        document.getElementById('rvm-cam').classList.add('hidden');

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
            startTimer(); // Reset timer to 60s
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
                initApp(); // Refresh user data
                closeDepositModal(); 
            }
        } catch(e) { showToast('Error during checkout.'); }
    }

    window.onload = initApp;
</script>
</body>
</html>