<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'Please fill in all required fields.';
    } else {
        // Store as inquiry if student is logged in, otherwise just show success
        if (isLoggedIn() && hasRole('student')) {
            $stmt = $pdo->prepare("INSERT INTO inquiries (student_id, subject, message) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $subject, $message]);
        }
        
        $success = 'Thank you for your message! We will get back to you as soon as possible.';
    }
}

$page_title = 'Contact Us';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="py-5" style="background: linear-gradient(135deg, var(--lavender) 0%, var(--sky) 100%);">
    <div class="container">
        <h2 class="section-title text-center">Contact the Counseling Office</h2>
        <p class="section-subtitle text-center">We're here to help. Reach out to us anytime.</p>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-5 mb-4">
                <div class="card shadow-sm border-0 rounded-4 h-100">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4" style="color: var(--primary);"><i class="fas fa-map-marker-alt me-2"></i>Contact Information</h5>
                        <div class="mb-4">
                            <p class="mb-2"><i class="fas fa-map-marker-alt me-2" style="color: var(--primary);"></i><strong>Address:</strong></p>
                            <p class="text-muted ms-4">Sulu National High School<br>Jolo, Sulu, BARMM</p>
                        </div>
                        <div class="mb-4">
                            <p class="mb-2"><i class="fas fa-phone me-2" style="color: var(--primary);"></i><strong>Phone:</strong></p>
                            <p class="text-muted ms-4">(064) 123-4567</p>
                        </div>
                        <div class="mb-4">
                            <p class="mb-2"><i class="fas fa-envelope me-2" style="color: var(--primary);"></i><strong>Email:</strong></p>
                            <p class="text-muted ms-4">guidance@snhs.edu.ph</p>
                        </div>
                        <div class="mb-4">
                            <p class="mb-2"><i class="fas fa-clock me-2" style="color: var(--primary);"></i><strong>Office Hours:</strong></p>
                            <p class="text-muted ms-4">Monday - Friday<br>8:00 AM - 5:00 PM</p>
                        </div>
                        <div class="p-3" style="background: var(--lavender); border-radius: 10px;">
                            <p class="mb-0 small text-muted"><i class="fas fa-shield-alt me-2" style="color: var(--teal);"></i>All inquiries are confidential and handled with care.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-7">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4" style="color: var(--primary);"><i class="fas fa-envelope-open-text me-2"></i>Send a Message</h5>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Your Name *</label>
                                    <input type="text" class="form-control" name="name" required <?php echo isLoggedIn() ? 'value="' . e($_SESSION['user_name']) . '"' : ''; ?>>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Your Email *</label>
                                    <input type="email" class="form-control" name="email" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Subject *</label>
                                <input type="text" class="form-control" name="subject" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Message *</label>
                                <textarea class="form-control" name="message" rows="5" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 rounded-pill"><i class="fas fa-paper-plane me-2"></i>Send Message</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>