<?php
// ============================================
// DATABASE CONFIGURATION & SITE SETTINGS
// ============================================

// Database credentials (default XAMPP/WAMP)
define('DB_HOST', 'localhost');
define('DB_NAME', 'student_mental_health');
define('DB_USER', 'root');       // change if needed
define('DB_PASS', '');           // empty if no password

// Base URL - mahalaga ito para sa tamang link ng images, CSS, JS, etc.
// Kung nasa 'student-mental-health-system' folder ka sa htdocs, ito ay tama.
// Gumamit ng conditional para hindi mag-error kung na-define na sa ibang file.
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/student-mental-health-system');
}

// Start session kung hindi pa naka-start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// DATABASE CONNECTION (PDO)
// ============================================
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>