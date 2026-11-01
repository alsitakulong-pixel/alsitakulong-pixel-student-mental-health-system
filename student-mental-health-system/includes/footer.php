<?php
$flash = getFlashMessage();

// Logo auto-detect for footer
$footerLogoPath = '/assets/images/snhs-logo.png';
if (!file_exists($_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system' . $footerLogoPath)) {
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/assets/images/snhs-logo.jpg')) {
        $footerLogoPath = '/assets/images/snhs-logo.jpg';
    }
}
$footerLogoUrl = BASE_URL . $footerLogoPath;
?>
        </div><!-- /.main-wrapper -->
        
        <!-- Footer -->
        <footer class="app-footer">
            <div class="container-fluid py-4">
                <div class="row align-items-center">
                    <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                        <div class="d-flex align-items-center justify-content-center justify-content-md-start">
                            <img src="<?php echo $footerLogoUrl; ?>" alt="SNHS Logo" class="app-footer-logo">
                            <div class="ms-3 text-start">
                                <div class="app-footer-title">SNHS WELLNESS</div>
                                <div class="app-footer-subtitle">© <?php echo date('Y'); ?> Sulu National High School</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 text-center text-md-end">
                        <a href="<?php echo BASE_URL; ?>/pages/about.php" class="app-footer-link">About</a>
                        <a href="<?php echo BASE_URL; ?>/pages/faq.php" class="app-footer-link">FAQs</a>
                        <a href="<?php echo BASE_URL; ?>/pages/contact.php" class="app-footer-link">Contact</a>
                    </div>
                </div>
            </div>
        </footer>
    </div><!-- /.app-content -->
</div><!-- /.app-layout -->

<?php if ($flash): ?>
    <div class="flash-message d-none" data-type="<?php echo $flash['type']; ?>" data-message="<?php echo $flash['message']; ?>"></div>
<?php endif; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?php echo BASE_URL; ?>/assets/js/main.js?v=5"></script>
</body>
</html>