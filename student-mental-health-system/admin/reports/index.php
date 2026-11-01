<?php
// ============================================
// ADMIN - GENERATE REPORTS
// ============================================

require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/auth.php';

requireRole('admin');

// Get filters
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$status = $_GET['status'] ?? '';

// Build query
$where = "WHERE a.appointment_date BETWEEN ? AND ?";
$params = [$start_date, $end_date];

if ($status) {
    $where .= " AND a.status = ?";
    $params[] = $status;
}

// Appointment Report
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
    $where
    ORDER BY a.appointment_date DESC
");
$stmt->execute($params);
$appointments = $stmt->fetchAll();

// Summary Statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_appointments,
        SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN a.status = 'scheduled' THEN 1 ELSE 0 END) as scheduled,
        SUM(CASE WHEN a.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
    FROM appointments a
    $where
");
$stmt->execute($params);
$summary = $stmt->fetch();

// Counseling Requests Report
$stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m-%d') as date,
        COUNT(*) as total
    FROM counseling_requests
    WHERE created_at BETWEEN ? AND ?
    GROUP BY DATE_FORMAT(created_at, '%Y-%m-%d')
    ORDER BY date DESC
");
$stmt->execute([$start_date, $end_date]);
$requests_daily = $stmt->fetchAll();

$page_title = 'Reports';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h4 class="fw-bold" style="color: var(--primary);"><i class="fas fa-chart-bar me-2"></i>System Reports</h4>
            <p class="text-muted">Generate and view system reports.</p>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-body p-4">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Start Date</label>
                    <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">End Date</label>
                    <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">-- All Status --</option>
                        <option value="scheduled" <?php echo $status === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                        <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary rounded-pill w-100"><i class="fas fa-filter me-1"></i>Apply Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="stat-value"><?php echo $summary['total_appointments'] ?? 0; ?></div>
                <div class="stat-label">Total</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card" style="border-left-color: var(--success);">
                <div class="stat-value" style="color: var(--success);"><?php echo $summary['completed'] ?? 0; ?></div>
                <div class="stat-label">Completed</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card" style="border-left-color: var(--primary);">
                <div class="stat-value" style="color: var(--primary);"><?php echo $summary['scheduled'] ?? 0; ?></div>
                <div class="stat-label">Scheduled</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card" style="border-left-color: var(--danger);">
                <div class="stat-value" style="color: var(--danger);"><?php echo $summary['cancelled'] ?? 0; ?></div>
                <div class="stat-label">Cancelled</div>
            </div>
        </div>
    </div>

    <!-- Appointments Report -->
    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-header bg-white border-0 pt-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-calendar-alt me-2" style="color: var(--primary);"></i>Appointment Report</h5>
                <button onclick="window.print()" class="btn btn-sm btn-outline-primary rounded-pill"><i class="fas fa-print me-1"></i>Print</button>
            </div>
        </div>
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
                                    <td><?php echo e($appt['student_first'] . ' ' . $appt['student_last']); ?></td>
                                    <td><?php echo e($appt['counselor_first'] . ' ' . $appt['counselor_last']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($appt['appointment_date'])); ?></td>
                                    <td><?php echo date('h:i A', strtotime($appt['appointment_time'])); ?></td>
                                    <td><?php echo e($appt['concern_name']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $appt['status'] === 'completed' ? 'success' : ($appt['status'] === 'cancelled' ? 'danger' : 'primary'); ?> rounded-pill">
                                            <?php echo ucfirst($appt['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted text-center py-4 mb-0">No appointments found for the selected period.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/footer.php'; ?>