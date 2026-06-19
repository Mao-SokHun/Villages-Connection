<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="empty-state glass-panel my-5 reveal text-center p-5">
            <i class="fa-solid fa-compass fa-3x text-warning mb-3"></i>
            <h1 class="text-white mb-3"><?php echo __('page.404.title'); ?></h1>
            <p class="text-secondary mb-4"><?php echo __('page.404.desc'); ?></p>
            <div class="d-flex flex-wrap gap-3 justify-content-center">
                <a href="<?php echo app_url('index.php'); ?>" class="btn btn-gradient px-4"><i class="fa-solid fa-house"></i> <?php echo __('page.404.back_feed'); ?></a>
                <a href="<?php echo app_url('about.php'); ?>" class="btn btn-outline-custom px-4"><i class="fa-solid fa-circle-info"></i> <?php echo __('nav.about'); ?></a>
                <a href="<?php echo app_url('contact.php'); ?>" class="btn btn-outline-custom px-4"><i class="fa-solid fa-envelope"></i> <?php echo __('nav.contact'); ?></a>
            </div>
        </div>
    </div>
</div>
