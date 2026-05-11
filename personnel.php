<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>RECYCOIN - Personnel Admin</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/html5-qrcode"></script>
<style>
    :root { --g900: #042c1e; --g700: #0F6E56; --a500: #BA7517; }
    body { font-family: 'DM Sans', sans-serif; background-color: #042c1e; color: #fff; margin: 0; }
    .app-container { max-width: 480px; margin: 0 auto; background: var(--g900); min-height: 100vh; display: flex; flex-direction: column; }
    .card { background: white; color: #1a1a1a; border-radius: 16px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); margin-bottom: 16px; }
    .toast { position: fixed; top: 20px; left: 50%; transform: translateX(-50%); background: #111; color: white; padding: 12px 24px; border-radius: 30px; font-size: 14px; opacity: 0; transition: 0.3s; z-index: 100; white-space: nowrap;}
    .toast.show { opacity: 1; }
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
        <a href="index.php" class="bg-white/10 p-2 rounded-lg text-white hover:bg-white/20 transition tooltip flex items-center gap-2 text-sm" title="Switch to Resident">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
        </a>
    </div>

    <!-- Step 1: Scan -->
    <div id="step-scan" class="card">
        <h2 class="font-bold text-lg mb-2">1. Scan Resident ID</h2>
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

</div>

<script>
    let currentResident = null;
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
            // Note: Item and Cost are now strictly enforced server-side inside api.php?action=redeem
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