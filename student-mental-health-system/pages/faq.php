<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$page_title = 'FAQs';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="py-5" style="background: linear-gradient(135deg, var(--lavender) 0%, var(--sky) 100%);">
    <div class="container">
        <h2 class="section-title text-center">Frequently Asked Questions</h2>
        <p class="section-subtitle text-center">Answers to common questions about our counseling services.</p>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item border-0 shadow-sm mb-3 rounded-4 overflow-hidden">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                Is counseling confidential?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted">
                                Yes, all counseling sessions are confidential. Information shared during counseling is protected and only accessible to authorized school personnel. However, there are legal exceptions where confidentiality may be limited (e.g., if there is a risk of harm to yourself or others).
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item border-0 shadow-sm mb-3 rounded-4 overflow-hidden">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                How do I request a counseling session?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted">
                                You can request a counseling session by logging into your account and clicking "Request Counseling" on your dashboard. Fill out the form with your concern category and preferred schedule, and a counselor will review and confirm your appointment.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item border-0 shadow-sm mb-3 rounded-4 overflow-hidden">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                Can I choose my preferred counselor?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted">
                                Yes! When submitting a counseling request, you can choose a preferred counselor from the available list. If you don't have a preference, you can leave it blank and any available counselor will handle your request.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item border-0 shadow-sm mb-3 rounded-4 overflow-hidden">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                What should I do in case of emergency?
                            </button>
                        </h2>
                        <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted">
                                If you are experiencing a mental health emergency, please contact the school guidance office immediately, or reach out to a trusted adult, parent, or guardian. You can also contact local emergency services or mental health hotlines.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item border-0 shadow-sm mb-3 rounded-4 overflow-hidden">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                Are wellness resources available to everyone?
                            </button>
                        </h2>
                        <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted">
                                Yes! Wellness resources are published by counselors and administrators, and they are available to all students, parents, and visitors on the public website.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>