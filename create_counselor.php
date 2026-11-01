<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/config/database.php';

// Settings
$username = 'counselor1';
$password = 'counselor123';  // gusto mong password
$email = 'counselor1@snhs.edu.ph';
$first_name = 'Maria';
$last_name = 'Santos';
$school_id = 'COUN001';

// Check if already exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$username]);
if ($stmt->fetch()) {
    die("Counselor account already exists!");
}

// Hash password
$hashed = password_hash($password, PASSWORD_DEFAULT);

// Insert
$stmt = $pdo->prepare("INSERT INTO users (role, username, password, email, first_name, last_name, school_id) VALUES ('counselor', ?, ?, ?, ?, ?, ?)");
$stmt->execute([$username, $hashed, $email, $first_name, $last_name, $school_id]);

echo "Counselor account created successfully!<br>";
echo "Username: $username<br>";
echo "Password: $password";
?>