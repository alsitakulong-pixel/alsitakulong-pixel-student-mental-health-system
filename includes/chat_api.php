<?php
// ============================================
// CHAT API - Returns JSON for AJAX
// ============================================

require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/chat_functions.php';

header('Content-Type: application/json');

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];

$action = $_GET['action'] ?? '';

// ============================================
// GET MESSAGES (polling)
// ============================================
if ($action === 'get_messages') {
    $otherUserId = intval($_GET['user_id'] ?? 0);
    $lastId = intval($_GET['last_id'] ?? 0);
    
    if ($otherUserId <= 0) {
        echo json_encode(['error' => 'Invalid user']);
        exit;
    }
    
    // Get new messages since last_id
    $stmt = $pdo->prepare("
        SELECT m.id, m.sender_id, m.receiver_id, m.message, m.created_at, m.is_read,
               u.first_name, u.last_name
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE ((m.sender_id = ? AND m.receiver_id = ?) 
           OR (m.sender_id = ? AND m.receiver_id = ?))
           AND m.id > ?
        ORDER BY m.id ASC
        LIMIT 100
    ");
    $stmt->execute([$userId, $otherUserId, $otherUserId, $userId, $lastId]);
    $messages = $stmt->fetchAll();
    
    // Mark incoming as read
    $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND sender_id = ?");
    $stmt->execute([$userId, $otherUserId]);
    
    // Format messages
    $formatted = [];
    foreach ($messages as $msg) {
        $formatted[] = [
            'id' => $msg['id'],
            'sender_id' => $msg['sender_id'],
            'is_mine' => ($msg['sender_id'] == $userId),
            'message' => $msg['message'],
            'created_at' => date('M d, h:i A', strtotime($msg['created_at'])),
            'sender_name' => $msg['first_name'] . ' ' . $msg['last_name']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'messages' => $formatted,
        'last_id' => count($messages) > 0 ? end($messages)['id'] : $lastId
    ]);
    exit;
}

// ============================================
// SEND MESSAGE (AJAX)
// ============================================
if ($action === 'send_message' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $receiverId = intval($_POST['receiver_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    
    if (empty($message) || $receiverId <= 0) {
        echo json_encode(['error' => 'Invalid message']);
        exit;
    }
    
    // Verify receiver exists and is active
    $stmt = $pdo->prepare("SELECT id, role, first_name, last_name FROM users WHERE id = ? AND status = 'active'");
    $stmt->execute([$receiverId]);
    $receiver = $stmt->fetch();
    
    if (!$receiver) {
        echo json_encode(['error' => 'Receiver not found']);
        exit;
    }
    
    // Insert message
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $receiverId, $message]);
    $newId = $pdo->lastInsertId();
    
    // Get sender info for notification
    $stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $sender = $stmt->fetch();
    
    // Send notification
    createNotification($pdo, $receiverId, 'New Message from ' . $sender['first_name'] . ' ' . $sender['last_name'], substr($message, 0, 100), 'info');
    
    // Return the new message
    echo json_encode([
        'success' => true,
        'message' => [
            'id' => $newId,
            'sender_id' => $userId,
            'is_mine' => true,
            'message' => $message,
            'created_at' => date('M d, h:i A'),
            'sender_name' => $sender['first_name'] . ' ' . $sender['last_name']
        ]
    ]);
    exit;
}

// ============================================
// GET UNREAD COUNT
// ============================================
if ($action === 'unread_count') {
    $count = getTotalUnreadMessages($pdo, $userId);
    echo json_encode(['success' => true, 'count' => $count]);
    exit;
}

echo json_encode(['error' => 'Invalid action']);