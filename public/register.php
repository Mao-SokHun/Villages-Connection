<?php
require_once dirname(__DIR__) . '/bootstrap.php';
$page_title = __('auth.register');
$name = '';
$email = '';
$errors = array();

if (isLoggedIn()) {
    redirect_to('index.php');
}

if (!registration_is_enabled()) {
    setFlashMessage('warning', 'New registrations are currently disabled.');
    redirect_to('login.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_valid_csrf();

    $ip_id = client_rate_limit_id();
    if (!rate_limit_hit('register_form', $ip_id, 5, 3600)) {
        $errors[] = rate_limit_blocked_response('register_form', $ip_id, 3600, false);
    }

    if (isset($_POST['name'])) {
        $name = sanitize_plain_text_field($_POST['name'], 80);
    }
    if (isset($_POST['email'])) {
        $email = normalize_email($_POST['email']);
    }

    $password = '';
    if (isset($_POST['password'])) {
        $password = $_POST['password'];
    }

    $confirm_password = '';
    if (isset($_POST['confirm_password'])) {
        $confirm_password = $_POST['confirm_password'];
    }

    if (count($errors) == 0 && $name == '') {
        $errors[] = 'Full name is required.';
    }
    if (count($errors) == 0 && ($email == '' || !filter_var($email, FILTER_VALIDATE_EMAIL))) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (count($errors) == 0 && strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }
    if (count($errors) == 0 && $password != $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }
    if (count($errors) == 0 && (!isset($_POST['agree_terms']) || $_POST['agree_terms'] != '1')) {
        $errors[] = 'You must agree to the Terms of Service and Privacy Policy.';
    }

    if (count($errors) == 0) {
        $sql = 'SELECT COUNT(*) FROM users WHERE LOWER(email) = :email';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array('email' => $email));
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            $errors[] = 'An account with this email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $sql = "INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, 'author') RETURNING id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array(
                'name' => $name,
                'email' => $email,
                'password' => $hash
            ));
            $new_id = (int) $stmt->fetchColumn();
            $user = get_user_by_id($pdo, $new_id);

            require_once APP_PATH . '/Core/oauth.php';
            require_once APP_PATH . '/Core/mail.php';
            log_activity($pdo, 'user.registered', $email);

            if (email_verification_required()) {
                issue_verification_for_new_user($pdo, $new_id);
                setFlashMessage('info', __('auth.check_email_verify'));
                redirect_to('resend-verification.php');
            }

            mark_user_email_verified($pdo, $new_id);
            login_user_session($user);
            send_welcome_email($email, $name);
            setFlashMessage('success', 'Welcome to ' . SITE_NAME . ', ' . $name . '! Create your first post to get started.');
            admin_redirect_to('posts.php?action=add');
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
                        <span class="hero-badge mb-3"><i class="fa-solid fa-pen-nib me-2"></i>Join Us</span>
                        <h2 class="text-white mb-3">Join the Community</h2>
                        <p class="text-secondary">Create posts with photos and videos — share updates, moments, and stories like on social media.</p>
                        <ul class="auth-features">
                            <li><i class="fa-solid fa-check"></i> Free member account</li>
                            <li><i class="fa-solid fa-check"></i> Easy post editor</li>
                            <li><i class="fa-solid fa-check"></i> Share with your village</li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-7">
                    <div class="auth-form-wrap p-4 p-md-5">
                        <div class="text-center mb-4 d-md-none">
                            <i class="fa-solid fa-user-plus text-warning fs-1 mb-2"></i>
                            <h2>Create Account</h2>
                        </div>
                        <div class="mb-4 d-none d-md-block">
                            <h2 class="text-white mb-1">Create Account</h2>
                            <p class="text-secondary small mb-0">Start posting on <?php echo SITE_NAME; ?></p>
                        </div>

                        <?php if (count($errors) > 0): ?>
                            <?php render_user_alerts($errors, 'danger'); ?>
                        <?php endif; ?>

                        <form action="<?php echo app_url('register.php'); ?>" method="POST" id="register_form">
                            <?php echo csrf_field(); ?>
                            <div class="mb-3">
                                <label for="name" class="form-label form-label-custom">Full Name</label>
                                <input type="text" name="name" id="name" class="form-control form-control-custom" value="<?php echo htmlspecialchars($name); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label form-label-custom">Email Address</label>
                                <input type="email" name="email" id="email" class="form-control form-control-custom" value="<?php echo htmlspecialchars($email); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label form-label-custom">Password</label>
                                <div class="password-input-wrap">
                                    <input type="password" name="password" id="password" class="form-control form-control-custom" minlength="8" autocomplete="new-password" required>
                                </div>
                                <div class="form-text text-secondary small"><i class="fa-solid fa-shield-halved me-1"></i>At least 8 characters for security</div>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label form-label-custom">Confirm Password</label>
                                <div class="password-input-wrap">
                                    <input type="password" name="confirm_password" id="confirm_password" class="form-control form-control-custom" minlength="8" autocomplete="new-password" required>
                                </div>
                            </div>
                            <div class="mb-4">
                                <div class="form-check auth-terms-check">
                                    <input class="form-check-input" type="checkbox" name="agree_terms" value="1" id="agree_terms">
                                    <label class="form-check-label text-secondary small" for="agree_terms">
                                        I agree to the <a href="<?php echo app_url('terms.php'); ?>" class="text-warning" target="_blank" rel="noopener">Terms of Service</a> and <a href="<?php echo app_url('privacy.php'); ?>" class="text-warning" target="_blank" rel="noopener">Privacy Policy</a>
                                    </label>
                                </div>
                            </div>

                            <button type="submit" id="register_btn" class="btn-auth-submit" disabled>
                                <i class="fa-solid fa-user-plus"></i>
                                <span>Register</span>
                            </button>
                        </form>

                        <?php require ROOT_PATH . '/app/Views/partials/social-auth.php'; ?>

                        <div class="text-center mt-4">
                            <span class="text-secondary small">Already have an account?</span>
                            <a href="<?php echo app_url('login.php'); ?>" class="text-warning small text-decoration-none ms-1 fw-semibold">Sign In</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once ROOT_PATH . '/app/Views/layouts/footer.php'; ?>

