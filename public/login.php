<?php
require_once dirname(__DIR__) . '/bootstrap.php';
$page_title = __('auth.sign_in');
$email = '';
$errors = array();
$recent_accounts = array();

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

if (isset($_COOKIE['vc_recent_accounts']) && $_COOKIE['vc_recent_accounts'] != '') {
    $decoded = json_decode($_COOKIE['vc_recent_accounts'], true);
    if (is_array($decoded)) {
        foreach ($decoded as $item) {
            if (!is_array($item) || !isset($item['email'])) {
                continue;
            }
            $item_email = sanitize_plain_text_field(trim((string) $item['email']), 120);
            if ($item_email == '' || !filter_var($item_email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $recent_accounts[] = array(
                'name' => isset($item['name']) ? sanitize_plain_text_field($item['name'], 80) : '',
                'email' => $item_email,
                'avatar' => isset($item['avatar']) ? sanitize_plain_text_field($item['avatar'], 255) : '',
            );
            if (count($recent_accounts) >= 5) {
                break;
            }
        }
    }
}

if (isset($_GET['account']) && $email == '') {
    $requested_email = sanitize_plain_text_field(trim((string) $_GET['account']), 120);
    if ($requested_email != '' && filter_var($requested_email, FILTER_VALIDATE_EMAIL)) {
        foreach ($recent_accounts as $recent) {
            if (strcasecmp($recent['email'], $requested_email) == 0) {
                $email = $recent['email'];
                break;
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_valid_csrf();

    $ip_id = client_rate_limit_id();
    if (!rate_limit_hit('login_form', $ip_id, 20, 900)) {
        $errors[] = rate_limit_blocked_response('login_form', $ip_id, 900, false);
    }

    if (isset($_POST['email'])) {
        $email = trim($_POST['email']);
    }

    $password = '';
    if (isset($_POST['password'])) {
        $password = $_POST['password'];
    }

    if (count($errors) == 0 && login_is_locked($email)) {
        $wait = login_lock_remaining($email);
        $mins = (int) ceil($wait / 60);
        $errors[] = 'Too many failed attempts. Try again in about ' . $mins . ' minute(s).';
    } elseif (count($errors) == 0 && ($email == '' || $password == '')) {
        $errors[] = 'Please enter both your email and password.';
    } elseif (count($errors) == 0) {
        $sql = 'SELECT * FROM users WHERE email = :email';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array('email' => $email));
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if (user_is_deleted($user)) {
                $errors[] = 'This account has been closed.';
            } elseif (user_is_banned($user)) {
                $reason = '';
                if (isset($user['banned_reason']) && $user['banned_reason'] != '') {
                    $reason = ' Reason: ' . $user['banned_reason'];
                }
                $errors[] = 'This account has been suspended.' . $reason;
            } elseif (user_needs_email_verification($user)) {
                $errors[] = __('auth.verify_email_first');
            } else {
            require_once APP_PATH . '/Core/oauth.php';
            clear_login_fails($email);
            login_user_session($user);
            log_activity($pdo, 'user.login', $user['email']);
            setFlashMessage('success', 'Welcome back, ' . $user['name'] . '!');

            if ($user['role'] == 'admin') {
                header('Location: admin/dashboard.php');
            } else {
                header('Location: index.php');
            }
            exit;
            }
        } else {
            register_login_fail($email);
            $errors[] = 'Invalid email or password.';
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
                        <span class="hero-badge mb-3"><i class="fa-solid fa-seedling me-2"></i><?php echo SITE_NAME; ?></span>
                        <h2 class="text-white mb-3"><?php echo __('auth.welcome_back'); ?></h2>
                        <p class="text-secondary">Sign in to manage your posts, share new content, and connect with the community.</p>
                        <ul class="auth-features">
                            <li><i class="fa-solid fa-check"></i> Create and publish posts</li>
                            <li><i class="fa-solid fa-check"></i> Upload photos and videos</li>
                            <li><i class="fa-solid fa-check"></i> Track views and likes</li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-7">
                    <div class="auth-form-wrap p-4 p-md-5">
                        <div class="text-center mb-4 d-md-none">
                            <i class="fa-solid fa-lock text-warning fs-1 mb-2"></i>
                            <h2><?php echo __('auth.sign_in'); ?></h2>
                        </div>
                        <div class="mb-4 d-none d-md-block">
                            <h2 class="text-white mb-1"><?php echo __('auth.sign_in'); ?></h2>
                            <p class="text-secondary small mb-0">Access your member account</p>
                        </div>

                        <?php if (count($errors) > 0): ?>
                            <div class="alert alert-danger" role="alert">
                                <ul class="mb-0 small">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if (count($recent_accounts) > 0): ?>
                        <div class="recent-accounts mb-3">
                            <div class="recent-accounts-head">Recent accounts</div>
                            <div class="recent-accounts-list">
                                <?php foreach ($recent_accounts as $recent): ?>
                                <a href="<?php echo app_url('login.php?account=' . rawurlencode($recent['email'])); ?>" class="recent-account-item">
                                    <?php echo render_user_avatar($recent['name'] != '' ? $recent['name'] : $recent['email'], $recent['avatar'], 'recent-account-avatar', $recent['email']); ?>
                                    <span class="recent-account-meta">
                                        <strong><?php echo htmlspecialchars($recent['name'] != '' ? $recent['name'] : 'Account'); ?></strong>
                                        <small><?php echo htmlspecialchars($recent['email']); ?></small>
                                    </span>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <form action="login.php" method="POST">
                            <?php echo csrf_field(); ?>
                            <div class="mb-3">
                                <label for="email" class="form-label form-label-custom"><?php echo __('auth.email'); ?></label>
                                <input type="email" name="email" id="email" class="form-control form-control-custom" value="<?php echo htmlspecialchars($email); ?>" required>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label for="password" class="form-label form-label-custom mb-0"><?php echo __('auth.password'); ?></label>
                                    <a href="forgot-password.php" class="small text-warning text-decoration-none"><?php echo __('auth.forgot_password'); ?></a>
                                </div>
                                <input type="password" name="password" id="password" class="form-control form-control-custom" required>
                            </div>
                            <button type="submit" class="btn-auth-submit mt-1">
                                <i class="fa-solid fa-right-to-bracket"></i>
                                <span><?php echo __('auth.sign_in'); ?></span>
                            </button>
                        </form>

                        <?php if (in_array(__('auth.verify_email_first'), $errors, true)): ?>
                        <p class="text-center mt-3 mb-0"><a href="resend-verification.php" class="small text-warning text-decoration-none"><?php echo __('auth.resend_verification'); ?></a></p>
                        <?php endif; ?>

                        <?php require ROOT_PATH . '/app/Views/partials/social-auth.php'; ?>

                        <div class="text-center mt-4">
                            <span class="text-secondary small"><?php echo __('auth.no_account'); ?></span>
                            <a href="register.php" class="text-warning small text-decoration-none ms-1 fw-semibold"><?php echo __('nav.register'); ?></a>
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

