<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/config/database.php';

// Settings
$username = 'admin';
$password = 'admin123';
$email = 'admin@snhs.edu.ph';
$first_name = 'System';
$last_name = 'Administrator';
$school_id = 'ADMIN001';

// Check if already exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$username]);
if ($stmt->fetch()) {
    die("Admin account already exists!<br>Try logging in with username: <strong>$username</strong>");
}

// Hash password
$hashed = password_hash($password, PASSWORD_DEFAULT);

// Insert
$stmt = $pdo->prepare("INSERT INTO users (role, username, password, email, first_name, last_name, school_id) VALUES ('admin', ?, ?, ?, ?, ?, ?)");
$stmt->execute([$username, $hashed, $email, $first_name, $last_name, $school_id]);

echo "Admin account created successfully!<br>";
echo "Username: <strong>$username</strong><br>";
echo "Password: <strong>$password</strong>";
?>