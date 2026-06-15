<?php

require_once dirname(__DIR__) . '/bootstrap.php';

$page_title = __('auth.email_verified');
$token = '';
if (isset($_GET['token'])) {
    $token = trim($_GET['token']);
}

if ($token != '') {
    $result = verify_email_with_token($pdo, $token);
    if ($result['ok']) {
        setFlashMessage('success', __('auth.email_verified'));
    } else {
        setFlashMessage('danger', __('auth.verification_invalid'));
    }
    header('Location: login.php');
    exit;
}

$errors = array();
$sent = false;
$email = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_valid_csrf();

    if (isset($_POST['email'])) {
        $email = trim($_POST['email']);
    }

    if ($email == '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = __('auth.email') . ' — invalid.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE LOWER(email) = LOWER(:email)');
        $stmt->execute(array('email' => $email));
        $user = $stmt->fetch();

        if ($user && user_needs_email_verification($user)) {
            send_user_verification_email($pdo, $user);
            $sent = true;
        } else {
            $sent = true;
        }
    }
}

require_once ROOT_PATH . '/app/Views/layouts/header.php';
?>

<div class="row justify-content-center g-4">
    <div class="col-lg-6">
        <div class="glass-panel p-4 p-md-5 reveal">
            <h2 class="text-white mb-3"><i class="fa-solid fa-envelope-circle-check text-warning me-2"></i><?php echo __('auth.resend_verification'); ?></h2>

            <?php if ($sent): ?>
            <div class="alert alert-success"><?php echo __('auth.verification_sent'); ?></div>
            <a href="login.php" class="btn btn-gradient"><?php echo __('auth.sign_in'); ?></a>
            <?php else: ?>

            <?php if (count($errors) > 0): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $err): ?>
                <div><?php echo htmlspecialchars($err); ?></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <p class="text-secondary"><?php echo __('auth.verify_email_first'); ?></p>
            <form method="POST" action="resend-verification.php">
                <?php echo csrf_field(); ?>
                <div class="mb-3">
                    <label class="form-label form-label-custom"><?php echo __('auth.email'); ?></label>
                    <input type="email" name="email" class="form-control form-control-custom" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                <button type="submit" class="btn btn-gradient w-100"><?php echo __('auth.resend_verification'); ?></button>
            </form>
            <p class="text-secondary small mt-3 mb-0"><a href="login.php" class="footer-link"><?php echo __('auth.sign_in'); ?></a></p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once ROOT_PATH . '/app/Views/layouts/footer.php'; ?>
