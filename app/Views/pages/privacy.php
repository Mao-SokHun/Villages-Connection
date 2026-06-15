<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="glass-panel p-4 p-md-5 reveal">
            <span class="hero-badge mb-3"><i class="fa-solid fa-user-shield me-2"></i>Privacy</span>
            <h1 class="text-white mb-3">Privacy Policy</h1>
            <p class="text-secondary small">Last updated: <?php echo date('F j, Y'); ?></p>

            <div class="policy-content text-secondary">
                <h5 class="text-white mt-4">1. Information We Collect</h5>
                <p>When you register, we collect your name, email address, and password (stored securely as a hash). Profile information such as bio, location, website, and avatar photo is optional.</p>

                <h5 class="text-white mt-4">2. How We Use Your Data</h5>
                <p>Your information is used to manage your account, publish your posts, display your author profile, and send security emails such as password reset OTP codes.</p>

                <h5 class="text-white mt-4">3. Cookies & Sessions</h5>
                <p>We use session cookies to keep you signed in and remember preferences such as theme mode. Anonymous visitors receive a visitor key for post likes.</p>

                <h5 class="text-white mt-4">4. Data Sharing</h5>
                <p>We do not sell your personal data. Published posts and public profile details may be visible to all site visitors. Share buttons may send links to third-party services such as Facebook or Telegram.</p>

                <h5 class="text-white mt-4">5. Security</h5>
                <p>We use password hashing, CSRF protection, login rate limiting, and secure OTP password reset. Please use a strong password and keep your account credentials private.</p>

                <h5 class="text-white mt-4">6. Account Deletion</h5>
                <p>You can delete your account from Edit Profile. Your posts may remain on the site without an author name, but your personal account data will be removed.</p>

                <h5 class="text-white mt-4">7. Contact</h5>
                <p>For privacy questions, contact us through the <a href="contact.php" class="footer-link">Contact page</a> or email <a href="mailto:<?php echo htmlspecialchars(site_contact_email()); ?>" class="footer-link"><?php echo htmlspecialchars(site_contact_email()); ?></a>.</p>
            </div>

            <div class="mt-4 pt-3 border-top border-secondary">
                <a href="contact.php" class="btn btn-outline-custom btn-sm me-2">Contact Us</a>
                <a href="terms.php" class="btn btn-outline-custom btn-sm me-2">Terms of Service</a>
                <a href="index.php" class="btn btn-gradient btn-sm">Back to Home</a>
            </div>
        </div>
    </div>
</div>
