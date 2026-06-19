<section class="glass-panel p-5 reveal text-center text-md-start mb-5">

    <div class="row align-items-center g-4">

        <div class="col-md-8">

            <span class="hero-badge mb-3"><i class="fa-solid fa-hand-holding-heart me-2"></i><?php echo htmlspecialchars(__('site.name')); ?></span>

            <h1 class="text-white mb-3"><?php echo __('page.about.title'); ?></h1>

            <p class="text-secondary lead-sm"><?php echo __('site.desc'); ?></p>

            <p class="text-secondary"><?php echo __('page.about.intro'); ?></p>

            <a href="<?php echo app_url('register.php'); ?>" class="btn btn-gradient mt-3"><i class="fa-solid fa-user-plus"></i> <?php echo __('page.about.join'); ?></a>
            <a href="<?php echo app_url('contact.php'); ?>" class="btn btn-outline-custom mt-3 ms-md-2"><i class="fa-solid fa-envelope"></i> <?php echo __('nav.contact'); ?></a>

        </div>

        <div class="col-md-4 text-center">

            <div class="about-icon-ring">

                <i class="fa-solid fa-share-nodes"></i>

            </div>

        </div>

    </div>

</section>



<section class="about-features mb-5 reveal">
    <div class="glass-panel about-features-panel">
        <div class="about-features-intro">
            <h2 class="text-white mb-2"><?php echo __('page.about.features_title'); ?></h2>
            <p class="text-secondary mb-0"><?php echo __('page.about.features_desc'); ?></p>
        </div>

        <div class="about-feature-grid">
            <article class="about-feature-item">
                <span class="about-feature-line"></span>
                <div class="about-feature-body">
                    <h3><i class="fa-solid fa-camera"></i> <?php echo __('page.about.feat_media_title'); ?></h3>
                    <p><?php echo __('page.about.feat_media_desc'); ?></p>
                </div>
            </article>
            <article class="about-feature-item">
                <span class="about-feature-line"></span>
                <div class="about-feature-body">
                    <h3><i class="fa-solid fa-tags"></i> <?php echo __('page.about.feat_topics_title'); ?></h3>
                    <p><?php echo __('page.about.feat_topics_desc'); ?></p>
                </div>
            </article>
            <article class="about-feature-item">
                <span class="about-feature-line"></span>
                <div class="about-feature-body">
                    <h3><i class="fa-solid fa-heart"></i> <?php echo __('page.about.feat_like_title'); ?></h3>
                    <p><?php echo __('page.about.feat_like_desc'); ?></p>
                </div>
            </article>
            <article class="about-feature-item">
                <span class="about-feature-line"></span>
                <div class="about-feature-body">
                    <h3><i class="fa-solid fa-users"></i> <?php echo __('page.about.feat_members_title'); ?></h3>
                    <p><?php echo __('page.about.feat_members_desc'); ?></p>
                </div>
            </article>
        </div>
    </div>
</section>



<section class="glass-panel p-5 reveal text-center">

    <h3 class="text-white mb-3"><?php echo __('page.about.cta_title'); ?></h3>

    <p class="text-secondary mb-4"><?php echo __('page.about.cta_desc'); ?></p>

    <div class="d-flex flex-wrap gap-3 justify-content-center">
        <?php if (isLoggedIn()): ?>
        <a href="<?php echo create_post_url($base_path); ?>" class="btn btn-gradient px-4"><i class="fa-solid fa-pen-nib"></i> <?php echo __('nav.create_post'); ?></a>
        <?php else: ?>
        <a href="<?php echo app_url('register.php'); ?>" class="btn btn-gradient px-4"><i class="fa-solid fa-user-plus"></i> <?php echo __('page.about.create_account'); ?></a>
        <?php endif; ?>
        <a href="<?php echo app_url('index.php'); ?>" class="btn btn-outline-custom px-4"><i class="fa-solid fa-images"></i> <?php echo __('page.about.browse_feed'); ?></a>
        <a href="<?php echo app_url('contact.php'); ?>" class="btn btn-outline-custom px-4"><i class="fa-solid fa-envelope"></i> <?php echo __('nav.contact'); ?></a>
    </div>

</section>
