<?php
// api.php - The bridge between the Web Apps and the Database
header('Content-Type: application/json');
require 'config.php';

$action = $_GET['action'] ?? '';

// --- NEW: Offline PISOWIFI-style Auto-Registration & Session Init ---
if ($action === 'init_session') {
    // Grab the IP address of the device connected to the Raspberry Pi hotspot
    $ip = $_SERVER['REMOTE_ADDR'];
    
    // Check if this IP has connected before
    $stmt = $pdo->prepare("SELECT * FROM users WHERE ip_address = ?");
    $stmt->execute([$ip]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If not found, create a new resident profile instantly behind the scenes
    if (!$user) {
        // Generate a unique Resident ID (QR Code string)
        // Format: RC-YYYY-[Random 6 chars based on IP and time]
        $new_qr = 'RC-' . date('Y') . '-' . strtoupper(substr(md5($ip . time()), 0, 6));
        $name = "Resident (" . $ip . ")";
        
        $insert = $pdo->prepare("INSERT INTO users (ip_address, qr_code, full_name, total_points) VALUES (?, ?, ?, ?)");
        $insert->execute([$ip, $new_qr, $name, 0]);
        
        // Fetch the newly created record
        $stmt->execute([$ip]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    echo json_encode(['status' => 'success', 'data' => $user]);
}

// 1. Get User Data by QR Code (Used by Personnel App)
elseif ($action === 'get_user') {
    $qr = $_GET['qr'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM users WHERE qr_code = ?");
    $stmt->execute([$qr]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo json_encode(['status' => 'success', 'data' => $user]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid QR Code. Resident not found.']);
    }
}

// 2. Add Bottle Deposit (Simulates the RVM Machine sending data)
elseif ($action === 'deposit') {
    $data = json_decode(file_get_contents('php://input'), true);
    $qr = $data['qr'] ?? '';
    $size = $data['size'] ?? 'Bulk Deposit';
    $points = floatval($data['points'] ?? 0);
    
    // Find user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE qr_code = ?");
    $stmt->execute([$qr]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $userId = $user['id'];
        
        // Add Points
        $update = $pdo->prepare("UPDATE users SET total_points = total_points + ? WHERE id = ?");
        $update->execute([$points, $userId]);
        
        // Log Deposit
        $insert = $pdo->prepare("INSERT INTO deposits (user_id, bottle_size, points_earned) VALUES (?, ?, ?)");
        $insert->execute([$userId, $size, $points]);
        
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
    }
}

// 3. Redeem Rewards (Used by Personnel to deduct points)
elseif ($action === 'redeem') {
    $data = json_decode(file_get_contents('php://input'), true);
    $qr = $data['qr'] ?? '';
    $item = $data['item'] ?? '';
    $cost = floatval($data['cost'] ?? 0);
    
    $personnelId = 1; // Hardcoded for prototype
    $stmt = $pdo->prepare("SELECT * FROM users WHERE qr_code = ?");
    $stmt->execute([$qr]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        if (floatval($user['total_points']) >= $cost) {
            $userId = $user['id'];
            
            // Deduct points
            $update = $pdo->prepare("UPDATE users SET total_points = total_points - ? WHERE id = ?");
            $update->execute([$cost, $userId]);
            
            // Log redemption
            $insert = $pdo->prepare("INSERT INTO redemptions (user_id, personnel_id, reward_item, points_deducted) VALUES (?, ?, ?, ?)");
            $insert->execute([$userId, $personnelId, $item, $cost]);
            
            echo json_encode(['status' => 'success', 'message' => 'Reward redeemed successfully!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Insufficient points.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'User not found.']);
    }
}
?>