<?php
// lcddisplay.php - Real-time Desktop / Kiosk Display for RecyCoin
require 'config.php';

// --- AJAX POLLING BACKEND ---
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    $active_user = null;
    $now = time();

    // 1. Prioritize the user currently holding the machine lock (actively inserting bottles)
    $lock_stmt = $pdo->query("SELECT user_qr FROM system_lock WHERE id = 1 AND expires_at > $now");
    $lock = $lock_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($lock && $lock['user_qr']) {
        $usr_stmt = $pdo->prepare("SELECT * FROM users WHERE qr_code = ?");
        $usr_stmt->execute([$lock['user_qr']]);
        $active_user = $usr_stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 2. If no active lock, find the first connected user via Hotspot Ping
    if (!$active_user) {
        $stmt = $pdo->query("SELECT * FROM users ORDER BY GREATEST(IFNULL(updated_at, '2000-01-01'), created_at) DESC LIMIT 10");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($users as $u) {
            $ip = $u['ip_address'];
            
            // Localhost is always considered "connected" (for testing)
            if ($ip === '::1' || $ip === '127.0.0.1') {
                $active_user = $u;
                break;
            }
            
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

    // 3. Last resort fallback: Show the latest user if they interacted within the last 60 seconds
    if (!$active_user) {
        $fallback_stmt = $pdo->query("SELECT * FROM users WHERE updated_at >= NOW() - INTERVAL 1 MINUTE ORDER BY updated_at DESC LIMIT 1");
        $active_user = $fallback_stmt->fetch(PDO::FETCH_ASSOC);
    }

    if ($active_user) {
        echo json_encode(['status' => 'success', 'user' => $active_user]);
    } else {
        echo json_encode(['status' => 'empty']);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RecyCoin - LCD Kiosk Display</title>
    <script src="functions/tailwind.js"></script>
    <link href="functions/font.css" rel="stylesheet">
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
            opacity: 0;
            pointer-events: none;
            transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
            transform: scale(0.95);
        }
        .screen.active {
            opacity: 1;
            pointer-events: auto;
            transform: scale(1);
        }
        .points-display {
            font-size: 8rem;
            line-height: 1;
            font-weight: 900;
            color: var(--g400);
            text-shadow: 0 10px 30px rgba(93, 202, 165, 0.2);
        }
        .top-bar {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            padding: 2rem 3rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 10;
        }
    </style>
</head>
<body>

<div class="top-bar">
    <div class="flex items-center gap-3">
        <!-- Logo fallback ensures it loads if the path changes slightly -->
        <img src="assets/logo.png" onerror="this.src='logo.png'" alt="Logo" class="w-12 h-12 bg-white rounded-full p-1 drop-shadow-sm object-contain">
        <h2 class="text-2xl font-bold tracking-widest text-green-100">RECYCOIN</h2>
    </div>
</div>

<!-- IDLE SCREEN (No User Connected) -->
<div id="idle-screen" class="screen active text-center">
    <h1 class="text-6xl font-black mb-4">Welcome to RECYCOIN</h1>
    <p class="text-3xl text-green-200 mb-12">Scan to connect to our hotspot and start recycling.</p>
    
    <div class="bg-white p-8 rounded-[40px] shadow-[0_0_80px_rgba(93,202,165,0.15)] inline-block transform transition-transform hover:scale-105 duration-500">
        <!-- Using the requested qr.png asset -->
        <img src="assets/qr.png" onerror="this.src='qr.png'" alt="Wi-Fi QR Code" class="w-80 h-80 mx-auto">
    </div>
    
    <div class="mt-12 bg-black/20 backdrop-blur-sm px-8 py-4 rounded-full border border-white/10 shadow-inner">
        <p class="text-2xl text-green-400 font-mono tracking-widest font-bold">WIFI: <span class="text-white">RECYCOIN_HOTSPOT</span></p>
    </div>
</div>

<!-- USER SCREEN (Active Connection) -->
<div id="user-screen" class="screen w-full max-w-6xl px-12">
    <div class="flex items-center gap-8 mb-12 w-full">
        <div class="flex-1 min-w-0">
            <p class="text-2xl text-green-400 font-bold uppercase tracking-widest mb-1 flex items-center gap-3">
                <span class="relative flex h-4 w-4">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-4 w-4 bg-green-500"></span>
                </span>
                Connected Resident
            </p>
            <h1 class="text-6xl font-black text-white truncate max-w-4xl" id="display-name">---</h1>
        </div>
    </div>
    
    <div class="grid grid-cols-5 gap-8 w-full">
        <div class="col-span-3 bg-white/5 backdrop-blur-xl rounded-[40px] p-12 border border-white/10 shadow-2xl relative overflow-hidden">
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
</div>

<script>
    let currentUserId = null;

    // Data Polling Logic
    async function pollStatus() {
        try {
            let res = await fetch('lcddisplay.php?ajax=1');
            let json = await res.json();
            
            if (json.status === 'success' && json.user) {
                let u = json.user;
                
                // Populate UI Data
                document.getElementById('display-name').innerText = u.full_name;
                document.getElementById('display-points').innerText = parseFloat(u.total_points).toFixed(2);
                document.getElementById('display-qr').innerText = u.qr_code;
                
                // Crossfade to user screen if not already visible
                if (currentUserId !== u.id) {
                    document.getElementById('idle-screen').classList.remove('active');
                    document.getElementById('user-screen').classList.add('active');
                    currentUserId = u.id;
                }
            } else {
                // No user found, fade back to QR Code screen
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