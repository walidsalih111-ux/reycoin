<?php
// api.php - The bridge between the Web Apps and the Database
session_start(); // START SESSION AT THE VERY TOP
header('Content-Type: application/json');
require 'config.php';

$action = $_GET['action'] ?? '';

// --- NEW: Offline PISOWIFI-style Auto-Registration & Session Init ---
if ($action === 'init_session') {
    $ip = $_SERVER['REMOTE_ADDR'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE ip_address = ?");
    $stmt->execute([$ip]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $new_qr = 'RC-' . date('Y') . '-' . strtoupper(substr(md5($ip . time()), 0, 6));
        $name = "Resident (" . $ip . ")";
        
        $insert = $pdo->prepare("INSERT INTO users (ip_address, qr_code, full_name, total_points) VALUES (?, ?, ?, ?)");
        $insert->execute([$ip, $new_qr, $name, 0]);
        
        $stmt->execute([$ip]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    echo json_encode(['status' => 'success', 'data' => $user]);
}

// 1. Get User Data by QR Code
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

// 2. Add Bottle Deposit
elseif ($action === 'deposit') {
    $data = json_decode(file_get_contents('php://input'), true);
    $qr = $data['qr'] ?? '';
    $size = $data['size'] ?? 'Bulk Deposit';
    $points = floatval($data['points'] ?? 0);
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE qr_code = ?");
    $stmt->execute([$qr]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $userId = $user['id'];
        $update = $pdo->prepare("UPDATE users SET total_points = total_points + ? WHERE id = ?");
        $update->execute([$points, $userId]);
        
        $insert = $pdo->prepare("INSERT INTO deposits (user_id, bottle_size, points_earned) VALUES (?, ?, ?)");
        $insert->execute([$userId, $size, $points]);
        
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
    }
}

// 3. Redeem Rewards (Hardcoded to strictly 100 points)
elseif ($action === 'redeem') {
    $data = json_decode(file_get_contents('php://input'), true);
    $qr = $data['qr'] ?? '';
    $cost = 100.00; // Fixed 100 points
    $item = "100 Points Reward";
    
    // In a real scenario, personnel_id would come from $_SESSION['personnel_id']
    $personnelId = $_SESSION['personnel_id'] ?? 1; 
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE qr_code = ?");
    $stmt->execute([$qr]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        if (floatval($user['total_points']) >= $cost) {
            $userId = $user['id'];
            
            $update = $pdo->prepare("UPDATE users SET total_points = total_points - ? WHERE id = ?");
            $update->execute([$cost, $userId]);
            
            $insert = $pdo->prepare("INSERT INTO redemptions (user_id, personnel_id, reward_item, points_deducted) VALUES (?, ?, ?, ?)");
            $insert->execute([$userId, $personnelId, $item, $cost]);
            
            echo json_encode(['status' => 'success', 'message' => '100 Points redeemed successfully!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Insufficient points. Must have at least 100.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'User not found.']);
    }
}

// 4. Update Resident Name
elseif ($action === 'update_name') {
    $data = json_decode(file_get_contents('php://input'), true);
    $qr = $data['qr'] ?? '';
    $name = $data['name'] ?? '';
    
    if ($qr && $name) {
        $stmt = $pdo->prepare("UPDATE users SET full_name = ? WHERE qr_code = ?");
        $stmt->execute([$name, $qr]);
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
    }
}

// 5. Personnel Login
elseif ($action === 'login') {
    $data = json_decode(file_get_contents('php://input'), true);
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';
    
    $stmt = $pdo->prepare("SELECT id FROM personnel WHERE username = ? AND password_hash = ?");
    $stmt->execute([$username, $password]);
    $personnel = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($personnel) {
        // SECURE THE SESSION HERE
        $_SESSION['personnel_logged_in'] = true;
        $_SESSION['personnel_id'] = $personnel['id'];
        
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid username or password']);
    }
}

// NEW: 5.5 Logout Personnel
elseif ($action === 'logout') {
    session_unset();
    session_destroy();
    echo json_encode(['status' => 'success']);
}

// 6. Get User History Logs (Individual)
elseif ($action === 'get_history') {
    $qr = $_GET['qr'] ?? '';
    $stmt = $pdo->prepare("SELECT id FROM users WHERE qr_code = ?");
    $stmt->execute([$qr]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $userId = $user['id'];
        
        $query = $pdo->prepare("
            SELECT 'deposit' as type, points_earned as points, created_at, bottle_size as detail
            FROM deposits WHERE user_id = ?
            UNION ALL
            SELECT 'redemption' as type, points_deducted as points, created_at, reward_item as detail
            FROM redemptions WHERE user_id = ?
            ORDER BY created_at DESC
        ");
        $query->execute([$userId, $userId]);
        $history = $query->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['status' => 'success', 'data' => $history]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
    }
}

// 7. Update Admin Credentials
elseif ($action === 'update_admin') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $_SESSION['personnel_id'] ?? 1; // Securely get ID from session if available
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';
    
    if ($username && $password) {
        $stmt = $pdo->prepare("UPDATE personnel SET username = ?, password_hash = ? WHERE id = ?");
        $stmt->execute([$username, $password, $id]);
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
    }
}

// 8. Get All History (Global for Personnel)
elseif ($action === 'get_all_history') {
    $query = $pdo->query("
        SELECT 'deposit' as type, u.full_name, d.points_earned as points, d.created_at, d.bottle_size as detail
        FROM deposits d JOIN users u ON d.user_id = u.id
        UNION ALL
        SELECT 'redemption' as type, u.full_name, r.points_deducted as points, r.created_at, r.reward_item as detail
        FROM redemptions r JOIN users u ON r.user_id = u.id
        ORDER BY created_at DESC LIMIT 100
    ");
    $history = $query->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['status' => 'success', 'data' => $history]);
}
?>