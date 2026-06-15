<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="glass-panel p-4 p-md-5 reveal text-center text-md-start mb-4">
            <span class="hero-badge mb-3"><i class="fa-solid fa-envelope me-2"></i>Contact</span>
            <h1 class="text-white mb-3">Contact Us</h1>
            <p class="text-secondary mb-0">Having trouble or need help? Email <a href="mailto:<?php echo htmlspecialchars(site_contact_email()); ?>" class="footer-link"><?php echo htmlspecialchars(site_contact_email()); ?></a> or send a message below.</p>
        </div>

        <?php if ($sent): ?>
        <div class="alert alert-success glass-panel-sm reveal" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i>
            Thank you! Your message was received.
            <?php if (isLoggedIn()): ?>
            <?php if ($sent_message_id > 0): ?>
            <a href="support.php?message=<?php echo (int) $sent_message_id; ?>" class="footer-link">View in Support Messages</a>
            or watch the <a href="notifications.php" class="footer-link">notification bell</a> for admin replies.
            <?php else: ?>
            Check <a href="support.php" class="footer-link">Support Messages</a> or the <a href="notifications.php" class="footer-link">notification bell</a> for admin replies.
            <?php endif; ?>
            <?php else: ?>
            We will reply by email when possible. <a href="login.php" class="footer-link">Sign in</a> to get replies in your notification bell.
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-md-5">
                <div class="glass-panel p-4 reveal h-100">
                    <h4 class="text-white mb-3"><i class="fa-solid fa-headset text-warning me-2"></i>Email Support</h4>
                    <p class="text-secondary small mb-3">For account issues, bugs, or general questions, contact our support team:</p>
                    <p class="mb-4">
                        <a href="mailto:<?php echo htmlspecialchars(site_contact_email()); ?>" class="btn btn-outline-custom btn-sm">
                            <i class="fa-solid fa-envelope"></i> <?php echo htmlspecialchars(site_contact_email()); ?>
                        </a>
                    </p>
                    <ul class="auth-features text-secondary mb-0">
                        <li><i class="fa-solid fa-check"></i> Account help — see the <a href="faq.php" class="footer-link">FAQ</a></li>
                        <li><i class="fa-solid fa-check"></i> Bad content — use <a href="report.php" class="footer-link">Report Content</a></li>
                        <li><i class="fa-solid fa-check"></i> Password reset — <a href="forgot-password.php" class="footer-link">Forgot Password</a></li>
                    </ul>
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

                    <?php if (!isLoggedIn()): ?>
                    <p class="text-secondary small mb-3"><i class="fa-solid fa-bell me-1"></i> <a href="login.php" class="footer-link">Sign in</a> to receive admin replies in your notification bell.</p>
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
