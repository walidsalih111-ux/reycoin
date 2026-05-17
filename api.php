<?php
// api.php - The bridge between the Web Apps and the Database
session_start();
header('Content-Type: application/json');
require 'config.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS system_lock (
        id INT PRIMARY KEY,
        user_qr VARCHAR(50) DEFAULT NULL,
        expires_at INT DEFAULT 0
    )");
    $pdo->exec("INSERT IGNORE INTO system_lock (id, user_qr, expires_at) VALUES (1, NULL, 0)");
    
    // Add updated_at to store latest update timestamps (silently ignores if already exists)
    $pdo->exec("ALTER TABLE users ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
} catch (\PDOException $e) {}

$action = $_GET['action'] ?? '';

// --- Existing logic... ---
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
        $user['is_new'] = true;
    } else { $user['is_new'] = false; }
    echo json_encode(['status' => 'success', 'data' => $user]);
}
elseif ($action === 'get_user') {
    $qr = $_GET['qr'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM users WHERE qr_code = ?");
    $stmt->execute([$qr]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    echo $user ? json_encode(['status' => 'success', 'data' => $user]) : json_encode(['status' => 'error', 'message' => 'Not found.']);
}
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
    } else { echo json_encode(['status' => 'error']); }
}
// ==========================================
// RESIDENT PROFILE UPDATE
// ==========================================
elseif ($action === 'update_name') {
    $data = json_decode(file_get_contents('php://input'), true);
    $qr = $data['qr'] ?? '';
    $name = trim($data['new_name'] ?? '');

    // Backend validation: Min 3 chars, allows A-Z, a-z, 0-9, spaces, periods, and hyphens.
    if (strlen($name) < 3 || !preg_match('/^[A-Za-z0-9 .\-]+$/', $name)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid name format. Only letters, numbers, spaces, periods, and hyphens allowed.']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE users SET full_name = ? WHERE qr_code = ?");
    $success = $stmt->execute([$name, $qr]);

    if ($success) {
        echo json_encode(['status' => 'success', 'new_name' => $name]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database update failed.']);
    }
}
elseif ($action === 'request_lock') {
    $qr = $_GET['qr'] ?? '';
    $now = time();
    $expires = $now + 70;
    $stmt = $pdo->query("SELECT * FROM system_lock WHERE id = 1");
    $lock = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($lock && $lock['user_qr'] && $lock['user_qr'] !== $qr && $lock['expires_at'] > $now) {
        echo json_encode(['status' => 'error', 'message' => 'Machine in use']);
    } else {
        $update = $pdo->prepare("UPDATE system_lock SET user_qr = ?, expires_at = ? WHERE id = 1");
        $update->execute([$qr, $expires]);
        echo json_encode(['status' => 'success']);
    }
}
elseif ($action === 'extend_lock') {
    $qr = $_GET['qr'] ?? '';
    $expires = time() + 70;
    $update = $pdo->prepare("UPDATE system_lock SET expires_at = ? WHERE id = 1 AND user_qr = ?");
    $update->execute([$expires, $qr]);
    echo json_encode(['status' => 'success']);
}
elseif ($action === 'release_lock') {
    $qr = $_GET['qr'] ?? '';
    $update = $pdo->prepare("UPDATE system_lock SET user_qr = NULL, expires_at = 0 WHERE id = 1 AND user_qr = ?");
    $update->execute([$qr]);
    echo json_encode(['status' => 'success']);
}

// ==========================================
// PERSONNEL AUTHENTICATION & ADMIN CONTROLS
// ==========================================
elseif ($action === 'personnel_login') {
    $data = json_decode(file_get_contents('php://input'), true);
    $user = $data['username'] ?? '';
    $pass = $data['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM personnel WHERE username = ?");
    $stmt->execute([$user]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    // Matches the plain text password from the SQL dump
    if ($admin && $admin['password_hash'] === $pass) {
        $_SESSION['personnel_logged_in'] = true;
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid credentials']);
    }
}
elseif ($action === 'logout') {
    unset($_SESSION['personnel_logged_in']);
    session_destroy();
    echo json_encode(['status' => 'success']);
}
elseif ($action === 'update_admin') {
    if (!isset($_SESSION['personnel_logged_in']) || $_SESSION['personnel_logged_in'] !== true) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }
    $data = json_decode(file_get_contents('php://input'), true);
    $user = $data['username'] ?? '';
    $pass = $data['password'] ?? '';

    if ($user && $pass) {
        $stmt = $pdo->prepare("UPDATE personnel SET username = ?, password_hash = ? WHERE id = 1");
        $stmt->execute([$user, $pass]);
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Missing fields']);
    }
}
elseif ($action === 'get_all_history') {
    if (!isset($_SESSION['personnel_logged_in']) || $_SESSION['personnel_logged_in'] !== true) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }
    $stmt = $pdo->query("
        SELECT d.created_at, d.points_earned as points, d.bottle_size as detail, u.full_name, 'deposit' as type
        FROM deposits d JOIN users u ON d.user_id = u.id
        UNION ALL
        SELECT r.created_at, r.points_deducted as points, r.reward_item as detail, u.full_name, 'redemption' as type
        FROM redemptions r JOIN users u ON r.user_id = u.id
        ORDER BY created_at DESC LIMIT 50
    ");
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['status' => 'success', 'data' => $history]);
}

// ==========================================
// YOLO HARDWARE BRIDGES (PHP to Python)
// ==========================================
elseif ($action === 'bin_status') {
    $ctx = stream_context_create(['http' => ['timeout' => 2]]);
    $res = @file_get_contents('http://127.0.0.1:5000/status', false, $ctx);
    if ($res) {
        echo $res;
    } else {
        echo json_encode(['status' => 'error', 'fill_percent' => 0, 'is_full' => false]);
    }
}
elseif ($action === 'yolo_start') {
    $ctx = stream_context_create(['http' => ['timeout' => 2]]);
    $res = @file_get_contents('http://127.0.0.1:5000/start', false, $ctx);
    echo $res ? $res : json_encode(['status' => 'error']);
}
elseif ($action === 'yolo_stop') {
    $ctx = stream_context_create(['http' => ['timeout' => 2]]);
    $res = @file_get_contents('http://127.0.0.1:5000/stop', false, $ctx);
    echo $res ? $res : json_encode(['status' => 'error']);
}
elseif ($action === 'yolo_poll') {
    $ctx = stream_context_create(['http' => ['timeout' => 2]]);
    $res = @file_get_contents('http://127.0.0.1:5000/poll', false, $ctx);
    echo $res ? $res : json_encode(['status' => 'empty']);
}

// ==========================================
// REDEEM
// ==========================================
elseif ($action === 'redeem') {
    $data = json_decode(file_get_contents('php://input'), true);
    $qr = $data['qr'] ?? '';
    $stmt = $pdo->prepare("SELECT id, total_points FROM users WHERE qr_code = ?");
    $stmt->execute([$qr]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && $user['total_points'] >= 100) {
        $update = $pdo->prepare("UPDATE users SET total_points = total_points - 100 WHERE id = ?");
        $update->execute([$user['id']]);
        
        $insert = $pdo->prepare("INSERT INTO redemptions (user_id, reward_item, points_deducted) VALUES (?, '100 Points Reward', 100)");
        $insert->execute([$user['id']]);
        
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Insufficient points or user not found.']);
    }
}
?>