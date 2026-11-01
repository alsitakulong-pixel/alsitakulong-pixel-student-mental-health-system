<?php
// ============================================
// ADMIN - MANAGE COUNSELOR ACCOUNTS
// ============================================

require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/auth.php';

requireRole('admin');

$error = '';
$success = '';

// Add counselor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_counselor'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $email = trim($_POST['email']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $school_id = trim($_POST['school_id']);
    
    if (empty($username) || empty($password) || empty($email) || empty($first_name) || empty($last_name) || empty($school_id)) {
        $error = 'Please fill in all required fields.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        // Check if username exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = 'Username already exists.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (role, username, password, email, first_name, last_name, school_id) VALUES ('counselor', ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $hashed_password, $email, $first_name, $last_name, $school_id]);
            
            $success = 'Counselor account created successfully!';
        }
    }
}

// Toggle status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $counselorId = $_POST['counselor_id'];
    $newStatus = $_POST['new_status'];
    
    $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ? AND role = 'counselor'");
    $stmt->execute([$newStatus, $counselorId]);
    
    $success = 'Counselor account ' . ($newStatus === 'active' ? 'activated' : 'deactivated') . ' successfully!';
}

// Delete counselor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_counselor'])) {
    $counselorId = $_POST['counselor_id'];
    
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'counselor'");
    $stmt->execute([$counselorId]);
    
    $success = 'Counselor account deleted!';
}

// Get all counselors
$stmt = $pdo->prepare("
    SELECT u.*, 
           (SELECT COUNT(*) FROM counseling_requests cr WHERE cr.counselor_id = u.id) as total_requests,
           (SELECT COUNT(*) FROM appointments a WHERE a.counselor_id = u.id AND a.status = 'completed') as completed_sessions
    FROM users u
    WHERE u.role = 'counselor'
    ORDER BY u.created_at DESC
");
$stmt->execute();
$counselors = $stmt->fetchAll();

$page_title = 'Manage Counselors';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h4 class="fw-bold" style="color: var(--primary);"><i class="fas fa-user-md me-2"></i>Manage Counselors</h4>
            <p class="text-muted">Add, activate, deactivate, or delete counselor accounts.</p>
        </div>
        <div class="col-md-4 text-end">
            <button type="button" class="btn btn-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#addCounselorModal">
                <i class="fas fa-plus me-1"></i>Add Counselor
            </button>
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
            <?php if (count($counselors) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Counselor</th>
                                <th>School ID</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Completed Sessions</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($counselors as $counselor): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo e($counselor['first_name'] . ' ' . $counselor['last_name']); ?></strong>
                                    </td>
                                    <td><?php echo e($counselor['school_id']); ?></td>
                                    <td><?php echo e($counselor['email']); ?></td>
                                    <td>
                                        <?php if ($counselor['status'] === 'active'): ?>
                                            <span class="badge bg-success rounded-pill">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger rounded-pill">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-success rounded-pill"><?php echo $counselor['completed_sessions']; ?></span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <form method="POST" action="" class="d-inline" onsubmit="return confirm('Are you sure?');">
                                                <input type="hidden" name="counselor_id" value="<?php echo $counselor['id']; ?>">
                                                <input type="hidden" name="new_status" value="<?php echo $counselor['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                                <button type="submit" name="toggle_status" value="1" class="btn btn-sm btn-outline-warning rounded-pill">
                                                    <?php echo $counselor['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                                </button>
                                            </form>
                                            <form method="POST" action="" class="d-inline" onsubmit="return confirm('Delete this counselor account permanently?');">
                                                <input type="hidden" name="counselor_id" value="<?php echo $counselor['id']; ?>">
                                                <button type="submit" name="delete_counselor" value="1" class="btn btn-sm btn-outline-danger rounded-pill ms-1">
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
                <p class="text-muted text-center py-4 mb-0">No counselors found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Counselor Modal -->
<div class="modal fade" id="addCounselorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Counselor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="add_counselor" value="1">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">First Name *</label>
                        <input type="text" class="form-control" name="first_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Last Name *</label>
                        <input type="text" class="form-control" name="last_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username *</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">School ID *</label>
                        <input type="text" class="form-control" name="school_id" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password *</label>
                        <input type="password" class="form-control" name="password" required>
                        <small class="text-muted">At least 6 characters.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill"><i class="fas fa-plus me-1"></i>Add Counselor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/footer.php'; ?>