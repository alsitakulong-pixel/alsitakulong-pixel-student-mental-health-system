<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/auth.php';

// If already logged in, redirect
if (isLoggedIn()) {
    redirect(BASE_URL . '/' . $_SESSION['user_role'] . '/dashboard.php');
}

// ============================================
// AUTO-DETECT LOGO FILE
// ============================================
$logoPath = '/assets/images/snhs-logo.png';
$logoFullPath = $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system' . $logoPath;
if (!file_exists($logoFullPath)) {
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/assets/images/snhs-logo.jpg')) {
        $logoPath = '/assets/images/snhs-logo.jpg';
    } elseif (file_exists($_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/assets/images/snhs-logo.jpeg')) {
        $logoPath = '/assets/images/snhs-logo.jpeg';
    } elseif (file_exists($_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/assets/images/SNHS-logo.png')) {
        $logoPath = '/assets/images/SNHS-logo.png';
    } elseif (file_exists($_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/assets/images/logo.png')) {
        $logoPath = '/assets/images/logo.png';
    }
}
$logoUrl = BASE_URL . $logoPath;

$login_error = '';
$register_error = '';
$register_success = '';

// ============================================
// HANDLE LOGIN
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {
    $username = trim($_POST['login_username']);
    $password = trim($_POST['login_password']);
    
    if (empty($username) || empty($password)) {
        $login_error = 'Please fill in all fields.';
    } else {
        if (loginUser($pdo, $username, $password)) {
            redirect(BASE_URL . '/' . $_SESSION['user_role'] . '/dashboard.php');
        } else {
            $login_error = 'Invalid username or password.';
        }
    }
}

// ============================================
// HANDLE REGISTRATION
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_submit'])) {
    $username = trim($_POST['reg_username']);
    $email = trim($_POST['reg_email']);
    $password = $_POST['reg_password'];
    $confirm_password = $_POST['reg_confirm_password'];
    $first_name = trim($_POST['reg_first_name']);
    $last_name = trim($_POST['reg_last_name']);
    $school_id = trim($_POST['reg_school_id']);
    $grade_level = trim($_POST['reg_grade_level']);
    $section = trim($_POST['reg_section']);
    
    if (empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name) || empty($school_id)) {
        $register_error = 'Please fill in all required fields.';
    } elseif ($password !== $confirm_password) {
        $register_error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $register_error = 'Password must be at least 6 characters.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $register_error = 'Username already exists.';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $register_error = 'Email already exists.';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (role, username, password, email, first_name, last_name, school_id, grade_level, section) VALUES ('student', ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$username, $hashed_password, $email, $first_name, $last_name, $school_id, $grade_level, $section]);
                
                $userId = $pdo->lastInsertId();
                createNotification($pdo, $userId, 'Welcome to SNHS Wellness!', 'Your account has been created successfully. You can now request counseling.', 'success');
                
                $register_success = 'Registration successful! You can now login.';
            }
        }
    }
}

$page_title = 'Login / Register';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/header.php';
?>

<style>
/* ============================================
   MODERN SLIDING AUTH FORM
   ============================================ */

.auth-section {
    min-height: 90vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 0;
    background: linear-gradient(135deg, #DBEAFE 0%, #E0F2FE 50%, #BFDBFE 100%);
    position: relative;
    overflow: hidden;
}

[data-theme="dark"] .auth-section {
    background: linear-gradient(135deg, #1A0505 0%, #450A0A 50%, #7F1D1D 100%);
}

.auth-section::before {
    content: '';
    position: absolute;
    top: -100px;
    right: -100px;
    width: 400px;
    height: 400px;
    background: radial-gradient(circle, rgba(139,92,246,0.2) 0%, transparent 70%);
    border-radius: 50%;
}

[data-theme="dark"] .auth-section::before {
    background: radial-gradient(circle, rgba(220,38,38,0.25) 0%, transparent 70%);
}

.auth-section::after {
    content: '';
    position: absolute;
    bottom: -80px;
    left: -80px;
    width: 350px;
    height: 350px;
    background: radial-gradient(circle, rgba(236,72,153,0.15) 0%, transparent 70%);
    border-radius: 50%;
}

.auth-wrapper {
    position: relative;
    width: 100%;
    max-width: 850px;
    height: 600px;
    border-radius: 28px;
    overflow: hidden;
    box-shadow: 0 25px 60px rgba(124, 58, 237, 0.2);
    z-index: 1;
}

[data-theme="dark"] .auth-wrapper {
    box-shadow: 0 25px 60px rgba(220, 38, 38, 0.2);
}

/* ========== SLIDING OVERLAY ========== */
.overlay-container {
    position: absolute;
    top: 0;
    left: 50%;
    width: 50%;
    height: 100%;
    overflow: hidden;
    transition: transform 0.6s ease-in-out;
    z-index: 100;
}

.auth-wrapper.right-panel-active .overlay-container {
    transform: translateX(-100%);
}

.overlay {
    position: relative;
    left: -100%;
    width: 200%;
    height: 100%;
    background: linear-gradient(135deg, #8B5CF6 0%, #EC4899 50%, #3B82F6 100%);
    color: #fff;
    transform: translateX(0);
    transition: transform 0.6s ease-in-out;
}

[data-theme="dark"] .overlay {
    background: linear-gradient(135deg, #7F1D1D 0%, #DC2626 50%, #991B1B 100%);
}

.auth-wrapper.right-panel-active .overlay {
    transform: translateX(50%);
}

.overlay-panel {
    position: absolute;
    top: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 0 40px;
    text-align: center;
    height: 100%;
    width: 50%;
    transition: transform 0.6s ease-in-out;
}

.overlay-left {
    transform: translateX(-20%);
}

.auth-wrapper.right-panel-active .overlay-left {
    transform: translateX(0);
}

.overlay-right {
    right: 0;
    transform: translateX(0);
}

.auth-wrapper.right-panel-active .overlay-right {
    transform: translateX(20%);
}

.overlay-panel h2 {
    font-weight: 800;
    font-size: 1.8rem;
    margin-bottom: 10px;
    letter-spacing: -0.5px;
    color: #fff;
}

.overlay-panel p {
    font-size: 0.95rem;
    opacity: 0.95;
    margin-bottom: 25px;
    line-height: 1.5;
    color: #fff;
}

/* ========== LOGO IN OVERLAY ========== */
.overlay-logo-wrapper {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: #fff;
    padding: 4px;
    margin-bottom: 22px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.25);
    border: 4px solid rgba(255,255,255,0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.overlay-logo {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
    display: block;
}

.overlay-logo-fallback {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: linear-gradient(135deg, #8B5CF6, #EC4899);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 2.5rem;
    font-weight: 800;
}

.btn-ghost {
    background: transparent;
    color: #fff;
    border: 2px solid #fff;
    border-radius: 50px;
    padding: 12px 40px;
    font-weight: 700;
    font-size: 0.9rem;
    letter-spacing: 1px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-ghost:hover {
    background: #fff;
    color: #7C3AED;
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.2);
}

[data-theme="dark"] .btn-ghost:hover {
    color: #DC2626;
}

/* ========== FORM CONTAINERS ========== */
.form-container {
    position: absolute;
    top: 0;
    height: 100%;
    width: 50%;
    transition: all 0.6s ease-in-out;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 0;
    background: rgba(255,255,255,0.98);
    overflow-y: auto;
}

[data-theme="dark"] .form-container {
    background: rgba(45, 10, 10, 0.98);
}

.sign-in-container {
    left: 0;
    z-index: 2;
}

.auth-wrapper.right-panel-active .sign-in-container {
    transform: translateX(100%);
    opacity: 0;
    z-index: 1;
}

.sign-up-container {
    left: 0;
    opacity: 0;
    z-index: 1;
}

.auth-wrapper.right-panel-active .sign-up-container {
    transform: translateX(100%);
    opacity: 1;
    z-index: 5;
    animation: show 0.6s;
}

@keyframes show {
    0%, 49.99% {
        opacity: 0;
        z-index: 1;
    }
    50%, 100% {
        opacity: 1;
        z-index: 5;
    }
}

.form-inner {
    padding: 40px 45px;
    width: 100%;
}

.form-inner h2 {
    font-weight: 800;
    color: var(--text);
    margin-bottom: 5px;
    font-size: 1.6rem;
}

.form-inner .subtitle {
    color: var(--text-secondary);
    font-size: 0.9rem;
    margin-bottom: 25px;
}

/* ========== MOBILE LOGO ========== */
.mobile-form-logo {
    display: none;
    text-align: center;
    margin-bottom: 15px;
}

.mobile-form-logo img {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid var(--primary);
    background: #fff;
    padding: 3px;
    box-shadow: 0 6px 20px rgba(139, 92, 246, 0.25);
}

/* ========== FORM FIELDS ========== */
.modern-input-group {
    position: relative;
    margin-bottom: 18px;
}

.modern-input-group .input-icon {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: #9CA3AF;
    font-size: 1rem;
    transition: color 0.3s;
    z-index: 2;
    pointer-events: none;
}

.modern-input-group .form-control {
    padding: 14px 18px 14px 45px;
    border: 2px solid #E5E7EB;
    border-radius: 14px;
    background: #F9FAFB;
    font-size: 0.92rem;
    transition: all 0.3s ease;
    color: var(--text);
    width: 100%;
    height: auto;
}

.modern-input-group select.form-control {
    appearance: none;
    -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%239CA3AF' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 16px center;
    padding-right: 40px;
}

.modern-input-group .form-control:focus {
    border-color: #8B5CF6;
    box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.12);
    background: #fff;
    outline: none;
}

[data-theme="dark"] .modern-input-group .form-control {
    border-color: #450A0A;
    background: #2D0A0A;
    color: #FECACA;
}

[data-theme="dark"] .modern-input-group .form-control:focus {
    border-color: #DC2626;
    box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.12);
    background: #450A0A;
}

.modern-input-group .form-control:focus ~ .input-icon {
    color: #8B5CF6;
}

[data-theme="dark"] .modern-input-group .form-control:focus ~ .input-icon {
    color: #DC2626;
}

.modern-input-group .form-control::placeholder {
    color: #9CA3AF;
    font-size: 0.88rem;
}

/* ========== BUTTONS ========== */
.btn-auth-primary {
    background: linear-gradient(135deg, #8B5CF6 0%, #EC4899 100%);
    color: #fff;
    border: none;
    border-radius: 50px;
    padding: 14px 40px;
    font-weight: 700;
    font-size: 0.95rem;
    letter-spacing: 0.5px;
    width: 100%;
    transition: all 0.3s ease;
    box-shadow: 0 6px 20px rgba(139, 92, 246, 0.3);
    cursor: pointer;
}

[data-theme="dark"] .btn-auth-primary {
    background: linear-gradient(135deg, #7F1D1D 0%, #DC2626 100%);
    box-shadow: 0 6px 20px rgba(220, 38, 38, 0.3);
}

.btn-auth-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(139, 92, 246, 0.4);
    color: #fff;
}

[data-theme="dark"] .btn-auth-primary:hover {
    box-shadow: 0 10px 30px rgba(220, 38, 38, 0.4);
}

/* ========== SIGN-UP SCROLLABLE ========== */
.sign-up-container .form-inner {
    padding: 30px 40px;
}

.sign-up-container .modern-input-group {
    margin-bottom: 12px;
}

.sign-up-container .form-inner h2 {
    font-size: 1.3rem;
}

/* ========== RESPONSIVE ========== */
@media (max-width: 768px) {
    .auth-wrapper {
        max-width: 100%;
        height: auto;
        min-height: 600px;
        border-radius: 0;
        box-shadow: none;
    }
    
    .overlay-container {
        display: none;
    }
    
    .mobile-form-logo {
        display: block;
    }
    
    .form-container {
        width: 100%;
        position: relative;
        height: auto;
        min-height: 500px;
        transform: none !important;
        opacity: 1 !important;
        z-index: 1 !important;
    }
    
    .sign-in-container {
        display: block;
    }
    
    .sign-up-container {
        display: none;
    }
    
    .auth-wrapper.right-panel-active .sign-up-container {
        display: block;
    }
    
    .auth-wrapper.right-panel-active .sign-in-container {
        display: none;
    }
    
    .form-inner {
        padding: 30px 25px;
    }
    
    .mobile-toggle {
        display: block;
        text-align: center;
        margin-top: 20px;
    }
    
    .mobile-toggle a {
        color: #8B5CF6;
        font-weight: 600;
        text-decoration: none;
    }
    
    [data-theme="dark"] .mobile-toggle a {
        color: #FCA5A5;
    }
}

@media (min-width: 769px) {
    .mobile-toggle {
        display: none;
    }
}
</style>

<section class="auth-section">
    <div class="auth-wrapper" id="authWrapper">
        
        <!-- ========== REGISTRATION FORM ========== -->
        <div class="form-container sign-up-container">
            <div class="form-inner">
                <div class="mobile-form-logo">
                    <img src="<?php echo $logoUrl; ?>" alt="SNHS Logo" onerror="this.style.display='none';">
                </div>
                <h2><i class="fas fa-user-plus me-2" style="color: var(--primary);"></i>Create Account</h2>
                <p class="subtitle">Join SNHS Wellness Portal</p>
                
                <?php if ($register_error): ?>
                    <div class="alert alert-danger py-2" style="font-size: 0.85rem;"><?php echo e($register_error); ?></div>
                <?php endif; ?>
                
                <?php if ($register_success): ?>
                    <div class="alert alert-success py-2" style="font-size: 0.85rem;"><?php echo e($register_success); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <input type="hidden" name="register_submit" value="1">
                    
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="modern-input-group">
                                <input type="text" class="form-control" name="reg_first_name" placeholder="First Name" required>
                                <i class="fas fa-user input-icon"></i>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="modern-input-group">
                                <input type="text" class="form-control" name="reg_last_name" placeholder="Last Name" required>
                                <i class="fas fa-user input-icon"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modern-input-group">
                        <input type="text" class="form-control" name="reg_username" placeholder="Username" required>
                        <i class="fas fa-at input-icon"></i>
                    </div>
                    
                    <div class="modern-input-group">
                        <input type="email" class="form-control" name="reg_email" placeholder="Email Address" required>
                        <i class="fas fa-envelope input-icon"></i>
                    </div>
                    
                    <div class="row g-2">
                        <div class="col-4">
                            <div class="modern-input-group">
                                <input type="text" class="form-control" name="reg_school_id" placeholder="School ID" required>
                                <i class="fas fa-id-card input-icon"></i>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="modern-input-group">
                                <select class="form-control" name="reg_grade_level" style="padding-left: 45px;">
                                    <option value="">Grade</option>
                                    <option value="Grade 7">Grade 7</option>
                                    <option value="Grade 8">Grade 8</option>
                                    <option value="Grade 9">Grade 9</option>
                                    <option value="Grade 10">Grade 10</option>
                                    <option value="Grade 11">Grade 11</option>
                                    <option value="Grade 12">Grade 12</option>
                                </select>
                                <i class="fas fa-layer-group input-icon"></i>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="modern-input-group">
                                <input type="text" class="form-control" name="reg_section" placeholder="Section">
                                <i class="fas fa-users input-icon"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modern-input-group">
                        <input type="password" class="form-control" name="reg_password" placeholder="Password (min 6 characters)" required>
                        <i class="fas fa-lock input-icon"></i>
                    </div>
                    
                    <div class="modern-input-group">
                        <input type="password" class="form-control" name="reg_confirm_password" placeholder="Confirm Password" required>
                        <i class="fas fa-lock input-icon"></i>
                    </div>
                    
                    <button type="submit" class="btn-auth-primary mt-2">
                        <i class="fas fa-user-plus me-2"></i>Sign Up
                    </button>
                    
                    <div class="mobile-toggle">
                        <p class="mt-3 text-muted" style="font-size: 0.85rem;">
                            Already have an account? 
                            <a href="#" onclick="togglePanel(); return false;">Sign In</a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- ========== LOGIN FORM ========== -->
        <div class="form-container sign-in-container">
            <div class="form-inner">
                <div class="mobile-form-logo">
                    <img src="<?php echo $logoUrl; ?>" alt="SNHS Logo" onerror="this.style.display='none';">
                </div>
                <h2><i class="fas fa-sign-in-alt me-2" style="color: var(--primary);"></i>Welcome Back</h2>
                <p class="subtitle">Sign in to SNHS Wellness Portal</p>
                
                <?php if ($login_error): ?>
                    <div class="alert alert-danger py-2" style="font-size: 0.85rem;"><?php echo e($login_error); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <input type="hidden" name="login_submit" value="1">
                    
                    <div class="modern-input-group">
                        <input type="text" class="form-control" name="login_username" placeholder="Username" required>
                        <i class="fas fa-user input-icon"></i>
                    </div>
                    
                    <div class="modern-input-group">
                        <input type="password" class="form-control" name="login_password" placeholder="Password" required>
                        <i class="fas fa-lock input-icon"></i>
                    </div>
                    
                    <button type="submit" class="btn-auth-primary mt-2">
                        <i class="fas fa-sign-in-alt me-2"></i>Sign In
                    </button>
                    
                    <div class="mobile-toggle">
                        <p class="mt-3 text-muted" style="font-size: 0.85rem;">
                            Don't have an account? 
                            <a href="#" onclick="togglePanel(); return false;">Sign Up</a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- ========== OVERLAY (Desktop only) ========== -->
        <div class="overlay-container">
            <div class="overlay">
                <div class="overlay-panel overlay-left">
                    <div class="overlay-logo-wrapper">
                        <img src="<?php echo $logoUrl; ?>" alt="SNHS Logo" class="overlay-logo" 
                             onerror="this.style.display='none'; this.parentElement.innerHTML='<div class=&quot;overlay-logo-fallback&quot;>SNHS</div>';">
                    </div>
                    <h2>Welcome Back!</h2>
                    <p>To keep connected with us, please login with your personal info</p>
                    <button type="button" class="btn-ghost" onclick="togglePanel();">
                        <i class="fas fa-sign-in-alt me-2"></i>Sign In
                    </button>
                </div>
                <div class="overlay-panel overlay-right">
                    <div class="overlay-logo-wrapper">
                        <img src="<?php echo $logoUrl; ?>" alt="SNHS Logo" class="overlay-logo"
                             onerror="this.style.display='none'; this.parentElement.innerHTML='<div class=&quot;overlay-logo-fallback&quot;>SNHS</div>';">
                    </div>
                    <h2>Hello, Friend!</h2>
                    <p>Enter your personal details and start your journey with us</p>
                    <button type="button" class="btn-ghost" onclick="togglePanel();">
                        <i class="fas fa-user-plus me-2"></i>Sign Up
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function togglePanel() {
    const wrapper = document.getElementById('authWrapper');
    wrapper.classList.toggle('right-panel-active');
    document.querySelectorAll('.alert').forEach(a => a.remove());
}

<?php if ($register_error): ?>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('authWrapper').classList.add('right-panel-active');
});
<?php endif; ?>
</script>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/footer.php'; ?>