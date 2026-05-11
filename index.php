<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>RECYCOIN - Resident</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<style>
    :root {
        --green-900: #042c1e; --green-800: #0a5c46; --green-700: #0F6E56; --green-400: #5DCAA5;
        --bg-color: #f4f3ef;
    }
    body { font-family: 'DM Sans', sans-serif; background-color: var(--bg-color); color: #1a1a1a; margin: 0; padding: 0; }
    .app-container { max-width: 480px; margin: 0 auto; background: var(--bg-color); min-height: 100vh; position: relative; box-shadow: 0 0 20px rgba(0,0,0,0.05); overflow-x: hidden; }
    .header-curved { background: var(--green-900); border-radius: 0 0 24px 24px; padding: 24px 20px 40px; color: white; }
    .card { background: white; border-radius: 16px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); margin-bottom: 16px; }
    
    /* Toast */
    #toast {
        position: fixed; bottom: 80px; left: 50%; transform: translateX(-50%) translateY(100px);
        background: #1a1a1a; color: white; padding: 12px 24px; border-radius: 30px;
        font-weight: 500; font-size: 14px; opacity: 0; transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        z-index: 100; pointer-events: none; white-space: nowrap; box-shadow: 0 10px 20px rgba(0,0,0,0.2);
    }
    #toast.show { transform: translateX(-50%) translateY(0); opacity: 1; }
</style>
</head>
<body>

<div class="app-container">
    
    <div class="header-curved">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">RECYCOIN</h1>
                <p class="text-green-400 text-sm opacity-80" id="user-name">Loading...</p>
            </div>
            <div class="bg-white/10 p-2 rounded-full backdrop-blur-sm">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
            </div>
        </div>

        <div class="text-center mt-2">
            <p class="text-sm text-green-200 font-medium mb-1">Total Balance</p>
            <h2 class="text-5xl font-black tracking-tighter" id="total-points">0.00</h2>
            <p class="text-xs text-green-400 mt-1 uppercase tracking-widest">Points</p>
        </div>
    </div>

    <div class="px-5 -mt-6 relative z-10">
        
        <div class="card text-center relative overflow-hidden border border-gray-100">
            <div class="absolute top-0 right-0 w-24 h-24 bg-green-50 rounded-bl-full -z-10"></div>
            <h3 class="font-bold text-gray-800 mb-1">My Resident ID</h3>
            <p class="text-xs text-gray-500 mb-4">Scan this at the RVM or to redeem rewards</p>
            
            <div class="bg-white p-3 rounded-xl inline-block border-2 border-gray-100 shadow-sm mb-2">
                <div id="qrcode"></div>
            </div>
            <p class="font-mono text-sm font-medium text-gray-600 tracking-wider mt-1" id="qr-text">RC-XXXX-XXXX</p>
        </div>

        <button onclick="openDepositModal()" class="w-full bg-[var(--green-700)] text-white font-bold py-4 rounded-xl shadow-lg shadow-green-900/20 active:scale-[0.98] transition-transform flex items-center justify-center gap-2 mb-6">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            Insert Bottle     </button>

    </div>

    <div id="toast"></div>

    <div id="deposit-modal" class="fixed inset-0 bg-black/80 hidden z-50 flex flex-col items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white w-full max-w-md rounded-2xl p-6 relative shadow-2xl">
            
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-[var(--green-900)]"></h2>
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
                <button onclick="closeDepositModal()" class="flex-1 py-3 bg-gray-100 text-gray-700 rounded-xl font-bold active:bg-gray-200 transition-colors">Cancel</button>
                <button onclick="confirmBalance()" class="flex-[2] py-3 bg-[var(--green-700)] text-white rounded-xl font-bold shadow-md active:bg-[var(--green-800)] transition-colors">Confirm Balance</button>
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
    @keyframes scan {
        0% { top: 0; }
        50% { top: 100%; }
        100% { top: 0; }
    }
</style>

<script>
    // --- APP CONFIG ---
    // Hardcoded QR for the Resident prototype
    const MY_QR_CODE = 'RC-2026-001'; 
    
    // --- STATE ---
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

    function generateQRCode(text) {
        document.getElementById("qrcode").innerHTML = "";
        new QRCode(document.getElementById("qrcode"), {
            text: text, width: 140, height: 140,
            colorDark : "#1a1a1a", colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });
        document.getElementById('qr-text').innerText = text;
    }

    async function fetchMyData() {
        try {
            let res = await fetch(`api.php?action=get_user&qr=${MY_QR_CODE}`);
            let json = await res.json();
            
            if(json.status === 'success') {
                document.getElementById('user-name').innerText = json.data.full_name;
                document.getElementById('total-points').innerText = parseFloat(json.data.total_points).toFixed(2);
                generateQRCode(json.data.qr_code);
            }
        } catch(e) { console.error("API Error", e); }
    }

    // --- MODAL & RVM LOGIC ---

    async function openDepositModal() {
        document.getElementById('deposit-modal').classList.remove('hidden');
        sessionPoints = 0;
        sessionBottles = 0;
        updateSessionUI();
        startTimer();
        startWebcam();
    }

    function closeDepositModal() {
        document.getElementById('deposit-modal').classList.add('hidden');
        clearInterval(timerInterval);
        stopWebcam();
    }

    function updateSessionUI() {
        document.getElementById('session-total-pts').innerText = sessionPoints;
        document.getElementById('session-bottle-count').innerText = `${sessionBottles} bottles scanned`;
    }

    function startTimer() {
        clearInterval(timerInterval);
        timeLeft = 60;
        updateTimerDisplay();
        timerInterval = setInterval(() => {
            timeLeft--;
            updateTimerDisplay();
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                showToast('Time is up! Session closed.');
                // Auto-confirm balance if time runs out and they have points
                if(sessionPoints > 0) confirmBalance(); 
                else closeDepositModal();
            }
        }, 1000);
    }

    function updateTimerDisplay() {
        let m = Math.floor(timeLeft / 60);
        let s = timeLeft % 60;
        document.getElementById('timer-display').innerText = `0${m}:${s < 10 ? '0' : ''}${s}`;
    }

    async function startWebcam() {
        try {
            stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
            document.getElementById('webcam-feed').srcObject = stream;
        } catch (err) {
            console.error("Webcam error:", err);
            // Non-blocking error, just shows a black box if no webcam
        }
    }

    function stopWebcam() {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }
    }

    // This function acts as the bridge between your Hardware/ML model and the UI.
    function triggerDetection(isPet, sizeLabel, pointsValue) {
        if (isPet) {
            sessionPoints += pointsValue;
            sessionBottles++;
            updateSessionUI();
            startTimer(); // Reset timer to 60s
            showToast(`✅ Valid PET (${sizeLabel})! +${pointsValue} pts. Timer reset.`);
        } else {
            showToast('❌ Invalid bottle detected. Please remove.');
            // Note: Timer does NOT reset here
        }
    }

    async function confirmBalance() {
        if (sessionPoints <= 0) {
            showToast('No points collected in this session.');
            closeDepositModal();
            return;
        }
        
        try {
            // Sending the bulk session points to the API
            let res = await fetch('api.php?action=deposit', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    qr: MY_QR_CODE, 
                    size: `Bulk Deposit (${sessionBottles} bottles)`, 
                    points: sessionPoints 
                })
            });
            let json = await res.json();
            
            if (json.status === 'success') {
                showToast(`Success! ${sessionPoints} points added to balance.`);
                fetchMyData(); // Refresh the main UI numbers
                closeDepositModal();
            } else {
                showToast('Error saving data.');
            }
        } catch(e) {
            showToast('Network error during checkout.');
        }
    }

    // Load main data on start
    window.onload = () => {
        fetchMyData();
        // Poll every 5 seconds to show real-time updates if Personnel deducts points
        setInterval(fetchMyData, 5000); 
    };
</script>

</body>
</html>