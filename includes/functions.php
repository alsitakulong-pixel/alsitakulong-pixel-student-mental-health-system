<?php
// ============================================
// HELPER FUNCTIONS
// ============================================

// Escape output for XSS protection
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check user role
function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

// Require login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit();
    }
}

// Require specific role
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        header('Location: ' . BASE_URL . '/index.php');
        exit();
    }
}

// Get current user from database
function getCurrentUser($pdo) {
    if (!isLoggedIn()) return null;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Redirect to a URL
function redirect($url) {
    header('Location: ' . $url);
    exit();
}

// ============================================
// NOTIFICATION FUNCTIONS
// ============================================

// Create a notification
function createNotification($pdo, $userId, $title, $message, $type = 'info') {
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $title, $message, $type]);
}

// Get unread notification count
function getUnreadNotifications($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return $stmt->fetchColumn();
}

// Get all notifications for a user
function getUserNotifications($pdo, $userId, $limit = 10) {
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT " . intval($limit));
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

// Mark notification as read
function markNotificationRead($pdo, $notificationId, $userId) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$notificationId, $userId]);
}

// ============================================
// FLASH MESSAGE HELPERS
// ============================================

function setFlashMessage($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// ============================================
// PROFILE IMAGE HELPERS
// ============================================

/**
 * Get profile image info or fallback to initials avatar
 * @param array $user - User record from database
 * @return array ['type' => 'image'|'initials', 'url' => string, 'initials' => string, 'color' => string]
 */
function getProfileAvatar($user) {
    $initials = strtoupper(substr($user['first_name'] ?? 'U', 0, 1) . substr($user['last_name'] ?? '', 0, 1));
    if (empty($initials)) $initials = 'U';
    
    // Generate a consistent color based on user id
    $colors = [
        'linear-gradient(135deg, #8B5CF6, #EC4899)',
        'linear-gradient(135deg, #3B82F6, #06B6D4)',
        'linear-gradient(135deg, #10B981, #34D399)',
        'linear-gradient(135deg, #F59E0B, #F97316)',
        'linear-gradient(135deg, #EF4444, #F43F5E)',
        'linear-gradient(135deg, #6366F1, #8B5CF6)',
    ];
    $color = $colors[($user['id'] ?? 0) % count($colors)];
    
    if (!empty($user['profile_image'])) {
        $filePath = $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/assets/uploads/profiles/' . $user['profile_image'];
        if (file_exists($filePath)) {
            return [
                'type' => 'image',
                'url' => BASE_URL . '/assets/uploads/profiles/' . $user['profile_image'],
                'initials' => $initials,
                'color' => $color
            ];
        }
    }
    
    return [
        'type' => 'initials',
        'url' => '',
        'initials' => $initials,
        'color' => $color
    ];
}

/**
 * Render profile avatar as HTML
 * @param array $user - User record
 * @param int $size - Size in pixels (default 45)
 * @param string $extraClass - Extra CSS class
 */
function renderAvatar($user, $size = 45, $extraClass = '') {
    $avatar = getProfileAvatar($user);
    $style = "width: {$size}px; height: {$size}px; border-radius: 50%; object-fit: cover; flex-shrink: 0;";
    
    if ($avatar['type'] === 'image') {
        return '<img src="' . e($avatar['url']) . '" alt="Profile" class="avatar-img ' . e($extraClass) . '" style="' . $style . ' border: 2px solid rgba(255,255,255,0.3); background: #fff;">';
    } else {
        $fontSize = round($size * 0.4);
        return '<div class="avatar-initials ' . e($extraClass) . '" style="' . $style . ' background: ' . $avatar['color'] . '; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: ' . $fontSize . 'px; border: 2px solid rgba(255,255,255,0.3);">' . e($avatar['initials']) . '</div>';
    }
}

/**
 * Handle profile image upload
 * @param array $file - $_FILES['profile_image']
 * @param int $userId - User ID
 * @param array $user - Current user record (to delete old image)
 * @return array ['success' => bool, 'filename' => string, 'error' => string]
 */
function handleProfileImageUpload($file, $userId, $user) {
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => false, 'error' => 'No file uploaded.'];
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload failed (error code: ' . $file['error'] . ').'];
    }
    
    // Max 5MB
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'error' => 'File too large. Max 5MB.'];
    }
    
    // Validate extension
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        return ['success' => false, 'error' => 'Invalid file type. Allowed: JPG, PNG, GIF, WEBP.'];
    }
    
    // Validate it's really an image
    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        return ['success' => false, 'error' => 'File is not a valid image.'];
    }
    
    // Create directory if needed
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/assets/uploads/profiles/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            return ['success' => false, 'error' => 'Cannot create upload directory. Please create assets/uploads/profiles/ manually.'];
        }
    }
    
    // Delete old image if exists
    if (!empty($user['profile_image'])) {
        $oldFile = $uploadDir . $user['profile_image'];
        if (file_exists($oldFile)) {
            @unlink($oldFile);
        }
    }
    
    // Generate unique filename
    $filename = 'profile_' . $userId . '_' . time() . '.' . $ext;
    
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        return ['success' => false, 'error' => 'Failed to save file. Check folder permissions.'];
    }
    
    return ['success' => true, 'filename' => $filename, 'error' => ''];
}
?>