<?php
// api.php - The bridge between the Web Apps and the Database
session_start();
header('Content-Type: application/json');
require 'config.php';

const LOCK_SECONDS = 70;         // Machine lock; each verified bottle extends it (SESSION_IDLE_SECONDS in yolo.py)
const TOKEN_COOKIE = 'rc_token'; // Random per-device token that identifies a resident (instead of their IP)

// ==========================================
// HELPERS
// ==========================================
function json_body() {
    $data = json_decode(file_get_contents('php://input'), true);
    return is_array($data) ? $data : [];
}

// A string field from a JSON body ('' when it is missing or not a plain value)
function input_str($data, $key) {
    return isset($data[$key]) && is_scalar($data[$key]) ? (string)$data[$key] : '';
}

function require_personnel() {
    if (!isset($_SESSION['personnel_logged_in']) || $_SESSION['personnel_logged_in'] !== true) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }
}

function user_by_token($pdo, $token) {
    if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/', $token)) return null;
    // Only a hash of the token is stored, so a leaked database dump can't be used to sign in as a resident
    $stmt = $pdo->prepare("SELECT id, qr_code, full_name, total_points FROM users WHERE device_token = ?");
    $stmt->execute([hash('sha256', $token)]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

// The resident using this browser, identified by the device token cookie (never by IP address)
function current_user($pdo) {
    return user_by_token($pdo, $_COOKIE[TOKEN_COOKIE] ?? '');
}

function require_resident($pdo) {
    $user = current_user($pdo);
    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'Profile error. Please refresh the page.']);
        exit;
    }
    return $user;
}

function personnel_password_matches($input, $stored) {
    if (password_get_info($stored)['algo']) {
        return password_verify($input, $stored);
    }
    return hash_equals($stored, $input); // Plain-text password from the original SQL dump (hashed on next login)
}

// Calls the scanner service (yolo.py) on this Pi; returns its decoded JSON, or null if it is unreachable
function scanner($path) {
    $ctx = stream_context_create(['http' => ['timeout' => 2]]);
    $res = @file_get_contents('http://127.0.0.1:5000/' . $path, false, $ctx);
    return $res ? json_decode($res, true) : null;
}

function machine_lock($pdo) {
    return $pdo->query("SELECT user_qr, expires_at, started_at FROM system_lock WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
}

// Credits every bottle the scanner has verified to the resident whose session it was detected in.
// Points only ever come from the scanner, never from the browser. Each bottle has a unique id
// (deposits.event_id), so it is credited once even if two requests collect it at the same time
// or an /ack is lost. Returns the scanner's poll response (null if the scanner is unreachable).
function credit_verified_bottles($pdo) {
    $scan = scanner('poll');
    if (!$scan || empty($scan['items']) || !is_array($scan['items'])) return $scan;

    $credited = [];
    foreach ($scan['items'] as $item) {
        $eventId = (string)($item['id'] ?? '');
        if (!preg_match('/^[a-f0-9]{32}$/', $eventId)) continue;
        $owner = (string)($item['owner'] ?? '');
        $points = (float)($item['points'] ?? 0);

        try {
            $pdo->beginTransaction();
            $find = $pdo->prepare("SELECT id FROM users WHERE qr_code = ?");
            $find->execute([$owner]);
            $userId = $find->fetchColumn();

            if ($userId && $points > 0) {
                $update = $pdo->prepare("UPDATE users SET total_points = total_points + ? WHERE id = ?");
                $update->execute([$points, $userId]);
                $insert = $pdo->prepare("INSERT INTO deposits (user_id, bottle_size, points_earned, event_id) VALUES (?, ?, ?, ?)");
                $insert->execute([$userId, (string)($item['size'] ?? ''), $points, $eventId]);

                // Each bottle keeps its resident's lock alive, matching the scanner's own idle timer
                $extend = $pdo->prepare("UPDATE system_lock SET expires_at = GREATEST(expires_at, ?) WHERE id = 1 AND user_qr = ?");
                $extend->execute([(int)($item['ts'] ?? time()) + LOCK_SECONDS, $owner]);
            }
            $pdo->commit();
            $credited[] = $eventId;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            // 1062 = duplicate event_id: another request already credited this bottle
            if (($e->errorInfo[1] ?? 0) == 1062) $credited[] = $eventId;
        }
    }
    if ($credited) scanner('ack?ids=' . implode(',', $credited));
    return $scan;
}

// Bottles and points credited to a resident since their scanning session started
function session_totals($pdo, $userId, $startedAt) {
    $stmt = $pdo->prepare("SELECT COUNT(*) AS bottles, COALESCE(SUM(points_earned), 0) AS points FROM deposits WHERE user_id = ? AND event_id IS NOT NULL AND created_at >= FROM_UNIXTIME(?)");
    $stmt->execute([$userId, $startedAt]);
    $totals = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT bottle_size, points_earned FROM deposits WHERE user_id = ? AND event_id IS NOT NULL AND created_at >= FROM_UNIXTIME(?) ORDER BY id DESC LIMIT 1");
    $stmt->execute([$userId, $startedAt]);
    $last = $stmt->fetch(PDO::FETCH_ASSOC);

    return [
        'bottles' => (int)$totals['bottles'],
        'points' => (float)$totals['points'],
        'last_size' => $last ? $last['bottle_size'] : null,
        'last_points' => $last ? (float)$last['points_earned'] : null
    ];
}

$action = $_GET['action'] ?? '';

// ==========================================
// RESIDENT SESSION
// ==========================================
if ($action === 'init_session') {
    $ip = $_SERVER['REMOTE_ADDR'];
    $token = $_COOKIE[TOKEN_COOKIE] ?? '';
    $user = user_by_token($pdo, $token);
    $is_new = false;

    if (!$user) {
        $token = bin2hex(random_bytes(32));
        $token_hash = hash('sha256', $token);

        // One-time upgrade: an account from before device tokens (matched by IP) moves to this device
        $claim = $pdo->prepare("UPDATE users SET device_token = ? WHERE ip_address = ? AND device_token IS NULL ORDER BY id DESC LIMIT 1");
        $claim->execute([$token_hash, $ip]);

        if ($claim->rowCount() === 0) {
            $insert = $pdo->prepare("INSERT INTO users (ip_address, qr_code, full_name, total_points, device_token) VALUES (?, ?, ?, 0, ?)");
            for ($attempt = 1; ; $attempt++) {
                $new_qr = 'RC-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));
                try {
                    $insert->execute([$ip, $new_qr, "Resident (" . $ip . ")", $token_hash]);
                    break;
                } catch (PDOException $e) {
                    // Retry the rare duplicate Resident ID
                    if (($e->errorInfo[1] ?? 0) != 1062 || $attempt >= 5) throw $e;
                }
            }
            $is_new = true;
        }
        $user = user_by_token($pdo, $token);
    }

    // Track the device's current IP for display.php; a reassigned IP stops pointing at its previous owner
    $pdo->prepare("UPDATE users SET ip_address = NULL WHERE ip_address = ? AND id <> ?")->execute([$ip, $user['id']]);
    $pdo->prepare("UPDATE users SET ip_address = ? WHERE id = ?")->execute([$ip, $user['id']]);

    // Re-issued on every visit; browsers cap cookie lifetimes at 400 days
    setcookie(TOKEN_COOKIE, $token, ['expires' => time() + 400 * 86400, 'httponly' => true, 'samesite' => 'Lax']);

    $user['is_new'] = $is_new;
    echo json_encode(['status' => 'success', 'data' => $user]);
}
elseif ($action === 'me') {
    // Collect bottles verified since the last check (e.g. after the session ended) so the balance is current
    credit_verified_bottles($pdo);
    $user = require_resident($pdo);
    echo json_encode(['status' => 'success', 'data' => $user]);
}
elseif ($action === 'get_user') {
    // Personnel look up a resident by Resident ID before a redemption
    require_personnel();
    credit_verified_bottles($pdo);
    $qr = $_GET['qr'] ?? '';
    $stmt = $pdo->prepare("SELECT id, qr_code, full_name, total_points FROM users WHERE qr_code = ?");
    $stmt->execute([$qr]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    echo $user ? json_encode(['status' => 'success', 'data' => $user]) : json_encode(['status' => 'error', 'message' => 'Not found.']);
}
// ==========================================
// RESIDENT PROFILE UPDATE
// ==========================================
elseif ($action === 'update_name') {
    $user = require_resident($pdo);
    $data = json_body();
    $name = trim(input_str($data, 'new_name'));

    // Backend validation: 3-100 chars, allows A-Z, a-z, 0-9, spaces, periods, and hyphens.
    if (strlen($name) < 3 || strlen($name) > 100 || !preg_match('/^[A-Za-z0-9 .\-]+$/', $name)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid name format. Only letters, numbers, spaces, periods, and hyphens allowed.']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE users SET full_name = ? WHERE id = ?");
    $success = $stmt->execute([$name, $user['id']]);

    if ($success) {
        echo json_encode(['status' => 'success', 'new_name' => $name]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database update failed.']);
    }
}
// ==========================================
// MACHINE LOCK (one resident scans at a time)
// ==========================================
elseif ($action === 'request_lock') {
    $user = require_resident($pdo);
    $qr = $user['qr_code'];

    // Settle verified bottles first: they may extend the current holder's lock
    credit_verified_bottles($pdo);

    // Check and take the lock in one statement so two residents can't both get the machine
    $now = time();
    $take = $pdo->prepare("UPDATE system_lock SET user_qr = ?, expires_at = ?, started_at = ? WHERE id = 1 AND (user_qr IS NULL OR user_qr = ? OR expires_at <= ?)");
    $take->execute([$qr, $now + LOCK_SECONDS, $now, $qr, $now]);
    $lock = machine_lock($pdo);
    if (!$lock || $lock['user_qr'] !== $qr) {
        echo json_encode(['status' => 'error', 'message' => 'Machine in use']);
        exit;
    }

    // Start the scanner for this resident (camera feed pops up natively on the Pi); every bottle
    // it verifies is credited to them
    $start = scanner('start?owner=' . urlencode($qr));
    if (!$start || ($start['status'] ?? '') !== 'started') {
        $pdo->prepare("UPDATE system_lock SET user_qr = NULL, expires_at = 0 WHERE id = 1 AND user_qr = ?")->execute([$qr]);
        echo json_encode(['status' => 'error', 'message' => 'Scanner is offline. Please try again later.']);
        exit;
    }
    echo json_encode(['status' => 'success']);
}
elseif ($action === 'release_lock') {
    $user = require_resident($pdo);
    $qr = $user['qr_code'];
    $lock = machine_lock($pdo);

    $release = $pdo->prepare("UPDATE system_lock SET user_qr = NULL, expires_at = 0 WHERE id = 1 AND user_qr = ?");
    $release->execute([$qr]);
    if ($release->rowCount() > 0) {
        // Stops new detections (closes the native window on the Pi). A bottle that is still
        // being verified finishes and is credited to this resident.
        scanner('stop?owner=' . urlencode($qr));
    }
    credit_verified_bottles($pdo);

    $held = $lock && $lock['user_qr'] === $qr;
    $totals = $held ? session_totals($pdo, $user['id'], (int)$lock['started_at']) : ['bottles' => 0, 'points' => 0];
    echo json_encode(['status' => 'success', 'balance' => (float)current_user($pdo)['total_points']] + $totals);
}

// ==========================================
// PERSONNEL AUTHENTICATION & ADMIN CONTROLS
// ==========================================
elseif ($action === 'personnel_login') {
    $data = json_body();
    $user = input_str($data, 'username');
    $pass = input_str($data, 'password');

    $stmt = $pdo->prepare("SELECT * FROM personnel WHERE username = ?");
    $stmt->execute([$user]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && personnel_password_matches($pass, $admin['password_hash'])) {
        // Replaces a plain-text password from the original SQL dump with a hash
        if (password_needs_rehash($admin['password_hash'], PASSWORD_DEFAULT)) {
            $rehash = $pdo->prepare("UPDATE personnel SET password_hash = ? WHERE id = ?");
            $rehash->execute([password_hash($pass, PASSWORD_DEFAULT), $admin['id']]);
        }
        session_regenerate_id(true);
        $_SESSION['personnel_logged_in'] = true;
        $_SESSION['personnel_id'] = (int)$admin['id'];
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid credentials']);
    }
}
elseif ($action === 'logout') {
    unset($_SESSION['personnel_logged_in'], $_SESSION['personnel_id']);
    session_destroy();
    echo json_encode(['status' => 'success']);
}
elseif ($action === 'update_admin') {
    require_personnel();
    $data = json_body();
    $user = trim(input_str($data, 'username'));
    $pass = input_str($data, 'password');
    $current = input_str($data, 'current_password');
    $admin_id = $_SESSION['personnel_id'] ?? 1; // Sessions from before this update only stored the login flag

    if ($user === '' || $pass === '') {
        echo json_encode(['status' => 'error', 'message' => 'Missing fields']);
        exit;
    }
    if (strlen($user) > 50) {
        echo json_encode(['status' => 'error', 'message' => 'Username must be 50 characters or fewer.']);
        exit;
    }
    if (strlen($pass) < 8) {
        echo json_encode(['status' => 'error', 'message' => 'New password must be at least 8 characters.']);
        exit;
    }

    // Re-check the current password so a hijacked session can't take over the account
    $stmt = $pdo->prepare("SELECT password_hash FROM personnel WHERE id = ?");
    $stmt->execute([$admin_id]);
    $stored = $stmt->fetchColumn();
    if ($stored === false || !personnel_password_matches($current, $stored)) {
        echo json_encode(['status' => 'error', 'message' => 'Current password is incorrect.']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE personnel SET username = ?, password_hash = ? WHERE id = ?");
    $stmt->execute([$user, password_hash($pass, PASSWORD_DEFAULT), $admin_id]);
    echo json_encode(['status' => 'success']);
}
elseif ($action === 'get_all_history') {
    require_personnel();
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
elseif ($action === 'yolo_poll') {
    // Credits verified bottles, then reports this resident's running session total
    $user = require_resident($pdo);
    $scan = credit_verified_bottles($pdo);
    $lock = machine_lock($pdo);
    if (!$lock || $lock['user_qr'] !== $user['qr_code'] || (int)$lock['expires_at'] <= time()) {
        // The session is over: released, expired, or the machine went to someone else
        echo json_encode(['status' => 'ended']);
        exit;
    }
    echo json_encode([
        'status' => 'success',
        // null when the scanner can't be reached right now, so the page keeps the session open
        'active' => $scan ? (!empty($scan['active']) && ($scan['owner'] ?? null) === $user['qr_code']) : null,
        'busy' => $scan ? !empty($scan['busy']) : false,
        'balance' => (float)current_user($pdo)['total_points']
    ] + session_totals($pdo, $user['id'], (int)$lock['started_at']));
}

// ==========================================
// REDEEM
// ==========================================
elseif ($action === 'redeem') {
    require_personnel();
    $data = json_body();
    $qr = input_str($data, 'qr');

    // The balance check and the deduction are one statement, so a double tap (or two devices)
    // can't redeem the same points twice
    $pdo->beginTransaction();
    $update = $pdo->prepare("UPDATE users SET total_points = total_points - 100 WHERE qr_code = ? AND total_points >= 100");
    $update->execute([$qr]);

    if ($update->rowCount() === 1) {
        $insert = $pdo->prepare("INSERT INTO redemptions (user_id, personnel_id, reward_item, points_deducted) SELECT id, ?, '100 Points Reward', 100 FROM users WHERE qr_code = ?");
        $insert->execute([$_SESSION['personnel_id'] ?? null, $qr]);
        $pdo->commit();

        $balance = $pdo->prepare("SELECT total_points FROM users WHERE qr_code = ?");
        $balance->execute([$qr]);
        echo json_encode(['status' => 'success', 'balance' => (float)$balance->fetchColumn()]);
    } else {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Insufficient points or user not found.']);
    }
}
else {
    echo json_encode(['status' => 'error', 'message' => 'Unknown action']);
}
?>
