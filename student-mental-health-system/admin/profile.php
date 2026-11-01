<?php
// ============================================
// ADMIN PROFILE (with image upload)
// ============================================

require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/security.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/auth.php';

requireRole('admin');

$userId = $_SESSION['user_id'];
$user = getCurrentUser($pdo);
$error = '';
$success = '';

// Handle profile image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_image'])) {
    if (isset($_FILES['profile_image'])) {
        $result = handleProfileImageUpload($_FILES['profile_image'], $userId, $user);
        if ($result['success']) {
            $stmt = $pdo->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
            $stmt->execute([$result['filename'], $userId]);
            $success = 'Profile image updated successfully!';
            $user = getCurrentUser($pdo);
        } else {
            $error = $result['error'];
        }
    }
}

// Handle remove image
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_image'])) {
    if (!empty($user['profile_image'])) {
        $file = $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/assets/uploads/profiles/' . $user['profile_image'];
        if (file_exists($file)) @unlink($file);
    }
    $stmt = $pdo->prepare("UPDATE users SET profile_image = NULL WHERE id = ?");
    $stmt->execute([$userId]);
    $success = 'Profile image removed.';
    $user = getCurrentUser($pdo);
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    
    if (empty($first_name) || empty($last_name) || empty($email)) {
        $error = 'Please fill in all required fields.';
    } else {
        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?");
        $stmt->execute([$first_name, $last_name, $email, $userId]);
        $_SESSION['user_name'] = $first_name . ' ' . $last_name;
        $success = 'Profile updated successfully!';
        $user = getCurrentUser($pdo);
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'Please fill in all password fields.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match.';
    } elseif (strlen($new_password) < 6) {
        $error = 'New password must be at least 6 characters.';
    } else {
        if (password_verify($current_password, $user['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $userId]);
            $success = 'Password changed successfully!';
        } else {
            $error = 'Current password is incorrect.';
        }
    }
}

$page_title = 'Admin Profile';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/header.php';
?>

<style>
.profile-image-section {
    text-align: center;
    padding: 30px 20px;
    background: linear-gradient(135deg, #F3E8FF, #EFF6FF);
    border-radius: 16px;
    margin-bottom: 25px;
}
[data-theme="dark"] .profile-image-section {
    background: linear-gradient(135deg, #2D0A0A, #450A0A);
}
.profile-image-wrapper {
    width: 140px; height: 140px;
    margin: 0 auto 15px;
    position: relative;
    border-radius: 50%;
    box-shadow: 0 10px 30px rgba(139, 92, 246, 0.3);
    border: 5px solid #fff;
    background: #fff;
    overflow: hidden;
    transition: all 0.3s;
}
[data-theme="dark"] .profile-image-wrapper {
    border-color: #450A0A;
    box-shadow: 0 10px 30px rgba(220, 38, 38, 0.3);
}
.profile-image-wrapper:hover { transform: scale(1.03); }
.profile-image-wrapper img { width: 100%; height: 100%; object-fit: cover; display: block; }
.profile-image-initials {
    width: 100%; height: 100%;
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-weight: 800; font-size: 3.5rem;
}
.profile-image-upload-btn {
    position: absolute; bottom: 5px; right: 5px;
    width: 42px; height: 42px;
    background: linear-gradient(135deg, #8B5CF6, #EC4899);
    color: #fff; border: 3px solid #fff; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: all 0.3s;
    box-shadow: 0 4px 15px rgba(139, 92, 246, 0.4);
}
.profile-image-upload-btn:hover {
    transform: scale(1.15) rotate(10deg);
    background: linear-gradient(135deg, #7C3AED, #DB2777);
}
.profile-name { font-weight: 800; font-size: 1.2rem; color: var(--text); margin-bottom: 3px; }
.profile-id { color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 12px; }
.remove-image-btn {
    background: transparent; border: 1px solid #EF4444; color: #EF4444;
    padding: 5px 14px; border-radius: 20px; font-size: 0.75rem;
    cursor: pointer; transition: all 0.2s; font-weight: 600;
}
.remove-image-btn:hover { background: #EF4444; color: #fff; }
</style>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white border-0 pt-3">
                    <h5 class="fw-bold mb-0"><i class="fas fa-user-shield me-2" style="color: var(--primary);"></i>Profile Information</h5>
                </div>
                <div class="card-body p-4">
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <div class="profile-image-section">
                        <?php $avatar = getProfileAvatar($user); ?>
                        <div class="profile-image-wrapper">
                            <?php if ($avatar['type'] === 'image'): ?>
                                <img src="<?php echo e($avatar['url']); ?>?t=<?php echo time(); ?>" alt="Profile">
                            <?php else: ?>
                                <div class="profile-image-initials" style="background: <?php echo $avatar['color']; ?>;">
                                    <?php echo e($avatar['initials']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <button type="button" class="profile-image-upload-btn" onclick="document.getElementById('imageInput').click();" title="Upload photo">
                                <i class="fas fa-camera"></i>
                            </button>
                        </div>
                        
                        <div class="profile-name"><?php echo e($user['first_name'] . ' ' . $user['last_name']); ?></div>
                        <div class="profile-id"><?php echo e($user['school_id']); ?></div>
                        
                        <form method="POST" action="" enctype="multipart/form-data" id="imageUploadForm" style="display: none;">
                            <input type="hidden" name="upload_image" value="1">
                            <input type="file" name="profile_image" id="imageInput" accept="image/*" onchange="this.form.submit();">
                        </form>
                        
                        <?php if ($avatar['type'] === 'image'): ?>
                            <form method="POST" action="" onsubmit="return confirm('Remove your profile image?');" style="display: inline;">
                                <input type="hidden" name="remove_image" value="1">
                                <button type="submit" class="remove-image-btn"><i class="fas fa-trash me-1"></i>Remove Photo</button>
                            </form>
                        <?php endif; ?>
                        
                        <p class="text-muted small mb-0 mt-2"><i class="fas fa-info-circle me-1"></i>JPG, PNG, GIF, WEBP. Max 5MB.</p>
                    </div>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="update_profile" value="1">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name *</label>
                                <input type="text" class="form-control" name="first_name" value="<?php echo e($user['first_name']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name *</label>
                                <input type="text" class="form-control" name="last_name" value="<?php echo e($user['last_name']); ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control" name="email" value="<?php echo e($user['email']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" value="<?php echo e($user['username']); ?>" disabled>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 rounded-pill"><i class="fas fa-save me-2"></i>Update Profile</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white border-0 pt-3">
                    <h5 class="fw-bold mb-0"><i class="fas fa-lock me-2" style="color: var(--primary);"></i>Change Password</h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="">
                        <input type="hidden" name="change_password" value="1">
                        <div class="mb-3">
                            <label class="form-label">Current Password *</label>
                            <input type="password" class="form-control" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password *</label>
                            <input type="password" class="form-control" name="new_password" required>
                            <small class="text-muted">At least 6 characters.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password *</label>
                            <input type="password" class="form-control" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn btn-outline-primary w-100 rounded-pill"><i class="fas fa-key me-2"></i>Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/footer.php'; ?>