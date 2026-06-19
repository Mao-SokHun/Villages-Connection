<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once APP_PATH . '/Core/mail.php';

$page_title = 'Forgot Password';
$email = '';
$errors = array();

if (isLoggedIn()) {
    redirect_to('index.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_valid_csrf();

    if (isset($_POST['email'])) {
        $email = normalize_email($_POST['email']);
    }

    if ($email == '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } else {
        $sql = 'SELECT id, name, email FROM users WHERE LOWER(email) = :email';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array('email' => $email));
        $user = $stmt->fetch();

        if ($user) {
            $limit_key = $email . '|' . (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'local');
            $limit_max = 5;
            $limit_window = 900;

            if (!rate_limit_hit('forgot_password', $limit_key, $limit_max, $limit_window)) {
                $wait = rate_limit_remaining_seconds('forgot_password', $limit_key, $limit_window);
                $mins = (int) ceil($wait / 60);
                $errors[] = 'Too many OTP requests. Please try again in about ' . $mins . ' minute(s), or use the last code if it is still valid (15 minutes).';
            } else {
                $otp = create_password_reset_otp($pdo, $user['id'], $user['email']);
                $mail_ok = send_password_reset_otp_email($user['email'], $user['name'], $otp);

                if (!$mail_ok) {
                    $errors[] = 'Could not send email right now. Please try again later.';
                } else {
                    $_SESSION['reset_email'] = $user['email'];
                    setFlashMessage('success', 'Check your email for the 6-digit OTP code.');
                    redirect_to('reset-password.php');
                }
            }
        } else {
            $_SESSION['reset_email'] = $email;
            setFlashMessage('success', 'Check your email for the 6-digit OTP code.');
            redirect_to('reset-password.php');
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
                        <span class="hero-badge mb-3"><i class="fa-solid fa-shield-halved me-2"></i>Account Security</span>
                        <h2 class="text-white mb-3">Forgot Password?</h2>
                        <p class="text-secondary">Enter your email and we will send a 6-digit OTP code to reset your password.</p>
                        <ul class="auth-features">
                            <li><i class="fa-solid fa-check"></i> OTP expires in 15 minutes</li>
                            <li><i class="fa-solid fa-check"></i> One-time use code</li>
                            <li><i class="fa-solid fa-check"></i> Secure password reset</li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-7">
                    <div class="auth-form-wrap p-4 p-md-5">
                        <div class="mb-4">
                            <h2 class="text-white mb-1">Forgot Password</h2>
                            <p class="text-secondary small mb-0">We will email you a verification code</p>
                        </div>

                        <?php if (count($errors) > 0): ?>
                        <?php render_user_alerts($errors, 'danger'); ?>
                        <?php endif; ?>

                        <form action="forgot-password.php" method="POST">
                            <?php echo csrf_field(); ?>
                            <div class="mb-4">
                                <label for="email" class="form-label form-label-custom">Email Address</label>
                                <input type="email" name="email" id="email" class="form-control form-control-custom" value="<?php echo htmlspecialchars($email); ?>" required>
                            </div>
                            <button type="submit" class="btn btn-gradient w-100 py-3">
                                <i class="fa-solid fa-paper-plane"></i> Send OTP Code
                            </button>
                        </form>

                        <div class="text-center mt-4">
                            <a href="login.php" class="text-warning small text-decoration-none fw-semibold">
                                <i class="fa-solid fa-arrow-left"></i> Back to Sign In
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once ROOT_PATH . '/app/Views/layouts/footer.php'; ?>

