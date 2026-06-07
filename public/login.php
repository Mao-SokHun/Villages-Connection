<?php
require_once dirname(__DIR__) . '/bootstrap.php';
$page_title = 'Sign In';
$email = '';
$errors = array();

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_valid_csrf();

    if (isset($_POST['email'])) {
        $email = trim($_POST['email']);
    }

    $password = '';
    if (isset($_POST['password'])) {
        $password = $_POST['password'];
    }

    if (login_is_locked($email)) {
        $wait = login_lock_remaining($email);
        $mins = (int) ceil($wait / 60);
        $errors[] = 'Too many failed attempts. Try again in about ' . $mins . ' minute(s).';
    } elseif ($email == '' || $password == '') {
        $errors[] = 'Please enter both your email and password.';
    } else {
        $sql = 'SELECT * FROM users WHERE email = :email';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array('email' => $email));
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if (user_is_banned($user)) {
                $reason = '';
                if (isset($user['banned_reason']) && $user['banned_reason'] != '') {
                    $reason = ' Reason: ' . $user['banned_reason'];
                }
                $errors[] = 'This account has been suspended.' . $reason;
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
                        <h2 class="text-white mb-3">Welcome Back</h2>
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
                            <h2>Sign In</h2>
                        </div>
                        <div class="mb-4 d-none d-md-block">
                            <h2 class="text-white mb-1">Sign In</h2>
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

                        <form action="login.php" method="POST">
                            <?php echo csrf_field(); ?>
                            <div class="mb-3">
                                <label for="email" class="form-label form-label-custom">Email Address</label>
                                <input type="email" name="email" id="email" class="form-control form-control-custom" value="<?php echo htmlspecialchars($email); ?>" required>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label for="password" class="form-label form-label-custom mb-0">Password</label>
                                    <a href="forgot-password.php" class="small text-warning text-decoration-none">Forgot password?</a>
                                </div>
                                <input type="password" name="password" id="password" class="form-control form-control-custom" required>
                            </div>
                            <button type="submit" class="btn-auth-submit mt-1">
                                <i class="fa-solid fa-right-to-bracket"></i>
                                <span>Sign In</span>
                            </button>
                        </form>

                        <?php require ROOT_PATH . '/app/Views/partials/social-auth.php'; ?>

                        <div class="text-center mt-4">
                            <span class="text-secondary small">Don't have an account?</span>
                            <a href="register.php" class="text-warning small text-decoration-none ms-1 fw-semibold">Register Now</a>
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

