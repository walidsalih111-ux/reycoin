<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>RECYCOIN - Personnel Admin</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<!-- Add HTML5-QRCode Library for Camera Scanning -->
<script src="https://unpkg.com/html5-qrcode"></script>
<style>
    :root { --g900: #042c1e; --g700: #0F6E56; --a500: #BA7517; }
    body { font-family: 'DM Sans', sans-serif; background-color: #042c1e; color: #fff; margin: 0; }
    .app-container { max-width: 480px; margin: 0 auto; background: var(--g900); min-height: 100vh; display: flex; flex-direction: column; }
    .card { background: white; color: #1a1a1a; border-radius: 16px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); margin-bottom: 16px; }
    .reward-btn { border: 1px solid #e5e7eb; transition: 0.2s; }
    .reward-btn.selected { border-color: var(--g700); background-color: #E1F5EE; color: var(--g900); font-weight: bold; }
    .toast { position: fixed; top: 20px; left: 50%; transform: translateX(-50%); background: #111; color: white; padding: 12px 24px; border-radius: 30px; font-size: 14px; opacity: 0; transition: 0.3s; z-index: 100;}
    .toast.show { opacity: 1; }
    /* Scanner Video Canvas fix */
    #reader video { border-radius: 12px; object-fit: cover; }
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
        <div class="bg-white/10 p-2 rounded-lg">
            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
        </div>
    </div>

    <!-- Step 1: Scan -->
    <div id="step-scan" class="card">
        <h2 class="font-bold text-lg mb-2">1. Scan Resident ID</h2>
        <p class="text-sm text-gray-500 mb-4">Use the camera to scan the Resident's QR code.</p>
        
        <!-- Scanner Window -->
        <div id="reader" width="100%" class="mb-4 hidden rounded-xl overflow-hidden border-2 border-green-200"></div>

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

    <!-- Step 2: Reward Selection (Hidden initially) -->
    <div id="step-reward" class="card hidden">
        <div class="flex justify-between items-center mb-4 pb-4 border-b">
            <div>
                <p class="text-xs text-gray-400">Resident Name</p>
                <h3 class="font-bold text-lg" id="res-name">---</h3>
            </div>
            <div class="text-right">
                <p class="text-xs text-gray-400">Available</p>
                <h3 class="font-bold text-xl text-green-700" id="res-pts">0 <span class="text-sm">pts</span></h3>
            </div>
        </div>

        <h2 class="font-bold text-md mb-3">2. Select Reward Item</h2>
        <div class="grid grid-cols-2 gap-2 mb-4">
            <button class="reward-btn p-3 rounded-lg text-center" onclick="selectReward(this, '1kg Rice', 50)">
                <div class="text-2xl mb-1">🍚</div>
                <div class="text-sm">1kg Rice</div>
                <div class="text-xs text-gray-500 font-mono mt-1">-50 pts</div>
            </button>
            <button class="reward-btn p-3 rounded-lg text-center" onclick="selectReward(this, 'Canned Goods', 30)">
                <div class="text-2xl mb-1">🥫</div>
                <div class="text-sm">Canned Goods</div>
                <div class="text-xs text-gray-500 font-mono mt-1">-30 pts</div>
            </button>
        </div>

        <button id="redeem-btn" disabled onclick="processRedemption()" class="w-full bg-gray-300 text-gray-500 font-bold py-3 rounded-lg transition-all">
            Confirm & Deduct
        </button>
        <button onclick="resetScan()" class="w-full text-center text-sm text-gray-400 mt-4 underline">Cancel</button>
    </div>

</div>

<script>
    let currentResident = null;
    let selectedReward = null;
    let html5QrcodeScanner = null;

    function showToast(msg) {
        const t = document.getElementById('toast');
        t.innerText = msg;
        t.classList.add('show');
        setTimeout(() => t.classList.remove('show'), 3000);
    }

    // --- CAMERA SCANNER LOGIC --- //
    function startScanner() {
        document.getElementById('reader').classList.remove('hidden');
        document.getElementById('start-scan-btn').classList.add('hidden');
        document.getElementById('stop-scan-btn').classList.remove('hidden');

        // Initialize the scanner targeting the 'reader' div
        html5QrcodeScanner = new Html5Qrcode("reader");
        
        // Start scanning with rear camera
        html5QrcodeScanner.start(
            { facingMode: "environment" }, 
            {
                fps: 10,    // Scans per second
                qrbox: { width: 250, height: 250 } // Scanning box size
            },
            (decodedText, decodedResult) => {
                // SUCCESS: A QR Code was found!
                document.getElementById('qr-input').value = decodedText;
                showToast("QR Code Detected!");
                stopScanner();
                verifyQR(); // Automatically verify it in the database
            },
            (errorMessage) => {
                // Background errors while scanning (ignored to prevent spamming console)
            }
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
            }).catch((err) => {
                console.error("Failed to stop scanner", err);
            });
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
                document.getElementById('res-pts').innerHTML = `${currentResident.total_points} <span class="text-sm">pts</span>`;
                
                document.getElementById('step-scan').classList.add('hidden');
                document.getElementById('step-reward').classList.remove('hidden');
                showToast('Resident Verified');
            } else {
                showToast(json.message); // Invalid QR
            }
        } catch(e) { showToast('Database connection failed'); }
    }

    function selectReward(el, name, cost) {
        document.querySelectorAll('.reward-btn').forEach(b => b.classList.remove('selected'));
        el.classList.add('selected');
        selectedReward = { name, cost };

        const btn = document.getElementById('redeem-btn');
        if (parseFloat(currentResident.total_points) >= cost) {
            btn.disabled = false;
            btn.className = "w-full bg-green-700 text-white font-bold py-3 rounded-lg transition-all hover:bg-green-800";
            btn.innerText = `Confirm (${cost} pts)`;
        } else {
            btn.disabled = true;
            btn.className = "w-full bg-red-100 text-red-600 font-bold py-3 rounded-lg transition-all";
            btn.innerText = "Insufficient Points";
        }
    }

    async function processRedemption() {
        if (!selectedReward || !currentResident) return;

        try {
            let res = await fetch('api.php?action=redeem', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    qr: currentResident.qr_code, 
                    item: selectedReward.name, 
                    cost: selectedReward.cost 
                })
            });
            let json = await res.json();
            
            if (json.status === 'success') {
                showToast('✅ Success! Item distributed.');
                setTimeout(resetScan, 2000);
            } else {
                showToast('Error: ' + json.message);
            }
        } catch(e) { showToast('Network Error'); }
    }

    function resetScan() {
        stopScanner(); // Make sure camera is closed
        document.getElementById('qr-input').value = '';
        currentResident = null;
        selectedReward = null;
        document.querySelectorAll('.reward-btn').forEach(b => b.classList.remove('selected'));
        document.getElementById('redeem-btn').disabled = true;
        document.getElementById('redeem-btn').className = "w-full bg-gray-300 text-gray-500 font-bold py-3 rounded-lg transition-all";
        document.getElementById('redeem-btn').innerText = "Confirm & Deduct";
        
        document.getElementById('step-reward').classList.add('hidden');
        document.getElementById('step-scan').classList.remove('hidden');
    }
</script>
</body>
</html>