<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$page_title = 'About';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="py-5" style="background: linear-gradient(135deg, var(--lavender) 0%, var(--sky) 100%);">
    <div class="container">
        <h2 class="section-title text-center">About the Counseling Office</h2>
        <p class="section-subtitle text-center">Get to know our counseling services and team.</p>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-5 text-center">
                <img src="<?php echo BASE_URL; ?>/assets/images/snhs-logo.jpg" alt="SNHS Logo" style="width: 250px; height: 250px; border-radius: 50%; object-fit: cover; box-shadow: 0 10px 30px rgba(51,78,172,0.2);">
            </div>
            <div class="col-md-7">
                <h4 class="fw-bold mb-3" style="color: var(--primary);">Our Mission</h4>
                <p class="text-muted mb-3">The Sulu National High School Counseling Office is committed to providing a safe, confidential, and supportive environment where every student can seek guidance and counseling services.</p>
                <h4 class="fw-bold mb-3" style="color: var(--primary);">Our Vision</h4>
                <p class="text-muted mb-3">To promote mental wellness, academic success, and personal growth among all SNHS students through accessible and professional counseling support.</p>
                <h4 class="fw-bold mb-3" style="color: var(--primary);">Our Values</h4>
                <ul class="list-unstyled text-muted">
                    <li class="mb-2"><i class="fas fa-check-circle me-2" style="color: var(--teal);"></i>Confidentiality</li>
                    <li class="mb-2"><i class="fas fa-check-circle me-2" style="color: var(--teal);"></i>Respect</li>
                    <li class="mb-2"><i class="fas fa-check-circle me-2" style="color: var(--teal);"></i>Empathy</li>
                    <li class="mb-2"><i class="fas fa-check-circle me-2" style="color: var(--teal);"></i>Professionalism</li>
                    <li class="mb-2"><i class="fas fa-check-circle me-2" style="color: var(--teal);"></i>Student-Centered</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>