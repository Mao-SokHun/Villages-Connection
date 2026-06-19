<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="glass-panel p-4 p-md-5 reveal">
            <span class="hero-badge mb-3"><i class="fa-solid fa-user-shield me-2"></i><?php echo __('page.privacy.badge'); ?></span>
            <h1 class="text-white mb-3"><?php echo __('page.privacy.title'); ?></h1>
            <p class="text-secondary small"><?php echo __('page.privacy.updated', array('date' => format_date(date('Y-m-d')))); ?></p>

            <div class="policy-content text-secondary">
                <?php
                foreach (array('s1', 's2', 's3', 's4', 's5', 's6') as $section):
                ?>
                <h5 class="text-white mt-4"><?php echo __('page.privacy.' . $section . '_title'); ?></h5>
                <p><?php echo __('page.privacy.' . $section . '_body', array('site' => __('site.name'))); ?></p>
                <?php endforeach; ?>
                <h5 class="text-white mt-4"><?php echo __('page.privacy.s7_title'); ?></h5>
                <p><?php echo __('page.privacy.s7_body', array('email' => site_contact_email())); ?>
                <a href="<?php echo app_url('contact.php'); ?>" class="footer-link"><?php echo __('nav.contact'); ?></a>
                <?php echo __('page.privacy.s7_or'); ?>
                <a href="mailto:<?php echo htmlspecialchars(site_contact_email()); ?>" class="footer-link"><?php echo htmlspecialchars(site_contact_email()); ?></a>.</p>
            </div>

            <div class="mt-4 pt-3 border-top border-secondary">
                <a href="<?php echo app_url('contact.php'); ?>" class="btn btn-outline-custom btn-sm me-2"><?php echo __('nav.contact'); ?></a>
                <a href="<?php echo app_url('terms.php'); ?>" class="btn btn-outline-custom btn-sm me-2"><?php echo __('footer.terms'); ?></a>
                <a href="<?php echo app_url('index.php'); ?>" class="btn btn-gradient btn-sm"><?php echo __('home.back_home'); ?></a>
            </div>
        </div>
    </div>
</div>
