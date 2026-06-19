</div>

<footer class="footer-glass">
    <div class="container">
        <div class="row footer-main g-4">
            <div class="col-lg-4 footer-brand-col">
                <a href="<?php echo app_url('index.php'); ?>" class="footer-brand-link d-inline-block mb-2 text-decoration-none">
                    <?php echo render_code_logo('footer'); ?>
                    <p class="text-secondary footer-desc mb-0 small mt-2"><?php echo htmlspecialchars(__('site.tagline')); ?></p>
                </a>
            </div>
            <div class="col-lg-5 footer-links-col">
                <div class="row g-4">
                    <div class="col-sm-4">
                        <h6 class="footer-section-title"><?php echo __('footer.explore'); ?></h6>
                        <ul class="list-unstyled footer-link-list mb-0">
                            <li><a href="<?php echo app_url('index.php'); ?>" class="footer-link"><?php echo __('footer.feed'); ?></a></li>
                            <li><a href="<?php echo app_url('index.php?sort=popular'); ?>" class="footer-link"><?php echo __('footer.popular'); ?></a></li>
                            <li><a href="<?php echo app_url('about.php'); ?>" class="footer-link"><?php echo __('footer.about'); ?></a></li>
                        </ul>
                    </div>
                    <div class="col-sm-4">
                        <h6 class="footer-section-title"><?php echo __('footer.community'); ?></h6>
                        <ul class="list-unstyled footer-link-list mb-0">
                            <li><a href="<?php echo app_url('register.php'); ?>" class="footer-link"><?php echo __('footer.join'); ?></a></li>
                            <li><a href="<?php echo app_url('faq.php'); ?>" class="footer-link"><?php echo __('nav.faq'); ?></a></li>
                            <li><a href="<?php echo app_url('help-us.php'); ?>" class="footer-link"><?php echo __('nav.help_us'); ?></a></li>
                            <li><a href="<?php echo app_url('contact.php'); ?>" class="footer-link"><?php echo __('nav.contact'); ?></a></li>
                            <li><a href="<?php echo app_url('report.php'); ?>" class="footer-link"><?php echo __('nav.report'); ?></a></li>
                            <?php if (!isLoggedIn()): ?>
                            <li><a href="<?php echo app_url('login.php'); ?>" class="footer-link"><?php echo __('nav.sign_in'); ?></a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div class="col-sm-4">
                        <h6 class="footer-section-title"><?php echo __('footer.legal'); ?></h6>
                        <ul class="list-unstyled footer-link-list mb-0">
                            <li><a href="<?php echo app_url('terms.php'); ?>" class="footer-link"><?php echo __('footer.terms'); ?></a></li>
                            <li><a href="<?php echo app_url('privacy.php'); ?>" class="footer-link"><?php echo __('footer.privacy'); ?></a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 footer-cta-col">
                <h6 class="footer-section-title"><?php echo __('footer.start_posting'); ?></h6>
                <p class="text-secondary footer-desc"><?php echo __('footer.start_desc'); ?></p>
                <div class="d-flex flex-wrap gap-2 footer-actions">
                    <a href="<?php echo create_post_url($base_path); ?>" class="btn btn-gradient"><i class="fa-solid fa-pen-nib me-1"></i> <?php echo isLoggedIn() ? __('footer.create_post') : __('footer.register_now'); ?></a>
                    <a href="<?php echo app_url('contact.php'); ?>" class="btn btn-outline-custom"><i class="fa-solid fa-envelope me-1"></i> <?php echo __('nav.contact'); ?></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p class="footer-copy mb-0">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. <?php echo __('footer.rights'); ?></p>
        </div>
    </div>
</footer>

<?php
if (!$is_admin_dir):
    $mobile_tab = '';
    if ($current_page === 'search.php') {
        $mobile_tab = 'search';
    } elseif (in_array($current_page, array('profile.php', 'edit-profile.php', 'bookmarks.php', 'notifications.php'), true)) {
        $mobile_tab = 'profile';
    } elseif (in_array($current_page, array('login.php', 'register.php'), true)) {
        $mobile_tab = 'profile';
    } elseif ($current_page === 'index.php' && isset($_GET['sort']) && $_GET['sort'] === 'popular') {
        $mobile_tab = 'popular';
    } elseif ($current_page === 'index.php') {
        $mobile_tab = 'feed';
    }
    $mobile_profile_href = isLoggedIn() ? profile_url((int) $_SESSION['user_id']) : app_url('login.php');
?>
<nav class="mobile-tab-bar" id="mobile-tab-bar" aria-label="<?php echo htmlspecialchars(__('common.nav_toggle')); ?>">
    <a href="<?php echo app_url('index.php'); ?>" class="mobile-tab-item<?php if ($mobile_tab === 'feed') echo ' is-active'; ?>"<?php if ($mobile_tab === 'feed') echo ' aria-current="page"'; ?>>
        <span class="mobile-tab-icon"><i class="fa-solid fa-house" aria-hidden="true"></i></span>
        <span class="mobile-tab-label"><?php echo __('nav.feed'); ?></span>
    </a>
    <a href="<?php echo app_url('index.php?sort=popular'); ?>" class="mobile-tab-item<?php if ($mobile_tab === 'popular') echo ' is-active'; ?>"<?php if ($mobile_tab === 'popular') echo ' aria-current="page"'; ?>>
        <span class="mobile-tab-icon"><i class="fa-solid fa-fire" aria-hidden="true"></i></span>
        <span class="mobile-tab-label"><?php echo __('nav.popular'); ?></span>
    </a>
    <a href="<?php echo app_url('search.php'); ?>" class="mobile-tab-item<?php if ($mobile_tab === 'search') echo ' is-active'; ?>"<?php if ($mobile_tab === 'search') echo ' aria-current="page"'; ?>>
        <span class="mobile-tab-icon"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></span>
        <span class="mobile-tab-label"><?php echo __('nav.search_page'); ?></span>
    </a>
    <a href="<?php echo htmlspecialchars($mobile_profile_href); ?>" class="mobile-tab-item<?php if ($mobile_tab === 'profile') echo ' is-active'; ?>"<?php if ($mobile_tab === 'profile') echo ' aria-current="page"'; ?>>
        <span class="mobile-tab-icon"><i class="fa-solid fa-user" aria-hidden="true"></i></span>
        <span class="mobile-tab-label"><?php echo isLoggedIn() ? __('nav.my_profile') : __('nav.sign_in'); ?></span>
    </a>
    <button type="button" class="mobile-tab-item mobile-tab-item--menu" id="mobile-menu-tab" aria-expanded="false" aria-controls="navbarContent">
        <span class="mobile-tab-icon"><i class="fa-solid fa-bars" aria-hidden="true"></i></span>
        <span class="mobile-tab-label"><?php echo __('nav.menu'); ?></span>
    </button>
</nav>
<?php if (isLoggedIn() && !isAdmin()): ?>
<a href="<?php echo create_post_url($base_path); ?>" class="mobile-fab" aria-label="<?php echo htmlspecialchars(__('nav.create_post')); ?>" title="<?php echo htmlspecialchars(__('nav.create_post')); ?>">
    <i class="fa-solid fa-plus" aria-hidden="true"></i>
</a>
<?php endif; ?>
<?php endif; ?>

<button type="button" id="back-to-top" class="back-to-top" aria-label="<?php echo htmlspecialchars(__('footer.back_to_top')); ?>">
    <i class="fa-solid fa-arrow-up"></i>
</button>

<?php displayFlashMessage(); ?>

<div class="modal fade confirm-action-modal" id="confirmActionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered confirm-action-dialog">
        <div class="modal-content flash-modal-content confirm-action-content">
            <div class="modal-body confirm-action-body text-center">
                <div class="confirm-action-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <h4 class="confirm-action-title" id="confirmActionTitle"><?php echo htmlspecialchars(__('common.confirm')); ?></h4>
                <p class="confirm-action-text" id="confirmActionMessage"><?php echo htmlspecialchars(__('common.confirm_message')); ?></p>
                <div class="confirm-action-buttons">
                    <button type="button" class="btn btn-outline-custom px-4" data-bs-dismiss="modal" id="confirmActionCancelBtn"><?php echo htmlspecialchars(__('common.cancel')); ?></button>
                    <button type="button" class="btn btn-danger px-4" id="confirmActionBtn"><?php echo htmlspecialchars(__('common.confirm')); ?></button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
<script src="<?php echo public_asset_url('js/main.js'); ?>?v=<?php echo asset_version('js/main.js'); ?>" defer></script>
</body>
</html>
