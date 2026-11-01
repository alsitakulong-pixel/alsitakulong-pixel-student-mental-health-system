<?php
// ============================================
// STUDENT COUNSELING REQUEST - COMPLETE CATEGORIES
// ============================================

require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/security.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/auth.php';

requireRole('student');

$userId = $_SESSION['user_id'];
$user = getCurrentUser($pdo);
$error = '';
$success = '';

// Fetch ALL concern categories
$stmt = $pdo->prepare("SELECT * FROM concern_categories ORDER BY id ASC");
$stmt->execute();
$allCategories = $stmt->fetchAll();

// Group categories by section for better UX
$categoryGroups = [
    'Academic Concerns' => [
        'Academic Stress', 'Study Difficulties', 'Test Anxiety', 'Academic Pressure',
        'Time Management', 'Low Motivation', 'Failing Grades', 'Subject Difficulty'
    ],
    'Emotional & Mental Health' => [
        'Anxiety', 'Depression', 'Stress Management', 'Anger Management', 'Emotional Concerns',
        'Self-Esteem Issues', 'Identity Crisis', 'Loneliness', 'Grief and Loss', 'Trauma',
        'Suicidal Thoughts', 'Self-Harm', 'Sleep Problems'
    ],
    'Family & Home' => [
        'Family Concerns', 'Parental Pressure', 'Family Conflict', 'Parental Separation',
        'Financial Concerns', 'Domestic Issues', 'Sibling Problems'
    ],
    'Social & Peer' => [
        'Peer Problems', 'Bullying', 'Cyberbullying', 'Peer Pressure', 'Social Anxiety',
        'Making Friends', 'Friendship Conflicts', 'Discrimination', 'Sexual Harassment',
        'Relationship Issues'
    ],
    'School Life' => [
        'School Adjustment', 'Teacher Concerns', 'Classroom Issues', 'Extracurricular Stress',
        'Discipline Concerns', 'Attendance Problems', 'Transfer/New Student'
    ],
    'Personal Development' => [
        'Personal Problems', 'Behavioral Concerns', 'Habit Changes', 'Spiritual Concerns',
        'Life Direction'
    ],
    'Career & Future' => [
        'Career Guidance', 'College Application', 'Course Selection', 'Scholarship Concerns'
    ],
    'Health & Wellness' => [
        'Physical Health', 'Eating Habits', 'Substance Use', 'Screen Addiction',
        'Physical Activity'
    ],
    'Special Support' => [
        'LGBTQ+ Support', 'Special Needs Support', 'Crisis Support', 'Mental Health Awareness'
    ],
    'Other' => [
        'Other Concerns'
    ]
];

// Fetch counselors
$stmt = $pdo->prepare("SELECT id, first_name, last_name FROM users WHERE role = 'counselor' AND status = 'active'");
$stmt->execute();
$counselors = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = $_POST['category_id'] ?? '';
    $counselor_id = $_POST['counselor_id'] ?? '';
    $preferred_date = $_POST['preferred_date'] ?? '';
    $preferred_time = $_POST['preferred_time'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $urgency = $_POST['urgency'] ?? 'normal';

    if (empty($category_id) || empty($preferred_date) || empty($preferred_time) || empty($description)) {
        $error = 'Please fill in all required fields.';
    } else {
        $today = date('Y-m-d');
        if ($preferred_date < $today) {
            $error = 'Preferred date must be today or in the future.';
        } else {
            // Insert request
            $stmt = $pdo->prepare("INSERT INTO counseling_requests (student_id, counselor_id, concern_category_id, preferred_date, preferred_time, description) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $counselor_id ?: null, $category_id, $preferred_date, $preferred_time, $description]);
            
            $requestId = $pdo->lastInsertId();
            
            // Notify student
            $urgencyMsg = $urgency === 'urgent' ? ' (URGENT)' : '';
            createNotification($pdo, $userId, 'Counseling Request Submitted' . $urgencyMsg, 'Your counseling request has been submitted. You will be notified once it is reviewed.', 'info');
            
            // Notify counselor(s)
            if ($counselor_id) {
                createNotification($pdo, $counselor_id, 'New Counseling Request' . $urgencyMsg, 'A student has submitted a new counseling request. Please review it.', $urgency === 'urgent' ? 'error' : 'warning');
            } else {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'counselor' AND status = 'active'");
                $stmt->execute();
                while ($counselor = $stmt->fetch()) {
                    createNotification($pdo, $counselor['id'], 'New Counseling Request' . $urgencyMsg, 'A student has submitted a new counseling request. Please review it.', $urgency === 'urgent' ? 'error' : 'warning');
                }
            }
            
            $success = 'Your counseling request has been submitted successfully!';
            redirect(BASE_URL . '/student/appointments/index.php?success=1');
        }
    }
}

$page_title = 'Request Counseling';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/header.php';
?>

<style>
.category-group {
    border: 2px solid #E5E7EB;
    border-radius: 12px;
    padding: 15px;
    margin-bottom: 12px;
    transition: all 0.3s;
}
.category-group:hover {
    border-color: #8B5CF6;
    box-shadow: 0 4px 12px rgba(139, 92, 246, 0.1);
}
.category-group-title {
    font-weight: 700;
    color: var(--primary);
    font-size: 0.95rem;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
}
.category-group-title i {
    margin-right: 8px;
    font-size: 1.1rem;
}
.category-options {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 8px;
}
.category-radio {
    display: flex;
    align-items: center;
    padding: 8px 12px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    border: 1px solid transparent;
    font-size: 0.85rem;
}
.category-radio:hover {
    background: var(--lavender);
}
.category-radio input {
    margin-right: 8px;
    cursor: pointer;
}
.category-radio.selected {
    background: linear-gradient(135deg, #8B5CF6 0%, #EC4899 100%);
    color: #fff;
    border-color: #8B5CF6;
}
[data-theme="dark"] .category-radio.selected {
    background: linear-gradient(135deg, #7F1D1D 0%, #DC2626 100%);
}
[data-theme="dark"] .category-group {
    border-color: #450A0A;
}
[data-theme="dark"] .category-group:hover {
    border-color: #DC2626;
}
[data-theme="dark"] .category-radio:hover {
    background: #450A0A;
}
.urgent-warning {
    background: linear-gradient(135deg, #FEF3C7, #FDE68A);
    border-left: 4px solid #F59E0B;
    padding: 12px 16px;
    border-radius: 10px;
    margin-bottom: 15px;
    font-size: 0.85rem;
    color: #78350F;
}
[data-theme="dark"] .urgent-warning {
    background: linear-gradient(135deg, #78350F, #92400E);
    color: #FED7AA;
}
.search-categories {
    margin-bottom: 15px;
}
</style>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <h4 class="fw-bold" style="color: var(--primary);"><i class="fas fa-hand-holding-heart me-2"></i>Request Counseling</h4>
                        <p class="text-muted">We're here to support you. Fill out this form to request a counseling session.</p>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo e($error); ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo e($success); ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" id="counselingForm">
                        
                        <!-- Student Info -->
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <label class="form-label">Student Information</label>
                                <div class="p-3" style="background: var(--lavender); border-radius: 10px;">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <small class="text-muted d-block">Name</small>
                                            <strong><?php echo e($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                                        </div>
                                        <div class="col-md-4">
                                            <small class="text-muted d-block">School ID</small>
                                            <strong><?php echo e($user['school_id']); ?></strong>
                                        </div>
                                        <div class="col-md-4">
                                            <small class="text-muted d-block">Grade & Section</small>
                                            <strong><?php echo e($user['grade_level'] . ' - ' . $user['section']); ?></strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Concern Category - Search box -->
                        <div class="mb-3">
                            <label class="form-label">Concern Category *</label>
                            <div class="search-categories">
                                <input type="text" class="form-control" id="categorySearch" placeholder="🔍 Search concern... (e.g., anxiety, bullying, family)" autocomplete="off">
                            </div>
                            <input type="hidden" name="category_id" id="selectedCategoryId" required>
                            <div id="selectedCategoryDisplay" class="mb-3" style="display: none;">
                                <div class="alert alert-success py-2 mb-0">
                                    <i class="fas fa-check-circle me-1"></i> Selected: <strong id="selectedCategoryName"></strong>
                                </div>
                            </div>
                            
                            <!-- Categories grouped -->
                            <div id="categoriesContainer" style="max-height: 450px; overflow-y: auto; padding-right: 5px;">
                                <?php foreach ($categoryGroups as $groupName => $catNames): ?>
                                    <?php 
                                        // Find categories from DB that match this group
                                        $groupCats = array_filter($allCategories, function($cat) use ($catNames) {
                                            return in_array($cat['name'], $catNames);
                                        });
                                        if (count($groupCats) === 0) continue;
                                    ?>
                                    <div class="category-group" data-group="<?php echo e($groupName); ?>">
                                        <div class="category-group-title">
                                            <?php
                                                $icon = 'fa-tag';
                                                if (strpos($groupName, 'Academic') !== false) $icon = 'fa-book';
                                                elseif (strpos($groupName, 'Emotional') !== false) $icon = 'fa-heart';
                                                elseif (strpos($groupName, 'Family') !== false) $icon = 'fa-home';
                                                elseif (strpos($groupName, 'Social') !== false) $icon = 'fa-users';
                                                elseif (strpos($groupName, 'School') !== false) $icon = 'fa-school';
                                                elseif (strpos($groupName, 'Personal') !== false) $icon = 'fa-user';
                                                elseif (strpos($groupName, 'Career') !== false) $icon = 'fa-compass';
                                                elseif (strpos($groupName, 'Health') !== false) $icon = 'fa-heartbeat';
                                                elseif (strpos($groupName, 'Special') !== false) $icon = 'fa-hands-helping';
                                                else $icon = 'fa-ellipsis-h';
                                            ?>
                                            <i class="fas <?php echo $icon; ?>"></i>
                                            <?php echo e($groupName); ?>
                                        </div>
                                        <div class="category-options">
                                            <?php foreach ($groupCats as $cat): ?>
                                                <label class="category-radio" data-name="<?php echo e(strtolower($cat['name'])); ?>">
                                                    <input type="radio" name="category_radio" value="<?php echo $cat['id']; ?>" data-name="<?php echo e($cat['name']); ?>">
                                                    <span><?php echo e($cat['name']); ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Urgency Level -->
                        <div class="mb-3">
                            <label class="form-label">Urgency Level *</label>
                            <div class="urgent-warning">
                                <i class="fas fa-info-circle me-1"></i>
                                <strong>Note:</strong> Kung ito ay emergency o urgent, mangyaring makipag-ugnayan agad sa guidance office.
                            </div>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="category-radio w-100" style="padding: 12px;">
                                        <input type="radio" name="urgency" value="normal" checked>
                                        <span><strong>Normal</strong> — Regular counseling request</span>
                                    </label>
                                </div>
                                <div class="col-md-6">
                                    <label class="category-radio w-100" style="padding: 12px;">
                                        <input type="radio" name="urgency" value="urgent">
                                        <span><strong>Urgent</strong> — Needs immediate attention</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Preferred Counselor -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Preferred Counselor (Optional)</label>
                                <select class="form-select" name="counselor_id">
                                    <option value="">-- Any Available Counselor --</option>
                                    <?php foreach ($counselors as $counselor): ?>
                                        <option value="<?php echo $counselor['id']; ?>"><?php echo e($counselor['first_name'] . ' ' . $counselor['last_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Preferred Date *</label>
                                <input type="date" class="form-control" name="preferred_date" required min="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Preferred Time *</label>
                                <select class="form-select" name="preferred_time" required>
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
                        
                        <div class="mb-4">
                            <label class="form-label">Brief Description of Your Concern *</label>
                            <textarea class="form-control" name="description" rows="4" required placeholder="Please briefly describe what you would like to talk about..."></textarea>
                            <small class="text-muted"><i class="fas fa-lock me-1"></i>This information is confidential and only visible to authorized counselors.</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 py-3 rounded-pill"><i class="fas fa-paper-plane me-2"></i>Submit Request</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Category selection
document.querySelectorAll('input[name="category_radio"]').forEach(radio => {
    radio.addEventListener('change', function() {
        // Remove previous selections
        document.querySelectorAll('.category-radio').forEach(lbl => lbl.classList.remove('selected'));
        // Highlight selected
        this.closest('.category-radio').classList.add('selected');
        // Set hidden input
        document.getElementById('selectedCategoryId').value = this.value;
        // Show selected
        document.getElementById('selectedCategoryName').textContent = this.dataset.name;
        document.getElementById('selectedCategoryDisplay').style.display = 'block';
    });
});

// Search filter
document.getElementById('categorySearch').addEventListener('input', function() {
    const query = this.value.toLowerCase().trim();
    let visibleCount = 0;
    
    document.querySelectorAll('.category-radio').forEach(label => {
        const name = label.dataset.name;
        if (!query || name.includes(query)) {
            label.style.display = 'flex';
            visibleCount++;
        } else {
            label.style.display = 'none';
        }
    });
    
    // Hide groups with no visible items
    document.querySelectorAll('.category-group').forEach(group => {
        const visible = group.querySelectorAll('.category-radio[style*="flex"], .category-radio:not([style*="none"])').length;
        const hasVisible = Array.from(group.querySelectorAll('.category-radio')).some(l => l.style.display !== 'none');
        group.style.display = hasVisible ? 'block' : 'none';
    });
});

// Form validation
document.getElementById('counselingForm').addEventListener('submit', function(e) {
    const selected = document.getElementById('selectedCategoryId').value;
    if (!selected) {
        e.preventDefault();
        alert('Please select a concern category.');
        document.getElementById('categoriesContainer').scrollIntoView({ behavior: 'smooth' });
        return false;
    }
});
</script>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/footer.php'; ?>