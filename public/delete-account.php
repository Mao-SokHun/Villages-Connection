<?php
require_once dirname(__DIR__) . '/bootstrap.php';
requireLogin();

$user = get_user_by_id($pdo, $_SESSION['user_id']);
if (!$user) {
    setFlashMessage('danger', 'Account not found.');
    header('Location: logout.php');
    exit;
}

$page_title = 'Delete Account';
$errors = array();
$can_delete = can_delete_own_account($pdo, $user);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_valid_csrf();

    if (!$can_delete) {
        $errors[] = 'You cannot delete the only administrator account.';
    } else {
        $password = '';
        if (isset($_POST['password'])) {
            $password = $_POST['password'];
        }

        $confirm_text = '';
        if (isset($_POST['confirm_text'])) {
            $confirm_text = trim($_POST['confirm_text']);
        }

        if ($password == '') {
            $errors[] = 'Please enter your current password.';
        } elseif (!password_verify($password, $user['password'])) {
            $errors[] = 'Current password is incorrect.';
        }

        if ($confirm_text != 'DELETE') {
            $errors[] = 'Please type DELETE to confirm account removal.';
        }

        if (count($errors) == 0) {
            $user_id = (int) $user['id'];
            $ok = delete_user_account($pdo, $user_id);

            if ($ok) {
                $_SESSION = array();
                if (ini_get('session.use_cookies')) {
                    $params = session_get_cookie_params();
                    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
                }
                session_destroy();
                session_start();
                setFlashMessage('success', 'Your account has been permanently deleted.');
                header('Location: index.php');
                exit;
            } else {
                $errors[] = 'Could not delete account. Please contact support.';
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
                    <p class="text-secondary mb-0">This action is permanent and cannot be undone.</p>
                </div>
                <a href="edit-profile.php" class="btn btn-outline-custom btn-sm">Back to Edit Profile</a>
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
                                <strong>Account removed</strong>
                                <p>Your profile, login, and personal settings will be deleted.</p>
                            </div>
                        </div>
                        <div class="danger-zone-item">
                            <span class="danger-zone-item-icon"><i class="fa-solid fa-newspaper"></i></span>
                            <div>
                                <strong>Posts may remain</strong>
                                <p>Published posts can stay visible without your author details.</p>
                            </div>
                        </div>
                        <div class="danger-zone-item">
                            <span class="danger-zone-item-icon"><i class="fa-solid fa-image"></i></span>
                            <div>
                                <strong>Data erased</strong>
                                <p>Your avatar and account data will be permanently removed.</p>
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

            <form method="POST" action="delete-account.php" class="danger-zone-confirm-form">
                <?php echo csrf_field(); ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label form-label-custom" for="delete_password">Current Password</label>
                        <div class="password-input-wrap">
                            <i class="fa-solid fa-shield-halved password-input-icon"></i>
                            <input type="password" name="password" id="delete_password" class="form-control form-control-custom" autocomplete="current-password" placeholder="Enter your password" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label form-label-custom" for="confirm_text">Type <strong>DELETE</strong> to confirm</label>
                        <div class="password-input-wrap">
                            <i class="fa-solid fa-triangle-exclamation password-input-icon danger-input-icon"></i>
                            <input type="text" name="confirm_text" id="confirm_text" class="form-control form-control-custom" placeholder="DELETE" required>
                        </div>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-4">
                    <button type="submit" class="btn btn-danger danger-zone-btn">
                        <i class="fa-solid fa-trash-can"></i> Permanently Delete Account
                    </button>
                    <a href="edit-profile.php" class="btn btn-outline-custom">Cancel</a>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once ROOT_PATH . '/app/Views/layouts/footer.php'; ?>
