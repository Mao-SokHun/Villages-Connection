<?php
require_once dirname(__DIR__) . '/bootstrap.php';
requireLogin();

$user = get_user_by_id($pdo, $_SESSION['user_id']);
if (!$user) {
    perform_logout('danger', 'Account not found.');
    redirect_to('login.php');
}

$page_title = 'Edit Profile';
$errors = array();
$show_password_form = false;

$name = $user['name'];
$email = $user['email'];
$bio = '';
$location = '';
$website = '';
$avatar = '';

if (isset($user['bio'])) {
    $bio = $user['bio'];
}
if (isset($user['location'])) {
    $location = $user['location'];
}
if (isset($user['website'])) {
    $website = $user['website'];
}
if (isset($user['avatar'])) {
    $avatar = $user['avatar'];
}

$is_oauth_user = is_oauth_user($user);
$email_is_managed = user_has_managed_email($user);
$can_edit_email = !$is_oauth_user || $email_is_managed;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_valid_csrf();

    if (isset($_POST['name'])) {
        $name = sanitize_plain_text_field($_POST['name'], 80);
    }
    if (!$can_edit_email) {
        $email = $user['email'];
    } elseif (isset($_POST['email'])) {
        $email = normalize_email($_POST['email']);
    }
    if (isset($_POST['bio'])) {
        $bio = trim($_POST['bio']);
    }
    if (isset($_POST['location'])) {
        $location = trim($_POST['location']);
    }
    if (isset($_POST['website'])) {
        $website = trim($_POST['website']);
    }

    $current_password = '';
    if (isset($_POST['current_password'])) {
        $current_password = $_POST['current_password'];
    }

    $new_password = '';
    if (isset($_POST['new_password'])) {
        $new_password = $_POST['new_password'];
    }

    $confirm_password = '';
    if (isset($_POST['confirm_password'])) {
        $confirm_password = $_POST['confirm_password'];
    }

    if ($name == '') {
        $errors[] = 'Name is required.';
    }
    if ($can_edit_email && ($email == '' || !filter_var($email, FILTER_VALIDATE_EMAIL))) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($bio) > 500) {
        $errors[] = 'Bio cannot exceed 500 characters.';
    }
    if ($website != '' && !filter_var($website, FILTER_VALIDATE_URL)) {
        $errors[] = 'Website must be a valid URL (include https://).';
    }

    if ($new_password != '' || $confirm_password != '' || $current_password != '') {
        if ($is_oauth_user) {
            $errors[] = 'Password changes are managed by your social login provider.';
        } elseif ($current_password == '') {
            $errors[] = 'Enter your current password to change it.';
        } elseif (!password_verify($current_password, $user['password'])) {
            $errors[] = 'Current password is incorrect.';
        } elseif (strlen($new_password) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        } elseif ($new_password != $confirm_password) {
            $errors[] = 'New passwords do not match.';
        }
    }

    if (count($errors) == 0 && $can_edit_email && normalize_email($email) != normalize_email($user['email'])) {
        $sql = 'SELECT COUNT(*) FROM users WHERE LOWER(email) = :email AND id != :id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array('email' => $email, 'id' => $user['id']));
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Another account already uses this email.';
        }
    }

    $new_avatar = $avatar;
    if (count($errors) == 0) {
        if (isset($_POST['remove_avatar']) && $_POST['remove_avatar'] == '1') {
            if ($avatar != '') {
                delete_upload($avatar, 'avatars');
            }
            $new_avatar = '';
        } elseif (isset($_FILES['avatar'])) {
            $up = handle_avatar_upload($_FILES['avatar'], $avatar);
            if ($up['ok'] == false) {
                $errors[] = $up['error'];
            } else {
                if (isset($up['filename'])) {
                    $new_avatar = $up['filename'];
                }
            }
        }
    }

    if (count($errors) == 0) {
        $old_email = $user['email'];
        $sql = 'UPDATE users SET name = :name, email = :email, bio = :bio, location = :location, website = :website, avatar = :avatar, updated_at = CURRENT_TIMESTAMP';
        $params = array(
            'name' => $name,
            'email' => $email,
            'bio' => $bio,
            'location' => $location,
            'website' => $website,
            'avatar' => $new_avatar,
            'id' => $user['id']
        );

        if ($new_password != '') {
            $sql = $sql . ', password = :password';
            $params['password'] = password_hash($new_password, PASSWORD_BCRYPT);
        }

        $sql = $sql . ' WHERE id = :id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        require_once APP_PATH . '/Core/verification.php';
        $verification_sent = issue_verification_on_email_change($pdo, (int) $user['id'], $old_email, $email);

        refresh_user_session($pdo, $user['id']);
        if ($verification_sent) {
            setFlashMessage('info', __('auth.check_email_verify'));
            redirect_to('resend-verification.php');
        } else {
            setFlashMessage('success', 'Profile updated successfully.');
            header('Location: ' . profile_url((int) $_SESSION['user_id']));
        }
        exit;
    }

    if ($current_password != '' || $new_password != '' || $confirm_password != '') {
        $show_password_form = true;
    }
    foreach ($errors as $err) {
        if (stripos($err, 'password') !== false) {
            $show_password_form = true;
        }
    }
}

require_once ROOT_PATH . '/app/Views/layouts/header.php';
?>

<div class="row justify-content-center g-4">
    <div class="col-lg-8">
        <div class="glass-panel p-4 p-md-5 reveal">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="text-white mb-1"><i class="fa-solid fa-user-pen text-warning me-2"></i>Edit Profile</h2>
                    <p class="text-secondary small mb-0">Update your account details and public profile</p>
                </div>
                <a href="<?php echo profile_url((int) $_SESSION['user_id']); ?>" class="btn btn-outline-custom btn-sm">Back to Profile</a>
            </div>

            <?php if (count($errors) > 0): ?>
            <?php render_user_alerts($errors, 'danger'); ?>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" action="<?php echo app_url('edit-profile.php'); ?>">
                <?php echo csrf_field(); ?>
                <div class="profile-edit-preview mb-4">
                    <?php echo render_user_avatar($name, $avatar, 'user-avatar-xl', user_public_email($user)); ?>
                    <div>
                        <div class="text-white fw-semibold"><?php echo htmlspecialchars($name); ?></div>
                        <?php
                        $edit_subtitle = user_account_subtitle($user);
                        if ($edit_subtitle != ''):
                        ?>
                        <div class="text-secondary small"><?php echo htmlspecialchars($edit_subtitle); ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <h5 class="text-white mb-3">Profile Info</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label form-label-custom">Full Name *</label>
                        <input type="text" name="name" class="form-control form-control-custom" required value="<?php echo htmlspecialchars($name); ?>">
                    </div>
                    <?php if ($can_edit_email): ?>
                    <div class="col-md-6">
                        <label class="form-label form-label-custom">Email *</label>
                        <input type="email" name="email" class="form-control form-control-custom" required value="<?php echo htmlspecialchars($email); ?>">
                        <?php if ($email_is_managed): ?>
                        <div class="form-text text-secondary small">
                            <?php echo htmlspecialchars(oauth_provider_label(resolve_oauth_provider($user) ?: 'Facebook')); ?> did not share your email. Add one to receive OTP and activity emails.
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="col-md-6">
                        <label class="form-label form-label-custom">Email</label>
                        <input type="email" class="form-control form-control-custom" value="<?php echo htmlspecialchars($email); ?>" readonly>
                        <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                        <div class="form-text text-secondary small">Email is linked to your <?php echo htmlspecialchars(oauth_provider_label(resolve_oauth_provider($user))); ?> account.</div>
                    </div>
                    <?php endif; ?>
                    <div class="col-12">
                        <label class="form-label form-label-custom">Bio</label>
                        <textarea name="bio" class="form-control form-control-custom" rows="4" maxlength="500" placeholder="Tell readers about yourself..."><?php echo htmlspecialchars($bio); ?></textarea>
                        <div class="form-text text-secondary small">Max 500 characters. Shown on your profile and posts.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label form-label-custom">Location</label>
                        <input type="text" name="location" class="form-control form-control-custom" placeholder="e.g. Riverside Village" value="<?php echo htmlspecialchars($location); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label form-label-custom">Website</label>
                        <input type="url" name="website" class="form-control form-control-custom" placeholder="https://example.com" value="<?php echo htmlspecialchars($website); ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label form-label-custom">Profile Photo</label>
                        <div class="file-upload-box">
                            <label class="file-upload-btn" for="avatar_input">
                                <i class="fa-solid fa-camera"></i> Choose Photo
                            </label>
                            <span class="file-upload-name" id="avatar_name"><?php
                                if ($avatar != '') {
                                    echo 'Current: ' . htmlspecialchars($avatar);
                                } else {
                                    echo 'No file chosen';
                                }
                            ?></span>
                            <input type="file" name="avatar" id="avatar_input" class="file-upload-input" accept="image/*">
                        </div>
                        <div class="form-text text-secondary small">JPG, PNG, WEBP, or GIF. Max 2MB.</div>
                        <?php if ($avatar != ''): ?>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="remove_avatar" value="1" id="remove_avatar">
                            <label class="form-check-label text-secondary small" for="remove_avatar">Remove current photo</label>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!$is_oauth_user): ?>
                <div class="password-settings-card settings-section mb-4">
                    <div class="settings-section-head password-settings-head">
                        <div class="password-settings-title">
                            <span class="password-settings-icon"><i class="fa-solid fa-lock"></i></span>
                            <div>
                                <h5 class="text-white mb-1">Password</h5>
                                <p class="text-secondary small mb-0">Keep your account secure with a strong password</p>
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-custom btn-sm" id="toggle-password-btn" <?php if ($show_password_form) echo 'style="display:none;"'; ?>>
                            <i class="fa-solid fa-key"></i> Change Password
                        </button>
                    </div>
                    <div class="password-form-panel <?php if ($show_password_form) echo 'is-open'; ?>" id="password-form-panel">
                        <div class="password-requirements">
                            <span class="password-requirement"><i class="fa-solid fa-circle-check"></i> At least 8 characters</span>
                            <span class="password-requirement"><i class="fa-solid fa-circle-check"></i> Match confirmation</span>
                        </div>
                        <div class="password-fields">
                            <div class="password-field">
                                <label class="form-label form-label-custom" for="current_password">Current Password</label>
                                <div class="password-input-wrap">
                                    <i class="fa-solid fa-shield-halved password-input-icon"></i>
                                    <input type="password" name="current_password" id="current_password" class="form-control form-control-custom" autocomplete="current-password" placeholder="Enter current password">
                                </div>
                            </div>
                            <div class="password-field">
                                <label class="form-label form-label-custom" for="new_password">New Password</label>
                                <div class="password-input-wrap">
                                    <i class="fa-solid fa-key password-input-icon"></i>
                                    <input type="password" name="new_password" id="new_password" class="form-control form-control-custom" autocomplete="new-password" minlength="8" placeholder="Enter new password">
                                </div>
                            </div>
                            <div class="password-field">
                                <label class="form-label form-label-custom" for="confirm_password">Confirm New Password</label>
                                <div class="password-input-wrap">
                                    <i class="fa-solid fa-lock-open password-input-icon"></i>
                                    <input type="password" name="confirm_password" id="confirm_password" class="form-control form-control-custom" autocomplete="new-password" minlength="8" placeholder="Re-enter new password">
                                </div>
                            </div>
                        </div>
                        <div class="password-form-actions">
                            <button type="button" class="btn btn-outline-custom btn-sm" id="cancel-password-btn">
                                <i class="fa-solid fa-xmark"></i> Cancel
                            </button>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="settings-section mb-4 glass-panel-sm p-3">
                    <h5 class="text-white mb-2"><i class="fa-solid fa-lock text-secondary me-2"></i>Password</h5>
                    <p class="text-secondary small mb-0">You signed in with a social account. Password changes are managed by Google or Facebook.</p>
                </div>
                <?php endif; ?>

                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-gradient">
                        <i class="fa-solid fa-save"></i> Save Changes
                    </button>
                    <a href="<?php echo profile_url((int) $_SESSION['user_id']); ?>" class="btn btn-outline-custom">Cancel</a>
                </div>
            </form>
        </div>

        <div class="danger-zone-card reveal">
            <div class="danger-zone-topbar" aria-hidden="true"></div>
            <div class="danger-zone-inner">
                <div class="danger-zone-header">
                    <span class="danger-zone-icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
                    <div>
                        <h5 class="danger-zone-title mb-1">Danger Zone</h5>
                        <p class="danger-zone-subtitle mb-0">Close your account and sign out</p>
                    </div>
                </div>

                <div class="danger-zone-grid">
                    <div class="danger-zone-item">
                        <span class="danger-zone-item-icon"><i class="fa-solid fa-user-slash"></i></span>
                        <div>
                            <strong>Account closed</strong>
                            <p>Your profile and login will be disabled. You cannot sign in again.</p>
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
                            <strong>Data kept</strong>
                            <p>Your data stays in the database but is hidden from the site.</p>
                        </div>
                    </div>
                </div>

                <div class="danger-zone-footer">
                    <p class="danger-zone-note"><i class="fa-solid fa-circle-info"></i>
                        <?php if ($is_oauth_user): ?>
                        Enter your account email to confirm account closure.
                        <?php else: ?>
                        Enter your password to confirm account closure.
                        <?php endif; ?>
                    </p>
                    <a href="<?php echo app_url('delete-account.php'); ?>" class="btn btn-danger danger-zone-btn">
                        <i class="fa-solid fa-trash-can"></i> Delete My Account
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function setupFileInput(inputId, nameId) {
    var input = document.getElementById(inputId);
    var nameEl = document.getElementById(nameId);
    if (!input || !nameEl) return;
    input.addEventListener('change', function() {
        if (input.files && input.files.length > 0) {
            nameEl.textContent = input.files[0].name;
        } else {
            nameEl.textContent = 'No file chosen';
        }
    });
}
setupFileInput('avatar_input', 'avatar_name');

var togglePasswordBtn = document.getElementById('toggle-password-btn');
var cancelPasswordBtn = document.getElementById('cancel-password-btn');
var passwordPanel = document.getElementById('password-form-panel');

function openPasswordForm() {
    if (!passwordPanel) return;
    passwordPanel.classList.add('is-open');
    if (togglePasswordBtn) togglePasswordBtn.style.display = 'none';
}

function closePasswordForm() {
    if (!passwordPanel) return;
    passwordPanel.classList.remove('is-open');
    if (togglePasswordBtn) togglePasswordBtn.style.display = '';
    var inputs = passwordPanel.querySelectorAll('input[type="password"]');
    for (var i = 0; i < inputs.length; i++) {
        inputs[i].value = '';
    }
}

if (togglePasswordBtn) {
    togglePasswordBtn.addEventListener('click', openPasswordForm);
}
if (cancelPasswordBtn) {
    cancelPasswordBtn.addEventListener('click', closePasswordForm);
}
</script>

<?php require_once ROOT_PATH . '/app/Views/layouts/footer.php'; ?>

