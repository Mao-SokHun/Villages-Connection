<?php
require_once dirname(__DIR__) . '/bootstrap.php';
requireLogin();

$user = get_user_by_id($pdo, $_SESSION['user_id']);
if (!$user || user_is_deleted($user)) {
    perform_logout('danger', 'Account not found.');
    header('Location: login.php');
    exit;
}

$page_title = 'Delete Account';
$errors = array();
$can_delete = can_delete_own_account($pdo, $user);
$is_oauth_user = is_oauth_user($user);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_valid_csrf();

    if (!$can_delete) {
        $errors[] = 'You cannot delete the only administrator account.';
    } else {
        $password = '';
        if (isset($_POST['password'])) {
            $password = $_POST['password'];
        }

        $confirm_email = '';
        if (isset($_POST['confirm_email'])) {
            $confirm_email = trim($_POST['confirm_email']);
        }

        if ($is_oauth_user) {
            if ($confirm_email == '') {
                $errors[] = 'Please enter your account email to confirm.';
            } elseif (strtolower($confirm_email) != strtolower($user['email'])) {
                $errors[] = 'Email does not match your account.';
            }
        } else {
            if ($password == '') {
                $errors[] = 'Please enter your current password.';
            } elseif (!password_verify($password, $user['password'])) {
                $errors[] = 'Current password is incorrect.';
            }
        }

        if (count($errors) == 0) {
            $user_id = (int) $user['id'];
            $ok = delete_user_account($pdo, $user_id);

            if ($ok) {
                perform_logout('success', 'Your account has been closed. Your data is kept but hidden from the site.');
                header('Location: index.php');
                exit;
            } else {
                $errors[] = 'Could not close account. Please contact support.';
            }
        }
    }
}

require_once ROOT_PATH . '/app/Views/layouts/header.php';
?>

<div class="row justify-content-center g-4">
    <div class="col-lg-8">
        <div class="glass-panel p-4 p-md-5 reveal danger-zone-panel">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                <div>
                    <h2 class="text-white mb-1"><i class="fa-solid fa-triangle-exclamation text-danger me-2"></i>Delete Account</h2>
                    <p class="text-secondary mb-0">Your account will be closed and you will be signed out.</p>
                </div>
                <a href="<?php echo app_url('edit-profile.php'); ?>" class="btn btn-outline-custom btn-sm">Back to Edit Profile</a>
            </div>

            <?php if (!$can_delete): ?>
            <div class="danger-zone-card">
                <div class="danger-zone-topbar" aria-hidden="true"></div>
                <div class="danger-zone-inner">
                    <div class="alert alert-warning mb-0">
                        You are the only administrator. Create another admin account before deleting yours.
                    </div>
                </div>
            </div>
            <?php else: ?>

            <div class="danger-zone-card mb-4">
                <div class="danger-zone-topbar" aria-hidden="true"></div>
                <div class="danger-zone-inner">
                    <div class="danger-zone-grid">
                        <div class="danger-zone-item">
                            <span class="danger-zone-item-icon"><i class="fa-solid fa-user-slash"></i></span>
                            <div>
                                <strong>Account closed</strong>
                                <p>Your profile and login will be disabled. You cannot sign in again with this account.</p>
                            </div>
                        </div>
                        <div class="danger-zone-item">
                            <span class="danger-zone-item-icon"><i class="fa-solid fa-newspaper"></i></span>
                            <div>
                                <strong>Posts may remain</strong>
                                <p>Published posts can stay visible on the site.</p>
                            </div>
                        </div>
                        <div class="danger-zone-item">
                            <span class="danger-zone-item-icon"><i class="fa-solid fa-database"></i></span>
                            <div>
                                <strong>Data kept</strong>
                                <p>Your account data stays in the database but is hidden from the site.</p>
                            </div>
                        </div>
                    </div>
                </div>
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

            <form method="POST" action="<?php echo app_url('delete-account.php'); ?>" class="danger-zone-confirm-form">
                <?php echo csrf_field(); ?>
                <div class="row g-3">
                    <?php if ($is_oauth_user): ?>
                    <div class="col-12">
                        <p class="text-secondary small mb-2">You signed in with a social account. Enter your account email to confirm.</p>
                        <label class="form-label form-label-custom" for="confirm_email">Account Email</label>
                        <div class="password-input-wrap">
                            <i class="fa-solid fa-envelope password-input-icon"></i>
                            <input type="email" name="confirm_email" id="confirm_email" class="form-control form-control-custom" autocomplete="email" placeholder="Enter your email" required>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="col-12">
                        <label class="form-label form-label-custom" for="delete_password">Current Password</label>
                        <div class="password-input-wrap">
                            <i class="fa-solid fa-shield-halved password-input-icon"></i>
                            <input type="password" name="password" id="delete_password" class="form-control form-control-custom" autocomplete="current-password" placeholder="Enter your password" required>
                        </div>
                        <p class="text-secondary small mt-2 mb-0">Enter your password to confirm account closure.</p>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-4">
                    <button type="submit" class="btn btn-danger danger-zone-btn">
                        <i class="fa-solid fa-trash-can"></i> Close My Account
                    </button>
                    <a href="<?php echo app_url('edit-profile.php'); ?>" class="btn btn-outline-custom">Cancel</a>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once ROOT_PATH . '/app/Views/layouts/footer.php'; ?>
