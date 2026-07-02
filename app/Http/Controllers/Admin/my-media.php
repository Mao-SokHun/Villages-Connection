<?php
require_once __DIR__ . '/auth.php';

if (isAdmin()) {
    header('Location: media.php');
    exit;
}

$author_id = (int) $_SESSION['user_id'];
$files = author_media_files($pdo, $author_id);
$total_size = 0;
foreach ($files as $f) {
    $total_size += $f['size'];
}

$page_title = 'My Media';
$admin_active = 'my-media';
require_once ROOT_PATH . '/app/Views/layouts/header.php';
require_once ROOT_PATH . '/app/Views/layouts/admin-nav.php';
?>

<div class="glass-panel p-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h3 class="text-white mb-1"><i class="fa-solid fa-photo-film text-info me-2"></i>My Media</h3>
            <p class="text-secondary small mb-0"><?php echo count($files); ?> file<?php if (count($files) != 1) echo 's'; ?> · <?php echo format_file_size($total_size); ?> from your posts and profile</p>
        </div>
        <a href="posts.php?action=add" class="btn btn-gradient btn-sm"><i class="fa-solid fa-plus"></i> New Post</a>
    </div>

    <?php if (count($files) == 0): ?>
    <div class="text-center py-5 text-secondary">
        <i class="fa-solid fa-images fs-2 mb-3 text-muted"></i>
        <p class="mb-3">No media yet — upload images or videos when you create a post.</p>
        <a href="posts.php?action=add" class="btn btn-gradient btn-sm"><i class="fa-solid fa-plus"></i> Create Post</a>
    </div>
    <?php else: ?>
    <div class="row g-3">
        <?php foreach ($files as $f): ?>
        <div class="col-6 col-md-4 col-lg-3">
            <div class="glass-panel-sm p-2 h-100 author-media-card">
                <?php if ($f['type'] == 'video'): ?>
                <div class="img-placeholder rounded mb-2 author-media-thumb"><i class="fa-solid fa-video fa-2x"></i></div>
                <?php else: ?>
                <img src="../<?php echo htmlspecialchars($f['url']); ?>" alt="" class="img-fluid rounded mb-2 author-media-thumb">
                <?php endif; ?>
                <div class="small text-truncate text-secondary" title="<?php echo htmlspecialchars($f['name']); ?>"><?php echo htmlspecialchars($f['name']); ?></div>
                <div class="small text-secondary mb-1"><?php echo format_file_size($f['size']); ?> · <?php echo date('M j, Y', $f['modified']); ?></div>
                <div class="small text-secondary mb-2">
                    <span class="badge bg-secondary"><?php echo htmlspecialchars(ucfirst($f['type'])); ?></span>
                    <?php if ($f['post_id'] > 0): ?>
                    <a href="posts.php?action=edit&id=<?php echo (int) $f['post_id']; ?>" class="footer-link ms-1"><?php echo htmlspecialchars(excerpt($f['post_title'], 18)); ?></a>
                    <?php else: ?>
                    <span class="ms-1">Profile</span>
                    <?php endif; ?>
                </div>
                <div class="d-flex gap-1">
                    <button type="button" class="btn btn-sm btn-outline-custom flex-fill author-media-copy" data-url="../<?php echo htmlspecialchars($f['url']); ?>" title="Copy URL"><i class="fa-solid fa-link"></i></button>
                    <a href="../<?php echo htmlspecialchars($f['url']); ?>" class="btn btn-sm btn-outline-custom flex-fill" target="_blank" title="Open"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script>
(function() {
    var buttons = document.querySelectorAll('.author-media-copy');
    for (var i = 0; i < buttons.length; i++) {
        buttons[i].addEventListener('click', function() {
            var url = window.location.origin + window.location.pathname.replace(/\/admin\/my-media\.php.*/, '') + '/' + this.getAttribute('data-url').replace(/^\.\.\//, '');
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(function() {
                    if (typeof showToast === 'function') {
                        showToast('Copied', 'Media URL copied to clipboard.', 'success');
                    }
                });
            }
        });
    }
})();
</script>

<?php require_once ROOT_PATH . '/app/Views/layouts/footer.php'; ?>
