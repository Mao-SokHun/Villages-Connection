<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="glass-panel p-4 p-md-5 reveal text-center text-md-start mb-4">
            <span class="hero-badge mb-3"><i class="fa-solid fa-envelope me-2"></i>Contact</span>
            <h1 class="text-white mb-3">Contact Us</h1>
            <p class="text-secondary mb-0">Questions, feedback, or partnership ideas? Send us a message and we will get back to you.</p>
        </div>

        <?php if ($sent): ?>
        <div class="alert alert-success glass-panel-sm reveal" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i>
            Thank you! Your message has been sent. We will reply to your email as soon as possible.
        </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-md-5">
                <div class="glass-panel p-4 reveal h-100">
                    <h4 class="text-white mb-3"><i class="fa-solid fa-comments text-warning me-2"></i>Other Ways to Reach Us</h4>
                    <ul class="auth-features text-secondary mb-4">
                        <li><i class="fa-solid fa-check"></i> Account help — see the <a href="faq.php" class="footer-link">FAQ</a></li>
                        <li><i class="fa-solid fa-check"></i> Bad content — use <a href="report.php" class="footer-link">Report Content</a></li>
                        <li><i class="fa-solid fa-check"></i> Password reset — <a href="forgot-password.php" class="footer-link">Forgot Password</a></li>
                    </ul>
                    <p class="text-secondary small mb-0">Support email: <a href="mailto:<?php echo htmlspecialchars(SITE_CONTACT_EMAIL); ?>" class="footer-link"><?php echo htmlspecialchars(SITE_CONTACT_EMAIL); ?></a></p>
                </div>
            </div>
            <div class="col-md-7">
                <div class="glass-panel p-4 p-md-5 reveal">
                    <?php if (count($errors) > 0): ?>
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0 small">
                            <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <form action="contact.php" method="POST">
                        <?php echo csrf_field(); ?>
                        <div class="mb-3">
                            <label for="contact_name" class="form-label form-label-custom">Your Name</label>
                            <input type="text" name="name" id="contact_name" class="form-control form-control-custom" value="<?php echo htmlspecialchars($name); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="contact_email" class="form-label form-label-custom">Email Address</label>
                            <input type="email" name="email" id="contact_email" class="form-control form-control-custom" value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="contact_subject" class="form-label form-label-custom">Subject</label>
                            <input type="text" name="subject" id="contact_subject" class="form-control form-control-custom" value="<?php echo htmlspecialchars($subject); ?>" required>
                        </div>
                        <div class="mb-4">
                            <label for="contact_message" class="form-label form-label-custom">Message</label>
                            <textarea name="message" id="contact_message" rows="5" class="form-control form-control-custom" required><?php echo htmlspecialchars($message); ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-gradient px-4">
                            <i class="fa-solid fa-paper-plane"></i> Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
