<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="glass-panel p-4 p-md-5 reveal text-center text-md-start mb-4">
            <span class="hero-badge mb-3"><i class="fa-solid fa-envelope me-2"></i><?php echo __('page.contact.badge'); ?></span>
            <h1 class="text-white mb-3"><?php echo __('page.contact.title'); ?></h1>
            <p class="text-secondary mb-0"><?php echo __('page.contact.intro'); ?> <a href="mailto:<?php echo htmlspecialchars(site_contact_email()); ?>" class="footer-link"><?php echo htmlspecialchars(site_contact_email()); ?></a></p>
        </div>

        <?php if ($sent): ?>
        <div class="alert alert-success glass-panel-sm reveal" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i>
            <?php echo __('page.contact.thank_you'); ?>
            <?php if (isLoggedIn()): ?>
            <?php if ($sent_message_id > 0): ?>
            <a href="<?php echo app_url('support.php?message=' . (int) $sent_message_id); ?>" class="footer-link"><?php echo __('page.contact.view_support'); ?></a>
            <?php echo __('page.contact.watch_bell'); ?>
            <?php else: ?>
            <?php echo __('page.contact.check_support'); ?>
            <?php endif; ?>
            <?php else: ?>
            <?php echo __('page.contact.guest_reply'); ?> <a href="<?php echo app_url('login.php'); ?>" class="footer-link"><?php echo __('nav.sign_in'); ?></a> <?php echo __('page.contact.sign_in_replies'); ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-md-5">
                <div class="glass-panel p-4 reveal h-100">
                    <h4 class="text-white mb-3"><i class="fa-solid fa-headset text-warning me-2"></i><?php echo __('page.contact.email_support'); ?></h4>
                    <p class="text-secondary small mb-3"><?php echo __('page.contact.support_desc'); ?></p>
                    <p class="mb-4">
                        <a href="mailto:<?php echo htmlspecialchars(site_contact_email()); ?>" class="btn btn-outline-custom btn-sm">
                            <i class="fa-solid fa-envelope"></i> <?php echo htmlspecialchars(site_contact_email()); ?>
                        </a>
                    </p>
                    <ul class="auth-features text-secondary mb-0">
                        <li><i class="fa-solid fa-check"></i> <?php echo __('page.contact.tip_account'); ?> — <a href="<?php echo app_url('faq.php'); ?>" class="footer-link"><?php echo __('nav.faq'); ?></a></li>
                        <li><i class="fa-solid fa-check"></i> <?php echo __('page.contact.tip_report'); ?> — <a href="<?php echo app_url('report.php'); ?>" class="footer-link"><?php echo __('nav.report'); ?></a></li>
                        <li><i class="fa-solid fa-check"></i> <?php echo __('page.contact.tip_password'); ?> — <a href="<?php echo app_url('forgot-password.php'); ?>" class="footer-link"><?php echo __('auth.forgot_password'); ?></a></li>
                    </ul>
                </div>
            </div>
            <div class="col-md-7">
                <div class="glass-panel p-4 p-md-5 reveal">
                    <?php if (count($errors) > 0): ?>
                    <?php render_user_alerts($errors, 'danger'); ?>
                    <?php endif; ?>

                    <?php if (!isLoggedIn()): ?>
                    <p class="text-secondary small mb-3"><i class="fa-solid fa-bell me-1"></i> <a href="<?php echo app_url('login.php'); ?>" class="footer-link"><?php echo __('nav.sign_in'); ?></a> <?php echo __('page.contact.sign_in_bell'); ?></p>
                    <?php endif; ?>

                    <form action="<?php echo app_url('contact.php'); ?>" method="POST">
                        <?php echo csrf_field(); ?>
                        <div class="mb-3">
                            <label for="contact_name" class="form-label form-label-custom"><?php echo __('form.your_name'); ?></label>
                            <input type="text" name="name" id="contact_name" class="form-control form-control-custom" value="<?php echo htmlspecialchars($name); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="contact_email" class="form-label form-label-custom"><?php echo __('form.email'); ?></label>
                            <input type="email" name="email" id="contact_email" class="form-control form-control-custom" value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="contact_subject" class="form-label form-label-custom"><?php echo __('form.subject'); ?></label>
                            <input type="text" name="subject" id="contact_subject" class="form-control form-control-custom" value="<?php echo htmlspecialchars($subject); ?>" required>
                        </div>
                        <div class="mb-4">
                            <label for="contact_message" class="form-label form-label-custom"><?php echo __('form.message'); ?></label>
                            <textarea name="message" id="contact_message" rows="5" class="form-control form-control-custom" required><?php echo htmlspecialchars($message); ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-gradient px-4">
                            <i class="fa-solid fa-paper-plane"></i> <?php echo __('form.send_message'); ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
