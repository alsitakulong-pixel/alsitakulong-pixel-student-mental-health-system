<?php
// ============================================
// CHAT SYSTEM HELPER FUNCTIONS
// ============================================

// Send a message
function sendMessage($pdo, $senderId, $receiverId, $message) {
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
    return $stmt->execute([$senderId, $receiverId, $message]);
}

// Get conversation between two users
function getConversation($pdo, $userId, $otherUserId, $limit = 100) {
    $stmt = $pdo->prepare("
        SELECT m.*, 
               u.first_name as sender_first, u.last_name as sender_last
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE (m.sender_id = ? AND m.receiver_id = ?) 
           OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.created_at ASC
        LIMIT " . intval($limit)
    );
    $stmt->execute([$userId, $otherUserId, $otherUserId, $userId]);
    return $stmt->fetchAll();
}

// Mark messages as read
function markMessagesRead($pdo, $userId, $fromUserId) {
    $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND sender_id = ?");
    $stmt->execute([$userId, $fromUserId]);
}

// Get unread message count from a specific user
function getUnreadFromUser($pdo, $userId, $fromUserId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND sender_id = ? AND is_read = 0");
    $stmt->execute([$userId, $fromUserId]);
    return $stmt->fetchColumn();
}

// Get total unread messages for a user
function getTotalUnreadMessages($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return $stmt->fetchColumn();
}

// Get chat contacts for a user
function getChatContacts($pdo, $userId, $role) {
    if ($role === 'student') {
        $stmt = $pdo->prepare("
            SELECT u.id, u.first_name, u.last_name, u.email,
                   (SELECT COUNT(*) FROM messages WHERE sender_id = u.id AND receiver_id = ? AND is_read = 0) as unread_count,
                   (SELECT MAX(created_at) FROM messages WHERE (sender_id = u.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = u.id)) as last_message
            FROM users u
            WHERE u.role = 'counselor' AND u.status = 'active'
            ORDER BY last_message DESC, u.first_name ASC
        ");
        $stmt->execute([$userId, $userId, $userId]);
        return $stmt->fetchAll();
    } 
    elseif ($role === 'counselor') {
        $stmt = $pdo->prepare("
            SELECT DISTINCT u.id, u.first_name, u.last_name, u.email, u.school_id,
                   (SELECT COUNT(*) FROM messages WHERE sender_id = u.id AND receiver_id = ? AND is_read = 0) as unread_count,
                   (SELECT MAX(created_at) FROM messages WHERE (sender_id = u.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = u.id)) as last_message
            FROM users u
            LEFT JOIN counseling_requests cr ON cr.student_id = u.id AND cr.counselor_id = ?
            LEFT JOIN messages m ON (m.sender_id = u.id AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = u.id)
            WHERE u.role = 'student' AND u.status = 'active'
              AND (cr.id IS NOT NULL OR m.id IS NOT NULL)
            ORDER BY last_message DESC, u.first_name ASC
        ");
        $stmt->execute([$userId, $userId, $userId, $userId, $userId, $userId]);
        return $stmt->fetchAll();
    }
    elseif ($role === 'admin') {
        $stmt = $pdo->prepare("
            SELECT DISTINCT u.id, u.first_name, u.last_name, u.role, u.email,
                   (SELECT COUNT(*) FROM messages WHERE (sender_id = u.id OR receiver_id = u.id)) as total_messages,
                   (SELECT MAX(created_at) FROM messages WHERE sender_id = u.id OR receiver_id = u.id) as last_message
            FROM users u
            WHERE u.status = 'active'
              AND EXISTS (SELECT 1 FROM messages WHERE sender_id = u.id OR receiver_id = u.id)
            ORDER BY last_message DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    return [];
}

// ============================================
// ADMIN: Get admin's own direct chat contacts
// (users the admin has chatted with directly)
// ============================================
function getAdminDirectChats($pdo, $adminId) {
    $stmt = $pdo->prepare("
        SELECT DISTINCT u.id, u.first_name, u.last_name, u.role, u.school_id,
               (SELECT COUNT(*) FROM messages WHERE sender_id = u.id AND receiver_id = ? AND is_read = 0) as unread_count,
               (SELECT MAX(created_at) FROM messages WHERE (sender_id = u.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = u.id)) as last_message
        FROM users u
        JOIN messages m ON (m.sender_id = u.id AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = u.id)
        WHERE u.id != ? AND u.status = 'active' AND u.role IN ('student', 'counselor')
        ORDER BY last_message DESC
    ");
    $stmt->execute([$adminId, $adminId, $adminId, $adminId, $adminId, $adminId]);
    return $stmt->fetchAll();
}

// ============================================
// ADMIN: Get all student<->counselor conversations
// (for monitoring purposes - excludes admin)
// ============================================
function getAllConversations($pdo) {
    // Get admin ids to exclude
    $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'admin'");
    $stmt->execute();
    $adminIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $adminIds = array_map('intval', $adminIds);
    
    if (empty($adminIds)) {
        $adminIds = [0];
    }
    
    // Build exclusion clause
    $placeholders = implode(',', array_fill(0, count($adminIds), '?'));
    $params = [];
    
    $stmt = $pdo->prepare("
        SELECT 
            LEAST(m.sender_id, m.receiver_id) as user1_id,
            GREATEST(m.sender_id, m.receiver_id) as user2_id,
            u1.first_name as u1_first, u1.last_name as u1_last, u1.role as u1_role,
            u2.first_name as u2_first, u2.last_name as u2_last, u2.role as u2_role,
            COUNT(m.id) as message_count,
            MAX(m.created_at) as last_message_time,
            (SELECT message FROM messages WHERE ((sender_id = user1_id AND receiver_id = user2_id) OR (sender_id = user2_id AND receiver_id = user1_id)) ORDER BY created_at DESC LIMIT 1) as last_message
        FROM messages m
        JOIN users u1 ON u1.id = LEAST(m.sender_id, m.receiver_id)
        JOIN users u2 ON u2.id = GREATEST(m.sender_id, m.receiver_id)
        WHERE m.sender_id NOT IN ($placeholders)
          AND m.receiver_id NOT IN ($placeholders)
        GROUP BY user1_id, user2_id
        ORDER BY last_message_time DESC
    ");
    
    // Add params twice (for both IN clauses)
    foreach ($adminIds as $id) $params[] = $id;
    foreach ($adminIds as $id) $params[] = $id;
    
    $stmt->execute($params);
    return $stmt->fetchAll();
}
?>