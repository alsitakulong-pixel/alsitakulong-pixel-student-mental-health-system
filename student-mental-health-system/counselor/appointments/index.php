<?php
// ============================================
// COUNSELOR APPOINTMENTS MANAGEMENT
// ============================================

require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/auth.php';

requireRole('counselor');

$userId = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $appointmentId = $_POST['appointment_id'] ?? 0;
    
    if ($action === 'complete') {
        $notes = $_POST['notes'] ?? '';
        $recommendations = $_POST['follow_up_recommendations'] ?? '';
        
        $stmt = $pdo->prepare("UPDATE appointments SET status = 'completed' WHERE id = ? AND counselor_id = ?");
        $stmt->execute([$appointmentId, $userId]);
        
        // Add counseling notes
        $stmt = $pdo->prepare("INSERT INTO counseling_notes (appointment_id, counselor_id, notes, follow_up_recommendations) VALUES (?, ?, ?, ?)");
        $stmt->execute([$appointmentId, $userId, $notes, $recommendations]);
        
        // Get student id
        $stmt = $pdo->prepare("SELECT student_id FROM appointments WHERE id = ?");
        $stmt->execute([$appointmentId]);
        $studentId = $stmt->fetchColumn();
        
        // Update counseling request
        $stmt = $pdo->prepare("UPDATE counseling_requests SET status = 'completed' WHERE id = (SELECT counseling_request_id FROM appointments WHERE id = ?)");
        $stmt->execute([$appointmentId]);
        
        createNotification($pdo, $studentId, 'Session Completed', 'Your counseling session has been completed. Thank you!', 'success');
        
        $success = 'Appointment marked as completed!';
    } elseif ($action === 'cancel') {
        $stmt = $pdo->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ? AND counselor_id = ?");
        $stmt->execute([$appointmentId, $userId]);
        
        // Get student id
        $stmt = $pdo->prepare("SELECT student_id FROM appointments WHERE id = ?");
        $stmt->execute([$appointmentId]);
        $studentId = $stmt->fetchColumn();
        
        // Update counseling request
        $stmt = $pdo->prepare("UPDATE counseling_requests SET status = 'cancelled' WHERE id = (SELECT counseling_request_id FROM appointments WHERE id = ?)");
        $stmt->execute([$appointmentId]);
        
        createNotification($pdo, $studentId, 'Appointment Cancelled', 'Your appointment has been cancelled by the counselor.', 'error');
        
        $success = 'Appointment cancelled.';
    } elseif ($action === 'reschedule') {
        $newDate = $_POST['new_date'] ?? '';
        $newTime = $_POST['new_time'] ?? '';
        
        if (empty($newDate) || empty($newTime)) {
            $error = 'Please select a new date and time.';
        } else {
            $stmt = $pdo->prepare("UPDATE appointments SET appointment_date = ?, appointment_time = ?, status = 'scheduled' WHERE id = ? AND counselor_id = ?");
            $stmt->execute([$newDate, $newTime, $appointmentId, $userId]);
            
            // Get student id
            $stmt = $pdo->prepare("SELECT student_id FROM appointments WHERE id = ?");
            $stmt->execute([$appointmentId]);
            $studentId = $stmt->fetchColumn();
            
            createNotification($pdo, $studentId, 'Appointment Rescheduled', 'Your appointment has been rescheduled to ' . date('M d, Y', strtotime($newDate)) . ' at ' . date('h:i A', strtotime($newTime)) . '.', 'warning');
            
            $success = 'Appointment rescheduled!';
        }
    }
}

// Get all appointments
$stmt = $pdo->prepare("
    SELECT a.*, u.first_name, u.last_name, u.school_id, u.grade_level, u.section, cc.name as concern_name
    FROM appointments a
    JOIN users u ON a.student_id = u.id
    JOIN counseling_requests cr ON a.counseling_request_id = cr.id
    JOIN concern_categories cc ON cr.concern_category_id = cc.id
    WHERE a.counselor_id = ?
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
");
$stmt->execute([$userId]);
$appointments = $stmt->fetchAll();

$page_title = 'Manage Appointments';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h4 class="fw-bold" style="color: var(--primary);"><i class="fas fa-calendar-check me-2"></i>My Appointments</h4>
            <p class="text-muted">Manage your counseling appointments.</p>
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
            <?php if (count($appointments) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Concern</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $appt): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo e($appt['first_name'] . ' ' . $appt['last_name']); ?></strong>
                                        <br><small class="text-muted"><?php echo e($appt['school_id']); ?></small>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($appt['appointment_date'])); ?></td>
                                    <td><?php echo date('h:i A', strtotime($appt['appointment_time'])); ?></td>
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
                                            <?php if ($appt['appointment_date'] <= date('Y-m-d')): ?>
                                                <button type="button" class="btn btn-sm btn-success rounded-pill" data-bs-toggle="modal" data-bs-target="#completeModal<?php echo $appt['id']; ?>">
                                                    <i class="fas fa-check me-1"></i>Complete
                                                </button>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-sm btn-outline-warning rounded-pill" data-bs-toggle="modal" data-bs-target="#rescheduleModal<?php echo $appt['id']; ?>">
                                                <i class="fas fa-clock me-1"></i>Reschedule
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill" data-bs-toggle="modal" data-bs-target="#cancelModal<?php echo $appt['id']; ?>">
                                                <i class="fas fa-times me-1"></i>Cancel
                                            </button>
                                        <?php elseif ($appt['status'] === 'completed'): ?>
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
                <p class="text-muted text-center py-4 mb-0">No appointments found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php foreach ($appointments as $appt): ?>
    <?php if ($appt['status'] === 'scheduled'): ?>
        <!-- Complete Modal -->
        <div class="modal fade" id="completeModal<?php echo $appt['id']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Complete Session</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="complete">
                        <input type="hidden" name="appointment_id" value="<?php echo $appt['id']; ?>">
                        <div class="modal-body">
                            <p><strong>Student:</strong> <?php echo e($appt['first_name'] . ' ' . $appt['last_name']); ?></p>
                            <div class="mb-3">
                                <label class="form-label">Counseling Notes *</label>
                                <textarea class="form-control" name="notes" rows="4" required placeholder="Write your counseling notes..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Follow-up Recommendations</label>
                                <textarea class="form-control" name="follow_up_recommendations" rows="3" placeholder="Recommendations for follow-up..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success rounded-pill"><i class="fas fa-check me-1"></i>Complete Session</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Reschedule Modal -->
        <div class="modal fade" id="rescheduleModal<?php echo $appt['id']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Reschedule Appointment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="reschedule">
                        <input type="hidden" name="appointment_id" value="<?php echo $appt['id']; ?>">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">New Date *</label>
                                <input type="date" class="form-control" name="new_date" required min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">New Time *</label>
                                <select class="form-select" name="new_time" required>
                                    <option value="">-- Select Time --</option>
                                    <option value="08:00:00">8:00 AM</option>
                                    <option value="09:00:00">9:00 AM</option>
                                    <option value="10:00:00">10:00 AM</option>
                                    <option value="11:00:00">11:00 AM</option>
                                    <option value="13:00:00">1:00 PM</option>
                                    <option value="14:00:00">2:00 PM</option>
                                    <option value="15:00:00">3:00 PM</option>
                                    <option value="16:00:00">4:00 PM</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-warning rounded-pill"><i class="fas fa-clock me-1"></i>Reschedule</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Cancel Modal -->
        <div class="modal fade" id="cancelModal<?php echo $appt['id']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Cancel Appointment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="cancel">
                        <input type="hidden" name="appointment_id" value="<?php echo $appt['id']; ?>">
                        <div class="modal-body">
                            <p>Are you sure you want to cancel this appointment with <strong><?php echo e($appt['first_name'] . ' ' . $appt['last_name']); ?></strong>?</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">No, Keep</button>
                            <button type="submit" class="btn btn-danger rounded-pill"><i class="fas fa-times me-1"></i>Yes, Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endforeach; ?>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/footer.php'; ?>