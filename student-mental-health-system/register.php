<?php
define('BASE_URL', 'http://localhost/student-mental-health-system');
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $school_id = trim($_POST['school_id']);
    $grade_level = trim($_POST['grade_level']);
    $section = trim($_POST['section']);
    
    if (empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name) || empty($school_id)) {
        $error = 'Please fill in all required fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        // Check if username exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = 'Username already exists.';
        } else {
            // Check if email exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email already exists.';
            } else {
                // Insert user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (role, username, password, email, first_name, last_name, school_id, grade_level, section) VALUES ('student', ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$username, $hashed_password, $email, $first_name, $last_name, $school_id, $grade_level, $section]);
                
                // Create welcome notification
                $userId = $pdo->lastInsertId();
                createNotification($pdo, $userId, 'Welcome!', 'Your account has been created successfully. You can now request counseling.', 'success');
                
                $success = 'Registration successful! You can now login.';
                header('Location: ' . BASE_URL . '/login.php?registered=1');
                exit();
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-container">
    <div class="card auth-card auth-card-wide">
        <div class="card-body">
            <div class="text-center mb-4">
                <img src="<?php echo BASE_URL; ?>/assets/images/snhs-logo.jpg" alt="SNHS Logo" style="height: 80px; width: 80px; border-radius: 50%; object-fit: cover;">
                <h4 class="mt-3">Create Account</h4>
                <p class="text-muted">Register as a Student</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username *</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="first_name" class="form-label">First Name *</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="last_name" class="form-label">Last Name *</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="school_id" class="form-label">School ID *</label>
                            <input type="text" class="form-control" id="school_id" name="school_id" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="grade_level" class="form-label">Grade Level</label>
                            <select class="form-control" id="grade_level" name="grade_level">
                                <option value="">Select Grade</option>
                                <option value="Grade 7">Grade 7</option>
                                <option value="Grade 8">Grade 8</option>
                                <option value="Grade 9">Grade 9</option>
                                <option value="Grade 10">Grade 10</option>
                                <option value="Grade 11">Grade 11</option>
                                <option value="Grade 12">Grade 12</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="section" class="form-label">Section</label>
                            <input type="text" class="form-control" id="section" name="section">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="password" class="form-label">Password *</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password *</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100">Register</button>
            </form>
            <p class="text-center mt-3">Already have an account? <a href="<?php echo BASE_URL; ?>/login.php">Login</a></p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>