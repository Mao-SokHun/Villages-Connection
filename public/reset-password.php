<?php
require_once dirname(__DIR__) . '/bootstrap.php';
$page_title = 'Reset Password';
$email = '';
$errors = array();

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

if (isset($_SESSION['reset_email'])) {
    $email = $_SESSION['reset_email'];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_valid_csrf();

    $email_key = 'email:guest';
    if (isset($_POST['email'])) {
        $email_key = 'email:' . strtolower(trim($_POST['email']));
    }
    enforce_rate_limit_or_exit('reset_otp_verify', client_rate_limit_id() . '|' . $email_key, 5, 900, false);

    if (isset($_POST['email'])) {
        $email = trim($_POST['email']);
    }

    $otp = '';
    if (isset($_POST['otp'])) {
        $otp = trim($_POST['otp']);
    }

    $password = '';
    if (isset($_POST['password'])) {
        $password = $_POST['password'];
    }

    $confirm_password = '';
    if (isset($_POST['confirm_password'])) {
        $confirm_password = $_POST['confirm_password'];
    }

    if ($email == '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($otp == '' || strlen($otp) != 6 || !ctype_digit($otp)) {
        $errors[] = 'Please enter the 6-digit OTP code from your email.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }
    if ($password != $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }

    if (count($errors) == 0) {
        $otp_row = verify_password_reset_otp($pdo, $email, $otp);
        if (!$otp_row) {
            $errors[] = 'Invalid or expired OTP code. Request a new one.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $sql = 'UPDATE users SET password = :password, updated_at = CURRENT_TIMESTAMP WHERE id = :id';
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array(
                'password' => $hash,
                'id' => $otp_row['user_id']
            ));

            mark_otp_used($pdo, $otp_row['id']);
            unset($_SESSION['reset_email']);

            setFlashMessage('success', 'Password reset successful. Please sign in with your new password.');
            header('Location: login.php');
            exit;
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
                        <span class="hero-badge mb-3"><i class="fa-solid fa-key me-2"></i>Reset Access</span>
                        <h2 class="text-white mb-3">Create New Password</h2>
                        <p class="text-secondary">Enter the OTP from your email and choose a new password for your account.</p>
                    </div>
                </div>
                <div class="col-md-7">
                    <div class="auth-form-wrap p-4 p-md-5">
                        <div class="mb-4">
                            <h2 class="text-white mb-1">Reset Password</h2>
                            <p class="text-secondary small mb-0">OTP code expires in 15 minutes</p>
                        </div>

                        <?php if (count($errors) > 0): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0 small">
                                <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <form action="reset-password.php" method="POST">
                            <?php echo csrf_field(); ?>
                            <div class="mb-3">
                                <label for="email" class="form-label form-label-custom">Email Address</label>
                                <input type="email" name="email" id="email" class="form-control form-control-custom" value="<?php echo htmlspecialchars($email); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="otp" class="form-label form-label-custom">OTP Code</label>
                                <input type="text" name="otp" id="otp" class="form-control form-control-custom" maxlength="6" pattern="[0-9]{6}" placeholder="6-digit code" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label form-label-custom">New Password</label>
                                <div class="password-input-wrap">
                                    <input type="password" name="password" id="password" class="form-control form-control-custom" minlength="8" autocomplete="new-password" required>
                                </div>
                                <div class="form-text text-secondary small">At least 8 characters</div>
                            </div>
                            <div class="mb-4">
                                <label for="confirm_password" class="form-label form-label-custom">Confirm New Password</label>
                                <div class="password-input-wrap">
                                    <input type="password" name="confirm_password" id="confirm_password" class="form-control form-control-custom" minlength="8" autocomplete="new-password" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-gradient w-100 py-3">
                                <i class="fa-solid fa-lock"></i> Reset Password
                            </button>
                        </form>

                        <div class="text-center mt-4">
                            <span class="text-secondary small">Did not receive a code?</span>
                            <a href="forgot-password.php" class="text-warning small text-decoration-none ms-1 fw-semibold">Send again</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once ROOT_PATH . '/app/Views/layouts/footer.php'; ?>

