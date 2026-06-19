<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="glass-panel p-4 p-md-5 reveal">
            <span class="hero-badge mb-3"><i class="fa-solid fa-file-contract me-2"></i><?php echo __('page.terms.badge'); ?></span>
            <h1 class="text-white mb-3"><?php echo __('page.terms.title'); ?></h1>
            <p class="text-secondary small"><?php echo __('page.terms.updated', array('date' => format_date(date('Y-m-d')))); ?></p>

            <div class="policy-content text-secondary">
                <?php
                $sections = array('s1', 's2', 's3', 's4', 's5', 's6', 's7');
                foreach ($sections as $section):
                ?>
                <h5 class="text-white mt-4"><?php echo __('page.terms.' . $section . '_title'); ?></h5>
                <p><?php echo __('page.terms.' . $section . '_body', array('site' => __('site.name'))); ?></p>
                <?php endforeach; ?>
            </div>

            <div class="mt-4 pt-3 border-top border-secondary">
                <a href="<?php echo app_url('privacy.php'); ?>" class="btn btn-outline-custom btn-sm me-2"><?php echo __('footer.privacy'); ?></a>
                <a href="<?php echo app_url('register.php'); ?>" class="btn btn-gradient btn-sm"><?php echo __('auth.register'); ?></a>
            </div>
        </div>
    </div>
</div>
