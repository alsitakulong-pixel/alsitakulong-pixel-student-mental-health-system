<?php
// ============================================
// ADMIN - MONITOR ALL APPOINTMENTS
// ============================================

require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/auth.php';

requireRole('admin');

// Get all appointments
$stmt = $pdo->prepare("
    SELECT a.*, 
           u.first_name as student_first, u.last_name as student_last, u.school_id,
           c.first_name as counselor_first, c.last_name as counselor_last,
           cc.name as concern_name
    FROM appointments a
    JOIN users u ON a.student_id = u.id
    JOIN users c ON a.counselor_id = c.id
    JOIN counseling_requests cr ON a.counseling_request_id = cr.id
    JOIN concern_categories cc ON cr.concern_category_id = cc.id
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
");
$stmt->execute();
$appointments = $stmt->fetchAll();

$page_title = 'All Appointments';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h4 class="fw-bold" style="color: var(--primary);"><i class="fas fa-calendar-check me-2"></i>All Appointments</h4>
            <p class="text-muted">Monitor all counseling appointments in the system.</p>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body">
            <?php if (count($appointments) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Counselor</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Concern</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $appt): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo e($appt['student_first'] . ' ' . $appt['student_last']); ?></strong>
                                        <br><small class="text-muted"><?php echo e($appt['school_id']); ?></small>
                                    </td>
                                    <td><?php echo e($appt['counselor_first'] . ' ' . $appt['counselor_last']); ?></td>
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

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/footer.php'; ?>