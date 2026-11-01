<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/security.php';

// Get category filter
$categoryFilter = isset($_GET['category']) ? trim($_GET['category']) : '';
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$sql = "SELECT wr.*, u.first_name, u.last_name FROM wellness_resources wr JOIN users u ON wr.published_by = u.id WHERE wr.status = 'published'";
$params = [];

if ($categoryFilter) {
    $sql .= " AND wr.category = ?";
    $params[] = $categoryFilter;
}

if ($searchQuery) {
    $sql .= " AND (wr.title LIKE ? OR wr.short_description LIKE ? OR wr.content LIKE ?)";
    $searchTerm = '%' . $searchQuery . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql .= " ORDER BY wr.published_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$resources = $stmt->fetchAll();

// Get categories
$stmt = $pdo->prepare("SELECT DISTINCT category FROM wellness_resources WHERE status = 'published' ORDER BY category");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

$page_title = 'Wellness Resources';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/header.php';
?>

<section class="py-5" style="background: linear-gradient(135deg, #DBEAFE 0%, #E0F2FE 50%, #BFDBFE 100%);">
    <div class="container text-center">
        <h1 class="section-title">Mental Wellness Resources</h1>
        <p class="section-subtitle">Educational materials to support your mental health and well-being.</p>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <!-- Search & Filter -->
        <div class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-body p-3">
                <form method="GET" action="" class="row g-2 align-items-center">
                    <div class="col-md-5">
                        <input type="text" class="form-control" name="search" placeholder="Search resources..." value="<?php echo e($searchQuery); ?>">
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo e($cat); ?>" <?php echo $categoryFilter === $cat ? 'selected' : ''; ?>><?php echo e($cat); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100 rounded-pill"><i class="fas fa-search me-1"></i>Search</button>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if ($categoryFilter || $searchQuery): ?>
            <p class="text-muted">
                Showing <?php echo count($resources); ?> result(s)
                <?php if ($categoryFilter): ?> in <strong><?php echo e($categoryFilter); ?></strong><?php endif; ?>
                <?php if ($searchQuery): ?> for "<strong><?php echo e($searchQuery); ?></strong>"<?php endif; ?>
                — <a href="<?php echo BASE_URL; ?>/pages/resources.php">Clear filters</a>
            </p>
        <?php endif; ?>
        
        <?php if (count($resources) > 0): ?>
            <div class="row">
                <?php foreach ($resources as $resource): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card service-card h-100">
                            <?php if ($resource['featured_image'] && file_exists($_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/assets/uploads/' . $resource['featured_image'])): ?>
                                <img src="<?php echo BASE_URL; ?>/assets/uploads/<?php echo e($resource['featured_image']); ?>" class="card-img-top" alt="<?php echo e($resource['title']); ?>" style="height: 200px; object-fit: cover; border-radius: 16px 16px 0 0;">
                            <?php else: ?>
                                <div class="card-img-top d-flex align-items-center justify-content-center" style="height: 200px; background: linear-gradient(135deg, var(--lavender), var(--sky)); border-radius: 16px 16px 0 0;">
                                    <i class="fas fa-book-medical fa-4x" style="color: var(--primary);"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body p-4">
                                <span class="badge bg-primary rounded-pill mb-2"><?php echo e($resource['category']); ?></span>
                                <h5 class="fw-bold"><?php echo e($resource['title']); ?></h5>
                                <p class="text-muted" style="font-size: 0.9rem;"><?php echo e($resource['short_description']); ?></p>
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#resourceModal<?php echo $resource['id']; ?>">
                                    Read More <i class="fas fa-arrow-right ms-1"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Resource Modal -->
                    <div class="modal fade" id="resourceModal<?php echo $resource['id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title fw-bold"><?php echo e($resource['title']); ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <?php if ($resource['featured_image'] && file_exists($_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/assets/uploads/' . $resource['featured_image'])): ?>
                                        <img src="<?php echo BASE_URL; ?>/assets/uploads/<?php echo e($resource['featured_image']); ?>" style="width: 100%; max-height: 300px; object-fit: cover; border-radius: 12px; margin-bottom: 20px;">
                                    <?php endif; ?>
                                    <span class="badge bg-primary rounded-pill mb-3"><?php echo e($resource['category']); ?></span>
                                    <p class="text-muted small">
                                        By <?php echo e($resource['first_name'] . ' ' . $resource['last_name']); ?> • 
                                        <?php echo date('F d, Y', strtotime($resource['published_date'])); ?>
                                    </p>
                                    <p><em><?php echo e($resource['short_description']); ?></em></p>
                                    <hr>
                                    <div style="line-height: 1.8;"><?php echo nl2br(e($resource['content'])); ?></div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-book-open fa-4x text-muted mb-3" style="opacity: 0.3;"></i>
                <h4>No resources found</h4>
                <p class="text-muted">Try a different search term or category.</p>
                <a href="<?php echo BASE_URL; ?>/pages/resources.php" class="btn btn-primary rounded-pill"><i class="fas fa-redo me-1"></i>Reset Filters</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/footer.php'; ?>