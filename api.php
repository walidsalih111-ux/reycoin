<?php
// api.php - The bridge between the Web Apps and the Database
header('Content-Type: application/json');
require 'config.php';

$action = $_GET['action'] ?? '';

// 1. Get User Data by QR Code
if ($action === 'get_user') {
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
    $size = $data['size'] ?? 'Medium';
    $points = floatval($data['points'] ?? 0);
    
    // Find user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE qr_code = ?");
    $stmt->execute([$qr]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $userId = $user['id'];
        
        // Update user totals
        $update = $pdo->prepare("UPDATE users SET total_points = total_points + ?, total_bottles = total_bottles + 1 WHERE id = ?");
        $update->execute([$points, $userId]);
        
        // Log deposit
        $insert = $pdo->prepare("INSERT INTO deposits (user_id, bottle_size, points_earned) VALUES (?, ?, ?)");
        $insert->execute([$userId, $size, $points]);
        
        echo json_encode(['status' => 'success', 'message' => 'Deposit successful']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
    }
}

// 3. Redeem Reward (Personnel deducts points)
elseif ($action === 'redeem') {
    $data = json_decode(file_get_contents('php://input'), true);
    $qr = $data['qr'] ?? '';
    $item = $data['item'] ?? '';
    $cost = floatval($data['cost'] ?? 0);
    
    // Hardcoded personnel ID for prototype (usually fetched from session)
    $personnelId = 1; 

    // Find user
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
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid API Action']);
}
?>