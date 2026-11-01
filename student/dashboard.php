<?php
define('BASE_URL', 'http://localhost/student-mental-health-system');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('student');

$userId = $_SESSION['user_id'];
$user = getCurrentUser($pdo);

// Get dashboard stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM counseling_requests WHERE student_id = ? AND status = 'upcoming'");
$stmt->execute([$userId]);
$upcomingAppointments = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM counseling_requests WHERE student_id = ? AND status = 'pending'");
$stmt->execute([$userId]);
$pendingRequests = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE student_id = ? AND status = 'completed'");
$stmt->execute([$userId]);
$completedSessions = $stmt->fetchColumn();

$unreadNotifications = getUnreadNotifications($pdo, $userId);

// Get upcoming appointment
$stmt = $pdo->prepare("SELECT a.*, u.first_name as counselor_first, u.last_name as counselor_last, cc.name as concern_name 
                        FROM appointments a 
                        JOIN users u ON a.counselor_id = u.id 
                        JOIN counseling_requests cr ON a.counseling_request_id = cr.id 
                        JOIN concern_categories cc ON cr.concern_category_id = cc.id 
                        WHERE a.student_id = ? AND a.status = 'scheduled' 
                        ORDER BY a.appointment_date ASC, a.appointment_time ASC LIMIT 1");
$stmt->execute([$userId]);
$nextAppointment = $stmt->fetch();

// Get recent notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$userId]);
$recentNotifications = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Welcome Card -->
    <div class="card welcome-card mb-4">
        <div class="card-body">
            <h4>Good day, <?php echo $user['first_name']; ?>!</h4>
            <p class="text-muted">Your well-being matters. Counseling support is available whenever you need guidance.</p>
        </div>
    </div>
    
    <!-- Dashboard Stats -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="stat-value"><?php echo $upcomingAppointments; ?></div>
                <div class="stat-label">Upcoming Appointments</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card" style="border-left-color: var(--warning);">
                <div class="stat-value" style="color: var(--warning);"><?php echo $pendingRequests; ?></div>
                <div class="stat-label">Pending Requests</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card" style="border-left-color: var(--success);">
                <div class="stat-value" style="color: var(--success);"><?php echo $completedSessions; ?></div>
                <div class="stat-label">Completed Sessions</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card" style="border-left-color: var(--warm);">
                <div class="stat-value" style="color: var(--warm);"><?php echo $unreadNotifications; ?></div>
                <div class="stat-label">Unread Notifications</div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <a href="<?php echo BASE_URL; ?>/student/counseling-request/create.php" class="quick-action">
                <i class="fas fa-plus-circle mb-2"></i><br>Request Counseling
            </a>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <a href="<?php echo BASE_URL; ?>/student/appointments/index.php" class="quick-action">
                <i class="fas fa-calendar-check mb-2"></i><br>View Appointments
            </a>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <a href="<?php echo BASE_URL; ?>/pages/resources.php" class="quick-action">
                <i class="fas fa-book-heart mb-2"></i><br>Wellness Resources
            </a>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <a href="<?php echo BASE_URL; ?>/student/inquiries/create.php" class="quick-action">
                <i class="fas fa-envelope mb-2"></i><br>Send Inquiry
            </a>
        </div>
    </div>
    
    <div class="row">
        <!-- Upcoming Appointment -->
        <div class="col-md-7 mb-4">
            <div class="card">
                <div class="card-header bg-white">
                    <h5><i class="fas fa-calendar-alt"></i> Upcoming Counseling Appointment</h5>
                </div>
                <div class="card-body">
                    <?php if ($nextAppointment): ?>
                        <div class="appointment-card">
                            <h6><?php echo $nextAppointment['counselor_first'] . ' ' . $nextAppointment['counselor_last']; ?></h6>
                            <p><i class="fas fa-calendar"></i> <?php echo date('F d, Y', strtotime($nextAppointment['appointment_date'])); ?></p>
                            <p><i class="fas fa-clock"></i> <?php echo date('h:i A', strtotime($nextAppointment['appointment_time'])); ?></p>
                            <p><i class="fas fa-tag"></i> <?php echo $nextAppointment['concern_name']; ?></p>
                            <span class="badge bg-success">Scheduled</span>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No upcoming appointments. <a href="<?php echo BASE_URL; ?>/student/counseling-request/create.php">Request counseling</a> if you need support.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Recent Notifications -->
        <div class="col-md-5 mb-4">
            <div class="card">
                <div class="card-header bg-white">
                    <h5><i class="fas fa-bell"></i> Recent Notifications</h5>
                </div>
                <div class="card-body">
                    <?php if (count($recentNotifications) > 0): ?>
                        <?php foreach ($recentNotifications as $notif): ?>
                            <div class="notification-item">
                                <strong><?php echo $notif['title']; ?></strong>
                                <p class="mb-0 text-muted small"><?php echo $notif['message']; ?></p>
                                <small class="text-secondary"><?php echo date('M d, Y h:i A', strtotime($notif['created_at'])); ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">No notifications yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>