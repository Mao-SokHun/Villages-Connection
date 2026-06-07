<?php
require_once __DIR__ . '/auth.php';
requireAdmin();

$errors = array();
$saved = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_valid_csrf();

    $keys = array(
        'registration_enabled',
        'require_post_approval',
        'maintenance_mode',
        'comments_enabled',
        'comments_require_approval'
    );

    foreach ($keys as $key) {
        $val = isset($_POST[$key]) ? '1' : '0';
        set_setting($pdo, $key, $val);
    }

    $text_keys = array(
        'site_contact_email',
        'maintenance_message',
        'default_meta_description',
        'email_template_reset_subject',
        'email_template_reset_body',
        'email_template_welcome_subject',
        'email_template_welcome_body',
        'seo_home_title',
        'seo_home_description',
        'seo_about_description'
    );

    foreach ($text_keys as $key) {
        if (isset($_POST[$key])) {
            set_setting($pdo, $key, trim($_POST[$key]));
        }
    }

    log_activity($pdo, 'settings.updated', 'Site settings saved');
    setFlashMessage('success', 'Settings saved successfully.');
    header('Location: settings.php');
    exit;
}

$page_title = 'Site Settings';
$admin_active = 'settings';
require_once ROOT_PATH . '/app/Views/layouts/header.php';
require_once ROOT_PATH . '/app/Views/layouts/admin-nav.php';
?>

<div class="row g-4">
    <div class="col-lg-8">
        <form method="POST" action="settings.php" class="glass-panel p-4">
            <?php echo csrf_field(); ?>
            <h3 class="text-white mb-4"><i class="fa-solid fa-gear text-warning me-2"></i>Site Settings</h3>

            <h5 class="text-white mb-3">General</h5>
            <div class="mb-3">
                <label class="form-label form-label-custom">Contact Email</label>
                <input type="email" name="site_contact_email" class="form-control form-control-custom" value="<?php echo htmlspecialchars(get_setting('site_contact_email', SITE_CONTACT_EMAIL)); ?>">
            </div>
            <div class="mb-3">
                <label class="form-label form-label-custom">Default Meta Description</label>
                <textarea name="default_meta_description" class="form-control form-control-custom" rows="2"><?php echo htmlspecialchars(get_setting('default_meta_description', SITE_DESC)); ?></textarea>
            </div>
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" name="registration_enabled" id="registration_enabled" <?php if (registration_is_enabled()) echo 'checked'; ?>>
                <label class="form-check-label text-secondary" for="registration_enabled">Allow new registrations</label>
            </div>
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" name="require_post_approval" id="require_post_approval" <?php if (posts_require_approval()) echo 'checked'; ?>>
                <label class="form-check-label text-secondary" for="require_post_approval">Require admin approval before posts go live</label>
            </div>
            <div class="form-check form-switch mb-4">
                <input class="form-check-input" type="checkbox" name="maintenance_mode" id="maintenance_mode" <?php if (maintenance_mode_active()) echo 'checked'; ?>>
                <label class="form-check-label text-secondary" for="maintenance_mode">Maintenance mode (admins can still access site)</label>
            </div>
            <div class="mb-4">
                <label class="form-label form-label-custom">Maintenance Message</label>
                <textarea name="maintenance_message" class="form-control form-control-custom" rows="2"><?php echo htmlspecialchars(get_setting('maintenance_message')); ?></textarea>
            </div>

            <h5 class="text-white mb-3">Comments</h5>
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" name="comments_enabled" id="comments_enabled" <?php if (comments_are_enabled()) echo 'checked'; ?>>
                <label class="form-check-label text-secondary" for="comments_enabled">Enable comments on posts</label>
            </div>
            <div class="form-check form-switch mb-4">
                <input class="form-check-input" type="checkbox" name="comments_require_approval" id="comments_require_approval" <?php if (comments_require_approval()) echo 'checked'; ?>>
                <label class="form-check-label text-secondary" for="comments_require_approval">Require admin approval for new comments</label>
            </div>

            <h5 class="text-white mb-3">SEO</h5>
            <div class="mb-3">
                <label class="form-label form-label-custom">Home Page Title Override</label>
                <input type="text" name="seo_home_title" class="form-control form-control-custom" value="<?php echo htmlspecialchars(get_setting('seo_home_title')); ?>" placeholder="Leave blank to use default">
            </div>
            <div class="mb-3">
                <label class="form-label form-label-custom">Home Page Description</label>
                <textarea name="seo_home_description" class="form-control form-control-custom" rows="2"><?php echo htmlspecialchars(get_setting('seo_home_description')); ?></textarea>
            </div>
            <div class="mb-4">
                <label class="form-label form-label-custom">About Page Description</label>
                <textarea name="seo_about_description" class="form-control form-control-custom" rows="2"><?php echo htmlspecialchars(get_setting('seo_about_description')); ?></textarea>
            </div>

            <h5 class="text-white mb-3">Email Templates</h5>
            <p class="text-secondary small">Use placeholders: <code>{name}</code>, <code>{otp}</code>, <code>{site_name}</code></p>
            <div class="mb-3">
                <label class="form-label form-label-custom">Password Reset Subject</label>
                <input type="text" name="email_template_reset_subject" class="form-control form-control-custom" value="<?php echo htmlspecialchars(get_setting('email_template_reset_subject')); ?>">
            </div>
            <div class="mb-3">
                <label class="form-label form-label-custom">Password Reset Body</label>
                <textarea name="email_template_reset_body" class="form-control form-control-custom" rows="3"><?php echo htmlspecialchars(get_setting('email_template_reset_body')); ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label form-label-custom">Welcome Email Subject</label>
                <input type="text" name="email_template_welcome_subject" class="form-control form-control-custom" value="<?php echo htmlspecialchars(get_setting('email_template_welcome_subject')); ?>">
            </div>
            <div class="mb-4">
                <label class="form-label form-label-custom">Welcome Email Body</label>
                <textarea name="email_template_welcome_body" class="form-control form-control-custom" rows="3"><?php echo htmlspecialchars(get_setting('email_template_welcome_body')); ?></textarea>
            </div>

            <button type="submit" class="btn btn-gradient px-4"><i class="fa-solid fa-save"></i> Save Settings</button>
        </form>
    </div>
    <div class="col-lg-4">
        <div class="glass-panel p-4 mb-4">
            <h5 class="text-white mb-3">Quick Links</h5>
            <div class="d-grid gap-2">
                <a href="announcements.php" class="btn btn-outline-custom btn-sm"><i class="fa-solid fa-bullhorn"></i> Announcements</a>
                <a href="analytics.php" class="btn btn-outline-custom btn-sm"><i class="fa-solid fa-chart-line"></i> Analytics & Export</a>
                <a href="activity.php" class="btn btn-outline-custom btn-sm"><i class="fa-solid fa-clock-rotate-left"></i> Activity Log</a>
                <a href="../sitemap.php" target="_blank" class="btn btn-outline-custom btn-sm"><i class="fa-solid fa-sitemap"></i> View Sitemap</a>
            </div>
        </div>
    </div>
</div>

<?php require_once ROOT_PATH . '/app/Views/layouts/footer.php'; ?>
