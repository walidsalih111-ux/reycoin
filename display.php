<?php
require 'config.php';

// --- AJAX POLLING BACKEND ---
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    $active_user = null;
    $now = time();

    $lock_stmt = $pdo->query("SELECT user_qr FROM system_lock WHERE id = 1 AND expires_at > $now");
    $lock = $lock_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($lock && $lock['user_qr']) {
        // Exclude localhost users even if they somehow acquired a lock
        $usr_stmt = $pdo->prepare("SELECT * FROM users WHERE qr_code = ? AND ip_address NOT IN ('127.0.0.1', '::1')");
        $usr_stmt->execute([$lock['user_qr']]);
        $active_user = $usr_stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 2. If no active lock, find the first connected user via Hotspot Ping
    if (!$active_user) {
        // Exclude localhost IP addresses from the fetch
        $stmt = $pdo->query("SELECT * FROM users WHERE ip_address NOT IN ('127.0.0.1', '::1') ORDER BY GREATEST(IFNULL(updated_at, '2000-01-01'), created_at) DESC LIMIT 10");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($users as $u) {
            $ip = $u['ip_address'];
            
            // Safe ping execution
            if (function_exists('exec')) {
                $disabled = explode(',', ini_get('disable_functions'));
                if (!in_array('exec', array_map('trim', $disabled))) {
                    // Check OS to format ping command correctly (Linux/Pi vs Windows)
                    $ping_cmd = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') 
                        ? "ping -n 1 -w 1000 " 
                        : "ping -c 1 -W 1 ";
                        
                    exec($ping_cmd . escapeshellarg($ip) . " > /dev/null 2>&1", $output, $status);
                    
                    if ($status === 0) {
                        $active_user = $u;
                        break; // Found the active connected user!
                    }
                }
            }
        }
    }

    // 3. Last resort fallback: Show the latest user if they interacted within the last 60 seconds (excluding localhost)
    if (!$active_user) {
        $fallback_stmt = $pdo->query("SELECT * FROM users WHERE ip_address NOT IN ('127.0.0.1', '::1') AND updated_at >= NOW() - INTERVAL 1 MINUTE ORDER BY updated_at DESC LIMIT 1");
        $active_user = $fallback_stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Fetch Bin Status from hardware endpoint
    $bin_status = ['fill_percent' => 0, 'is_full' => false];
    $ctx = stream_context_create(['http' => ['timeout' => 1]]);
    $bin_res = @file_get_contents('http://127.0.0.1:5000/status', false, $ctx);
    if ($bin_res) {
        $parsed = json_decode($bin_res, true);
        if ($parsed && isset($parsed['fill_percent'])) {
            $bin_status = $parsed;
        }
    }

    if ($active_user) {
        echo json_encode(['status' => 'success', 'user' => $active_user, 'bin' => $bin_status]);
    } else {
        echo json_encode(['status' => 'empty', 'bin' => $bin_status]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RECYCOIN - Display</title>
    <script src="functions/tailwind.js"></script>
    <link href="functions/font.css" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/logo.png">
    <style>
        :root {
            --g900: #042c1e; --g800: #0a5c46; --g700: #0F6E56; --g400: #5DCAA5;
        }
        body { 
            font-family: 'DM Sans', sans-serif; 
            background-color: var(--g900); 
            color: white; 
            margin: 0; 
            overflow: hidden; 
            height: 100vh;
            background-image: radial-gradient(circle at 50% -20%, #0a5c46 0%, #042c1e 60%);
        }
        .screen {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            display: none; 
        }
        .screen.active {
            display: flex; 
        }
        .points-display {
            font-size: 8rem;
            line-height: 1;
            font-weight: 900;
            color: var(--g400);
            text-shadow: 0 10px 30px rgba(93, 202, 165, 0.2);
        }
    </style>
</head>
<body>

<!-- IDLE SCREEN (No User Connected) -->
<div id="idle-screen" class="screen active text-center">
    <h1 class="text-6xl font-black mb-4">Welcome to RECYCOIN</h1>
    <p class="text-3xl text-green-200 mb-12">Scan to connect to our hotspot and start recycling.</p>
    
    <div class="bg-white p-8 rounded-[40px] shadow-[0_0_80px_rgba(93,202,165,0.15)] inline-block">
        <img src="assets/wifiqr.jpg" onerror="this.src='wifiqr.jpg'" alt="Wi-Fi QR Code" class="w-80 h-80 mx-auto">
    </div>
    
    <div class="mt-12 bg-black/20 backdrop-blur-sm px-8 py-4 rounded-full border border-white/10 shadow-inner">
        <p class="text-2xl text-green-400 font-mono tracking-widest font-bold">WIFI: <span class="text-white">RECYCOIN</span></p>
    </div>
</div>

<!-- USER SCREEN (Active Connection) -->
<div id="user-screen" class="screen w-full max-w-6xl px-12">
    <div class="flex items-center justify-between gap-8 mb-12 w-full">
        <div class="flex-1 min-w-0">
            <p class="text-2xl text-green-400 font-bold uppercase tracking-widest mb-1 flex items-center gap-3">
                <span class="inline-block rounded-full h-4 w-4 bg-green-500"></span>
                Connected
            </p>
            <h1 class="text-6xl font-black text-white truncate max-w-4xl" id="display-name">---</h1>
        </div>
        
        <!-- Portal QR Code & Instructions -->
        <div class="shrink-0 flex items-center gap-5 bg-black/20 backdrop-blur-md p-4 rounded-[32px] border border-white/10 shadow-2xl">
            <div class="text-right">
                <p class="text-lg font-bold text-green-400 uppercase tracking-widest mb-1">Resident Portal</p>
                <p class="text-sm text-green-100 mb-2">Scan to track points</p>
                <p class="text-xs font-mono text-white/70 bg-black/40 py-1.5 px-3 rounded-full inline-block tracking-widest border border-white/5">
                    10.0.0.1
                </p>
            </div>
            <div class="bg-white p-3 rounded-2xl shadow-inner">
                <img src="assets/qr.png" alt="Portal Link QR" class="w-28 h-28 object-contain">
            </div>
        </div>
    </div>
    
    <div class="grid grid-cols-5 gap-8 w-full">
        <div class="col-span-3 bg-white/5 backdrop-blur-xl rounded-[40px] p-12 border border-white/10 shadow-2xl relative overflow-hidden flex flex-col justify-center">
            <div class="absolute -right-20 -top-20 w-64 h-64 bg-green-500/10 rounded-full blur-3xl pointer-events-none"></div>
            <p class="text-2xl text-green-200 font-medium mb-6 relative z-10">Total Points Balance</p>
            <div class="points-display relative z-10" id="display-points">0.00</div>
        </div>
        
        <div class="col-span-2 bg-white/5 backdrop-blur-xl rounded-[40px] p-12 border border-white/10 shadow-2xl flex flex-col justify-center">
            <p class="text-2xl text-green-200 font-medium mb-6">Resident ID Code</p>
            <div class="bg-black/40 py-6 px-8 rounded-3xl border border-white/5 shadow-inner">
                <p class="text-4xl font-mono text-green-400 font-bold tracking-widest text-center" id="display-qr">RC-----</p>
            </div>
        </div>
    </div>

    <!-- NEW: Bin Fill Level Monitor UI -->
    <div class="mt-8 bg-white/5 backdrop-blur-xl rounded-[40px] p-8 border border-white/10 shadow-2xl w-full">
        <div class="flex items-center justify-between mb-4">
            <p class="text-xl text-green-200 font-medium flex items-center gap-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                RVM Bin Fill Level
            </p>
            <p class="text-3xl font-black text-white" id="display-bin-text">0%</p>
        </div>
        
        <div class="w-full bg-black/40 rounded-full h-8 border border-white/5 overflow-hidden shadow-inner relative">
            <div id="display-bin-bar" class="h-full bg-green-500 rounded-full transition-all duration-500 w-0"></div>
        </div>
        
        <!-- WARNING MESSAGE (Hidden by default) -->
        <div id="display-bin-warning" class="hidden mt-6 bg-red-500/20 border border-red-500/50 text-red-200 p-4 rounded-2xl text-center text-xl font-bold tracking-widest animate-pulse shadow-[0_0_20px_rgba(239,68,68,0.3)]">
            ⚠️ BIN IS FULL! TRAPDOOR MECHANISM DISABLED ⚠️
        </div>
    </div>
</div>

<script>
    let currentUserId = null;

    // Data Polling Logic
    async function pollStatus() {
        try {
            let res = await fetch('display.php?ajax=1');
            let json = await res.json();
            
            // --- UPDATE BIN STATUS UI ---
            if (json.bin) {
                let bar = document.getElementById('display-bin-bar');
                let txt = document.getElementById('display-bin-text');
                let warn = document.getElementById('display-bin-warning');
                
                if (bar && txt && warn) {
                    bar.style.width = json.bin.fill_percent + '%';
                    txt.innerText = json.bin.fill_percent + '%';
                    
                    // Progress Bar Color Logic
                    if (json.bin.fill_percent >= 90) {
                        bar.className = 'h-full rounded-full transition-all duration-500 bg-red-500 shadow-[0_0_15px_#ef4444]';
                    } else if (json.bin.fill_percent >= 60) {
                        bar.className = 'h-full rounded-full transition-all duration-500 bg-yellow-500 shadow-[0_0_15px_#eab308]';
                    } else {
                        bar.className = 'h-full rounded-full transition-all duration-500 bg-green-500 shadow-[0_0_15px_#22c55e]';
                    }

                    // Display warning lockout text if 95% threshold reached
                    if (json.bin.is_full) {
                        warn.classList.remove('hidden');
                    } else {
                        warn.classList.add('hidden');
                    }
                }
            }

            if (json.status === 'success' && json.user) {
                let u = json.user;
                
                // Populate UI Data
                document.getElementById('display-name').innerText = u.full_name;
                document.getElementById('display-points').innerText = parseFloat(u.total_points).toFixed(2);
                document.getElementById('display-qr').innerText = u.qr_code;
                
                // Snap to user screen if not already visible
                if (currentUserId !== u.id) {
                    document.getElementById('idle-screen').classList.remove('active');
                    document.getElementById('user-screen').classList.add('active');
                    currentUserId = u.id;
                }
            } else {
                // No user found, snap back to QR Code screen
                if (currentUserId !== null) {
                    document.getElementById('user-screen').classList.remove('active');
                    document.getElementById('idle-screen').classList.add('active');
                    currentUserId = null;
                }
            }
        } catch(e) {
            console.error("LCD Polling Error:", e);
        }
        
        // Re-poll every 2 seconds for real-time responsiveness
        setTimeout(pollStatus, 2000);
    }

    // Initialize Polling
    window.onload = pollStatus;
</script>

</body>
</html>