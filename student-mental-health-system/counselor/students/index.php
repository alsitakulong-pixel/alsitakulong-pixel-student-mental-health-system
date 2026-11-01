<?php
// ============================================
// COUNSELOR STUDENTS LIST
// ============================================

require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/auth.php';

requireRole('counselor');

// Get all students
$stmt = $pdo->prepare("
    SELECT u.*, 
           (SELECT COUNT(*) FROM counseling_requests cr WHERE cr.student_id = u.id) as total_requests,
           (SELECT COUNT(*) FROM appointments a WHERE a.student_id = u.id AND a.status = 'completed') as completed_sessions
    FROM users u
    WHERE u.role = 'student' AND u.status = 'active'
    ORDER BY u.last_name ASC
");
$stmt->execute();
$students = $stmt->fetchAll();

$page_title = 'Students';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h4 class="fw-bold" style="color: var(--primary);"><i class="fas fa-users me-2"></i>Registered Students</h4>
            <p class="text-muted">View all registered students and their counseling history.</p>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body">
            <?php if (count($students) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>School ID</th>
                                <th>Grade/Section</th>
                                <th>Email</th>
                                <th>Total Requests</th>
                                <th>Completed Sessions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo e($student['first_name'] . ' ' . $student['last_name']); ?></strong>
                                    </td>
                                    <td><?php echo e($student['school_id']); ?></td>
                                    <td><?php echo e($student['grade_level'] . ' - ' . $student['section']); ?></td>
                                    <td><?php echo e($student['email']); ?></td>
                                    <td>
                                        <span class="badge bg-primary rounded-pill"><?php echo $student['total_requests']; ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success rounded-pill"><?php echo $student['completed_sessions']; ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted text-center py-4 mb-0">No students registered yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/footer.php'; ?>