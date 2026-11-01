<?php
// ============================================
// COUNSELOR REQUESTS MANAGEMENT
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
    $requestId = $_POST['request_id'] ?? 0;
    
    if ($action === 'approve') {
        $appointmentDate = $_POST['appointment_date'] ?? '';
        $appointmentTime = $_POST['appointment_time'] ?? '';
        
        if (empty($appointmentDate) || empty($appointmentTime)) {
            $error = 'Please set an appointment date and time.';
        } else {
            // Get request details
            $stmt = $pdo->prepare("SELECT * FROM counseling_requests WHERE id = ?");
            $stmt->execute([$requestId]);
            $request = $stmt->fetch();
            
            if ($request) {
                // Update request status
                $stmt = $pdo->prepare("UPDATE counseling_requests SET status = 'scheduled', counselor_id = ? WHERE id = ?");
                $stmt->execute([$userId, $requestId]);
                
                // Create appointment
                $stmt = $pdo->prepare("INSERT INTO appointments (counseling_request_id, student_id, counselor_id, appointment_date, appointment_time) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$requestId, $request['student_id'], $userId, $appointmentDate, $appointmentTime]);
                
                // Notify student
                createNotification($pdo, $request['student_id'], 'Appointment Scheduled', 'Your counseling request has been approved and appointment scheduled for ' . date('M d, Y', strtotime($appointmentDate)) . ' at ' . date('h:i A', strtotime($appointmentTime)) . '.', 'success');
                
                $success = 'Request approved and appointment scheduled!';
            }
        }
    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare("UPDATE counseling_requests SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$requestId]);
        
        // Get student id
        $stmt = $pdo->prepare("SELECT student_id FROM counseling_requests WHERE id = ?");
        $stmt->execute([$requestId]);
        $studentId = $stmt->fetchColumn();
        
        createNotification($pdo, $studentId, 'Request Rejected', 'Your counseling request has been rejected. Please contact the guidance office for more information.', 'error');
        
        $success = 'Request rejected.';
    }
}

// Get all requests
$stmt = $pdo->prepare("
    SELECT cr.*, u.first_name, u.last_name, u.school_id, u.grade_level, u.section, cc.name as concern_name
    FROM counseling_requests cr
    JOIN users u ON cr.student_id = u.id
    JOIN concern_categories cc ON cr.concern_category_id = cc.id
    WHERE cr.status IN ('pending', 'scheduled')
    ORDER BY cr.created_at DESC
");
$stmt->execute();
$requests = $stmt->fetchAll();

// Get all students for dropdown (not needed but useful)
$stmt = $pdo->prepare("SELECT id, first_name, last_name, school_id FROM users WHERE role = 'student' AND status = 'active'");
$stmt->execute();
$students = $stmt->fetchAll();

$page_title = 'Manage Requests';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h4 class="fw-bold" style="color: var(--primary);"><i class="fas fa-inbox me-2"></i>Counseling Requests</h4>
            <p class="text-muted">Review and manage student counseling requests.</p>
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
            <?php if (count($requests) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>School ID</th>
                                <th>Grade/Section</th>
                                <th>Concern</th>
                                <th>Preferred Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $req): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo e($req['first_name'] . ' ' . $req['last_name']); ?></strong>
                                        <br><small class="text-muted"><?php echo e($req['description']); ?></small>
                                    </td>
                                    <td><?php echo e($req['school_id']); ?></td>
                                    <td><?php echo e($req['grade_level'] . ' - ' . $req['section']); ?></td>
                                    <td><?php echo e($req['concern_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($req['preferred_date'])); ?><br><small><?php echo date('h:i A', strtotime($req['preferred_time'])); ?></small></td>
                                    <td>
                                        <?php if ($req['status'] === 'pending'): ?>
                                            <span class="badge bg-warning text-dark rounded-pill">Pending</span>
                                        <?php elseif ($req['status'] === 'scheduled'): ?>
                                            <span class="badge bg-primary rounded-pill">Scheduled</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($req['status'] === 'pending'): ?>
                                            <button type="button" class="btn btn-sm btn-success rounded-pill" data-bs-toggle="modal" data-bs-target="#approveModal<?php echo $req['id']; ?>">
                                                <i class="fas fa-check me-1"></i>Approve
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill" data-bs-toggle="modal" data-bs-target="#rejectModal<?php echo $req['id']; ?>">
                                                <i class="fas fa-times me-1"></i>Reject
                                            </button>
                                        <?php elseif ($req['status'] === 'scheduled'): ?>
                                            <span class="text-muted small"><i class="fas fa-clock me-1"></i>Waiting</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted text-center py-4 mb-0">No counseling requests found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php foreach ($requests as $req): ?>
    <?php if ($req['status'] === 'pending'): ?>
        <!-- Approve Modal -->
        <div class="modal fade" id="approveModal<?php echo $req['id']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Approve Request & Schedule</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                        <div class="modal-body">
                            <p><strong>Student:</strong> <?php echo e($req['first_name'] . ' ' . $req['last_name']); ?></p>
                            <p><strong>Concern:</strong> <?php echo e($req['concern_name']); ?></p>
                            <div class="mb-3">
                                <label class="form-label">Appointment Date *</label>
                                <input type="date" class="form-control" name="appointment_date" required min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Appointment Time *</label>
                                <select class="form-select" name="appointment_time" required>
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
                            <button type="submit" class="btn btn-success rounded-pill"><i class="fas fa-check me-1"></i>Approve</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Reject Modal -->
        <div class="modal fade" id="rejectModal<?php echo $req['id']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Reject Request</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                        <div class="modal-body">
                            <p>Are you sure you want to reject this counseling request from <strong><?php echo e($req['first_name'] . ' ' . $req['last_name']); ?></strong>?</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger rounded-pill"><i class="fas fa-times me-1"></i>Reject</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endforeach; ?>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/footer.php'; ?>