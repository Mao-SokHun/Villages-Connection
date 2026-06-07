<?php
require_once APP_PATH . '/Core/oauth.php';

$show_social = oauth_any_configured();
if (!$show_social) {
    return;
}
?>
<div class="social-auth-wrap">
    <div class="social-auth-divider">
        <span>or continue with</span>
    </div>
    <div class="social-auth-buttons">
        <?php if (oauth_is_configured('google')): ?>
        <a href="auth/google.php" class="social-auth-btn social-auth-google">
            <i class="fa-brands fa-google"></i>
            <span>Google</span>
        </a>
        <?php endif; ?>
        <?php if (oauth_is_configured('facebook')): ?>
        <a href="auth/facebook.php" class="social-auth-btn social-auth-facebook">
            <i class="fa-brands fa-facebook-f"></i>
            <span>Facebook</span>
        </a>
        <?php endif; ?>
    </div>
</div>
