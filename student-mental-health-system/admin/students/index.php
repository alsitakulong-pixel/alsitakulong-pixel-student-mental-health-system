<?php
// ============================================
// ADMIN - MANAGE STUDENT ACCOUNTS
// ============================================

require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/auth.php';

requireRole('admin');

$error = '';
$success = '';

// Activate/Deactivate account
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $studentId = $_POST['student_id'];
    $newStatus = $_POST['new_status'];
    
    $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ? AND role = 'student'");
    $stmt->execute([$newStatus, $studentId]);
    
    $success = 'Student account ' . ($newStatus === 'active' ? 'activated' : 'deactivated') . ' successfully!';
}

// Delete account
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_student'])) {
    $studentId = $_POST['student_id'];
    
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'student'");
    $stmt->execute([$studentId]);
    
    $success = 'Student account deleted!';
}

// Get all students
$stmt = $pdo->prepare("
    SELECT u.*, 
           (SELECT COUNT(*) FROM counseling_requests cr WHERE cr.student_id = u.id) as total_requests
    FROM users u
    WHERE u.role = 'student'
    ORDER BY u.created_at DESC
");
$stmt->execute();
$students = $stmt->fetchAll();

$page_title = 'Manage Students';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h4 class="fw-bold" style="color: var(--primary);"><i class="fas fa-user-graduate me-2"></i>Manage Students</h4>
            <p class="text-muted">View, activate, deactivate, or delete student accounts.</p>
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
            <?php if (count($students) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>School ID</th>
                                <th>Grade/Section</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Total Requests</th>
                                <th>Action</th>
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
                                        <?php if ($student['status'] === 'active'): ?>
                                            <span class="badge bg-success rounded-pill">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger rounded-pill">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary rounded-pill"><?php echo $student['total_requests']; ?></span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <form method="POST" action="" class="d-inline" onsubmit="return confirm('Are you sure?');">
                                                <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                                <input type="hidden" name="new_status" value="<?php echo $student['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                                <button type="submit" name="toggle_status" value="1" class="btn btn-sm btn-outline-warning rounded-pill">
                                                    <?php echo $student['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                                </button>
                                            </form>
                                            <form method="POST" action="" class="d-inline" onsubmit="return confirm('Delete this student account permanently? This will delete all their records!');">
                                                <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                                <button type="submit" name="delete_student" value="1" class="btn btn-sm btn-outline-danger rounded-pill ms-1">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted text-center py-4 mb-0">No students registered.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/footer.php'; ?>