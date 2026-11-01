<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/config/database.php';

$accounts = [
    ['role' => 'admin', 'username' => 'admin', 'password' => 'admin123', 'email' => 'admin@snhs.edu.ph', 'first_name' => 'System', 'last_name' => 'Administrator', 'school_id' => 'ADMIN001'],
    ['role' => 'counselor', 'username' => 'counselor1', 'password' => 'counselor123', 'email' => 'counselor1@snhs.edu.ph', 'first_name' => 'Maria', 'last_name' => 'Santos', 'school_id' => 'COUN001'],
    ['role' => 'student', 'username' => 'student1', 'password' => 'student123', 'email' => 'student1@snhs.edu.ph', 'first_name' => 'Juan', 'last_name' => 'Dela Cruz', 'school_id' => '20250001', 'grade_level' => 'Grade 10', 'section' => 'Rizal']
];

foreach ($accounts as $acc) {
    // Check if exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$acc['username']]);
    if ($stmt->fetch()) {
        // Update password
        $hashed = password_hash($acc['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
        $stmt->execute([$hashed, $acc['username']]);
        echo "Updated password for {$acc['username']}<br>";
    } else {
        // Insert
        $hashed = password_hash($acc['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (role, username, password, email, first_name, last_name, school_id, grade_level, section) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$acc['role'], $acc['username'], $hashed, $acc['email'], $acc['first_name'], $acc['last_name'], $acc['school_id'], $acc['grade_level'] ?? null, $acc['section'] ?? null]);
        echo "Created account {$acc['username']}<br>";
    }
}
?>