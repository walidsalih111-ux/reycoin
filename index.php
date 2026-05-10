<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>RECYCOIN - Resident</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<!-- Library to dynamically generate real QR codes -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<style>
    :root {
        --green-900: #042c1e; --green-800: #0a5c46; --green-700: #0F6E56; --green-400: #5DCAA5;
        --bg-color: #f4f3ef;
    }
    body { font-family: 'DM Sans', sans-serif; background-color: var(--bg-color); color: #1a1a1a; margin: 0; padding: 0; }
    .app-container { max-width: 480px; margin: 0 auto; background: white; height: 100vh; display: flex; flex-direction: column; position: relative; box-shadow: 0 0 20px rgba(0,0,0,0.05); }
    .header { background: var(--green-800); color: white; padding: 20px; border-bottom-left-radius: 24px; border-bottom-right-radius: 24px; }
    .card { background: white; border-radius: 16px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 16px; border: 1px solid #f0f0f0; }
    .bottle-option.selected { border-color: var(--green-400); background-color: #E1F5EE; }
    .bottom-nav { position: absolute; bottom: 0; width: 100%; background: white; border-top: 1px solid #eee; display: flex; padding: 10px 0; padding-bottom: env(safe-area-inset-bottom, 10px); }
    .nav-item { flex: 1; text-align: center; color: #888; font-size: 12px; cursor: pointer; transition: 0.2s; }
    .nav-item.active { color: var(--green-800); font-weight: 600; }
    .nav-icon { height: 24px; margin-bottom: 4px; }
    .page { display: none; padding: 20px; flex: 1; overflow-y: auto; }
    .page.active { display: block; }
    .toast { position: fixed; top: 20px; left: 50%; transform: translateX(-50%); background: #333; color: white; padding: 12px 24px; border-radius: 30px; font-size: 14px; opacity: 0; pointer-events: none; transition: 0.3s; z-index: 100;}
    .toast.show { opacity: 1; }
</style>
</head>
<body>
<div class="app-container">
    
    <!-- HEADER -->
    <div class="header">
        <div class="flex justify-between items-center">
            <h1 class="text-xl font-bold tracking-wider">RECYCOIN</h1>
            <div class="text-sm opacity-80">Resident App</div>
        </div>
        <div class="mt-6 text-center">
            <p class="text-green-100 text-sm">Total Balance</p>
            <h2 class="text-4xl font-bold mt-1" id="ui-pts">0.00 <span class="text-lg font-normal">pts</span></h2>
        </div>
    </div>

    <div id="toast" class="toast">Message</div>

    <!-- PAGE: HOME -->
    <div id="page-home" class="page active">
        <div class="card bg-green-50/50">
            <h3 class="font-semibold text-gray-700 mb-2">My Stats</h3>
            <div class="flex justify-between mt-4">
                <div class="text-center w-1/2 border-r border-gray-200">
                    <p class="text-2xl font-bold text-green-800" id="ui-bottles">0</p>
                    <p class="text-xs text-gray-500">Bottles Recycled</p>
                </div>
                <div class="text-center w-1/2">
                    <p class="text-2xl font-bold text-green-800">1.2<span class="text-sm">kg</span></p>
                    <p class="text-xs text-gray-500">Carbon Saved</p>
                </div>
            </div>
        </div>

        <h3 class="font-bold text-gray-800 mb-3 mt-6">Simulate RVM Deposit</h3>
        <p class="text-xs text-gray-500 mb-4">In the real system, the hardware sends this data. Tap to simulate.</p>
        
        <div class="grid grid-cols-2 gap-3">
            <div class="card p-4 text-center cursor-pointer bottle-option border-2 border-transparent transition-all" onclick="selectBottle(this, 'Small', 1.0)">
                <div class="h-12 w-8 bg-gray-200 rounded-full mx-auto mb-2"></div>
                <p class="font-semibold">Small</p>
                <p class="text-xs text-green-600">+1.0 pt</p>
            </div>
            <div class="card p-4 text-center cursor-pointer bottle-option border-2 border-transparent transition-all" onclick="selectBottle(this, 'Large', 2.5)">
                <div class="h-16 w-10 bg-gray-200 rounded-full mx-auto mb-2"></div>
                <p class="font-semibold">Large</p>
                <p class="text-xs text-green-600">+2.5 pts</p>
            </div>
        </div>

        <button id="deposit-btn" onclick="submitDeposit()" disabled class="w-full mt-4 bg-green-800 text-white font-bold py-3 rounded-xl opacity-50 transition-all">
            Insert Bottle
        </button>
    </div>

    <!-- PAGE: QR CODE -->
    <div id="page-qr" class="page">
        <div class="card text-center mt-8">
            <h2 class="text-xl font-bold text-gray-800 mb-2">Your ID Card</h2>
            <p class="text-sm text-gray-500 mb-6">Present this to Personnel to redeem rewards.</p>
            
            <div class="bg-white p-4 rounded-xl inline-block mb-4 border-2 border-green-800 shadow-sm flex justify-center items-center mx-auto" style="width: 180px; height: 180px;">
                <!-- Real QR code image will be dynamically generated here -->
                <div id="qrcode-container"></div>
            </div>
            <p class="font-mono font-bold tracking-widest text-green-800 text-lg" id="ui-qr-text">Loading...</p>
            <p class="text-xs text-gray-400 mt-2">Show this to the scanner</p>
        </div>
    </div>

    <!-- BOTTOM NAVIGATION -->
    <div class="bottom-nav">
        <div class="nav-item active" onclick="switchPage('home', this)">
            <svg class="nav-icon mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
            Home
        </div>
        <div class="nav-item" onclick="switchPage('qr', this)">
            <svg class="nav-icon mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
            ID Card
        </div>
    </div>

</div>

<script>
    // HARDCODED FOR PROTOTYPE (Usually comes from Login session)
    const MY_QR_CODE = 'RC-2026-00142'; 
    let selectedBottleData = null;
    let qrCodeInstance = null; // Store the QR code object

    function showToast(msg) {
        const t = document.getElementById('toast');
        t.innerText = msg;
        t.classList.add('show');
        setTimeout(() => t.classList.remove('show'), 3000);
    }

    function switchPage(pageId, navEl) {
        document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
        document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
        document.getElementById('page-' + pageId).classList.add('active');
        navEl.classList.add('active');
    }

    function selectBottle(el, size, pts) {
        document.querySelectorAll('.bottle-option').forEach(o => o.classList.remove('selected'));
        el.classList.add('selected');
        selectedBottleData = { size, pts };
        
        const btn = document.getElementById('deposit-btn');
        btn.disabled = false;
        btn.classList.remove('opacity-50');
    }

    function generateQRCode(text) {
        // If it doesn't exist, create it
        if (!qrCodeInstance) {
            qrCodeInstance = new QRCode(document.getElementById("qrcode-container"), {
                text: text,
                width: 150,
                height: 150,
                colorDark : "#042c1e", // Recycoin Dark Green
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.H
            });
        } else {
            // If it exists, just update it
            qrCodeInstance.clear();
            qrCodeInstance.makeCode(text);
        }
    }

    // --- API INTEGRATION --- //

    async function fetchMyData() {
        try {
            let res = await fetch(`api.php?action=get_user&qr=${MY_QR_CODE}`);
            let json = await res.json();
            if (json.status === 'success') {
                document.getElementById('ui-pts').innerHTML = `${json.data.total_points} <span class="text-lg font-normal">pts</span>`;
                document.getElementById('ui-bottles').innerText = json.data.total_bottles;
                document.getElementById('ui-qr-text').innerText = json.data.qr_code;
                
                // Draw the actual scannable QR Code
                generateQRCode(json.data.qr_code);
            }
        } catch(e) { console.error("API Error", e); }
    }

    async function submitDeposit() {
        if (!selectedBottleData) return;
        
        try {
            let res = await fetch('api.php?action=deposit', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ qr: MY_QR_CODE, size: selectedBottleData.size, points: selectedBottleData.pts })
            });
            let json = await res.json();
            
            if (json.status === 'success') {
                showToast(`+${selectedBottleData.pts} Pts added!`);
                // Reset selection
                document.querySelectorAll('.bottle-option').forEach(o => o.classList.remove('selected'));
                document.getElementById('deposit-btn').disabled = true;
                document.getElementById('deposit-btn').classList.add('opacity-50');
                selectedBottleData = null;
                
                // Refresh points from DB
                fetchMyData();
            }
        } catch(e) {
            showToast('Network error during deposit');
        }
    }

    // Load data on start
    window.onload = () => {
        fetchMyData();
        // Poll every 5 seconds to show real-time updates if Personnel deducts points
        setInterval(fetchMyData, 5000); 
    };
</script>
</body>
</html>