<?php
// ============================================
// ADMIN DASHBOARD - COMPLETE
// ============================================

require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/auth.php';

requireRole('admin');

$userId = $_SESSION['user_id'];
$user = getCurrentUser($pdo);

// Dashboard stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'student' AND status = 'active'");
$stmt->execute();
$totalStudents = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'counselor' AND status = 'active'");
$stmt->execute();
$totalCounselors = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM counseling_requests WHERE status = 'pending'");
$stmt->execute();
$pendingRequests = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE status = 'scheduled'");
$stmt->execute();
$approvedAppointments = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE status = 'completed'");
$stmt->execute();
$completedSessions = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM inquiries");
$stmt->execute();
$totalInquiries = $stmt->fetchColumn();

// Monthly counseling requests (last 6 months)
$stmt = $pdo->prepare("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
    FROM counseling_requests
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
");
$stmt->execute();
$monthlyRequests = $stmt->fetchAll();

// Appointment status distribution
$stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM appointments GROUP BY status");
$stmt->execute();
$appointmentStatus = $stmt->fetchAll();

// Most common concern categories
$stmt = $pdo->prepare("
    SELECT cc.name, COUNT(*) as count
    FROM counseling_requests cr
    JOIN concern_categories cc ON cr.concern_category_id = cc.id
    GROUP BY cc.id, cc.name
    ORDER BY count DESC
    LIMIT 5
");
$stmt->execute();
$topConcerns = $stmt->fetchAll();

// Recent inquiries
$stmt = $pdo->prepare("
    SELECT i.*, u.first_name, u.last_name
    FROM inquiries i
    JOIN users u ON i.student_id = u.id
    ORDER BY i.created_at DESC
    LIMIT 5
");
$stmt->execute();
$recentInquiries = $stmt->fetchAll();

$page_title = 'Admin Dashboard';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/header.php';
?>

<div class="container py-4">
    <!-- Welcome Card -->
    <div class="card welcome-card animate__animated animate__fadeIn">
        <div class="card-body d-flex align-items-center">
            <div class="flex-grow-1">
                <h4><i class="fas fa-user-shield me-2"></i>Welcome, <?php echo e($user['first_name']); ?>!</h4>
                <p class="mb-0">System overview and management dashboard.</p>
            </div>
            <div class="d-none d-md-block">
                <i class="fas fa-chart-line fa-3x" style="color: var(--primary);"></i>
            </div>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="stat-value"><?php echo $totalStudents; ?></div>
                <div class="stat-label">Total Students</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card" style="border-left-color: var(--teal);">
                <div class="stat-value" style="color: var(--teal);"><?php echo $totalCounselors; ?></div>
                <div class="stat-label">Counselors</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card" style="border-left-color: var(--warning);">
                <div class="stat-value" style="color: var(--warning);"><?php echo $pendingRequests; ?></div>
                <div class="stat-label">Pending Requests</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card" style="border-left-color: var(--secondary);">
                <div class="stat-value" style="color: var(--secondary);"><?php echo $approvedAppointments; ?></div>
                <div class="stat-label">Scheduled</div>
            </div>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card" style="border-left-color: var(--success);">
                <div class="stat-value" style="color: var(--success);"><?php echo $completedSessions; ?></div>
                <div class="stat-label">Completed</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card" style="border-left-color: var(--warm);">
                <div class="stat-value" style="color: var(--warm);"><?php echo $totalInquiries; ?></div>
                <div class="stat-label">Total Inquiries</div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <a href="<?php echo BASE_URL; ?>/admin/students/index.php" class="quick-action">
                <i class="fas fa-users"></i>Manage Students
            </a>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <a href="<?php echo BASE_URL; ?>/admin/counselors/index.php" class="quick-action">
                <i class="fas fa-user-md"></i>Manage Counselors
            </a>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <a href="<?php echo BASE_URL; ?>/admin/appointments/index.php" class="quick-action">
                <i class="fas fa-calendar-check"></i>Appointments
            </a>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <a href="<?php echo BASE_URL; ?>/admin/reports/index.php" class="quick-action">
                <i class="fas fa-chart-bar"></i>Reports
            </a>
        </div>
    </div>
    
    <div class="row">
        <!-- Monthly Requests Chart (Simple Bar) -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white border-0 pt-3">
                    <h5 class="mb-0"><i class="fas fa-chart-line me-2" style="color: var(--primary);"></i>Counseling Requests (Last 6 Months)</h5>
                </div>
                <div class="card-body">
                    <?php if (count($monthlyRequests) > 0): ?>
                        <?php
                            // Prepare data for chart
                            $labels = [];
                            $data = [];
                            foreach ($monthlyRequests as $row) {
                                $monthYear = $row['month'];
                                $labels[] = date('M Y', strtotime($monthYear . '-01'));
                                $data[] = (int)$row['count'];
                            }
                        ?>
                        <canvas id="monthlyChart" height="200"></canvas>
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                var ctx = document.getElementById('monthlyChart').getContext('2d');
                                var chart = new Chart(ctx, {
                                    type: 'bar',
                                    data: {
                                        labels: <?php echo json_encode($labels); ?>,
                                        datasets: [{
                                            label: 'Counseling Requests',
                                            data: <?php echo json_encode($data); ?>,
                                            backgroundColor: '#334EAC',
                                            borderRadius: 8
                                        }]
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        plugins: {
                                            legend: { display: false }
                                        },
                                        scales: {
                                            y: { beginAtZero: true, grid: { display: true } },
                                            x: { grid: { display: false } }
                                        }
                                    }
                                });
                            });
                        </script>
                    <?php else: ?>
                        <p class="text-muted text-center py-3 mb-0">No data available.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Top Concerns -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white border-0 pt-3">
                    <h5 class="mb-0"><i class="fas fa-tags me-2" style="color: var(--teal);"></i>Top Concern Categories</h5>
                </div>
                <div class="card-body">
                    <?php if (count($topConcerns) > 0): ?>
                        <?php foreach ($topConcerns as $index => $concern): ?>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <span class="badge bg-<?php echo ['primary', 'success', 'warning', 'info', 'danger'][$index]; ?> rounded-pill me-2"><?php echo $index + 1; ?></span>
                                    <strong><?php echo e($concern['name']); ?></strong>
                                </div>
                                <span class="badge bg-light text-dark rounded-pill"><?php echo $concern['count']; ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted text-center py-3 mb-0">No data available.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Inquiries -->
    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-header bg-white border-0 pt-3">
            <h5 class="mb-0"><i class="fas fa-envelope me-2" style="color: var(--warm);"></i>Recent Inquiries</h5>
        </div>
        <div class="card-body">
            <?php if (count($recentInquiries) > 0): ?>
                <?php foreach ($recentInquiries as $inquiry): ?>
                    <div class="notification-item">
                        <strong><?php echo e($inquiry['first_name'] . ' ' . $inquiry['last_name']); ?></strong>
                        <p class="mb-1"><?php echo e($inquiry['subject']); ?></p>
                        <small class="text-secondary"><?php echo date('M d, Y h:i A', strtotime($inquiry['created_at'])); ?></small>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted text-center py-3 mb-0">No inquiries received.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/footer.php'; ?>