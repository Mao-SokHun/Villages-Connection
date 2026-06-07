<section class="glass-panel p-5 reveal text-center text-md-start mb-5">

    <div class="row align-items-center g-4">

        <div class="col-md-8">

            <span class="hero-badge mb-3"><i class="fa-solid fa-hand-holding-heart me-2"></i><?php echo SITE_NAME; ?></span>

            <h1 class="text-white mb-3">About Our Community Social Platform</h1>

            <p class="text-secondary lead-sm"><?php echo SITE_DESC; ?></p>

            <p class="text-secondary">Post photos, videos, and text updates from your area — connect with neighbors, share daily moments, and discover what is happening around you, just like on social media.</p>

            <a href="register.php" class="btn btn-gradient mt-3"><i class="fa-solid fa-user-plus"></i> Join Community</a>
            <a href="contact.php" class="btn btn-outline-custom mt-3 ms-md-2"><i class="fa-solid fa-envelope"></i> Contact Us</a>

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
            <h2 class="text-white mb-2">What you can do here</h2>
            <p class="text-secondary mb-0">Share updates, photos, and videos with your community — simple posting like Facebook, Instagram, and Twitter.</p>
        </div>

        <div class="about-feature-grid">
            <article class="about-feature-item">
                <span class="about-feature-line"></span>
                <div class="about-feature-body">
                    <h3><i class="fa-solid fa-camera"></i> Photos &amp; Videos</h3>
                    <p>Upload images or MP4 clips, or paste a YouTube link — show people what you are up to.</p>
                </div>
            </article>
            <article class="about-feature-item">
                <span class="about-feature-line"></span>
                <div class="about-feature-body">
                    <h3><i class="fa-solid fa-tags"></i> Topics &amp; Categories</h3>
                    <p>Tag posts by events, culture, food, travel, or create a new topic that fits your content.</p>
                </div>
            </article>
            <article class="about-feature-item">
                <span class="about-feature-line"></span>
                <div class="about-feature-body">
                    <h3><i class="fa-solid fa-heart"></i> Like &amp; Share</h3>
                    <p>React to posts and share links to Facebook or Telegram in one click.</p>
                </div>
            </article>
            <article class="about-feature-item">
                <span class="about-feature-line"></span>
                <div class="about-feature-body">
                    <h3><i class="fa-solid fa-users"></i> Community Members</h3>
                    <p>Register for free, build your profile, and post content that connects your village online.</p>
                </div>
            </article>
        </div>
    </div>
</section>



<section class="glass-panel p-5 reveal text-center">

    <h3 class="text-white mb-3">Ready to share your first post?</h3>

    <p class="text-secondary mb-4">Join the community and start posting photos, videos, and updates today.</p>

    <div class="d-flex flex-wrap gap-3 justify-content-center">
        <?php if (isLoggedIn()): ?>
        <a href="admin/posts.php?action=add" class="btn btn-gradient px-4"><i class="fa-solid fa-pen-nib"></i> Create Post</a>
        <?php else: ?>
        <a href="register.php" class="btn btn-gradient px-4"><i class="fa-solid fa-user-plus"></i> Create Account</a>
        <?php endif; ?>
        <a href="index.php" class="btn btn-outline-custom px-4"><i class="fa-solid fa-images"></i> Browse Feed</a>
        <a href="contact.php" class="btn btn-outline-custom px-4"><i class="fa-solid fa-envelope"></i> Contact</a>
    </div>

</section>
