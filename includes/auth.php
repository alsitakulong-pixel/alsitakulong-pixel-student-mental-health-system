<?php
// Authentication functions
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/functions.php';

// Handle user login
function loginUser($pdo, $username, $password) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        return true;
    }
    return false;
}

// Handle logout
function logoutUser() {
    session_unset();
    session_destroy();
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}
?>