<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$page_title = 'Counseling Services';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="py-5" style="background: linear-gradient(135deg, var(--lavender) 0%, var(--sky) 100%);">
    <div class="container">
        <h2 class="section-title text-center">Counseling Services</h2>
        <p class="section-subtitle text-center">We offer a range of services to support your well-being.</p>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card service-card h-100 text-center">
                    <i class="fas fa-user-friends service-icon"></i>
                    <h5>Individual Counseling</h5>
                    <p>Private one-on-one guidance for students experiencing personal, emotional, or academic concerns.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card service-card h-100 text-center">
                    <i class="fas fa-book-open service-icon"></i>
                    <h5>Academic Counseling</h5>
                    <p>Support for academic pressure, study habits, motivation, and school-related concerns.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card service-card h-100 text-center">
                    <i class="fas fa-users service-icon"></i>
                    <h5>Peer & Social Support</h5>
                    <p>Guidance for friendships, peer conflicts, bullying, and social difficulties.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card service-card h-100 text-center">
                    <i class="fas fa-compass service-icon"></i>
                    <h5>Career Guidance</h5>
                    <p>Help students understand their interests and possible future educational and career paths.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card service-card h-100 text-center">
                    <i class="fas fa-home service-icon"></i>
                    <h5>Family & Personal</h5>
                    <p>A supportive environment for students experiencing family or personal difficulties.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card service-card h-100 text-center">
                    <i class="fas fa-heart service-icon"></i>
                    <h5>Emotional Wellness</h5>
                    <p>Support for anxiety, depression, stress, and emotional well-being.</p>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <a href="<?php echo BASE_URL; ?>/register.php" class="btn btn-primary btn-lg rounded-pill px-4"><i class="fas fa-hand-holding-heart me-2"></i>Request Counseling</a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>