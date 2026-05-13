<?php
// config.php - Handles the database connection
$host = 'localhost';
$db   = 'recycoin_db';
$user = 'root'; // Default XAMPP username
$pass = '';     // Default XAMPP password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (\PDOException $e) {
    // Return JSON error if connection fails so the frontend can handle it
    die(json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()]));
}
?>