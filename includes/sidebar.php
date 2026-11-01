<?php
// ============================================
// SIDEBAR NAVIGATION (with profile image)
// ============================================

if (!isset($pdo)) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/config/database.php';
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/functions.php';

if (!function_exists('getTotalUnreadMessages')) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/chat_functions.php';
}

$userRole = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : null;
$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$baseUrl = BASE_URL;

// Get current user data (for profile image)
$sidebarUser = null;
if ($userId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $sidebarUser = $stmt->fetch();
}

// Unread counts
$unreadChat = 0;
$unreadNotif = 0;
if ($userId > 0) {
    if ($userRole !== 'admin') {
        $unreadChat = getTotalUnreadMessages($pdo, $userId);
    }
    if ($userRole === 'student') {
        $unreadNotif = getUnreadNotifications($pdo, $userId);
    }
}

// Logo auto-detect
$logoPath = '/assets/images/snhs-logo.png';
if (!file_exists($_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system' . $logoPath)) {
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/assets/images/snhs-logo.jpg')) {
        $logoPath = '/assets/images/snhs-logo.jpg';
    }
}
$logoUrl = $baseUrl . $logoPath;

function isActive($link) {
    $current = $_SERVER['PHP_SELF'];
    $basePath = '/student-mental-health-system';
    $relative = str_replace($basePath, '', $current);
    return ($relative === $link) ? 'active' : '';
}
?>
<aside class="app-sidebar" id="appSidebar">
    
    <div class="sidebar-header">
        <a href="<?php echo $baseUrl; ?>/index.php" class="sidebar-brand">
            <img src="<?php echo $logoUrl; ?>" alt="SNHS Logo" class="sidebar-logo">
            <div class="sidebar-brand-text">
                <span class="sidebar-brand-title">SNHS WELLNESS</span>
                <span class="sidebar-brand-tag">LISTEN • SUPPORT • EMPOWER</span>
            </div>
        </a>
        <button type="button" class="sidebar-close-btn" onclick="toggleSidebar()" aria-label="Close menu">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <?php if ($userRole && $sidebarUser): ?>
    <a href="<?php echo $baseUrl . '/' . $userRole; ?>/profile.php" class="sidebar-user" style="text-decoration:none;">
        <div class="sidebar-user-avatar-wrap">
            <?php echo renderAvatar($sidebarUser, 46); ?>
        </div>
        <div class="sidebar-user-info">
            <strong><?php echo e($userName); ?></strong>
            <small><?php echo ucfirst($userRole); ?></small>
        </div>
        <i class="fas fa-chevron-right" style="color: rgba(255,255,255,0.5); font-size: 0.7rem;"></i>
    </a>
    <?php endif; ?>
    
    <nav class="sidebar-nav">
        <?php if (!$userRole): ?>
            <div class="sidebar-section-label">Menu</div>
            <a href="<?php echo $baseUrl; ?>/index.php" class="sidebar-link <?php echo isActive('/index.php'); ?>">
                <i class="fas fa-home"></i><span>Home</span>
            </a>
            <a href="<?php echo $baseUrl; ?>/pages/services.php" class="sidebar-link <?php echo isActive('/pages/services.php'); ?>">
                <i class="fas fa-hands-helping"></i><span>Services</span>
            </a>
            <a href="<?php echo $baseUrl; ?>/pages/resources.php" class="sidebar-link <?php echo isActive('/pages/resources.php'); ?>">
                <i class="fas fa-book-open"></i><span>Resources</span>
            </a>
            <a href="<?php echo $baseUrl; ?>/pages/about.php" class="sidebar-link <?php echo isActive('/pages/about.php'); ?>">
                <i class="fas fa-info-circle"></i><span>About</span>
            </a>
            <a href="<?php echo $baseUrl; ?>/pages/faq.php" class="sidebar-link <?php echo isActive('/pages/faq.php'); ?>">
                <i class="fas fa-question-circle"></i><span>FAQs</span>
            </a>
            <a href="<?php echo $baseUrl; ?>/pages/contact.php" class="sidebar-link <?php echo isActive('/pages/contact.php'); ?>">
                <i class="fas fa-envelope"></i><span>Contact</span>
            </a>
            <div class="sidebar-section-label">Account</div>
            <a href="<?php echo $baseUrl; ?>/login.php" class="sidebar-link <?php echo isActive('/login.php'); ?>">
                <i class="fas fa-sign-in-alt"></i><span>Login</span>
            </a>
            
        <?php elseif ($userRole === 'student'): ?>
            <div class="sidebar-section-label">Main</div>
            <a href="<?php echo $baseUrl; ?>/student/dashboard.php" class="sidebar-link <?php echo isActive('/student/dashboard.php'); ?>">
                <i class="fas fa-tachometer-alt"></i><span>Dashboard</span>
            </a>
            <a href="<?php echo $baseUrl; ?>/student/appointments/index.php" class="sidebar-link <?php echo isActive('/student/appointments/index.php'); ?>">
                <i class="fas fa-calendar-check"></i><span>Appointments</span>
            </a>
            <a href="<?php echo $baseUrl; ?>/student/chat/index.php" class="sidebar-link <?php echo isActive('/student/chat/index.php'); ?>">
                <i class="fas fa-comments"></i><span>Chat</span>
                <?php if ($unreadChat > 0): ?><span class="sidebar-badge"><?php echo $unreadChat; ?></span><?php endif; ?>
            </a>
            <a href="<?php echo $baseUrl; ?>/student/notifications/index.php" class="sidebar-link <?php echo isActive('/student/notifications/index.php'); ?>">
                <i class="fas fa-bell"></i><span>Notifications</span>
                <?php if ($unreadNotif > 0): ?><span class="sidebar-badge"><?php echo $unreadNotif; ?></span><?php endif; ?>
            </a>
            <div class="sidebar-section-label">Actions</div>
            <a href="<?php echo $baseUrl; ?>/student/counseling-request/create.php" class="sidebar-link <?php echo isActive('/student/counseling-request/create.php'); ?>">
                <i class="fas fa-hand-holding-heart"></i><span>New Request</span>
            </a>
            <a href="<?php echo $baseUrl; ?>/student/inquiries/create.php" class="sidebar-link <?php echo isActive('/student/inquiries/create.php'); ?>">
                <i class="fas fa-envelope-open-text"></i><span>Send Inquiry</span>
            </a>
            <div class="sidebar-section-label">Account</div>
            <a href="<?php echo $baseUrl; ?>/student/profile.php" class="sidebar-link <?php echo isActive('/student/profile.php'); ?>">
                <i class="fas fa-user"></i><span>My Profile</span>
            </a>
            
        <?php elseif ($userRole === 'counselor'): ?>
            <div class="sidebar-section-label">Main</div>
            <a href="<?php echo $baseUrl; ?>/counselor/dashboard.php" class="sidebar-link <?php echo isActive('/counselor/dashboard.php'); ?>">
                <i class="fas fa-tachometer-alt"></i><span>Dashboard</span>
            </a>
            <a href="<?php echo $baseUrl; ?>/counselor/requests/index.php" class="sidebar-link <?php echo isActive('/counselor/requests/index.php'); ?>">
                <i class="fas fa-inbox"></i><span>Requests</span>
            </a>
            <a href="<?php echo $baseUrl; ?>/counselor/appointments/index.php" class="sidebar-link <?php echo isActive('/counselor/appointments/index.php'); ?>">
                <i class="fas fa-calendar-alt"></i><span>Appointments</span>
            </a>
            <a href="<?php echo $baseUrl; ?>/counselor/chat/index.php" class="sidebar-link <?php echo isActive('/counselor/chat/index.php'); ?>">
                <i class="fas fa-comments"></i><span>Chat</span>
                <?php if ($unreadChat > 0): ?><span class="sidebar-badge"><?php echo $unreadChat; ?></span><?php endif; ?>
            </a>
            <div class="sidebar-section-label">Manage</div>
            <a href="<?php echo $baseUrl; ?>/counselor/students/index.php" class="sidebar-link <?php echo isActive('/counselor/students/index.php'); ?>">
                <i class="fas fa-users"></i><span>Students</span>
            </a>
            <a href="<?php echo $baseUrl; ?>/counselor/resources/index.php" class="sidebar-link <?php echo isActive('/counselor/resources/index.php'); ?>">
                <i class="fas fa-book-medical"></i><span>Resources</span>
            </a>
            <a href="<?php echo $baseUrl; ?>/counselor/inquiries/index.php" class="sidebar-link <?php echo isActive('/counselor/inquiries/index.php'); ?>">
                <i class="fas fa-envelope"></i><span>Inquiries</span>
            </a>
            <div class="sidebar-section-label">Account</div>
            <a href="<?php echo $baseUrl; ?>/counselor/profile.php" class="sidebar-link <?php echo isActive('/counselor/profile.php'); ?>">
                <i class="fas fa-user"></i><span>My Profile</span>
            </a>
            
        <?php elseif ($userRole === 'admin'): ?>
            <div class="sidebar-section-label">Main</div>
            <a href="<?php echo $baseUrl; ?>/admin/dashboard.php" class="sidebar-link <?php echo isActive('/admin/dashboard.php'); ?>">
                <i class="fas fa-tachometer-alt"></i><span>Dashboard</span>
            </a>
            <a href="<?php echo $baseUrl; ?>/admin/appointments/index.php" class="sidebar-link <?php echo isActive('/admin/appointments/index.php'); ?>">
                <i class="fas fa-calendar-check"></i><span>Appointments</span>
            </a>
            <a href="<?php echo $baseUrl; ?>/admin/chat/index.php" class="sidebar-link <?php echo isActive('/admin/chat/index.php'); ?>">
                <i class="fas fa-comments"></i><span>Monitor Chats</span>
            </a>
            <div class="sidebar-section-label">Manage</div>
            <a href="<?php echo $baseUrl; ?>/admin/students/index.php" class="sidebar-link <?php echo isActive('/admin/students/index.php'); ?>">
                <i class="fas fa-user-graduate"></i><span>Students</span>
            </a>
            <a href="<?php echo $baseUrl; ?>/admin/counselors/index.php" class="sidebar-link <?php echo isActive('/admin/counselors/index.php'); ?>">
                <i class="fas fa-user-md"></i><span>Counselors</span>
            </a>
            <a href="<?php echo $baseUrl; ?>/admin/resources/index.php" class="sidebar-link <?php echo isActive('/admin/resources/index.php'); ?>">
                <i class="fas fa-book-medical"></i><span>Resources</span>
            </a>
            <a href="<?php echo $baseUrl; ?>/admin/inquiries/index.php" class="sidebar-link <?php echo isActive('/admin/inquiries/index.php'); ?>">
                <i class="fas fa-envelope"></i><span>Inquiries</span>
            </a>
            <div class="sidebar-section-label">Analytics</div>
            <a href="<?php echo $baseUrl; ?>/admin/reports/index.php" class="sidebar-link <?php echo isActive('/admin/reports/index.php'); ?>">
                <i class="fas fa-chart-bar"></i><span>Reports</span>
            </a>
            <div class="sidebar-section-label">Account</div>
            <a href="<?php echo $baseUrl; ?>/admin/profile.php" class="sidebar-link <?php echo isActive('/admin/profile.php'); ?>">
                <i class="fas fa-user-shield"></i><span>My Profile</span>
            </a>
        <?php endif; ?>
    </nav>
    
    <div class="sidebar-footer">
        <button type="button" id="darkModeToggle" class="sidebar-footer-btn">
            <i class="fas fa-moon" id="darkModeIcon"></i>
            <span class="sidebar-footer-label">Dark Mode</span>
        </button>
        <?php if ($userRole): ?>
        <a href="<?php echo $baseUrl; ?>/logout.php" class="sidebar-footer-btn sidebar-logout">
            <i class="fas fa-sign-out-alt"></i>
            <span class="sidebar-footer-label">Logout</span>
        </a>
        <?php endif; ?>
    </div>
</aside>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<header class="mobile-topbar">
    <button type="button" class="mobile-menu-btn" onclick="toggleSidebar()" aria-label="Open menu">
        <i class="fas fa-bars"></i>
    </button>
    <a href="<?php echo $baseUrl; ?>/index.php" class="mobile-brand">
        <img src="<?php echo $logoUrl; ?>" alt="SNHS Logo">
        <span>SNHS Wellness</span>
    </a>
    <button type="button" class="mobile-darkmode-btn" id="darkModeToggleMobile" aria-label="Toggle dark mode">
        <i class="fas fa-moon" id="darkModeIconMobile"></i>
    </button>
</header>