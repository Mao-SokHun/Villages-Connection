</div>

<footer class="footer-glass">
    <div class="container">
        <div class="row footer-main g-4">
            <div class="col-lg-4 footer-brand-col">
                <h5 class="footer-brand-title"><i class="fa-solid fa-house-chimney me-2 text-warning"></i><?php echo SITE_NAME; ?></h5>
                <p class="text-secondary footer-desc"><?php echo SITE_TAGLINE; ?></p>
            </div>
            <div class="col-lg-5 footer-links-col">
                <div class="row g-4">
                    <div class="col-sm-4">
                        <h6 class="footer-section-title">Explore</h6>
                        <ul class="list-unstyled footer-link-list mb-0">
                            <li><a href="<?php echo $base_path; ?>index.php" class="footer-link">Feed</a></li>
                            <li><a href="<?php echo $base_path; ?>index.php?sort=popular" class="footer-link">Popular</a></li>
                            <li><a href="<?php echo $base_path; ?>about.php" class="footer-link">About</a></li>
                        </ul>
                    </div>
                    <div class="col-sm-4">
                        <h6 class="footer-section-title">Community</h6>
                        <ul class="list-unstyled footer-link-list mb-0">
                            <li><a href="<?php echo $base_path; ?>register.php" class="footer-link">Join Community</a></li>
                            <li><a href="<?php echo $base_path; ?>faq.php" class="footer-link">FAQ</a></li>
                            <li><a href="<?php echo $base_path; ?>help-us.php" class="footer-link">Help Us</a></li>
                            <li><a href="<?php echo $base_path; ?>contact.php" class="footer-link">Contact</a></li>
                            <li><a href="<?php echo $base_path; ?>report.php" class="footer-link">Report Content</a></li>
                            <?php if (!isLoggedIn()): ?>
                            <li><a href="<?php echo $base_path; ?>login.php" class="footer-link">Sign In</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div class="col-sm-4">
                        <h6 class="footer-section-title">Legal</h6>
                        <ul class="list-unstyled footer-link-list mb-0">
                            <li><a href="<?php echo $base_path; ?>terms.php" class="footer-link">Terms of Service</a></li>
                            <li><a href="<?php echo $base_path; ?>privacy.php" class="footer-link">Privacy Policy</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 footer-cta-col">
                <h6 class="footer-section-title">Start Posting</h6>
                <p class="text-secondary footer-desc">Share photos, videos, and updates with your community.</p>
                <div class="d-flex flex-wrap gap-2 footer-actions">
                    <a href="<?php echo create_post_url($base_path); ?>" class="btn btn-gradient"><i class="fa-solid fa-pen-nib me-1"></i> <?php echo isLoggedIn() ? 'Create Post' : 'Register Now'; ?></a>
                    <a href="<?php echo $base_path; ?>contact.php" class="btn btn-outline-custom"><i class="fa-solid fa-envelope me-1"></i> Contact</a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p class="footer-copy mb-0">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
        </div>
    </div>
</footer>

<button type="button" id="back-to-top" class="back-to-top" aria-label="Back to top">
    <i class="fa-solid fa-arrow-up"></i>
</button>

<?php displayFlashMessage(); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo $base_path; ?>js/main.js"></script>
</body>
</html>
