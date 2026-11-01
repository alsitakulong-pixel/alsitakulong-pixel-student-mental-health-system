<?php
// ============================================
// SECURITY HELPERS - CSRF, VALIDATION, SANITIZATION
// ============================================

// Generate CSRF token
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Get CSRF token
function getCsrfToken() {
    return generateCsrfToken();
}

// Verify CSRF token
function verifyCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    return true;
}

// Validate CSRF on POST
function validateCsrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!verifyCsrfToken($token)) {
            die('Invalid CSRF token!');
        }
    }
}

// Sanitize input (prevent XSS)
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

// Validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Validate date (YYYY-MM-DD)
function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

// Validate time (HH:MM:SS)
function validateTime($time) {
    return preg_match('/^([01]\d|2[0-3]):([0-5]\d):([0-5]\d)$/', $time);
}

// Rate limiting (simple)
function rateLimit($key, $limit = 5, $timeout = 60) {
    $current = time();
    if (!isset($_SESSION['rate_limit'][$key])) {
        $_SESSION['rate_limit'][$key] = ['count' => 0, 'first' => $current];
    }
    
    $data = $_SESSION['rate_limit'][$key];
    if ($current - $data['first'] > $timeout) {
        $_SESSION['rate_limit'][$key] = ['count' => 1, 'first' => $current];
        return true;
    }
    
    if ($data['count'] >= $limit) {
        return false;
    }
    
    $_SESSION['rate_limit'][$key]['count']++;
    return true;
}

// Check for secure password
function validatePassword($password) {
    if (strlen($password) < 8) return false;
    if (!preg_match('/[A-Z]/', $password)) return false;
    if (!preg_match('/[a-z]/', $password)) return false;
    if (!preg_match('/[0-9]/', $password)) return false;
    return true;
}
?>