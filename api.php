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
// NEW: YOLO HARDWARE BRIDGES (PHP to Python)
// ==========================================
// We use a small timeout to prevent PHP from freezing if Python is off.
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
// ------------------------------------------

// Re-include remaining untouched api.php routes here (redeem, get_history, etc) 
// (Truncated to save space, keep your existing logic for other endpoints below)
?>