<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireRole('student');

$userId = $_SESSION['user_id'];
$user = getCurrentUser($pdo);

// Get all appointments for this student
$stmt = $pdo->prepare("
    SELECT a.*, 
           u.first_name as cf, u.last_name as cl, 
           cc.name as concern_name,
           cr.description as concern_description
    FROM appointments a
    JOIN users u ON a.counselor_id = u.id
    JOIN counseling_requests cr ON a.counseling_request_id = cr.id
    JOIN concern_categories cc ON cr.concern_category_id = cc.id
    WHERE a.student_id = ?
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
");
$stmt->execute([$userId]);
$appointments = $stmt->fetchAll();

// Get pending counseling requests (not yet appointments)
$stmt = $pdo->prepare("
    SELECT cr.*, cc.name as concern_name, 
           u.first_name as cf, u.last_name as cl
    FROM counseling_requests cr
    LEFT JOIN users u ON cr.counselor_id = u.id
    JOIN concern_categories cc ON cr.concern_category_id = cc.id
    WHERE cr.student_id = ? AND cr.status IN ('pending', 'approved')
    ORDER BY cr.created_at DESC
");
$stmt->execute([$userId]);
$pendingRequests = $stmt->fetchAll();

$page_title = 'My Appointments';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h4 class="fw-bold" style="color: var(--primary);"><i class="fas fa-calendar-check me-2"></i>My Appointments</h4>
            <p class="text-muted">View your counseling appointments and request status.</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="<?php echo BASE_URL; ?>/student/counseling-request/create.php" class="btn btn-primary rounded-pill px-4"><i class="fas fa-plus me-1"></i>New Request</a>
        </div>
    </div>

    <!-- Pending Requests -->
    <?php if (count($pendingRequests) > 0): ?>
        <div class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-header bg-white border-0 pt-3">
                <h6 class="fw-bold mb-0"><i class="fas fa-hourglass-half me-2" style="color: var(--warning);"></i>Pending Requests</h6>
            </div>
            <div class="card-body">
                <?php foreach ($pendingRequests as $request): ?>
                    <div class="notification-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong><?php echo e($request['concern_name']); ?></strong>
                                <p class="mb-1">Requested: <?php echo date('M d, Y', strtotime($request['preferred_date'])); ?> at <?php echo date('h:i A', strtotime($request['preferred_time'])); ?></p>
                                <p class="mb-1 text-muted small"><?php echo e($request['description']); ?></p>
                                <span class="badge bg-warning text-dark rounded-pill"><?php echo ucfirst($request['status']); ?></span>
                            </div>
                            <div class="text-end">
                                <?php if ($request['status'] === 'pending'): ?>
                                    <form method="POST" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to cancel this request?');">
                                        <input type="hidden" name="cancel_request_id" value="<?php echo $request['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill"><i class="fas fa-times me-1"></i>Cancel</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Appointments List -->
    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-header bg-white border-0 pt-3">
            <h6 class="fw-bold mb-0"><i class="fas fa-list me-2" style="color: var(--primary);"></i>Appointment History</h6>
        </div>
        <div class="card-body">
            <?php if (count($appointments) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Counselor</th>
                                <th>Concern</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $appt): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($appt['appointment_date'])); ?></td>
                                    <td><?php echo date('h:i A', strtotime($appt['appointment_time'])); ?></td>
                                    <td><?php echo e($appt['cf'] . ' ' . $appt['cl']); ?></td>
                                    <td><?php echo e($appt['concern_name']); ?></td>
                                    <td>
                                        <?php
                                            $statusClass = '';
                                            $statusLabel = '';
                                            switch($appt['status']) {
                                                case 'scheduled':
                                                    $statusClass = 'bg-primary';
                                                    $statusLabel = 'Scheduled';
                                                    break;
                                                case 'completed':
                                                    $statusClass = 'bg-success';
                                                    $statusLabel = 'Completed';
                                                    break;
                                                case 'cancelled':
                                                    $statusClass = 'bg-danger';
                                                    $statusLabel = 'Cancelled';
                                                    break;
                                                case 'rescheduled':
                                                    $statusClass = 'bg-warning text-dark';
                                                    $statusLabel = 'Rescheduled';
                                                    break;
                                            }
                                        ?>
                                        <span class="badge <?php echo $statusClass; ?> rounded-pill"><?php echo $statusLabel; ?></span>
                                    </td>
                                    <td>
                                        <?php if ($appt['status'] === 'scheduled'): ?>
                                            <form method="POST" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to cancel this appointment?');">
                                                <input type="hidden" name="cancel_appointment_id" value="<?php echo $appt['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill"><i class="fas fa-times me-1"></i>Cancel</button>
                                            </form>
                                        <?php elseif ($appt['status'] === 'completed' && !empty($appt['notes'])): ?>
                                            <button type="button" class="btn btn-sm btn-outline-info rounded-pill" data-bs-toggle="modal" data-bs-target="#notesModal<?php echo $appt['id']; ?>">
                                                <i class="fas fa-file-alt me-1"></i>Notes
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted text-center py-3 mb-0">No appointments yet. <a href="<?php echo BASE_URL; ?>/student/counseling-request/create.php">Request counseling</a> to get started.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Handle cancel request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_request_id'])) {
    $requestId = $_POST['cancel_request_id'];
    $stmt = $pdo->prepare("UPDATE counseling_requests SET status = 'cancelled' WHERE id = ? AND student_id = ?");
    $stmt->execute([$requestId, $userId]);
    createNotification($pdo, $userId, 'Request Cancelled', 'Your counseling request has been cancelled.', 'info');
    redirect(BASE_URL . '/student/appointments/index.php');
}

// Handle cancel appointment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_appointment_id'])) {
    $appointmentId = $_POST['cancel_appointment_id'];
    
    // Get appointment details
    $stmt = $pdo->prepare("SELECT * FROM appointments WHERE id = ? AND student_id = ?");
    $stmt->execute([$appointmentId, $userId]);
    $appointment = $stmt->fetch();
    
    if ($appointment) {
        $stmt = $pdo->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ? AND student_id = ?");
        $stmt->execute([$appointmentId, $userId]);
        
        // Update counseling request status
        $stmt = $pdo->prepare("UPDATE counseling_requests SET status = 'cancelled' WHERE id = ?");
        $stmt->execute([$appointment['counseling_request_id']]);
        
        // Notify student
        createNotification($pdo, $userId, 'Appointment Cancelled', 'Your appointment has been cancelled.', 'info');
        
        // Notify counselor
        createNotification($pdo, $appointment['counselor_id'], 'Appointment Cancelled', 'A student cancelled their appointment.', 'warning');
    }
    redirect(BASE_URL . '/student/appointments/index.php');
}
?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>