<?php
// ============================================
// COUNSELOR CHAT - LIVE AJAX VERSION
// ============================================

require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/security.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/chat_functions.php';

requireRole('counselor');

$userId = $_SESSION['user_id'];
$user = getCurrentUser($pdo);

$studentId = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;

// Get contacts
$contacts = getChatContacts($pdo, $userId, 'counselor');

// Get conversation if student selected
$conversation = [];
$selectedStudent = null;
$lastMessageId = 0;

if ($studentId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'student' AND status = 'active'");
    $stmt->execute([$studentId]);
    $selectedStudent = $stmt->fetch();
    
    if ($selectedStudent) {
        $conversation = getConversation($pdo, $userId, $studentId);
        markMessagesRead($pdo, $userId, $studentId);
        
        if (count($conversation) > 0) {
            $last = end($conversation);
            $lastMessageId = $last['id'];
        }
    }
}

$page_title = 'Chat with Students';
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
    width: 320px;
    background: linear-gradient(135deg, #F3E8FF 0%, #DBEAFE 100%);
    border-right: 2px solid rgba(139, 92, 246, 0.15);
    overflow-y: auto;
    flex-shrink: 0;
}
[data-theme="dark"] .chat-sidebar {
    background: linear-gradient(135deg, #2D0A0A 0%, #450A0A 100%);
    border-right-color: #7F1D1D;
}
.chat-sidebar-header {
    padding: 20px;
    border-bottom: 2px solid rgba(139, 92, 246, 0.15);
    position: sticky;
    top: 0;
    background: inherit;
    z-index: 10;
}
[data-theme="dark"] .chat-sidebar-header { border-bottom-color: #7F1D1D; }
.chat-contact {
    display: flex;
    align-items: center;
    padding: 15px 20px;
    text-decoration: none;
    color: var(--text);
    border-bottom: 1px solid rgba(139, 92, 246, 0.08);
    transition: all 0.2s;
}
.chat-contact:hover { background: rgba(139, 92, 246, 0.1); }
.chat-contact.active {
    background: linear-gradient(135deg, #8B5CF6 0%, #EC4899 100%);
    color: #fff;
}
[data-theme="dark"] .chat-contact.active {
    background: linear-gradient(135deg, #7F1D1D 0%, #DC2626 100%);
}
.chat-avatar {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background: linear-gradient(135deg, #8B5CF6, #EC4899);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 700;
    margin-right: 12px;
    flex-shrink: 0;
}
.chat-contact-info { flex: 1; min-width: 0; }
.chat-contact-info strong {
    display: block;
    font-size: 0.9rem;
    margin-bottom: 3px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.chat-contact-info small {
    font-size: 0.75rem;
    opacity: 0.7;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: block;
}
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
.chat-main { flex: 1; display: flex; flex-direction: column; background: var(--card); min-width: 0; }
.chat-header {
    padding: 15px 25px;
    background: linear-gradient(135deg, #8B5CF6 0%, #EC4899 100%);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
[data-theme="dark"] .chat-header {
    background: linear-gradient(135deg, #7F1D1D 0%, #DC2626 100%);
}
.chat-status {
    display: flex;
    align-items: center;
    font-size: 0.75rem;
    opacity: 0.9;
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
    padding: 25px;
    background: linear-gradient(135deg, #F3E8FF 0%, #EFF6FF 100%);
    scroll-behavior: smooth;
}
[data-theme="dark"] .chat-messages {
    background: linear-gradient(135deg, #1A0505 0%, #2D0A0A 100%);
}
.message { margin-bottom: 15px; display: flex; animation: slideIn 0.3s ease; }
@keyframes slideIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.message.sent { justify-content: flex-end; }
.message.received { justify-content: flex-start; }
.message-bubble {
    max-width: 70%;
    padding: 12px 18px;
    border-radius: 18px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    word-wrap: break-word;
    word-break: break-word;
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
    font-size: 0.7rem;
    opacity: 0.7;
    margin-top: 5px;
    display: block;
}
.chat-input-area {
    padding: 20px 25px;
    background: var(--card);
    border-top: 2px solid rgba(139, 92, 246, 0.1);
}
[data-theme="dark"] .chat-input-area { border-top-color: #7F1D1D; }
.chat-input-form { display: flex; gap: 10px; }
.chat-input-form input {
    flex: 1;
    border-radius: 25px;
    padding: 12px 20px;
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
[data-theme="dark"] .chat-input-form input:focus {
    background: #2D0A0A;
    border-color: #DC2626;
}
.chat-send-btn {
    background: linear-gradient(135deg, #8B5CF6 0%, #EC4899 100%);
    color: #fff;
    border: none;
    border-radius: 50%;
    width: 48px;
    height: 48px;
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
.chat-send-btn:hover { transform: scale(1.08); box-shadow: 0 6px 20px rgba(139, 92, 246, 0.4); }
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
    margin-bottom: 20px;
    background: linear-gradient(135deg, #8B5CF6, #EC4899);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.new-message-indicator {
    position: absolute;
    bottom: 100px;
    left: 50%;
    transform: translateX(-50%);
    background: linear-gradient(135deg, #8B5CF6, #EC4899);
    color: #fff;
    padding: 8px 20px;
    border-radius: 20px;
    font-size: 0.85rem;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(139, 92, 246, 0.4);
    display: none;
    z-index: 10;
}
@media (max-width: 768px) {
    .chat-container { flex-direction: column; height: auto; min-height: auto; }
    .chat-sidebar { width: 100%; max-height: 220px; }
    .chat-main { height: 500px; }
    .message-bubble { max-width: 85%; }
}
</style>

<div class="container py-4">
    <div class="mb-3">
        <h4 class="fw-bold" style="color: var(--primary);"><i class="fas fa-comments me-2"></i>Chat with Students</h4>
        <p class="text-muted mb-0">Respond to student messages in real-time.</p>
    </div>

    <div class="chat-container">
        <div class="chat-sidebar">
            <div class="chat-sidebar-header">
                <h6 class="mb-0 fw-bold"><i class="fas fa-user-graduate me-2" style="color: var(--primary);"></i>Students</h6>
                <small class="text-muted"><?php echo count($contacts); ?> contacts</small>
            </div>
            
            <?php if (count($contacts) > 0): ?>
                <?php foreach ($contacts as $contact): ?>
                    <a href="?student_id=<?php echo $contact['id']; ?>" class="chat-contact <?php echo $studentId == $contact['id'] ? 'active' : ''; ?>">
                        <div class="chat-avatar">
                            <?php echo strtoupper(substr($contact['first_name'], 0, 1) . substr($contact['last_name'], 0, 1)); ?>
                        </div>
                        <div class="chat-contact-info">
                            <strong><?php echo e($contact['first_name'] . ' ' . $contact['last_name']); ?></strong>
                            <small><?php echo e($contact['school_id'] ?? ''); ?> <?php echo $contact['last_message'] ? '• ' . date('M d, h:i A', strtotime($contact['last_message'])) : ''; ?></small>
                        </div>
                        <?php if ($contact['unread_count'] > 0): ?>
                            <span class="unread-badge"><?php echo $contact['unread_count']; ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="p-3 text-center text-muted">
                    <small>No students with active requests or messages yet.</small>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="chat-main" style="position: relative;">
            <?php if ($selectedStudent): ?>
                <div class="chat-header">
                    <div class="d-flex align-items-center">
                        <div class="chat-avatar" style="background: rgba(255,255,255,0.3); margin-right: 12px;">
                            <?php echo strtoupper(substr($selectedStudent['first_name'], 0, 1) . substr($selectedStudent['last_name'], 0, 1)); ?>
                        </div>
                        <div>
                            <strong><?php echo e($selectedStudent['first_name'] . ' ' . $selectedStudent['last_name']); ?></strong>
                            <div class="chat-status"><span class="status-dot"></span> <?php echo e($selectedStudent['grade_level'] . ' - ' . $selectedStudent['section']); ?></div>
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
                            <p>No messages yet. Send the first message.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="new-message-indicator" id="newMsgIndicator" onclick="scrollToBottom(true)">
                    <i class="fas fa-arrow-down me-1"></i> New message
                </div>
                
                <div class="chat-input-area">
                    <form class="chat-input-form" id="chatForm" onsubmit="return sendMessage(event);">
                        <input type="text" name="message" id="messageInput" placeholder="Type your message..." required autocomplete="off" autofocus>
                        <button type="submit" class="chat-send-btn" id="sendBtn" title="Send">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-comments"></i>
                    <h5>Select a student to start chatting</h5>
                    <p>Choose a student from the list on the left.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
const myId = <?php echo $userId; ?>;
const otherId = <?php echo $studentId; ?>;
const chatMessages = document.getElementById('chatMessages');
const messageInput = document.getElementById('messageInput');
const sendBtn = document.getElementById('sendBtn');
let lastMessageId = <?php echo $lastMessageId; ?>;
let isAtBottom = true;
const apiUrl = '<?php echo BASE_URL; ?>/includes/chat_api.php';

if (chatMessages) {
    chatMessages.addEventListener('scroll', function() {
        const threshold = 100;
        isAtBottom = (chatMessages.scrollHeight - chatMessages.scrollTop - chatMessages.clientHeight) < threshold;
        if (isAtBottom) {
            document.getElementById('newMsgIndicator').style.display = 'none';
        }
    });
}

function scrollToBottom(force = false) {
    if (chatMessages && (isAtBottom || force)) {
        chatMessages.scrollTop = chatMessages.scrollHeight;
        document.getElementById('newMsgIndicator').style.display = 'none';
    }
}

scrollToBottom(true);

function sendMessage(e) {
    e.preventDefault();
    const message = messageInput.value.trim();
    if (!message || !otherId) return false;
    
    sendBtn.disabled = true;
    messageInput.disabled = true;
    
    const formData = new FormData();
    formData.append('receiver_id', otherId);
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
    const emptyState = chatMessages.querySelector('.text-center');
    if (emptyState && !emptyState.querySelector('.message')) emptyState.remove();
    
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
    if (!otherId) return;
    
    fetch(apiUrl + '?action=get_messages&user_id=' + otherId + '&last_id=' + lastMessageId)
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
                    document.getElementById('newMsgIndicator').style.display = 'block';
                }
            }
        })
        .catch(err => console.log('Poll error:', err));
}

<?php if ($selectedStudent): ?>
setInterval(pollMessages, 2000);
setTimeout(pollMessages, 500);
<?php endif; ?>

if (messageInput) messageInput.focus();
</script>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/footer.php'; ?>