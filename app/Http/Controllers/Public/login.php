<?php
$page_title = __('auth.sign_in');
$email = '';
$errors = array();

if (isLoggedIn()) {
    redirect_to('index.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_valid_csrf();

    $ip_id = client_rate_limit_id();
    if (!rate_limit_hit('login_form', $ip_id, 20, 900)) {
        $errors[] = rate_limit_blocked_response('login_form', $ip_id, 900, false);
    }

    if (isset($_POST['email'])) {
        $email = normalize_email($_POST['email']);
    }

    $password = '';
    if (isset($_POST['password'])) {
        $password = $_POST['password'];
    }

    if (count($errors) == 0 && login_is_locked($email)) {
        $wait = login_lock_remaining($email);
        $mins = (int) ceil($wait / 60);
        $errors[] = __('auth.login_locked', array('mins' => $mins));
    } elseif (count($errors) == 0 && ($email == '' || $password == '')) {
        $errors[] = __('auth.enter_both');
    } elseif (count($errors) == 0) {
        $sql = 'SELECT * FROM users WHERE LOWER(email) = :email';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array('email' => $email));
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if (user_is_deleted($user)) {
                $errors[] = __('auth.account_closed');
            } elseif (user_is_banned($user)) {
                $reason = '';
                if (isset($user['banned_reason']) && $user['banned_reason'] != '') {
                    $reason = __('auth.suspend_reason', array('reason' => $user['banned_reason']));
                }
                $errors[] = __('auth.account_suspended') . $reason;
            } elseif (user_needs_email_verification($user)) {
                $errors[] = __('auth.verify_email_first');
            } else {
            require_once APP_PATH . '/Models/oauth.php';
            clear_login_fails($email);
            login_user_session($user);
            log_activity($pdo, 'user.login', $user['email']);
            setFlashMessage('success', __('auth.welcome_flash', array('name' => $user['name'])));

            if ($user['role'] == 'admin') {
                redirect_to('admin/dashboard.php');
            } else {
                redirect_to('index.php');
            }
            }
        } else {
            register_login_fail($email);
            $errors[] = __('auth.invalid_credentials');
        }
    }
}

require_once ROOT_PATH . '/app/Views/layouts/header.php';
?>

<div class="row justify-content-center g-4">
    <div class="col-lg-10">
        <div class="auth-layout glass-panel overflow-hidden">
            <div class="row g-0">
                <div class="col-md-5 auth-side d-none d-md-flex">
                    <div class="auth-side-inner">
                        <?php echo render_code_logo('auth'); ?>
                        <p class="text-secondary small mb-4 mt-3"><?php echo htmlspecialchars(SITE_TAGLINE); ?></p>
                        <h2 class="text-white mb-3"><?php echo __('auth.welcome_back'); ?></h2>
                        <p class="text-secondary"><?php echo __('auth.sidebar_desc'); ?></p>
                        <ul class="auth-features">
                            <li><i class="fa-solid fa-check"></i> <?php echo __('auth.sidebar_feat_posts'); ?></li>
                            <li><i class="fa-solid fa-check"></i> <?php echo __('auth.sidebar_feat_media'); ?></li>
                            <li><i class="fa-solid fa-check"></i> <?php echo __('auth.sidebar_feat_stats'); ?></li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-7">
                    <div class="auth-form-wrap p-4 p-md-5">
                        <div class="text-center mb-4 d-md-none">
                            <?php echo render_code_logo('auth', 'mx-auto'); ?>
                            <p class="text-secondary small mb-0 mt-2"><?php echo htmlspecialchars(SITE_TAGLINE); ?></p>
                            <h2 class="mt-3"><?php echo __('auth.sign_in'); ?></h2>
                        </div>
                        <div class="mb-4 d-none d-md-block">
                            <h2 class="text-white mb-1"><?php echo __('auth.sign_in'); ?></h2>
                            <p class="text-secondary small mb-0"><?php echo __('auth.access_account'); ?></p>
                        </div>

                        <?php if (count($errors) > 0): ?>
                            <?php render_user_alerts($errors, 'danger'); ?>
                        <?php endif; ?>

                        <form action="<?php echo app_url('login.php'); ?>" method="POST">
                            <?php echo csrf_field(); ?>
                            <div class="mb-3">
                                <label for="email" class="form-label form-label-custom"><?php echo __('auth.email'); ?></label>
                                <input type="email" name="email" id="email" class="form-control form-control-custom" value="<?php echo htmlspecialchars($email); ?>" required>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label for="password" class="form-label form-label-custom mb-0"><?php echo __('auth.password'); ?></label>
                                    <a href="<?php echo app_url('forgot-password.php'); ?>" class="small text-warning text-decoration-none"><?php echo __('auth.forgot_password'); ?></a>
                                </div>
                                <input type="password" name="password" id="password" class="form-control form-control-custom" required>
                            </div>
                            <button type="submit" class="btn-auth-submit mt-1">
                                <i class="fa-solid fa-right-to-bracket"></i>
                                <span><?php echo __('auth.sign_in'); ?></span>
                            </button>
                        </form>

                        <?php if (in_array(__('auth.verify_email_first'), $errors, true)): ?>
                        <p class="text-center mt-3 mb-0"><a href="<?php echo app_url('resend-verification.php'); ?>" class="small text-warning text-decoration-none"><?php echo __('auth.resend_verification'); ?></a></p>
                        <?php endif; ?>

                        <?php require ROOT_PATH . '/app/Views/partials/social-auth.php'; ?>

                        <div class="text-center mt-4">
                            <span class="text-secondary small"><?php echo __('auth.no_account'); ?></span>
                            <a href="<?php echo app_url('register.php'); ?>" class="text-warning small text-decoration-none ms-1 fw-semibold"><?php echo __('nav.register'); ?></a>
                        </div>

                        <?php if (defined('APP_DEBUG') && APP_DEBUG): ?>
                        <div class="mt-4 pt-3 border-top border-secondary text-center small text-secondary">
                            <p class="mb-1"><strong>Local Test Accounts:</strong></p>
                            <div>Admin: <code>admin@admin.com</code> / <code>admin123</code></div>
                            <div>Author: <code>author@author.com</code> / <code>author123</code></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once ROOT_PATH . '/app/Views/layouts/footer.php'; ?>
