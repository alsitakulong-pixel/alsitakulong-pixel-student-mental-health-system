<?php
// ============================================
// ADMIN CHAT - DIRECT MESSAGING + MONITOR
// ============================================

require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/security.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/chat_functions.php';

requireRole('admin');

$userId = $_SESSION['user_id'];
$user = getCurrentUser($pdo);

$tab = $_GET['tab'] ?? 'direct';
$chatWith = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$user1 = isset($_GET['user1']) ? intval($_GET['user1']) : 0;
$user2 = isset($_GET['user2']) ? intval($_GET['user2']) : 0;
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';

// ============================================
// HANDLE SEND MESSAGE
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $receiverId = intval($_POST['receiver_id']);
    $message = trim($_POST['message']);
    $returnTab = $_POST['return_tab'] ?? 'direct';
    
    if (!empty($message) && $receiverId > 0) {
        sendMessage($pdo, $userId, $receiverId, $message);
        
        // Notify receiver
        $stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
        $stmt->execute([$receiverId]);
        $receiver = $stmt->fetch();
        
        $stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $sender = $stmt->fetch();
        
        createNotification($pdo, $receiverId, 'New Message from Admin', 'You received a message from ' . $sender['first_name'] . ' ' . $sender['last_name'] . ' (Admin).', 'info');
        
        redirect(BASE_URL . '/admin/chat/index.php?tab=' . $returnTab . '&user_id=' . $receiverId);
    }
}

// ============================================
// LOAD DATA
// ============================================

// Admin's own direct chats
$directContacts = getAdminDirectChats($pdo, $userId);

// All student<->counselor conversations (monitor)
$conversations = getAllConversations($pdo);

// Filter monitor conversations by search
if ($searchQuery) {
    $conversations = array_filter($conversations, function($c) use ($searchQuery) {
        $search = strtolower($searchQuery);
        return strpos(strtolower($c['u1_first'] . ' ' . $c['u1_last']), $search) !== false
            || strpos(strtolower($c['u2_first'] . ' ' . $c['u2_last']), $search) !== false;
    });
}

// All students + counselors (for new chat picker)
$stmt = $pdo->prepare("
    SELECT id, first_name, last_name, role, school_id, grade_level, section 
    FROM users 
    WHERE id != ? AND status = 'active' AND role IN ('student', 'counselor')
    ORDER BY role ASC, first_name ASC
");
$stmt->execute([$userId]);
$allUsers = $stmt->fetchAll();

// Filter users by search (for picker)
$filteredUsers = $allUsers;
if ($searchQuery) {
    $filteredUsers = array_filter($allUsers, function($u) use ($searchQuery) {
        $search = strtolower($searchQuery);
        return strpos(strtolower($u['first_name'] . ' ' . $u['last_name']), $search) !== false
            || strpos(strtolower($u['school_id'] ?? ''), $search) !== false;
    });
}

// ============================================
// LOAD ACTIVE CONVERSATION
// ============================================

$conversation = [];
$chatPartner = null;
$lastMessageId = 0;

// Direct chat
if ($tab === 'direct' && $chatWith > 0) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND status = 'active'");
    $stmt->execute([$chatWith]);
    $chatPartner = $stmt->fetch();
    
    if ($chatPartner) {
        $conversation = getConversation($pdo, $userId, $chatWith, 500);
        markMessagesRead($pdo, $userId, $chatWith);
        
        if (count($conversation) > 0) {
            $last = end($conversation);
            $lastMessageId = $last['id'];
        }
    }
}

// Monitor conversation
$monitorConversation = [];
$user1Data = null;
$user2Data = null;
if ($tab === 'monitor' && $user1 > 0 && $user2 > 0) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user1]);
    $user1Data = $stmt->fetch();
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user2]);
    $user2Data = $stmt->fetch();
    
    if ($user1Data && $user2Data) {
        $monitorConversation = getConversation($pdo, $user1, $user2, 500);
    }
}

$page_title = 'Admin Chat';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/header.php';
?>

<style>
.chat-container {
    display: flex;
    height: calc(100vh - 220px);
    min-height: 550px;
    background: var(--card);
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}
.chat-sidebar {
    width: 340px;
    background: linear-gradient(135deg, #F3E8FF 0%, #DBEAFE 100%);
    border-right: 2px solid rgba(139, 92, 246, 0.15);
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
}
[data-theme="dark"] .chat-sidebar {
    background: linear-gradient(135deg, #2D0A0A 0%, #450A0A 100%);
    border-right-color: #7F1D1D;
}
.chat-tabs {
    display: flex;
    background: rgba(255,255,255,0.5);
    border-bottom: 2px solid rgba(139, 92, 246, 0.15);
    padding: 8px 8px 0;
    gap: 4px;
}
[data-theme="dark"] .chat-tabs {
    background: rgba(0,0,0,0.2);
    border-bottom-color: #7F1D1D;
}
.chat-tab {
    flex: 1;
    padding: 12px 10px;
    text-align: center;
    cursor: pointer;
    border-radius: 10px 10px 0 0;
    font-weight: 600;
    font-size: 0.82rem;
    color: var(--text-secondary);
    text-decoration: none;
    transition: all 0.2s;
    border: 2px solid transparent;
    border-bottom: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}
.chat-tab:hover {
    background: rgba(139, 92, 246, 0.1);
    color: var(--primary);
}
.chat-tab.active {
    background: var(--card);
    color: var(--primary);
    border-color: rgba(139, 92, 246, 0.2);
    font-weight: 700;
}
[data-theme="dark"] .chat-tab.active {
    color: #FCA5A5;
    border-color: #7F1D1D;
}
.chat-tab .tab-badge {
    background: #EF4444;
    color: #fff;
    border-radius: 50px;
    min-width: 18px;
    height: 18px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.65rem;
    font-weight: 700;
    padding: 0 5px;
}
.chat-search-area {
    padding: 12px;
    border-bottom: 1px solid rgba(139, 92, 246, 0.1);
}
[data-theme="dark"] .chat-search-area {
    border-bottom-color: #7F1D1D;
}
.chat-search-area input {
    width: 100%;
    padding: 8px 14px;
    border-radius: 20px;
    border: 1px solid rgba(139, 92, 246, 0.2);
    background: #fff;
    font-size: 0.85rem;
}
[data-theme="dark"] .chat-search-area input {
    background: #2D0A0A;
    border-color: #7F1D1D;
    color: #FECACA;
}
.chat-list {
    flex: 1;
    overflow-y: auto;
}
.chat-list-header {
    padding: 12px 16px 8px;
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--text-secondary);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.new-chat-btn {
    background: linear-gradient(135deg, #8B5CF6, #EC4899);
    color: #fff;
    border: none;
    border-radius: 20px;
    padding: 4px 12px;
    font-size: 0.68rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
    text-transform: none;
    letter-spacing: 0.3px;
}
.new-chat-btn:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(139, 92, 246, 0.4);
}
[data-theme="dark"] .new-chat-btn {
    background: linear-gradient(135deg, #7F1D1D, #DC2626);
}
.chat-contact {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    text-decoration: none;
    color: var(--text);
    border-bottom: 1px solid rgba(139, 92, 246, 0.06);
    transition: all 0.2s;
    gap: 12px;
}
.chat-contact:hover { background: rgba(139, 92, 246, 0.08); color: var(--text); }
.chat-contact.active {
    background: linear-gradient(135deg, #8B5CF6 0%, #EC4899 100%);
    color: #fff;
}
[data-theme="dark"] .chat-contact.active {
    background: linear-gradient(135deg, #7F1D1D 0%, #DC2626 100%);
}
.chat-avatar {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 700;
    font-size: 0.8rem;
    flex-shrink: 0;
    overflow: hidden;
}
.avatar-student { background: linear-gradient(135deg, #3B82F6, #8B5CF6); }
.avatar-counselor { background: linear-gradient(135deg, #10B981, #34D399); }
.avatar-admin { background: linear-gradient(135deg, #F59E0B, #F97316); }
.chat-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.chat-contact-info {
    flex: 1;
    min-width: 0;
}
.chat-contact-info strong {
    display: block;
    font-size: 0.85rem;
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.chat-contact-info small {
    font-size: 0.72rem;
    opacity: 0.7;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: block;
}
.role-badge {
    display: inline-block;
    padding: 1px 6px;
    border-radius: 6px;
    font-size: 0.6rem;
    font-weight: 700;
    text-transform: uppercase;
    margin-left: 4px;
}
.role-student { background: #DBEAFE; color: #1E40AF; }
.role-counselor { background: #D1FAE5; color: #065F46; }
[data-theme="dark"] .role-student,
[data-theme="dark"] .role-counselor { background: #7F1D1D; color: #FECACA; }
.unread-badge {
    background: #EF4444;
    color: #fff;
    border-radius: 50%;
    min-width: 22px;
    height: 22px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    font-weight: 700;
    padding: 0 6px;
}
.monitor-badge {
    background: rgba(139, 92, 246, 0.15);
    color: var(--primary);
    border-radius: 6px;
    padding: 2px 6px;
    font-size: 0.6rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
[data-theme="dark"] .monitor-badge {
    background: rgba(220, 38, 38, 0.2);
    color: #FCA5A5;
}
.chat-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: var(--card);
    min-width: 0;
}
.chat-header {
    padding: 14px 22px;
    background: linear-gradient(135deg, #8B5CF6 0%, #EC4899 100%);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}
[data-theme="dark"] .chat-header {
    background: linear-gradient(135deg, #7F1D1D 0%, #DC2626 100%);
}
.chat-header-left {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
    flex: 1;
}
.chat-header-info {
    min-width: 0;
}
.chat-header-info strong {
    display: block;
    font-size: 0.95rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.chat-header-info small {
    font-size: 0.72rem;
    opacity: 0.9;
}
.chat-status {
    display: flex;
    align-items: center;
    font-size: 0.72rem;
    margin-top: 2px;
}
.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #10B981;
    margin-right: 6px;
    animation: pulse-dot 2s infinite;
    box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
}
@keyframes pulse-dot {
    0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
    70% { box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
    100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}
.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 22px;
    background: linear-gradient(135deg, #F3E8FF 0%, #EFF6FF 100%);
    scroll-behavior: smooth;
}
[data-theme="dark"] .chat-messages {
    background: linear-gradient(135deg, #1A0505 0%, #2D0A0A 100%);
}
.message { margin-bottom: 14px; display: flex; animation: slideIn 0.3s ease; }
@keyframes slideIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.message.sent { justify-content: flex-end; }
.message.received { justify-content: flex-start; }
.message-bubble {
    max-width: 70%;
    padding: 11px 16px;
    border-radius: 18px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    word-wrap: break-word;
    word-break: break-word;
    position: relative;
}
.message-bubble .sender-label {
    font-size: 0.68rem;
    font-weight: 700;
    opacity: 0.85;
    display: block;
    margin-bottom: 4px;
}
.message.sent .message-bubble {
    background: linear-gradient(135deg, #8B5CF6 0%, #EC4899 100%);
    color: #fff;
    border-bottom-right-radius: 4px;
}
[data-theme="dark"] .message.sent .message-bubble {
    background: linear-gradient(135deg, #7F1D1D 0%, #DC2626 100%);
}
.message.received .message-bubble {
    background: #fff;
    color: var(--text);
    border-bottom-left-radius: 4px;
    border: 1px solid #DBEAFE;
}
[data-theme="dark"] .message.received .message-bubble {
    background: #2D0A0A;
    color: #FECACA;
    border-color: #7F1D1D;
}
.message-time {
    font-size: 0.68rem;
    opacity: 0.7;
    margin-top: 4px;
    display: block;
}
.chat-input-area {
    padding: 16px 22px;
    background: var(--card);
    border-top: 2px solid rgba(139, 92, 246, 0.1);
}
[data-theme="dark"] .chat-input-area {
    border-top-color: #7F1D1D;
}
.chat-input-form { display: flex; gap: 10px; align-items: center; }
.chat-input-form input {
    flex: 1;
    border-radius: 25px;
    padding: 11px 18px;
    border: 2px solid #DBEAFE;
    background: #F9FAFB;
    color: var(--text);
    transition: all 0.3s;
}
.chat-input-form input:focus {
    outline: none;
    border-color: #8B5CF6;
    box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.15);
    background: #fff;
}
[data-theme="dark"] .chat-input-form input {
    background: #450A0A;
    border-color: #7F1D1D;
    color: #FECACA;
}
.chat-send-btn {
    background: linear-gradient(135deg, #8B5CF6 0%, #EC4899 100%);
    color: #fff;
    border: none;
    border-radius: 50%;
    width: 46px;
    height: 46px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s;
    flex-shrink: 0;
}
[data-theme="dark"] .chat-send-btn {
    background: linear-gradient(135deg, #7F1D1D 0%, #DC2626 100%);
}
.chat-send-btn:hover { transform: scale(1.08); }
.chat-send-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.empty-state {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    color: var(--text-secondary);
    padding: 30px;
    text-align: center;
}
.empty-state i {
    font-size: 4rem;
    margin-bottom: 18px;
    background: linear-gradient(135deg, #8B5CF6, #EC4899);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    opacity: 0.6;
}
.empty-state h5 { color: var(--text); }
.new-message-indicator {
    position: absolute;
    bottom: 90px;
    left: 50%;
    transform: translateX(-50%);
    background: linear-gradient(135deg, #8B5CF6, #EC4899);
    color: #fff;
    padding: 8px 18px;
    border-radius: 20px;
    font-size: 0.8rem;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(139, 92, 246, 0.4);
    display: none;
    z-index: 10;
}
.monitor-notice {
    background: linear-gradient(135deg, #FEF3C7, #FDE68A);
    border-left: 4px solid #F59E0B;
    padding: 10px 16px;
    border-radius: 10px;
    margin: 10px 22px 0;
    font-size: 0.82rem;
    color: #78350F;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}
[data-theme="dark"] .monitor-notice {
    background: linear-gradient(135deg, #78350F, #92400E);
    color: #FED7AA;
}
.monitor-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.monitor-action-btn {
    background: rgba(255,255,255,0.7);
    border: 1px solid rgba(0,0,0,0.1);
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    color: #78350F;
    transition: all 0.2s;
}
.monitor-action-btn:hover {
    background: #fff;
    transform: translateY(-1px);
    color: #78350F;
}
@media (max-width: 768px) {
    .chat-container { flex-direction: column; height: auto; min-height: auto; }
    .chat-sidebar { width: 100%; max-height: 280px; }
    .chat-main { height: 500px; }
    .message-bubble { max-width: 85%; }
    .chat-tabs { font-size: 0.75rem; }
}
</style>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0" style="color: var(--primary);"><i class="fas fa-comments me-2"></i>Admin Chat</h4>
            <p class="text-muted mb-0 small">Send direct messages or monitor student ↔ counselor conversations.</p>
        </div>
        <a href="?tab=direct" class="btn btn-sm btn-primary rounded-pill"><i class="fas fa-plus me-1"></i>New Chat</a>
    </div>

    <div class="chat-container">
        <!-- SIDEBAR -->
        <div class="chat-sidebar">
            <!-- Tabs -->
            <div class="chat-tabs">
                <a href="?tab=direct" class="chat-tab <?php echo $tab === 'direct' ? 'active' : ''; ?>">
                    <i class="fas fa-user-friends"></i> My Messages
                </a>
                <a href="?tab=monitor" class="chat-tab <?php echo $tab === 'monitor' ? 'active' : ''; ?>">
                    <i class="fas fa-eye"></i> Monitor
                </a>
            </div>
            
            <!-- Search -->
            <div class="chat-search-area">
                <form method="GET" action="">
                    <input type="hidden" name="tab" value="<?php echo $tab; ?>">
                    <input type="text" name="q" placeholder="🔍 Search users..." value="<?php echo e($searchQuery); ?>">
                </form>
            </div>
            
            <div class="chat-list">
                <?php if ($tab === 'direct'): ?>
                    
                    <!-- NEW CHAT SECTION -->
                    <div class="chat-list-header">
                        <span>Start New Chat</span>
                    </div>
                    
                    <?php 
                    $shownUsers = 0;
                    foreach ($filteredUsers as $u): 
                        // Skip if already in direct contacts
                        $isExisting = false;
                        foreach ($directContacts as $dc) {
                            if ($dc['id'] == $u['id']) { $isExisting = true; break; }
                        }
                        if ($isExisting) continue;
                        $shownUsers++;
                    ?>
                        <a href="?tab=direct&user_id=<?php echo $u['id']; ?>" class="chat-contact <?php echo ($chatWith == $u['id']) ? 'active' : ''; ?>">
                            <div class="chat-avatar avatar-<?php echo $u['role']; ?>">
                                <?php echo strtoupper(substr($u['first_name'], 0, 1) . substr($u['last_name'], 0, 1)); ?>
                            </div>
                            <div class="chat-contact-info">
                                <strong>
                                    <?php echo e($u['first_name'] . ' ' . $u['last_name']); ?>
                                    <span class="role-badge role-<?php echo $u['role']; ?>"><?php echo $u['role']; ?></span>
                                </strong>
                                <small><?php echo e($u['school_id'] ?? ''); ?></small>
                            </div>
                        </a>
                    <?php endforeach; ?>
                    
                    <?php if ($shownUsers === 0): ?>
                        <div class="text-center text-muted py-2 px-3" style="font-size: 0.75rem;">
                            <small>All users have active chats below.</small>
                        </div>
                    <?php endif; ?>
                    
                    <!-- EXISTING DIRECT CHATS -->
                    <div class="chat-list-header mt-2">
                        <span>Active Chats (<?php echo count($directContacts); ?>)</span>
                    </div>
                    
                    <?php if (count($directContacts) > 0): ?>
                        <?php foreach ($directContacts as $contact): ?>
                            <a href="?tab=direct&user_id=<?php echo $contact['id']; ?>" class="chat-contact <?php echo ($chatWith == $contact['id']) ? 'active' : ''; ?>">
                                <div class="chat-avatar avatar-<?php echo $contact['role']; ?>">
                                    <?php echo strtoupper(substr($contact['first_name'], 0, 1) . substr($contact['last_name'], 0, 1)); ?>
                                </div>
                                <div class="chat-contact-info">
                                    <strong>
                                        <?php echo e($contact['first_name'] . ' ' . $contact['last_name']); ?>
                                        <span class="role-badge role-<?php echo $contact['role']; ?>"><?php echo $contact['role']; ?></span>
                                    </strong>
                                    <small>
                                        <?php echo $contact['last_message'] ? date('M d, h:i A', strtotime($contact['last_message'])) : 'No messages'; ?>
                                    </small>
                                </div>
                                <?php if ($contact['unread_count'] > 0): ?>
                                    <span class="unread-badge"><?php echo $contact['unread_count']; ?></span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-3 px-3">
                            <i class="fas fa-comments fa-2x mb-2" style="opacity: 0.3;"></i>
                            <p class="mb-0" style="font-size: 0.78rem;">No active chats. Pick a user above to start.</p>
                        </div>
                    <?php endif; ?>
                    
                <?php elseif ($tab === 'monitor'): ?>
                    
                    <div class="chat-list-header">
                        <span>Student ↔ Counselor Chats (<?php echo count($conversations); ?>)</span>
                    </div>
                    
                    <?php if (count($conversations) > 0): ?>
                        <?php foreach ($conversations as $conv): ?>
                            <a href="?tab=monitor&user1=<?php echo $conv['user1_id']; ?>&user2=<?php echo $conv['user2_id']; ?>" 
                               class="chat-contact <?php echo ($user1 == $conv['user1_id'] && $user2 == $conv['user2_id']) ? 'active' : ''; ?>">
                                <div class="chat-avatar avatar-<?php echo $conv['u1_role']; ?>">
                                    <?php echo strtoupper(substr($conv['u1_first'], 0, 1) . substr($conv['u1_last'], 0, 1)); ?>
                                </div>
                                <div class="chat-contact-info">
                                    <strong>
                                        <?php echo e($conv['u1_first'] . ' ' . $conv['u1_last']); ?>
                                        <span class="role-badge role-<?php echo $conv['u1_role']; ?>"><?php echo $conv['u1_role']; ?></span>
                                    </strong>
                                    <small>
                                        ↔ <?php echo e($conv['u2_first'] . ' ' . $conv['u2_last']); ?> (<?php echo $conv['u2_role']; ?>)
                                    </small>
                                    <small style="opacity: 0.6; font-style: italic; display: block; margin-top: 2px;">
                                        "<?php echo e(substr($conv['last_message'] ?? '', 0, 30)); ?><?php echo strlen($conv['last_message'] ?? '') > 30 ? '...' : ''; ?>"
                                    </small>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-4 px-3">
                            <i class="fas fa-comment-slash fa-2x mb-2" style="opacity: 0.3;"></i>
                            <p class="mb-0" style="font-size: 0.8rem;">No student-counselor conversations yet.</p>
                        </div>
                    <?php endif; ?>
                    
                <?php endif; ?>
            </div>
        </div>
        
        <!-- MAIN CHAT AREA -->
        <div class="chat-main" style="position: relative;">
            
            <!-- DIRECT CHAT VIEW -->
            <?php if ($tab === 'direct' && $chatPartner): ?>
                <div class="chat-header">
                    <div class="chat-header-left">
                        <div class="chat-avatar avatar-<?php echo $chatPartner['role']; ?>" style="background: rgba(255,255,255,0.3);">
                            <?php echo strtoupper(substr($chatPartner['first_name'], 0, 1) . substr($chatPartner['last_name'], 0, 1)); ?>
                        </div>
                        <div class="chat-header-info">
                            <strong>
                                <?php echo e($chatPartner['first_name'] . ' ' . $chatPartner['last_name']); ?>
                                <span class="role-badge role-<?php echo $chatPartner['role']; ?>" style="background: rgba(255,255,255,0.3); color: #fff;">
                                    <?php echo $chatPartner['role']; ?>
                                </span>
                            </strong>
                            <div class="chat-status"><span class="status-dot"></span> Active</div>
                        </div>
                    </div>
                </div>
                
                <div class="chat-messages" id="chatMessages">
                    <?php if (count($conversation) > 0): ?>
                        <?php foreach ($conversation as $msg): ?>
                            <div class="message <?php echo $msg['sender_id'] == $userId ? 'sent' : 'received'; ?>" data-id="<?php echo $msg['id']; ?>">
                                <div class="message-bubble">
                                    <?php echo nl2br(e($msg['message'])); ?>
                                    <small class="message-time"><?php echo date('M d, h:i A', strtotime($msg['created_at'])); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-comments fa-3x mb-3" style="opacity: 0.3;"></i>
                            <p>No messages yet. Say hello to <?php echo e($chatPartner['first_name']); ?>! 👋</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="new-message-indicator" id="newMsgIndicator" onclick="scrollToBottom(true)">
                    <i class="fas fa-arrow-down me-1"></i> New message
                </div>
                
                <div class="chat-input-area">
                    <form class="chat-input-form" id="chatForm" onsubmit="return sendDirectMessage(event);">
                        <input type="hidden" name="receiver_id" value="<?php echo $chatPartner['id']; ?>">
                        <input type="hidden" name="return_tab" value="direct">
                        <input type="text" name="message" id="messageInput" placeholder="Type your message to <?php echo e($chatPartner['first_name']); ?>..." required autocomplete="off" autofocus>
                        <button type="submit" class="chat-send-btn" id="sendBtn" title="Send">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
                
            <!-- MONITOR VIEW -->
            <?php elseif ($tab === 'monitor' && $user1Data && $user2Data): ?>
                <div class="chat-header">
                    <div class="chat-header-left">
                        <div class="chat-avatar avatar-admin" style="background: rgba(255,255,255,0.3);">
                            <i class="fas fa-eye"></i>
                        </div>
                        <div class="chat-header-info">
                            <strong>
                                <?php echo e($user1Data['first_name'] . ' ' . $user1Data['last_name']); ?>
                                ↔
                                <?php echo e($user2Data['first_name'] . ' ' . $user2Data['last_name']); ?>
                            </strong>
                            <small><?php echo count($monitorConversation); ?> messages</small>
                        </div>
                    </div>
                    <span class="badge bg-light text-dark"><i class="fas fa-eye me-1"></i>Read-only</span>
                </div>
                
                <div class="monitor-notice">
                    <div>
                        <i class="fas fa-info-circle me-1"></i>
                        You are monitoring this conversation. To send a message, start a direct chat.
                    </div>
                    <div class="monitor-actions">
                        <a href="?tab=direct&user_id=<?php echo $user1Data['id']; ?>" class="monitor-action-btn">
                            <i class="fas fa-paper-plane me-1"></i>Message <?php echo e($user1Data['first_name']); ?>
                        </a>
                        <a href="?tab=direct&user_id=<?php echo $user2Data['id']; ?>" class="monitor-action-btn">
                            <i class="fas fa-paper-plane me-1"></i>Message <?php echo e($user2Data['first_name']); ?>
                        </a>
                    </div>
                </div>
                
                <div class="chat-messages" id="monitorMessages">
                    <?php if (count($monitorConversation) > 0): ?>
                        <?php foreach ($monitorConversation as $msg): ?>
                            <?php 
                                $isUser1Sender = ($msg['sender_id'] == $user1Data['id']);
                                $sender = $isUser1Sender ? $user1Data : $user2Data;
                            ?>
                            <div class="message <?php echo $isUser1Sender ? 'received' : 'sent'; ?>">
                                <div class="message-bubble">
                                    <span class="sender-label">
                                        <?php echo e($sender['first_name'] . ' ' . $sender['last_name']); ?>
                                        <span class="role-badge role-<?php echo $sender['role']; ?>" style="background: rgba(255,255,255,0.3); color: inherit;">
                                            <?php echo ucfirst($sender['role']); ?>
                                        </span>
                                    </span>
                                    <?php echo nl2br(e($msg['message'])); ?>
                                    <small class="message-time"><?php echo date('M d, Y h:i A', strtotime($msg['created_at'])); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-5">
                            <p>No messages in this conversation.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
            <!-- EMPTY STATE -->
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-comments"></i>
                    <?php if ($tab === 'direct'): ?>
                        <h5>Select a user to start chatting</h5>
                        <p>Choose from the list on the left or search for a specific student/counselor.</p>
                    <?php else: ?>
                        <h5>Select a conversation to monitor</h5>
                        <p>View all student ↔ counselor conversations here. Click one to see the full chat.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
        </div>
    </div>
</div>

<script>
const myId = <?php echo $userId; ?>;
const chatPartnerId = <?php echo $chatPartner ? $chatPartner['id'] : 0; ?>;
const lastInitialId = <?php echo $lastMessageId; ?>;
let lastMessageId = lastInitialId;
const apiUrl = '<?php echo BASE_URL; ?>/includes/chat_api.php';
const chatMessages = document.getElementById('chatMessages');
const messageInput = document.getElementById('messageInput');
const sendBtn = document.getElementById('sendBtn');
let isAtBottom = true;

if (chatMessages) {
    chatMessages.addEventListener('scroll', function() {
        const threshold = 100;
        isAtBottom = (chatMessages.scrollHeight - chatMessages.scrollTop - chatMessages.clientHeight) < threshold;
        if (isAtBottom) {
            const indicator = document.getElementById('newMsgIndicator');
            if (indicator) indicator.style.display = 'none';
        }
    });
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function scrollToBottom(force) {
    if (chatMessages && (isAtBottom || force)) {
        chatMessages.scrollTop = chatMessages.scrollHeight;
        const indicator = document.getElementById('newMsgIndicator');
        if (indicator) indicator.style.display = 'none';
    }
}

function sendDirectMessage(e) {
    e.preventDefault();
    const message = messageInput.value.trim();
    if (!message || !chatPartnerId) return false;
    
    sendBtn.disabled = true;
    messageInput.disabled = true;
    
    const formData = new FormData();
    formData.append('receiver_id', chatPartnerId);
    formData.append('message', message);
    
    fetch(apiUrl + '?action=send_message', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            appendMessage(data.message);
            lastMessageId = data.message.id;
            messageInput.value = '';
            scrollToBottom(true);
        } else {
            alert(data.error || 'Failed to send');
        }
    })
    .catch(err => {
        console.error(err);
        alert('Connection error. Please try again.');
    })
    .finally(() => {
        sendBtn.disabled = false;
        messageInput.disabled = false;
        messageInput.focus();
    });
    
    return false;
}

function appendMessage(msg) {
    if (!chatMessages) return;
    const emptyState = chatMessages.querySelector('.text-center');
    if (emptyState) emptyState.remove();
    
    const div = document.createElement('div');
    div.className = 'message ' + (msg.is_mine ? 'sent' : 'received');
    div.dataset.id = msg.id;
    div.innerHTML = `
        <div class="message-bubble">
            ${escapeHtml(msg.message).replace(/\n/g, '<br>')}
            <small class="message-time">${msg.created_at}</small>
        </div>
    `;
    chatMessages.appendChild(div);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function pollMessages() {
    if (!chatPartnerId) return;
    fetch(apiUrl + '?action=get_messages&user_id=' + chatPartnerId + '&last_id=' + lastMessageId)
        .then(r => r.json())
        .then(data => {
            if (data.success && data.messages.length > 0) {
                const wasAtBottom = isAtBottom;
                data.messages.forEach(msg => {
                    if (!chatMessages.querySelector(`[data-id="${msg.id}"]`)) {
                        appendMessage(msg);
                        lastMessageId = Math.max(lastMessageId, msg.id);
                    }
                });
                if (wasAtBottom) {
                    scrollToBottom(true);
                } else {
                    const ind = document.getElementById('newMsgIndicator');
                    if (ind) ind.style.display = 'block';
                }
            }
        })
        .catch(err => console.log('Poll error:', err));
}

<?php if ($chatPartner): ?>
setInterval(pollMessages, 2000);
setTimeout(pollMessages, 500);
<?php endif; ?>

if (messageInput) messageInput.focus();
</script>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/footer.php'; ?>