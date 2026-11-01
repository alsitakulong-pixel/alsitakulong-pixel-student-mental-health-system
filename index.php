<?php
define('BASE_URL', 'http://localhost/student-mental-health-system');
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="hero-title animate-fadeInLeft">YOUR WELL-BEING MATTERS</h1>
                <p class="hero-subtitle animate-fadeInLeft">
                    A safe and supportive space where Sulu National High School students can seek guidance, counseling, and mental wellness support.
                </p>
                <div class="mt-4">
                    <a href="<?php echo BASE_URL; ?>/register.php" class="btn btn-hero-primary btn-lg me-2"><i class="fas fa-hand-holding-heart me-2"></i>Request Counseling</a>
                    <a href="<?php echo BASE_URL; ?>/pages/resources.php" class="btn btn-hero-secondary btn-lg"><i class="fas fa-book-open me-2"></i>Explore Resources</a>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <img src="<?php echo BASE_URL; ?>/assets/images/snhs-logo.jpg" alt="SNHS Wellness" class="hero-logo animate-fadeInRight">
            </div>
        </div>
    </div>
</section>

<!-- Counseling Services -->
<section class="services-section py-5">
    <div class="container">
        <h2 class="section-title text-center">Counseling Services</h2>
        <p class="section-subtitle text-center">We offer a range of services to support your well-being.</p>
        <div class="row mt-4">
            <div class="col-md-4 mb-4">
                <div class="card service-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-user-friends service-icon"></i>
                        <h5 class="card-title">Individual Counseling</h5>
                        <p class="card-text">Private guidance for students experiencing personal or emotional concerns.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card service-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-book-open service-icon"></i>
                        <h5 class="card-title">Academic Counseling</h5>
                        <p class="card-text">Support for academic pressure, study habits, motivation, and school-related concerns.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card service-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-users service-icon"></i>
                        <h5 class="card-title">Peer & Social Support</h5>
                        <p class="card-text">Guidance for friendships, peer conflicts, bullying, and social difficulties.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How Counseling Works -->
<section class="how-it-works py-5 bg-light">
    <div class="container">
        <h2 class="section-title text-center">How Counseling Works</h2>
        <div class="row mt-4">
            <div class="col-md-3 text-center">
                <div class="step-number">1</div>
                <h6>Create an Account</h6>
                <p>Register using your student information.</p>
            </div>
            <div class="col-md-3 text-center">
                <div class="step-number">2</div>
                <h6>Request Counseling</h6>
                <p>Choose your concern category and preferred schedule.</p>
            </div>
            <div class="col-md-3 text-center">
                <div class="step-number">3</div>
                <h6>Counselor Reviews</h6>
                <p>The guidance counselor reviews and confirms your appointment.</p>
            </div>
            <div class="col-md-3 text-center">
                <div class="step-number">4</div>
                <h6>Attend Your Session</h6>
                <p>Meet with your counselor according to the approved schedule.</p>
            </div>
        </div>
    </div>
</section>

<!-- Privacy Section -->
<section class="privacy-section py-5">
    <div class="container">
        <div class="card privacy-card">
            <div class="card-body text-center">
                <i class="fas fa-shield-alt privacy-icon"></i>
                <h4>Your Privacy Matters</h4>
                <p>Student counseling information is handled carefully and access is limited to authorized school personnel.</p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>