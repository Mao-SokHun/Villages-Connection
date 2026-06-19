<div class="row justify-content-center g-4">
    <div class="col-lg-9">
        <div class="glass-panel p-4 p-md-5 reveal text-center text-md-start">
            <span class="hero-badge mb-3"><i class="fa-solid fa-hand-holding-heart me-2"></i><?php echo __('page.help.badge'); ?></span>
            <h1 class="text-white mb-3"><?php echo __('page.help.title'); ?></h1>
            <p class="text-secondary lead-sm mb-0"><?php echo __('page.help.intro', array('site' => __('site.name'))); ?></p>
        </div>
    </div>

    <div class="col-lg-9">
        <div class="about-feature-grid reveal">
            <?php
            $help_features = array(
                array('icon' => 'fa-share-nodes', 'title' => 'page.help.share_title', 'desc' => 'page.help.share_desc'),
                array('icon' => 'fa-pen-nib', 'title' => 'page.help.post_title', 'desc' => 'page.help.post_desc'),
                array('icon' => 'fa-flag', 'title' => 'page.help.report_title', 'desc' => 'page.help.report_desc'),
                array('icon' => 'fa-lightbulb', 'title' => 'page.help.suggest_title', 'desc' => 'page.help.suggest_desc'),
            );
            foreach ($help_features as $feature):
            ?>
            <article class="about-feature-item glass-panel">
                <span class="about-feature-line"></span>
                <div class="about-feature-body">
                    <h3><i class="fa-solid <?php echo $feature['icon']; ?>"></i> <?php echo __($feature['title']); ?></h3>
                    <p><?php echo __($feature['desc'], array('site' => __('site.name'))); ?></p>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="col-lg-9">
        <div class="glass-panel p-4 p-md-5 reveal">
            <h4 class="text-white mb-3"><i class="fa-solid fa-envelope text-warning me-2"></i><?php echo __('page.help.get_in_touch'); ?></h4>
            <p class="text-secondary"><?php echo __('page.help.contact_blurb', array('email' => site_contact_email())); ?></p>
            <ul class="auth-features text-secondary">
                <li><i class="fa-solid fa-check"></i> <?php echo __('page.help.tip_login'); ?> — <a href="<?php echo app_url('faq.php'); ?>" class="footer-link"><?php echo __('nav.faq'); ?></a></li>
                <li><i class="fa-solid fa-check"></i> <?php echo __('page.help.tip_password'); ?> — <a href="<?php echo app_url('forgot-password.php'); ?>" class="footer-link"><?php echo __('auth.forgot_password'); ?></a></li>
                <li><i class="fa-solid fa-check"></i> <?php echo __('page.help.tip_report'); ?> — <a href="<?php echo app_url('report.php'); ?>" class="footer-link"><?php echo __('nav.report'); ?></a></li>
                <li><i class="fa-solid fa-check"></i> <?php echo __('page.help.tip_register'); ?> — <a href="<?php echo app_url('register.php'); ?>" class="footer-link"><?php echo __('auth.register'); ?></a></li>
            </ul>
            <div class="mt-4 pt-3 border-top border-secondary">
                <a href="<?php echo app_url('contact.php'); ?>" class="btn btn-gradient btn-sm me-2"><i class="fa-solid fa-envelope"></i> <?php echo __('nav.contact'); ?></a>
                <a href="<?php echo app_url('faq.php'); ?>" class="btn btn-outline-custom btn-sm me-2"><i class="fa-solid fa-circle-question"></i> <?php echo __('nav.faq'); ?></a>
                <a href="<?php echo app_url('about.php'); ?>" class="btn btn-outline-custom btn-sm"><i class="fa-solid fa-circle-info"></i> <?php echo __('nav.about'); ?></a>
            </div>
        </div>
    </div>
</div>
