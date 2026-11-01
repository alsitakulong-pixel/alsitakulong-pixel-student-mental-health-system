<?php
// ============================================
// NAVBAR - WITH CHAT & RESOURCES LINKS
// ============================================

require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/chat_functions.php';

$userRole = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : null;
$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';
$baseUrl = BASE_URL;
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// Get unread chat count
$unreadChat = 0;
if ($userId > 0 && $userRole !== 'admin') {
    $unreadChat = getTotalUnreadMessages($pdo, $userId);
}

// Logo auto-detect
$logoPath = '/assets/images/snhs-logo.png';
$logoFullPath = $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system' . $logoPath;
if (!file_exists($logoFullPath)) {
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/assets/images/snhs-logo.jpg')) {
        $logoPath = '/assets/images/snhs-logo.jpg';
    }
}
$logoUrl = $baseUrl . $logoPath;
?>
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="<?php echo $baseUrl; ?>/index.php">
            <img src="<?php echo $logoUrl; ?>" alt="SNHS Logo" style="height: 50px; width: 50px; border-radius: 50%; object-fit: cover; margin-right: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); background: #fff; padding: 2px;">
            <span>
                <span class="brand-text">SNHS WELLNESS</span><br>
                <small class="brand-tagline">LISTEN • SUPPORT • EMPOWER</small>
            </span>
        </a>
        
        <div class="d-flex align-items-center order-lg-3">
            <button type="button" id="darkModeToggle" class="btn btn-dark-mode me-2" title="Toggle Dark Mode">
                <i class="fas fa-moon" id="darkModeIcon"></i>
            </button>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
        
        <div class="collapse navbar-collapse order-lg-2" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <?php if (!$userRole): ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $baseUrl; ?>/index.php"><i class="fas fa-home me-1"></i>Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $baseUrl; ?>/pages/services.php"><i class="fas fa-hands-helping me-1"></i>Services</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $baseUrl; ?>/pages/resources.php"><i class="fas fa-book-open me-1"></i>Resources</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $baseUrl; ?>/pages/about.php"><i class="fas fa-info-circle me-1"></i>About</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $baseUrl; ?>/pages/faq.php"><i class="fas fa-question-circle me-1"></i>FAQs</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $baseUrl; ?>/pages/contact.php"><i class="fas fa-envelope me-1"></i>Contact</a></li>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-light btn-sm rounded-pill px-4 py-2 fw-bold" href="<?php echo $baseUrl; ?>/login.php"><i class="fas fa-sign-in-alt me-1"></i> Login</a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-warning btn-sm rounded-pill px-4 py-2 fw-bold" href="<?php echo $baseUrl; ?>/register.php"><i class="fas fa-user-plus me-1"></i> Register</a>
                    </li>
                <?php elseif ($userRole === 'student'): ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $baseUrl; ?>/student/dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $baseUrl; ?>/student/appointments/index.php"><i class="fas fa-calendar-check me-1"></i> Appointments</a></li>
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="<?php echo $baseUrl; ?>/student/chat/index.php">
                            <i class="fas fa-comments me-1"></i> Chat
                            <?php if ($unreadChat > 0): ?>
                                <span class="badge bg-danger rounded-pill ms-1"><?php echo $unreadChat; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $baseUrl; ?>/student/notifications/index.php"><i class="fas fa-bell me-1"></i> Notifications</a></li>
                    <li class="nav-item ms-lg-2">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1" style="font-size: 1.3rem;"></i> <?php echo $userName; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-4">
                            <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>/student/profile.php"><i class="fas fa-user me-2 text-primary"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>/student/inquiries/create.php"><i class="fas fa-envelope me-2 text-primary"></i> Send Inquiry</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?php echo $baseUrl; ?>/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </li>
                <?php elseif ($userRole === 'counselor'): ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $baseUrl; ?>/counselor/dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $baseUrl; ?>/counselor/requests/index.php"><i class="fas fa-inbox me-1"></i> Requests</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $baseUrl; ?>/counselor/appointments/index.php"><i class="fas fa-calendar-alt me-1"></i> Appointments</a></li>
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="<?php echo $baseUrl; ?>/counselor/chat/index.php">
                            <i class="fas fa-comments me-1"></i> Chat
                            <?php if ($unreadChat > 0): ?>
                                <span class="badge bg-danger rounded-pill ms-1"><?php echo $unreadChat; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $baseUrl; ?>/counselor/resources/index.php"><i class="fas fa-book-medical me-1"></i> Resources</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $baseUrl; ?>/counselor/inquiries/index.php"><i class="fas fa-envelope me-1"></i> Inquiries</a></li>
                    <li class="nav-item ms-lg-2">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1" style="font-size: 1.3rem;"></i> <?php echo $userName; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-4">
                            <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>/counselor/students/index.php"><i class="fas fa-users me-2 text-primary"></i> Students</a></li>
                            <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>/counselor/profile.php"><i class="fas fa-user me-2 text-primary"></i> Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?php echo $baseUrl; ?>/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </li>
                <?php elseif ($userRole === 'admin'): ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $baseUrl; ?>/admin/dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $baseUrl; ?>/admin/students/index.php"><i class="fas fa-user-graduate me-1"></i> Students</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $baseUrl; ?>/admin/counselors/index.php"><i class="fas fa-user-md me-1"></i> Counselors</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $baseUrl; ?>/admin/appointments/index.php"><i class="fas fa-calendar-check me-1"></i> Appointments</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $baseUrl; ?>/admin/resources/index.php"><i class="fas fa-book-medical me-1"></i> Resources</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $baseUrl; ?>/admin/chat/index.php"><i class="fas fa-comments me-1"></i> Monitor Chats</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $baseUrl; ?>/admin/reports/index.php"><i class="fas fa-chart-bar me-1"></i> Reports</a></li>
                    <li class="nav-item ms-lg-2">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1" style="font-size: 1.3rem;"></i> <?php echo $userName; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-4">
                            <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>/admin/profile.php"><i class="fas fa-user me-2 text-primary"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>/admin/inquiries/index.php"><i class="fas fa-envelope me-2 text-primary"></i> Inquiries</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?php echo $baseUrl; ?>/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>