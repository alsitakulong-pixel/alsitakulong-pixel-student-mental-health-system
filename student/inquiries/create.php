<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireRole('student');

$userId = $_SESSION['user_id'];
$user = getCurrentUser($pdo);
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (empty($subject) || empty($message)) {
        $error = 'Please fill in all required fields.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO inquiries (student_id, subject, message) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $subject, $message]);
        
        // Notify all counselors
        $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'counselor' AND status = 'active'");
        $stmt->execute();
        while ($counselor = $stmt->fetch()) {
            createNotification($pdo, $counselor['id'], 'New Inquiry Received', 'A student has submitted a new inquiry: ' . $subject, 'info');
        }
        
        $success = 'Your inquiry has been submitted successfully!';
        redirect(BASE_URL . '/student/dashboard.php?inquiry_sent=1');
    }
}

$page_title = 'Send Inquiry';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <h4 class="fw-bold" style="color: var(--primary);"><i class="fas fa-envelope-open-text me-2"></i>Send an Inquiry</h4>
                        <p class="text-muted">Have a question or concern? Send us a message and we'll get back to you.</p>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Subject *</label>
                            <input type="text" class="form-control" name="subject" required placeholder="What is your inquiry about?">
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Message *</label>
                            <textarea class="form-control" name="message" rows="5" required placeholder="Write your message here..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2 rounded-pill"><i class="fas fa-paper-plane me-2"></i>Send Inquiry</button>
                    </form>
                    
                    <div class="text-center mt-3">
                        <a href="<?php echo BASE_URL; ?>/student/dashboard.php" class="text-muted small">Back to Dashboard</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>