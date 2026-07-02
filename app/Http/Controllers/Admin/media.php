<?php
require_once __DIR__ . '/auth.php';
requireAdmin();

$subdir = '';
if (isset($_GET['subdir'])) {
    $subdir = trim($_GET['subdir'], '/');
}

$admin_post = admin_post_action();
if ($admin_post) {
    if ($admin_post['action'] == 'delete' && $admin_post['value'] != '') {
        $file = basename($admin_post['value']);
        if (media_file_in_use($pdo, $file, $subdir)) {
            setFlashMessage('danger', 'This file is still used by a post or profile and cannot be deleted.');
            header('Location: media.php' . ($subdir != '' ? '?subdir=' . urlencode($subdir) : ''));
            exit;
        }
        delete_upload($file, $subdir);
        log_activity($pdo, 'media.deleted', $subdir . '/' . $file);
        setFlashMessage('success', 'File deleted.');
        header('Location: media.php' . ($subdir != '' ? '?subdir=' . urlencode($subdir) : ''));
        exit;
    }
}

$tabs = array(
    '' => 'Post Images',
    'videos' => 'Videos',
    'avatars' => 'Avatars'
);

$files = scan_upload_directory($subdir);
$total_size = 0;
foreach ($files as $f) {
    $total_size += $f['size'];
}

$media_page = 'media.php' . ($subdir != '' ? '?subdir=' . urlencode($subdir) : '');

$page_title = 'Media Library';
$admin_active = 'media';
require_once ROOT_PATH . '/app/Views/layouts/header.php';
require_once ROOT_PATH . '/app/Views/layouts/admin-nav.php';
?>

<div class="glass-panel p-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h3 class="text-white mb-1"><i class="fa-solid fa-photo-film text-info me-2"></i>Media Library</h3>
            <p class="text-secondary small mb-0"><?php echo count($files); ?> files · <?php echo format_file_size($total_size); ?> total</p>
        </div>
        <div class="d-flex gap-2">
            <?php foreach ($tabs as $tab_subdir => $tab_label): ?>
            <a href="media.php<?php echo $tab_subdir != '' ? '?subdir=' . urlencode($tab_subdir) : ''; ?>" class="btn btn-sm <?php echo $subdir == $tab_subdir ? 'btn-gradient' : 'btn-outline-custom'; ?>"><?php echo htmlspecialchars($tab_label); ?></a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (count($files) == 0): ?>
    <p class="text-secondary mb-0">No files in this folder.</p>
    <?php else: ?>
    <div class="row g-3">
        <?php foreach ($files as $f): ?>
        <div class="col-6 col-md-4 col-lg-3">
            <div class="glass-panel-sm p-2 h-100">
                <?php if ($subdir == 'videos'): ?>
                <div class="img-placeholder rounded mb-2" style="height:100px"><i class="fa-solid fa-video fa-2x"></i></div>
                <?php else: ?>
                <img src="../<?php echo htmlspecialchars($f['url']); ?>" alt="" class="img-fluid rounded mb-2" style="height:100px;width:100%;object-fit:cover">
                <?php endif; ?>
                <div class="small text-truncate text-secondary" title="<?php echo htmlspecialchars($f['name']); ?>"><?php echo htmlspecialchars($f['name']); ?></div>
                <div class="small text-secondary"><?php echo format_file_size($f['size']); ?> · <?php echo date('M j, Y', $f['modified']); ?></div>
                <?php render_admin_action_button($media_page, 'delete', 0, array('class' => 'btn btn-sm btn-outline-custom text-danger mt-2 w-100', 'icon' => 'fa-solid fa-trash', 'label' => 'Delete', 'title' => 'Delete', 'confirm' => 'Delete this file?', 'value' => $f['name'])); ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once ROOT_PATH . '/app/Views/layouts/footer.php'; ?>
