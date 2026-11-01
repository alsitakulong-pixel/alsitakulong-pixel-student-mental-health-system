<?php
// ============================================
// ADMIN - VIEW ALL INQUIRIES
// ============================================

require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/auth.php';

requireRole('admin');

// Get all inquiries
$stmt = $pdo->prepare("
    SELECT i.*, u.first_name, u.last_name, u.school_id
    FROM inquiries i
    JOIN users u ON i.student_id = u.id
    ORDER BY i.created_at DESC
");
$stmt->execute();
$inquiries = $stmt->fetchAll();

$page_title = 'All Inquiries';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h4 class="fw-bold" style="color: var(--primary);"><i class="fas fa-envelope me-2"></i>All Inquiries</h4>
            <p class="text-muted">View all student inquiries.</p>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body">
            <?php if (count($inquiries) > 0): ?>
                <?php foreach ($inquiries as $inquiry): ?>
                    <div class="notification-item" style="border-left-color: <?php echo $inquiry['status'] === 'new' ? 'var(--warning)' : ($inquiry['status'] === 'responded' ? 'var(--success)' : 'var(--secondary)'); ?>;">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong><?php echo e($inquiry['first_name'] . ' ' . $inquiry['last_name']); ?></strong>
                                <span class="text-muted small">(<?php echo e($inquiry['school_id']); ?>)</span>
                                <p class="mb-1 mt-2"><strong><?php echo e($inquiry['subject']); ?></strong></p>
                                <p class="mb-2 text-muted"><?php echo e($inquiry['message']); ?></p>
                                <small class="text-secondary">Submitted: <?php echo date('M d, Y h:i A', strtotime($inquiry['created_at'])); ?></small>
                            </div>
                            <div class="text-end">
                                <?php if ($inquiry['status'] === 'new'): ?>
                                    <span class="badge bg-warning text-dark rounded-pill mb-2">New</span>
                                <?php elseif ($inquiry['status'] === 'responded'): ?>
                                    <span class="badge bg-success rounded-pill mb-2">Responded</span>
                                <?php elseif ($inquiry['status'] === 'closed'): ?>
                                    <span class="badge bg-secondary rounded-pill mb-2">Closed</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($inquiry['response']): ?>
                            <div class="p-3 mt-2" style="background: var(--lavender); border-radius: 10px;">
                                <strong><i class="fas fa-reply me-1"></i>Response:</strong>
                                <p class="mb-0 mt-1"><?php echo e($inquiry['response']); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted text-center py-4 mb-0">No inquiries received.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/footer.php'; ?>