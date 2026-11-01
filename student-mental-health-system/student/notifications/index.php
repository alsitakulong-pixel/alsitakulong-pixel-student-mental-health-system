<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireRole('student');

$userId = $_SESSION['user_id'];
$user = getCurrentUser($pdo);

// Mark all as read
if (isset($_GET['mark_all'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$userId]);
    redirect(BASE_URL . '/student/notifications/index.php');
}

// Get all notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();

$page_title = 'Notifications';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h4 class="fw-bold" style="color: var(--primary);"><i class="fas fa-bell me-2"></i>Notifications</h4>
        </div>
        <div class="col-md-4 text-end">
            <a href="?mark_all=1" class="btn btn-sm btn-outline-primary rounded-pill"><i class="fas fa-check-double me-1"></i>Mark All Read</a>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body">
            <?php if (count($notifications) > 0): ?>
                <?php foreach ($notifications as $notif): ?>
                    <div class="notification-item <?php echo $notif['is_read'] == 0 ? 'unread' : ''; ?>" style="<?php echo $notif['is_read'] == 0 ? 'border-left-color: var(--warm); background: #FFF8E7;' : 'border-left-color: #94A3B8;'; ?>">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong><?php echo e($notif['title']); ?></strong>
                                <p class="mb-1"><?php echo e($notif['message']); ?></p>
                                <small class="text-secondary"><?php echo date('M d, Y h:i A', strtotime($notif['created_at'])); ?></small>
                            </div>
                            <?php if ($notif['is_read'] == 0): ?>
                                <form method="POST" action="">
                                    <input type="hidden" name="notification_id" value="<?php echo $notif['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-link text-muted" title="Mark as read"><i class="fas fa-check"></i></button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted text-center py-4 mb-0">No notifications yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Handle mark as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notification_id'])) {
    markNotificationRead($pdo, $_POST['notification_id'], $userId);
    redirect(BASE_URL . '/student/notifications/index.php');
}
?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>