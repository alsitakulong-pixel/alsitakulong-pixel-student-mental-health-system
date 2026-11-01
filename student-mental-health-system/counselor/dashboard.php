<?php
// ============================================
// COUNSELOR DASHBOARD - COMPLETE
// ============================================

require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/auth.php';

requireRole('counselor');

$userId = $_SESSION['user_id'];
$user = getCurrentUser($pdo);

// Dashboard stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM counseling_requests WHERE status = 'pending'");
$stmt->execute();
$pendingRequests = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE counselor_id = ? AND appointment_date = CURDATE() AND status = 'scheduled'");
$stmt->execute([$userId]);
$todayAppointments = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE counselor_id = ? AND status = 'scheduled' AND appointment_date > CURDATE()");
$stmt->execute([$userId]);
$upcomingAppointments = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE counselor_id = ? AND status = 'completed'");
$stmt->execute([$userId]);
$completedSessions = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM inquiries WHERE status = 'new'");
$stmt->execute();
$newInquiries = $stmt->fetchColumn();

// Today's schedule
$stmt = $pdo->prepare("
    SELECT a.*, u.first_name, u.last_name, cc.name as concern_name
    FROM appointments a
    JOIN users u ON a.student_id = u.id
    JOIN counseling_requests cr ON a.counseling_request_id = cr.id
    JOIN concern_categories cc ON cr.concern_category_id = cc.id
    WHERE a.counselor_id = ? AND a.appointment_date = CURDATE() AND a.status = 'scheduled'
    ORDER BY a.appointment_time ASC
");
$stmt->execute([$userId]);
$todaySchedule = $stmt->fetchAll();

// Recent counseling requests
$stmt = $pdo->prepare("
    SELECT cr.*, u.first_name, u.last_name, u.school_id, u.grade_level, u.section, cc.name as concern_name
    FROM counseling_requests cr
    JOIN users u ON cr.student_id = u.id
    JOIN concern_categories cc ON cr.concern_category_id = cc.id
    WHERE cr.status = 'pending'
    ORDER BY cr.created_at DESC
    LIMIT 10
");
$stmt->execute();
$recentRequests = $stmt->fetchAll();

// Unread notifications count
$unreadNotifications = getUnreadNotifications($pdo, $userId);

// Recent notifications
$recentNotifications = getUserNotifications($pdo, $userId, 5);

$page_title = 'Counselor Dashboard';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/header.php';
?>

<div class="container py-4">
    <!-- Welcome Card -->
    <div class="card welcome-card animate__animated animate__fadeIn">
        <div class="card-body d-flex align-items-center">
            <div class="flex-grow-1">
                <h4><i class="fas fa-hand-holding-heart me-2"></i>Welcome, <?php echo e($user['first_name']); ?>!</h4>
                <p class="mb-0">You have <?php echo $pendingRequests; ?> pending request(s) and <?php echo $todayAppointments; ?> appointment(s) today.</p>
            </div>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="stat-value" style="color: var(--warning);"><?php echo $pendingRequests; ?></div>
                <div class="stat-label">Pending Requests</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card" style="border-left-color: var(--teal);">
                <div class="stat-value" style="color: var(--teal);"><?php echo $todayAppointments; ?></div>
                <div class="stat-label">Today's Appointments</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card" style="border-left-color: var(--secondary);">
                <div class="stat-value" style="color: var(--secondary);"><?php echo $upcomingAppointments; ?></div>
                <div class="stat-label">Upcoming</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card" style="border-left-color: var(--success);">
                <div class="stat-value" style="color: var(--success);"><?php echo $completedSessions; ?></div>
                <div class="stat-label">Completed</div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <a href="<?php echo BASE_URL; ?>/counselor/requests/index.php" class="quick-action">
                <i class="fas fa-inbox"></i>View Requests
            </a>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <a href="<?php echo BASE_URL; ?>/counselor/appointments/index.php" class="quick-action">
                <i class="fas fa-calendar-alt"></i>Manage Schedule
            </a>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <a href="<?php echo BASE_URL; ?>/counselor/students/index.php" class="quick-action">
                <i class="fas fa-users"></i>View Students
            </a>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <a href="<?php echo BASE_URL; ?>/counselor/resources/index.php" class="quick-action">
                <i class="fas fa-book-medical"></i>Add Resource
            </a>
        </div>
    </div>
    
    <div class="row">
        <!-- Today's Schedule -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white border-0 pt-3">
                    <h5 class="mb-0"><i class="fas fa-calendar-day me-2" style="color: var(--teal);"></i>Today's Schedule</h5>
                </div>
                <div class="card-body">
                    <?php if (count($todaySchedule) > 0): ?>
                        <?php foreach ($todaySchedule as $appt): ?>
                            <div class="notification-item" style="border-left-color: var(--teal);">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <strong><?php echo e($appt['first_name'] . ' ' . $appt['last_name']); ?></strong>
                                        <p class="mb-1 small"><?php echo e($appt['concern_name']); ?></p>
                                        <small class="text-secondary"><?php echo date('h:i A', strtotime($appt['appointment_time'])); ?></small>
                                    </div>
                                    <a href="<?php echo BASE_URL; ?>/counselor/appointments/index.php?appointment_id=<?php echo $appt['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill">View</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted text-center py-3 mb-0">No appointments scheduled for today.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Recent Requests -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white border-0 pt-3">
                    <h5 class="mb-0"><i class="fas fa-inbox me-2" style="color: var(--warning);"></i>Recent Requests</h5>
                </div>
                <div class="card-body">
                    <?php if (count($recentRequests) > 0): ?>
                        <?php foreach ($recentRequests as $req): ?>
                            <div class="notification-item" style="border-left-color: var(--warning);">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <strong><?php echo e($req['first_name'] . ' ' . $req['last_name']); ?></strong>
                                        <p class="mb-1 small"><?php echo e($req['concern_name']); ?></p>
                                        <small class="text-secondary">Requested: <?php echo date('M d, Y', strtotime($req['preferred_date'])); ?> at <?php echo date('h:i A', strtotime($req['preferred_time'])); ?></small>
                                    </div>
                                    <a href="<?php echo BASE_URL; ?>/counselor/requests/index.php?request_id=<?php echo $req['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill">Review</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted text-center py-3 mb-0">No pending requests.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/footer.php'; ?>