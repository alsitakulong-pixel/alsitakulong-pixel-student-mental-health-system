<?php
// ============================================
// COUNSELOR WELLNESS RESOURCES MANAGEMENT
// ============================================

require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/security.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/auth.php';

requireRole('counselor');

$userId = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle add resource
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_resource'])) {
    $title = trim($_POST['title']);
    $category = trim($_POST['category']);
    $short_description = trim($_POST['short_description']);
    $content = trim($_POST['content']);
    
    if (empty($title) || empty($category) || empty($short_description) || empty($content)) {
        $error = 'Please fill in all required fields.';
    } else {
        $featured_image = '';
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/assets/uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $file_ext = strtolower(pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($file_ext, $allowed)) {
                $featured_image = 'resource_' . time() . '_' . uniqid() . '.' . $file_ext;
                move_uploaded_file($_FILES['featured_image']['tmp_name'], $upload_dir . $featured_image);
            }
        }
        
        $stmt = $pdo->prepare("INSERT INTO wellness_resources (title, category, featured_image, short_description, content, published_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $category, $featured_image, $short_description, $content, $userId]);
        
        // Notify all students
        $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'student' AND status = 'active'");
        $stmt->execute();
        while ($student = $stmt->fetch()) {
            createNotification($pdo, $student['id'], 'New Wellness Resource', 'A new resource has been published: ' . $title, 'info');
        }
        
        $success = 'Resource published successfully!';
    }
}

// Handle edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_resource'])) {
    $resourceId = intval($_POST['resource_id']);
    $title = trim($_POST['title']);
    $category = trim($_POST['category']);
    $short_description = trim($_POST['short_description']);
    $content = trim($_POST['content']);
    
    if (empty($title) || empty($category) || empty($short_description) || empty($content)) {
        $error = 'Please fill in all required fields.';
    } else {
        $featured_image = $_POST['existing_image'] ?? '';
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/assets/uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $file_ext = strtolower(pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($file_ext, $allowed)) {
                $featured_image = 'resource_' . time() . '_' . uniqid() . '.' . $file_ext;
                move_uploaded_file($_FILES['featured_image']['tmp_name'], $upload_dir . $featured_image);
            }
        }
        
        // Only allow editing own resources
        $stmt = $pdo->prepare("UPDATE wellness_resources SET title = ?, category = ?, featured_image = ?, short_description = ?, content = ? WHERE id = ? AND published_by = ?");
        $stmt->execute([$title, $category, $featured_image, $short_description, $content, $resourceId, $userId]);
        
        $success = 'Resource updated successfully!';
    }
}

// Handle delete (own only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_resource'])) {
    $resourceId = intval($_POST['resource_id']);
    $stmt = $pdo->prepare("DELETE FROM wellness_resources WHERE id = ? AND published_by = ?");
    $stmt->execute([$resourceId, $userId]);
    $success = 'Resource deleted successfully!';
}

// Get resources - show own first, then others
$stmt = $pdo->prepare("
    SELECT wr.*, u.first_name, u.last_name, u.role,
           (wr.published_by = ?) as is_mine
    FROM wellness_resources wr
    JOIN users u ON wr.published_by = u.id
    ORDER BY is_mine DESC, wr.published_date DESC
");
$stmt->execute([$userId]);
$resources = $stmt->fetchAll();

// Stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM wellness_resources WHERE published_by = ?");
$stmt->execute([$userId]);
$myCount = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM wellness_resources");
$stmt->execute();
$totalCount = $stmt->fetchColumn();

$page_title = 'Wellness Resources';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h4 class="fw-bold" style="color: var(--primary);"><i class="fas fa-book-medical me-2"></i>Wellness Resources</h4>
            <p class="text-muted mb-0">Publish and manage mental wellness resources for students.</p>
        </div>
        <div class="col-md-4 text-end">
            <button type="button" class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addResourceModal">
                <i class="fas fa-plus me-1"></i>Add Resource
            </button>
        </div>
    </div>

    <!-- Stats -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="stat-value"><?php echo $myCount; ?></div>
                <div class="stat-label">My Resources</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card" style="border-left-color: var(--teal);">
                <div class="stat-value" style="color: var(--teal);"><?php echo $totalCount; ?></div>
                <div class="stat-label">All Resources</div>
            </div>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo e($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo e($success); ?></div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body">
            <?php if (count($resources) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Published By</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($resources as $res): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if ($res['featured_image']): ?>
                                                <img src="<?php echo BASE_URL; ?>/assets/uploads/<?php echo e($res['featured_image']); ?>" 
                                                     alt="" style="width: 45px; height: 45px; object-fit: cover; border-radius: 8px; margin-right: 10px;">
                                            <?php else: ?>
                                                <div style="width: 45px; height: 45px; background: var(--lavender); border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-right: 10px;">
                                                    <i class="fas fa-book" style="color: var(--primary);"></i>
                                                </div>
                                            <?php endif; ?>
                                            <strong><?php echo e($res['title']); ?></strong>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-primary rounded-pill"><?php echo e($res['category']); ?></span></td>
                                    <td>
                                        <?php echo e($res['first_name'] . ' ' . $res['last_name']); ?>
                                        <?php if ($res['is_mine']): ?>
                                            <span class="badge bg-success rounded-pill ms-1">You</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($res['published_date'])); ?></td>
                                    <td>
                                        <?php if ($res['status'] === 'published'): ?>
                                            <span class="badge bg-success rounded-pill">Published</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark rounded-pill">Draft</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $res['id']; ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if ($res['is_mine']): ?>
                                                <button type="button" class="btn btn-sm btn-outline-warning rounded-pill" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $res['id']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" action="" class="d-inline" onsubmit="return confirm('Delete this resource?');">
                                                    <input type="hidden" name="resource_id" value="<?php echo $res['id']; ?>">
                                                    <button type="submit" name="delete_resource" value="1" class="btn btn-sm btn-outline-danger rounded-pill ms-1">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-book-open fa-4x text-muted mb-3" style="opacity: 0.3;"></i>
                    <h5>No resources yet</h5>
                    <p class="text-muted">Click "Add Resource" to publish your first wellness resource.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Resource Modal -->
<div class="modal fade" id="addResourceModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Publish New Resource</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="add_resource" value="1">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Title *</label>
                        <input type="text" class="form-control" name="title" required placeholder="e.g., Managing Academic Stress">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category *</label>
                        <select class="form-select" name="category" required>
                            <option value="">-- Select Category --</option>
                            <option value="Stress Management">Stress Management</option>
                            <option value="Study Habits">Study Habits</option>
                            <option value="Emotional Wellness">Emotional Wellness</option>
                            <option value="Coping Strategies">Coping Strategies</option>
                            <option value="Bullying Awareness">Bullying Awareness</option>
                            <option value="Healthy Relationships">Healthy Relationships</option>
                            <option value="Time Management">Time Management</option>
                            <option value="Self-Care">Self-Care</option>
                            <option value="Academic Pressure">Academic Pressure</option>
                            <option value="Career Guidance">Career Guidance</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Featured Image</label>
                        <input type="file" class="form-control" name="featured_image" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Short Description *</label>
                        <textarea class="form-control" name="short_description" rows="2" required maxlength="200"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Full Content *</label>
                        <textarea class="form-control" name="content" rows="10" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill"><i class="fas fa-paper-plane me-1"></i>Publish</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php foreach ($resources as $res): ?>
    <!-- View Modal -->
    <div class="modal fade" id="viewModal<?php echo $res['id']; ?>" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo e($res['title']); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if ($res['featured_image']): ?>
                        <img src="<?php echo BASE_URL; ?>/assets/uploads/<?php echo e($res['featured_image']); ?>" style="width: 100%; max-height: 300px; object-fit: cover; border-radius: 12px; margin-bottom: 20px;">
                    <?php endif; ?>
                    <span class="badge bg-primary rounded-pill mb-3"><?php echo e($res['category']); ?></span>
                    <p><em><?php echo e($res['short_description']); ?></em></p>
                    <hr>
                    <div><?php echo nl2br(e($res['content'])); ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($res['is_mine']): ?>
    <!-- Edit Modal -->
    <div class="modal fade" id="editModal<?php echo $res['id']; ?>" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Resource</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="edit_resource" value="1">
                    <input type="hidden" name="resource_id" value="<?php echo $res['id']; ?>">
                    <input type="hidden" name="existing_image" value="<?php echo e($res['featured_image']); ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Title *</label>
                            <input type="text" class="form-control" name="title" value="<?php echo e($res['title']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category *</label>
                            <select class="form-select" name="category" required>
                                <?php 
                                    $cats = ['Stress Management', 'Study Habits', 'Emotional Wellness', 'Coping Strategies', 'Bullying Awareness', 'Healthy Relationships', 'Time Management', 'Self-Care', 'Academic Pressure', 'Career Guidance'];
                                    foreach ($cats as $cat) {
                                        $sel = ($res['category'] === $cat) ? 'selected' : '';
                                        echo "<option value=\"$cat\" $sel>$cat</option>";
                                    }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Featured Image</label>
                            <input type="file" class="form-control" name="featured_image" accept="image/*">
                            <small class="text-muted">Leave blank to keep existing.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Short Description *</label>
                            <textarea class="form-control" name="short_description" rows="2" required maxlength="200"><?php echo e($res['short_description']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Full Content *</label>
                            <textarea class="form-control" name="content" rows="10" required><?php echo e($res['content']); ?></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary rounded-pill"><i class="fas fa-save me-1"></i>Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
<?php endforeach; ?>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/footer.php'; ?>