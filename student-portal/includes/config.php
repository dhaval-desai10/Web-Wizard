<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'student_portal');
define('DB_USER', 'root');
define('DB_PASS', '');

// Admin credentials (hardcoded)
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'admin123');

// Create database connection
function getConnection() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>