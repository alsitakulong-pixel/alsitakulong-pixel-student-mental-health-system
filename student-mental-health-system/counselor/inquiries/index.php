<?php
// ============================================
// COUNSELOR INQUIRIES MANAGEMENT
// ============================================

require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/auth.php';

requireRole('counselor');

$userId = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle response
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['respond'])) {
    $inquiryId = $_POST['inquiry_id'];
    $response = trim($_POST['response']);
    
    if (empty($response)) {
        $error = 'Please write a response.';
    } else {
        $stmt = $pdo->prepare("UPDATE inquiries SET status = 'responded', response = ?, responded_by = ? WHERE id = ?");
        $stmt->execute([$response, $userId, $inquiryId]);
        
        // Get student id
        $stmt = $pdo->prepare("SELECT student_id FROM inquiries WHERE id = ?");
        $stmt->execute([$inquiryId]);
        $studentId = $stmt->fetchColumn();
        
        createNotification($pdo, $studentId, 'Inquiry Responded', 'Your inquiry has been responded to. Please check it.', 'success');
        
        $success = 'Response sent successfully!';
    }
}

// Get all inquiries
$stmt = $pdo->prepare("
    SELECT i.*, u.first_name, u.last_name, u.school_id
    FROM inquiries i
    JOIN users u ON i.student_id = u.id
    ORDER BY i.created_at DESC
");
$stmt->execute();
$inquiries = $stmt->fetchAll();

$page_title = 'Student Inquiries';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h4 class="fw-bold" style="color: var(--primary);"><i class="fas fa-envelope me-2"></i>Student Inquiries</h4>
            <p class="text-muted">Respond to student inquiries and questions.</p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

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
                                
                                <?php if ($inquiry['status'] === 'new'): ?>
                                    <button type="button" class="btn btn-sm btn-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#respondModal<?php echo $inquiry['id']; ?>">
                                        <i class="fas fa-reply me-1"></i>Respond
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($inquiry['response']): ?>
                            <div class="p-3 mt-2" style="background: var(--lavender); border-radius: 10px;">
                                <strong><i class="fas fa-reply me-1"></i>Your Response:</strong>
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

<?php foreach ($inquiries as $inquiry): ?>
    <?php if ($inquiry['status'] === 'new'): ?>
        <!-- Respond Modal -->
        <div class="modal fade" id="respondModal<?php echo $inquiry['id']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Respond to Inquiry</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" action="">
                        <input type="hidden" name="respond" value="1">
                        <input type="hidden" name="inquiry_id" value="<?php echo $inquiry['id']; ?>">
                        <div class="modal-body">
                            <p><strong>From:</strong> <?php echo e($inquiry['first_name'] . ' ' . $inquiry['last_name']); ?></p>
                            <p><strong>Subject:</strong> <?php echo e($inquiry['subject']); ?></p>
                            <p class="text-muted"><?php echo e($inquiry['message']); ?></p>
                            <div class="mb-3">
                                <label class="form-label">Your Response *</label>
                                <textarea class="form-control" name="response" rows="4" required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary rounded-pill"><i class="fas fa-paper-plane me-1"></i>Send Response</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endforeach; ?>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/footer.php'; ?>