<?php
require_once __DIR__ . '/auth.php';

$action = '';
if (isset($_GET['action'])) {
    $action = $_GET['action'];
}

$id = 0;
if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
}

$admin_post = admin_post_action();
if ($admin_post) {
    if ($admin_post['action'] == 'approve' && $admin_post['id'] > 0 && isAdmin()) {
        $pdo->prepare("UPDATE posts SET status = 'Published', updated_at = CURRENT_TIMESTAMP WHERE id = :id")->execute(array('id' => $admin_post['id']));
        notify_post_status_change($pdo, $admin_post['id'], 'Published');
        log_activity($pdo, 'post.approved', 'Post #' . $admin_post['id']);
        setFlashMessage('success', 'Post approved and published.');
        admin_redirect_to('posts.php');
    }
    if ($admin_post['action'] == 'reject' && $admin_post['id'] > 0 && isAdmin()) {
        $pdo->prepare("UPDATE posts SET status = 'Rejected', updated_at = CURRENT_TIMESTAMP WHERE id = :id")->execute(array('id' => $admin_post['id']));
        notify_post_status_change($pdo, $admin_post['id'], 'Rejected');
        log_activity($pdo, 'post.rejected', 'Post #' . $admin_post['id']);
        setFlashMessage('info', 'Post rejected.');
        admin_redirect_to('posts.php');
    }
    if ($admin_post['action'] == 'duplicate' && $admin_post['id'] > 0) {
        $result = duplicate_author_post($pdo, $admin_post['id']);
        if ($result['ok']) {
            setFlashMessage('success', 'Post duplicated as draft.');
            admin_redirect_to('posts.php?action=edit&id=' . (int) $result['id']);
        }
        setFlashMessage('danger', $result['error']);
        admin_redirect_to('posts.php');
    }
    if ($admin_post['action'] == 'delete' && $admin_post['id'] > 0) {
        $stmt = $pdo->prepare('SELECT * FROM posts WHERE id = :id');
        $stmt->execute(array('id' => $admin_post['id']));
        $row = $stmt->fetch();
        if ($row && admin_can_manage_post($row) && $row['status'] != 'Deleted') {
            soft_delete_post($pdo, $admin_post['id']);
            log_activity($pdo, 'post.deleted', 'Post #' . $admin_post['id']);
            setFlashMessage('success', 'Post deleted.');
        }
        admin_redirect_to('posts.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_action']) && isAdmin()) {
    require_valid_csrf();
    $bulk_action = $_POST['bulk_action'];
    $post_ids = array();
    if (isset($_POST['post_ids']) && is_array($_POST['post_ids'])) {
        foreach ($_POST['post_ids'] as $pid) {
            $post_ids[] = (int) $pid;
        }
    }

    if (count($post_ids) > 0) {
        $placeholders = implode(',', array_fill(0, count($post_ids), '?'));
        if ($bulk_action == 'publish') {
            $status_stmt = $pdo->prepare("SELECT id, status FROM posts WHERE id IN ($placeholders)");
            $status_stmt->execute($post_ids);
            foreach ($status_stmt->fetchAll() as $row) {
                if ($row['status'] != 'Published') {
                    notify_post_status_change($pdo, (int) $row['id'], 'Published');
                }
            }
            $pdo->prepare("UPDATE posts SET status = 'Published', updated_at = CURRENT_TIMESTAMP WHERE id IN ($placeholders)")->execute($post_ids);
            log_activity($pdo, 'post.bulk_publish', count($post_ids) . ' posts');
            setFlashMessage('success', count($post_ids) . ' post(s) published.');
        } elseif ($bulk_action == 'draft') {
            $pdo->prepare("UPDATE posts SET status = 'Draft' WHERE id IN ($placeholders)")->execute($post_ids);
            setFlashMessage('success', count($post_ids) . ' post(s) moved to draft.');
        } elseif ($bulk_action == 'delete') {
            foreach ($post_ids as $pid) {
                $stmt = $pdo->prepare('SELECT * FROM posts WHERE id = :id');
                $stmt->execute(array('id' => $pid));
                $row = $stmt->fetch();
                if ($row && $row['status'] != 'Deleted') {
                    soft_delete_post($pdo, (int) $pid);
                }
            }
            log_activity($pdo, 'post.bulk_delete', count($post_ids) . ' posts');
            setFlashMessage('success', count($post_ids) . ' post(s) deleted.');
        }
    }
    admin_redirect_to('posts.php');
}

$errors = array();
$category_mode = 'existing';
$new_category_name = '';
$new_category_description = '';
$new_category_icon = 'fa-tag';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['bulk_action']) && !isset($_POST['admin_action'])) {
    require_valid_csrf();
    $title = '';
    if (isset($_POST['title'])) {
        $title = sanitize_plain_text_field($_POST['title'], 200);
    }

    $category_id = 0;
    if (isset($_POST['category_id'])) {
        $category_id = (int) $_POST['category_id'];
    }

    $summary = '';
    if (isset($_POST['summary'])) {
        $summary = trim($_POST['summary']);
    }

    $content = '';
    if (isset($_POST['content'])) {
        $content = trim($_POST['content']);
    }

    $image_alt = '';
    if (isset($_POST['image_alt'])) {
        $image_alt = trim($_POST['image_alt']);
    }

    $status = 'Draft';
    if (isset($_POST['status'])) {
        $status = $_POST['status'];
    }

    $location = '';
    if (isset($_POST['location'])) {
        $location = trim($_POST['location']);
    }

    $latitude = '';
    if (isset($_POST['latitude'])) {
        $latitude = trim($_POST['latitude']);
    }

    $longitude = '';
    if (isset($_POST['longitude'])) {
        $longitude = trim($_POST['longitude']);
    }

    $expires_at_input = '';
    if (isset($_POST['expires_at'])) {
        $expires_at_input = trim($_POST['expires_at']);
    }

    $archive_on_expiry = 0;
    if (isset($_POST['archive_on_expiry'])) {
        $archive_on_expiry = 1;
    }

    $post_kind = 'general';
    if (isset($_POST['post_kind'])) {
        $post_kind = trim($_POST['post_kind']);
    }

    $knowledge_label = '';
    if (isset($_POST['knowledge_label'])) {
        $knowledge_label = trim($_POST['knowledge_label']);
    }

    $challenge_id = 0;
    if (isset($_POST['challenge_id'])) {
        $challenge_id = (int) $_POST['challenge_id'];
    }

    $is_featured = 0;
    if (isAdmin() && isset($_POST['is_featured'])) {
        $is_featured = 1;
    }

    if (isset($_POST['category_mode']) && $_POST['category_mode'] == 'new') {
        $category_mode = 'new';
    }

    if (isset($_POST['new_category_name'])) {
        $new_category_name = trim($_POST['new_category_name']);
    }
    if (isset($_POST['new_category_description'])) {
        $new_category_description = trim($_POST['new_category_description']);
    }
    if (isset($_POST['new_category_icon'])) {
        $new_category_icon = trim($_POST['new_category_icon']);
    }

    $video_type = 'none';
    if (isset($_POST['video_type'])) {
        $video_type = $_POST['video_type'];
    }

    $youtube_url = '';
    if (isset($_POST['youtube_url'])) {
        $youtube_url = trim($_POST['youtube_url']);
    }

    $user_id = (int) $_SESSION['user_id'];

    if ($title == '') {
        $errors[] = 'Title is required';
    }
    if ($category_mode == 'new') {
        $cat_result = create_user_category($pdo, $new_category_name, $new_category_description, $new_category_icon, $user_id);
        if ($cat_result['ok'] == false) {
            $errors[] = $cat_result['error'];
        } else {
            $category_id = (int) $cat_result['id'];
        }
    } elseif ($category_id <= 0) {
        $errors[] = 'Select a category';
    }
    if ($summary == '') {
        $errors[] = 'Summary is required';
    }
    if ($content == '') {
        $errors[] = 'Content is required';
    }
    if ($latitude !== '' && !is_numeric($latitude)) {
        $errors[] = 'Latitude must be a valid number.';
    }
    if ($longitude !== '' && !is_numeric($longitude)) {
        $errors[] = 'Longitude must be a valid number.';
    }
    $allowed_post_kinds = array('general', 'knowledge', 'event', 'alert');
    if (!in_array($post_kind, $allowed_post_kinds, true)) {
        $post_kind = 'general';
    }
    $allowed_knowledge_labels = array('', 'solved', 'useful', 'tutorial', 'verified');
    if (!in_array($knowledge_label, $allowed_knowledge_labels, true)) {
        $knowledge_label = '';
    }
    if ($post_kind != 'knowledge') {
        $knowledge_label = '';
    }
    if ($challenge_id > 0) {
        $challenge_check = $pdo->prepare('SELECT id FROM community_challenges WHERE id = :id LIMIT 1');
        $challenge_check->execute(array('id' => $challenge_id));
        if (!$challenge_check->fetch()) {
            $challenge_id = 0;
        }
    }
    $expires_at = null;
    if ($expires_at_input != '') {
        $expires_ts = strtotime($expires_at_input);
        if ($expires_ts == false) {
            $errors[] = 'Expiry date/time is not valid.';
        } else {
            $expires_at = date('Y-m-d H:i:s', $expires_ts);
        }
    }
    if (isAdmin()) {
        if ($status != 'Draft' && $status != 'Published' && $status != 'Pending' && $status != 'Rejected' && $status != 'Deleted') {
            $status = 'Draft';
        }
    } else {
        if ($status != 'Draft' && $status != 'Published') {
            $status = 'Draft';
        }
        $status = resolve_post_status_for_author($status);
    }

    $image_url = '';
    if (isset($_POST['existing_image'])) {
        $image_url = $_POST['existing_image'];
    }

    $no_file = array('error' => UPLOAD_ERR_NO_FILE);
    if (!isset($_FILES['image'])) {
        $img = handle_image_upload($no_file, $image_url);
    } else {
        $img = handle_image_upload($_FILES['image'], $image_url);
    }

    if ($img['ok'] == false) {
        $errors[] = $img['error'];
    }

    $existing_video = '';
    if (isset($_POST['existing_video'])) {
        $existing_video = $_POST['existing_video'];
    }

    $existing_vtype = 'none';
    if (isset($_POST['existing_video_type'])) {
        $existing_vtype = $_POST['existing_video_type'];
    }

    if (!isset($_FILES['video_file'])) {
        $vid = parse_video_input($video_type, $youtube_url, $no_file, $existing_video, $existing_vtype);
    } else {
        $vid = parse_video_input($video_type, $youtube_url, $_FILES['video_file'], $existing_video, $existing_vtype);
    }

    if ($vid['ok'] == false) {
        $errors[] = $vid['error'];
    }

    if (count($errors) == 0) {
        if (isset($img['filename'])) {
            $image_url = $img['filename'];
        }

        $slug = slugify($title);

        $video_url = '';
        if (isset($vid['url'])) {
            $video_url = $vid['url'];
        }

        $vid_type = 'none';
        if (isset($vid['type'])) {
            $vid_type = $vid['type'];
        }

        $fields = array(
            'category_id' => $category_id,
            'user_id' => $user_id,
            'title' => $title,
            'slug' => $slug,
            'summary' => $summary,
            'content' => $content,
            'image_url' => $image_url,
            'image_alt' => $image_alt,
            'video_url' => $video_url,
            'video_type' => $vid_type,
            'location' => $location,
            'latitude' => $latitude !== '' ? $latitude : null,
            'longitude' => $longitude !== '' ? $longitude : null,
            'expires_at' => $expires_at,
            'archive_on_expiry' => $archive_on_expiry ? true : false,
            'post_kind' => $post_kind,
            'knowledge_label' => $knowledge_label,
            'challenge_id' => $challenge_id > 0 ? $challenge_id : null,
            'is_featured' => $is_featured,
            'status' => $status,
        );

        $db_action = '';
        if (isset($_POST['db_action'])) {
            $db_action = $_POST['db_action'];
        }

        try {
            if ($db_action == 'add') {
                $sql = 'SELECT COUNT(*) FROM posts WHERE slug = :slug';
                $s = $pdo->prepare($sql);
                $s->execute(array('slug' => $slug));
                if ($s->fetchColumn() > 0) {
                    $fields['slug'] = $fields['slug'] . '-' . time();
                }

                $sql = 'INSERT INTO posts (category_id, user_id, title, slug, summary, content, image_url, image_alt, video_url, video_type, location, latitude, longitude, expires_at, archive_on_expiry, post_kind, knowledge_label, challenge_id, is_featured, status)
                        VALUES (:category_id, :user_id, :title, :slug, :summary, :content, :image_url, :image_alt, :video_url, :video_type, :location, :latitude, :longitude, :expires_at, :archive_on_expiry, :post_kind, :knowledge_label, :challenge_id, :is_featured, :status)
                        RETURNING id';
                $insert_stmt = $pdo->prepare($sql);
                $insert_stmt->execute($fields);
                $new_post_id = (int) $insert_stmt->fetchColumn();
                log_activity($pdo, 'post.created', $fields['title']);
                if ($fields['status'] == 'Published') {
                    notify_followers_on_new_post($pdo, $new_post_id);
                } elseif ($fields['status'] == 'Pending') {
                    notify_admins_pending_post($pdo, $new_post_id);
                }
                setFlashMessage('success', $status == 'Pending' ? 'Post submitted for admin approval.' : 'Post created successfully');
            } elseif ($db_action == 'edit' && $id > 0) {
                $check = $pdo->prepare('SELECT * FROM posts WHERE id = :id');
                $check->execute(array('id' => $id));
                $existing_post = $check->fetch();
                if (!$existing_post || !admin_can_manage_post($existing_post)) {
                    setFlashMessage('danger', 'You cannot edit this post.');
                    header('Location: posts.php');
                    exit;
                }

                $sql = 'SELECT COUNT(*) FROM posts WHERE slug = :slug AND id != :id';
                $s = $pdo->prepare($sql);
                $s->execute(array('slug' => $slug, 'id' => $id));
                if ($s->fetchColumn() > 0) {
                    $fields['slug'] = $fields['slug'] . '-' . time();
                }
                $fields['id'] = $id;

                $update_fields = $fields;
                unset($update_fields['user_id']);

                $sql = 'UPDATE posts SET category_id=:category_id, title=:title, slug=:slug, summary=:summary, content=:content,
                        image_url=:image_url, image_alt=:image_alt, video_url=:video_url, video_type=:video_type, location=:location,
                        latitude=:latitude, longitude=:longitude, expires_at=:expires_at, archive_on_expiry=:archive_on_expiry,
                        post_kind=:post_kind, knowledge_label=:knowledge_label, challenge_id=:challenge_id,
                        is_featured=:is_featured, status=:status, updated_at=CURRENT_TIMESTAMP WHERE id=:id';
                $pdo->prepare($sql)->execute($update_fields);
                if ($existing_post['status'] != $fields['status']) {
                    if ($fields['status'] == 'Published' || $fields['status'] == 'Rejected') {
                        notify_post_status_change($pdo, $id, $fields['status']);
                    } elseif ($fields['status'] == 'Pending') {
                        notify_admins_pending_post($pdo, $id);
                    }
                }
                setFlashMessage('success', 'Post updated successfully');
            }
            header('Location: posts.php');
            exit;
        } catch (PDOException $e) {
            app_log_error('Post save failed: ' . $e->getMessage());
            $errors[] = app_public_error_message('Could not save the post.');
        }
    }
}

$post = null;
if ($action == 'edit' && $id > 0) {
    $sql = 'SELECT * FROM posts WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array('id' => $id));
    $post = $stmt->fetch();
    if (!$post || !admin_can_manage_post($post)) {
        setFlashMessage('danger', 'Post not found or access denied.');
        admin_redirect_to('posts.php');
    }
}

$all_categories = $pdo->query('SELECT id, name, icon FROM categories ORDER BY name')->fetchAll();
$active_challenges = array();
try {
    $active_challenges = $pdo->query("SELECT id, title, status FROM community_challenges ORDER BY CASE status WHEN 'active' THEN 0 WHEN 'draft' THEN 1 ELSE 2 END, title ASC")->fetchAll();
} catch (PDOException $e) {
    $active_challenges = array();
}
$page_title = 'Manage Posts';
$admin_active = 'posts';
require_once ROOT_PATH . '/app/Views/layouts/header.php';
require_once ROOT_PATH . '/app/Views/layouts/admin-nav.php';
?>

<?php if ($action == 'add' || $action == 'edit'): ?>
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="glass-panel p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="text-white mb-0"><i class="fa-solid fa-pen me-2 text-warning"></i>
                    <?php if ($action == 'add'): ?>Create Post<?php else: ?>Edit Post<?php endif; ?>
                </h3>
                <a href="posts.php" class="btn btn-outline-custom btn-sm">Back</a>
            </div>
            <?php if (count($errors) > 0): ?>
            <?php render_user_alerts($errors, 'danger'); ?>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data" action="posts.php?action=<?php echo $action; ?><?php if ($id > 0) echo '&id=' . $id; ?>">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="db_action" value="<?php echo $action; ?>">
                <input type="hidden" name="existing_image" value="<?php if ($post && isset($post['image_url'])) echo htmlspecialchars($post['image_url']); ?>">
                <input type="hidden" name="existing_video" value="<?php if ($post && isset($post['video_url'])) echo htmlspecialchars($post['video_url']); ?>">
                <input type="hidden" name="existing_video_type" value="<?php if ($post && isset($post['video_type'])) echo htmlspecialchars($post['video_type']); else echo 'none'; ?>">

                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label form-label-custom">Title *</label>
                        <input type="text" name="title" class="form-control form-control-custom" required value="<?php if ($post && isset($post['title'])) echo htmlspecialchars($post['title']); ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label form-label-custom">Category *</label>
                        <div class="category-mode-tabs mb-2">
                            <label class="category-mode-tab">
                                <input type="radio" name="category_mode" value="existing" id="cat_mode_existing" <?php if ($category_mode != 'new') echo 'checked'; ?>>
                                <span><i class="fa-solid fa-list"></i> Existing Category</span>
                            </label>
                            <label class="category-mode-tab">
                                <input type="radio" name="category_mode" value="new" id="cat_mode_new" <?php if ($category_mode == 'new') echo 'checked'; ?>>
                                <span><i class="fa-solid fa-plus"></i> Create New Category</span>
                            </label>
                        </div>
                        <div id="existing-category-wrap" class="category-select-card" <?php if ($category_mode == 'new') echo 'style="display:none;"'; ?>>
                            <select name="category_id" id="category_id" class="form-select form-control-custom" data-custom-select-enhanced="category">
                                <option value="" data-icon="fa-layer-group">— Select category —</option>
                                <?php foreach ($all_categories as $c):
                                    $cat_icon = isset($c['icon']) && $c['icon'] != '' ? $c['icon'] : 'fa-tag';
                                ?>
                                <option value="<?php echo $c['id']; ?>" data-icon="<?php echo htmlspecialchars($cat_icon); ?>" <?php if ($post && $post['category_id'] == $c['id']) echo 'selected'; ?>><?php echo htmlspecialchars($c['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div id="new-category-wrap" class="new-category-panel" <?php if ($category_mode != 'new') echo 'style="display:none;"'; ?>>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label form-label-custom small">Category Name *</label>
                                    <input type="text" name="new_category_name" id="new_category_name" class="form-control form-control-custom" placeholder="e.g. Local Market" value="<?php echo htmlspecialchars($new_category_name); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label form-label-custom small">Description *</label>
                                    <input type="text" name="new_category_description" class="form-control form-control-custom" placeholder="Short description" value="<?php echo htmlspecialchars($new_category_description); ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label form-label-custom small">Category Icon *</label>
                                    <?php
                                    $icon_field_name = 'new_category_icon';
                                    $icon_selected = $new_category_icon;
                                    require ROOT_PATH . '/app/Views/partials/category-icon-picker.php';
                                    ?>
                                </div>
                            </div>
                            <p class="text-secondary small mb-0 mt-2"><i class="fa-solid fa-circle-info"></i> New categories are shared with all members. Admin can edit or remove them anytime.</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label form-label-custom">Location</label>
                        <input type="text" name="location" class="form-control form-control-custom" placeholder="e.g. Riverside Village, District..." value="<?php if ($post && isset($post['location'])) echo htmlspecialchars($post['location']); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label form-label-custom">Status</label>
                        <select name="status" class="form-select form-control-custom">
                            <option value="Draft" <?php if ($post && isset($post['status']) && $post['status'] == 'Draft') echo 'selected'; ?>>Draft</option>
                            <option value="Published" <?php if ($post && isset($post['status']) && $post['status'] == 'Published') echo 'selected'; ?>>Published</option>
                            <?php if (isAdmin()): ?>
                            <option value="Pending" <?php if ($post && isset($post['status']) && $post['status'] == 'Pending') echo 'selected'; ?>>Pending</option>
                            <option value="Rejected" <?php if ($post && isset($post['status']) && $post['status'] == 'Rejected') echo 'selected'; ?>>Rejected</option>
                            <?php elseif ($post && isset($post['status']) && $post['status'] == 'Pending'): ?>
                            <option value="Pending" selected>Pending approval</option>
                            <?php elseif ($post && isset($post['status']) && $post['status'] == 'Rejected'): ?>
                            <option value="Rejected" selected>Rejected</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <?php if (isAdmin()): ?>
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured" <?php if ($post && !empty($post['is_featured'])) echo 'checked'; ?>>
                            <label class="form-check-label text-secondary" for="is_featured">Featured post</label>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="col-12">
                        <div class="glass-panel-sm p-3 border border-secondary border-opacity-25">
                            <h6 class="text-white mb-3"><i class="fa-solid fa-sliders text-warning me-2"></i>Advanced Options</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label form-label-custom">Post Type</label>
                                    <select name="post_kind" id="post_kind" class="form-select form-control-custom">
                                        <?php
                                        $current_kind = ($post && isset($post['post_kind'])) ? $post['post_kind'] : 'general';
                                        $post_kind_options = array(
                                            'general' => 'General',
                                            'knowledge' => 'Knowledge / Tips',
                                            'event' => 'Event',
                                            'alert' => 'Alert',
                                        );
                                        foreach ($post_kind_options as $kind_value => $kind_label):
                                        ?>
                                        <option value="<?php echo htmlspecialchars($kind_value); ?>" <?php if ($current_kind === $kind_value) echo 'selected'; ?>><?php echo htmlspecialchars($kind_label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4" id="knowledge_label_wrap">
                                    <label class="form-label form-label-custom">Knowledge Label</label>
                                    <select name="knowledge_label" class="form-select form-control-custom">
                                        <?php
                                        $current_label = ($post && isset($post['knowledge_label'])) ? $post['knowledge_label'] : '';
                                        $label_options = array('' => 'None', 'solved' => 'Solved', 'useful' => 'Useful', 'tutorial' => 'Tutorial', 'verified' => 'Verified');
                                        foreach ($label_options as $label_value => $label_text):
                                        ?>
                                        <option value="<?php echo htmlspecialchars($label_value); ?>" <?php if ($current_label === $label_value) echo 'selected'; ?>><?php echo htmlspecialchars($label_text); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label form-label-custom">Community Challenge</label>
                                    <select name="challenge_id" class="form-select form-control-custom">
                                        <option value="0">— None —</option>
                                        <?php foreach ($active_challenges as $challenge): ?>
                                        <option value="<?php echo (int) $challenge['id']; ?>" <?php if ($post && (int) $post['challenge_id'] === (int) $challenge['id']) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($challenge['title']); ?><?php if ($challenge['status'] != 'active') echo ' (' . htmlspecialchars($challenge['status']) . ')'; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label form-label-custom">Latitude</label>
                                    <input type="text" name="latitude" class="form-control form-control-custom" placeholder="11.5564" value="<?php if ($post && isset($post['latitude']) && $post['latitude'] !== null && $post['latitude'] !== '') echo htmlspecialchars($post['latitude']); ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label form-label-custom">Longitude</label>
                                    <input type="text" name="longitude" class="form-control form-control-custom" placeholder="104.9282" value="<?php if ($post && isset($post['longitude']) && $post['longitude'] !== null && $post['longitude'] !== '') echo htmlspecialchars($post['longitude']); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label form-label-custom">Expires At</label>
                                    <?php
                                    $expires_at_value = '';
                                    if ($post && !empty($post['expires_at'])) {
                                        $expires_at_value = date('Y-m-d\TH:i', strtotime($post['expires_at']));
                                    }
                                    ?>
                                    <input type="datetime-local" name="expires_at" class="form-control form-control-custom" value="<?php echo htmlspecialchars($expires_at_value); ?>">
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" name="archive_on_expiry" id="archive_on_expiry" <?php if (!$post || !isset($post['archive_on_expiry']) || $post['archive_on_expiry']) echo 'checked'; ?>>
                                        <label class="form-check-label text-secondary small" for="archive_on_expiry">Auto-archive</label>
                                    </div>
                                </div>
                            </div>
                            <p class="text-secondary small mb-0 mt-2">Use coordinates for map/nearby features. Expired posts auto-archive when enabled.</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label form-label-custom">Image</label>
                        <?php
                        $current_image = '';
                        if ($post && isset($post['image_url']) && $post['image_url'] != '') {
                            $current_image = $post['image_url'];
                        }
                        ?>
                        <div class="file-upload-box">
                            <label class="file-upload-btn" for="image_input">
                                <i class="fa-solid fa-image"></i> Choose Image
                            </label>
                            <span class="file-upload-name" id="image_name"><?php
                                if ($current_image != '') {
                                    echo 'Current: ' . htmlspecialchars($current_image);
                                } else {
                                    echo 'No file chosen';
                                }
                            ?></span>
                            <input type="file" name="image" id="image_input" class="file-upload-input" accept="image/*">
                        </div>
                        <div class="form-text text-secondary small">JPG, PNG, WEBP, or GIF — max 5 MB</div>
                        <?php if ($current_image != '' && post_media_available($current_image, '')): ?>
                        <div class="mt-2">
                            <img src="<?php echo htmlspecialchars(resolve_media_src($current_image, '')); ?>" alt="Current post image" class="rounded" style="max-width:180px;max-height:120px;object-fit:cover;">
                        </div>
                        <?php endif; ?>
                        <div class="mt-2">
                            <label class="form-label form-label-custom small">Image Alt Text</label>
                            <input type="text" name="image_alt" class="form-control form-control-custom" maxlength="255" placeholder="Describe the image for accessibility and SEO" value="<?php if ($post && isset($post['image_alt'])) echo htmlspecialchars($post['image_alt']); ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label form-label-custom">Video</label>
                        <?php
                        $post_vtype = 'none';
                        if ($post && isset($post['video_type'])) {
                            $post_vtype = $post['video_type'];
                        }
                        ?>
                        <select name="video_type" id="video_type" class="form-select form-control-custom mb-2">
                            <option value="none" <?php if ($post_vtype == 'none') echo 'selected'; ?>>None</option>
                            <option value="upload" <?php if ($post_vtype == 'upload') echo 'selected'; ?>>Upload file (MP4)</option>
                            <option value="youtube" <?php if ($post_vtype == 'youtube') echo 'selected'; ?>>YouTube URL</option>
                        </select>
                        <?php
                        $current_video = '';
                        if ($post && isset($post['video_url']) && $post['video_type'] == 'upload') {
                            $current_video = $post['video_url'];
                        }
                        ?>
                        <div class="file-upload-box mb-2" id="video_file_wrap">
                            <label class="file-upload-btn" for="video_file">
                                <i class="fa-solid fa-video"></i> Choose Video
                            </label>
                            <span class="file-upload-name" id="video_name"><?php
                                if ($current_video != '') {
                                    echo 'Current: ' . htmlspecialchars($current_video);
                                } else {
                                    echo 'No file chosen';
                                }
                            ?></span>
                            <input type="file" name="video_file" id="video_file" class="file-upload-input" accept="video/mp4,video/webm">
                        </div>
                        <div class="form-text text-secondary small">MP4, WEBM, or MOV — max 500 MB, up to 6 minutes</div>
                        <input type="url" name="youtube_url" id="youtube_url" class="form-control form-control-custom" placeholder="https://youtube.com/watch?v=..."
                            value="<?php if ($post_vtype == 'youtube' && $post && isset($post['video_url'])) echo htmlspecialchars($post['video_url']); ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label form-label-custom">Summary *</label>
                        <textarea name="summary" rows="2" class="form-control form-control-custom" required><?php if ($post && isset($post['summary'])) echo htmlspecialchars($post['summary']); ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label form-label-custom">Content *</label>
                        <textarea name="content" rows="10" class="form-control form-control-custom" required><?php if ($post && isset($post['content'])) echo htmlspecialchars($post['content']); ?></textarea>
                        <div class="form-text text-secondary small">Supports basic Markdown: **bold**, *italic*, [link](url), # headings, - lists</div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-outline-custom flex-fill py-3" id="post_preview_btn"><i class="fa-solid fa-eye"></i> Preview</button>
                            <button type="submit" class="btn btn-gradient flex-fill py-3"><i class="fa-solid fa-save"></i> Save</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="postPreviewModal" tabindex="-1" aria-labelledby="postPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content glass-panel border-0">
            <div class="modal-header border-secondary border-opacity-25">
                <h5 class="modal-title text-white" id="postPreviewModalLabel"><i class="fa-solid fa-eye text-warning me-2"></i>Post Preview</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h3 class="text-white mb-2" id="preview_title">Post title</h3>
                <p class="text-secondary lead-summary mb-3" id="preview_summary"></p>
                <div id="preview_image_wrap" class="mb-3" style="display:none;">
                    <img id="preview_image" src="" alt="" class="img-fluid rounded w-100" style="max-height:320px;object-fit:cover;">
                </div>
                <div class="article-body text-secondary" id="preview_content"></div>
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
setupFileInput('image_input', 'image_name');
setupFileInput('video_file', 'video_name');

var catModeExisting = document.getElementById('cat_mode_existing');
var catModeNew = document.getElementById('cat_mode_new');
var existingWrap = document.getElementById('existing-category-wrap');
var newWrap = document.getElementById('new-category-wrap');
var categorySelect = document.getElementById('category_id');

function setCategoryMode(mode) {
    if (!existingWrap || !newWrap) return;
    if (mode == 'new') {
        existingWrap.style.display = 'none';
        newWrap.style.display = 'block';
        if (categorySelect) categorySelect.removeAttribute('required');
    } else {
        existingWrap.style.display = 'block';
        newWrap.style.display = 'none';
        if (categorySelect) categorySelect.setAttribute('required', 'required');
    }
}

if (catModeExisting) {
    catModeExisting.addEventListener('change', function() {
        if (this.checked) setCategoryMode('existing');
    });
}
if (catModeNew) {
    catModeNew.addEventListener('change', function() {
        if (this.checked) setCategoryMode('new');
    });
}
if (catModeNew && catModeNew.checked) {
    setCategoryMode('new');
}

document.getElementById('video_type').addEventListener('change', function() {
    var v = this.value;
    var videoWrap = document.getElementById('video_file_wrap');
    var youtubeInput = document.getElementById('youtube_url');
    if (v == 'upload') {
        videoWrap.style.display = 'flex';
    } else {
        videoWrap.style.display = 'none';
    }
    if (v == 'youtube') {
        youtubeInput.style.display = 'block';
    } else {
        youtubeInput.style.display = 'none';
    }
});
document.getElementById('video_type').dispatchEvent(new Event('change'));

function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function renderPreviewMarkdown(content) {
    var html = escapeHtml(content || '');
    html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
    html = html.replace(/\*(.+?)\*/g, '<em>$1</em>');
    html = html.replace(/\[(.+?)\]\((https?:\/\/[^\s)]+)\)/g, '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>');
    html = html.replace(/^### (.+)$/gm, '<h5>$1</h5>');
    html = html.replace(/^## (.+)$/gm, '<h4>$1</h4>');
    html = html.replace(/^# (.+)$/gm, '<h3>$1</h3>');
    html = html.replace(/^\- (.+)$/gm, '<li>$1</li>');
    if (html.indexOf('<li>') !== -1) {
        html = html.replace(/((?:<li>.+<\/li>\s*)+)/g, '<ul>$1</ul>');
    }
    return html.replace(/\n/g, '<br>');
}

var previewBtn = document.getElementById('post_preview_btn');
if (previewBtn && typeof bootstrap !== 'undefined') {
    var previewModal = new bootstrap.Modal(document.getElementById('postPreviewModal'));
    previewBtn.addEventListener('click', function() {
        var titleInput = document.querySelector('input[name="title"]');
        var summaryInput = document.querySelector('textarea[name="summary"]');
        var contentInput = document.querySelector('textarea[name="content"]');
        var existingImage = document.querySelector('input[name="existing_image"]');
        var imageInput = document.getElementById('image_input');

        document.getElementById('preview_title').textContent = titleInput && titleInput.value ? titleInput.value : 'Untitled post';
        document.getElementById('preview_summary').textContent = summaryInput ? summaryInput.value : '';
        document.getElementById('preview_content').innerHTML = renderPreviewMarkdown(contentInput ? contentInput.value : '');

        var imageWrap = document.getElementById('preview_image_wrap');
        var previewImage = document.getElementById('preview_image');
        var imageSrc = '';
        if (imageInput && imageInput.files && imageInput.files.length > 0) {
            imageSrc = URL.createObjectURL(imageInput.files[0]);
        } else if (existingImage && existingImage.value) {
            imageSrc = '../uploads/' + existingImage.value;
        }
        if (imageSrc) {
            previewImage.src = imageSrc;
            imageWrap.style.display = 'block';
        } else {
            previewImage.src = '';
            imageWrap.style.display = 'none';
        }

        previewModal.show();
    });
}

(function() {
    var postKind = document.getElementById('post_kind');
    var knowledgeWrap = document.getElementById('knowledge_label_wrap');
    if (!postKind || !knowledgeWrap) return;
    function syncKnowledgeField() {
        knowledgeWrap.style.display = postKind.value === 'knowledge' ? '' : 'none';
    }
    postKind.addEventListener('change', syncKnowledgeField);
    syncKnowledgeField();
})();
</script>
<?php else: ?>
<div class="glass-panel p-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h3 class="text-white mb-0"><i class="fa-solid fa-images text-warning me-2"></i><?php echo isAdmin() ? 'All Posts' : 'My Posts'; ?></h3>
            <?php if (!isAdmin()): ?>
            <p class="text-secondary small mb-0"><i class="fa-solid fa-lock me-1"></i>Drafts and pending posts are private — only you and admins can see them.</p>
            <?php endif; ?>
        </div>
        <a href="posts.php?action=add" class="btn btn-gradient btn-sm"><i class="fa-solid fa-plus"></i> Create New</a>
    </div>
    <?php
    $list_search = '';
    if (isset($_GET['search'])) {
        $list_search = trim($_GET['search']);
    }

    $list_status = '';
    if (isset($_GET['status'])) {
        $list_status = trim($_GET['status']);
    }

    $list_category = 0;
    if (isset($_GET['category'])) {
        $list_category = (int) $_GET['category'];
    }

    $list_sort = 'newest';
    if (isset($_GET['sort'])) {
        $list_sort = trim($_GET['sort']);
    }

    $list_where = ' WHERE 1=1';
    $list_params = array();

    if (!isAdmin()) {
        $list_where .= ' AND p.user_id = :owner_id';
        $list_params['owner_id'] = (int) $_SESSION['user_id'];
    }

    if ($list_search != '') {
        $list_where .= ' AND (p.title ILIKE :search OR p.summary ILIKE :search OR u.name ILIKE :search OR c.name ILIKE :search)';
        $list_params['search'] = '%' . $list_search . '%';
    }
    if ($list_status == 'Published' || $list_status == 'Draft' || $list_status == 'Pending' || $list_status == 'Rejected' || $list_status == 'Deleted') {
        $list_where .= ' AND p.status = :status';
        $list_params['status'] = $list_status;
    }
    if ($list_category > 0) {
        $list_where .= ' AND p.category_id = :category_id';
        $list_params['category_id'] = $list_category;
    }

    $list_order = ' ORDER BY p.id DESC';
    if ($list_sort == 'oldest') {
        $list_order = ' ORDER BY p.id ASC';
    } elseif ($list_sort == 'title_az') {
        $list_order = ' ORDER BY p.title ASC';
    } elseif ($list_sort == 'title_za') {
        $list_order = ' ORDER BY p.title DESC';
    } elseif ($list_sort == 'views') {
        $list_order = ' ORDER BY p.views DESC, p.id DESC';
    } elseif ($list_sort == 'likes') {
        $list_order = ' ORDER BY p.likes DESC, p.id DESC';
    }

    $sql = "SELECT p.*, c.name as category_name, u.name as author_name FROM posts p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN users u ON p.user_id = u.id" . $list_where . $list_order;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($list_params);
    $all_posts = $stmt->fetchAll();

    $list_has_filters = ($list_search != '' || $list_status != '' || $list_category > 0 || $list_sort != 'newest');
    ?>
    <form method="GET" action="posts.php" class="admin-list-toolbar mb-4">
        <div class="row g-2 align-items-end">
            <div class="col-md-4 col-lg-3">
                <label class="form-label form-label-custom small mb-1">Search</label>
                <input type="search" name="search" class="form-control form-control-custom" placeholder="Title, author, category..." value="<?php echo htmlspecialchars($list_search); ?>">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label form-label-custom small mb-1">Status</label>
                <select name="status" class="form-select form-control-custom">
                    <option value="">All</option>
                    <option value="Published" <?php if ($list_status == 'Published') echo 'selected'; ?>>Published</option>
                    <option value="Draft" <?php if ($list_status == 'Draft') echo 'selected'; ?>>Draft</option>
                    <option value="Pending" <?php if ($list_status == 'Pending') echo 'selected'; ?>>Pending</option>
                    <option value="Rejected" <?php if ($list_status == 'Rejected') echo 'selected'; ?>>Rejected</option>
                    <option value="Deleted" <?php if ($list_status == 'Deleted') echo 'selected'; ?>>Deleted</option>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label form-label-custom small mb-1">Category</label>
                <select name="category" class="form-select form-control-custom">
                    <option value="">All categories</option>
                    <?php foreach ($all_categories as $cat_opt):
                        $filter_icon = isset($cat_opt['icon']) && $cat_opt['icon'] != '' ? $cat_opt['icon'] : 'fa-tag';
                    ?>
                    <option value="<?php echo (int) $cat_opt['id']; ?>" data-icon="<?php echo htmlspecialchars($filter_icon); ?>" <?php if ($list_category == (int) $cat_opt['id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($cat_opt['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label form-label-custom small mb-1">Sort by</label>
                <select name="sort" class="form-select form-control-custom">
                    <option value="newest" <?php if ($list_sort == 'newest') echo 'selected'; ?>>Newest first</option>
                    <option value="oldest" <?php if ($list_sort == 'oldest') echo 'selected'; ?>>Oldest first</option>
                    <option value="title_az" <?php if ($list_sort == 'title_az') echo 'selected'; ?>>Title A–Z</option>
                    <option value="title_za" <?php if ($list_sort == 'title_za') echo 'selected'; ?>>Title Z–A</option>
                    <option value="views" <?php if ($list_sort == 'views') echo 'selected'; ?>>Most views</option>
                    <option value="likes" <?php if ($list_sort == 'likes') echo 'selected'; ?>>Most likes</option>
                </select>
            </div>
            <div class="col-6 col-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-gradient btn-sm"><i class="fa-solid fa-filter"></i> Apply</button>
                <?php if ($list_has_filters): ?>
                <a href="posts.php" class="btn btn-outline-custom btn-sm">Reset</a>
                <?php endif; ?>
            </div>
        </div>
        <p class="admin-list-results mb-0 mt-2"><i class="fa-solid fa-list-ul me-1"></i><?php echo count($all_posts); ?> post<?php if (count($all_posts) != 1) echo 's'; ?> found</p>
    </form>
    <?php if (count($all_posts) == 0): ?>
        <?php if ($list_has_filters): ?>
        <p class="text-secondary text-center py-5">No posts match your filters — <a href="posts.php">clear filters</a> or <a href="posts.php?action=add">create one</a></p>
        <?php else: ?>
        <p class="text-secondary text-center py-5">No posts yet — <a href="posts.php?action=add">create one</a></p>
        <?php endif; ?>
    <?php else: ?>
    <?php if (isAdmin()): ?>
    <form method="POST" action="posts.php" id="bulk_posts_form">
        <?php echo csrf_field(); ?>
        <div class="d-flex flex-wrap gap-2 mb-3">
            <select name="bulk_action" class="form-select form-control-custom" style="max-width:180px">
                <option value="">Bulk action...</option>
                <option value="publish">Publish selected</option>
                <option value="draft">Move to draft</option>
                <option value="delete">Delete selected</option>
            </select>
            <button type="submit" class="btn btn-outline-custom btn-sm" onclick="return confirm('Apply bulk action to selected posts?')">Apply</button>
        </div>
    <?php endif; ?>
    <div class="table-responsive">
        <table class="table table-custom table-hover mb-0">
            <thead><tr>
                <?php if (isAdmin()): ?><th><input type="checkbox" id="select_all_posts"></th><?php endif; ?>
                <th>Image</th><th>Title</th><?php if (isAdmin()): ?><th>Author</th><?php endif; ?><th>Category</th><th>Media</th><th>Status</th><th>Views/Likes</th><th></th>
            </tr></thead>
            <tbody>
            <?php foreach ($all_posts as $p): ?>
            <tr>
                <?php if (isAdmin()): ?><td><input type="checkbox" name="post_ids[]" value="<?php echo (int) $p['id']; ?>" form="bulk_posts_form"></td><?php endif; ?>
                <td><?php if ($p['image_url'] != '' && post_media_available($p['image_url'], '')): ?>
                    <img src="<?php echo htmlspecialchars(resolve_media_src($p['image_url'], '')); ?>" style="width:48px;height:48px;object-fit:cover;border-radius:8px">
                <?php else: ?>—<?php endif; ?></td>
                <td class="table-cell-title"><?php echo htmlspecialchars($p['title']); ?>
                    <?php if ($p['is_featured']): ?><span class="badge bg-warning text-dark ms-1">Featured</span><?php endif; ?>
                </td>
                <?php if (isAdmin()): ?>
                <td class="small table-cell-muted"><?php echo isset($p['author_name']) ? htmlspecialchars($p['author_name']) : '—'; ?></td>
                <?php endif; ?>
                <td class="table-cell-muted small"><?php
                    if (isset($p['category_name'])) {
                        echo htmlspecialchars($p['category_name']);
                    }
                ?></td>
                <td class="small">
                    <?php if (post_has_video($p)): ?><i class="fa-solid fa-video text-info"></i><?php endif; ?>
                    <?php if ($p['image_url'] != ''): ?><i class="fa-solid fa-image text-success"></i><?php endif; ?>
                </td>
                <td><span class="badge <?php echo post_status_badge_class($p['status']); ?>"><?php echo htmlspecialchars(post_status_label($p['status'])); ?></span></td>
                <td class="table-cell-strong small"><?php echo (int)$p['views']; ?> / <?php echo (int)$p['likes']; ?></td>
                <td class="text-nowrap">
                    <?php if (isAdmin() && $p['status'] == 'Pending'): ?>
                    <?php render_admin_action_button('posts.php', 'approve', $p['id'], array('class' => 'btn btn-sm btn-outline-custom text-success', 'icon' => 'fa-solid fa-check', 'title' => 'Approve')); ?>
                    <?php render_admin_action_button('posts.php', 'reject', $p['id'], array('class' => 'btn btn-sm btn-outline-custom text-warning', 'icon' => 'fa-solid fa-ban', 'title' => 'Reject')); ?>
                    <?php endif; ?>
                    <?php if ($p['status'] == 'Published'): ?>
                    <a href="../post/<?php echo urlencode($p['slug']); ?>" class="btn btn-sm btn-outline-custom" target="_blank" title="View published post"><i class="fa-solid fa-eye"></i></a>
                    <?php else: ?>
                    <a href="posts.php?action=edit&id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-custom" title="Preview unavailable until published"><i class="fa-solid fa-eye-slash"></i></a>
                    <?php endif; ?>
                    <a href="posts.php?action=edit&id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-custom text-info"><i class="fa-solid fa-edit"></i></a>
                    <?php render_admin_action_button('posts.php', 'duplicate', $p['id'], array('class' => 'btn btn-sm btn-outline-custom text-warning', 'icon' => 'fa-solid fa-copy', 'title' => 'Duplicate as draft')); ?>
                    <?php render_admin_action_button('posts.php', 'delete', $p['id'], array('class' => 'btn btn-sm btn-outline-custom text-danger', 'icon' => 'fa-solid fa-trash', 'title' => 'Delete', 'confirm' => 'Delete this post?')); ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (isAdmin()): ?></form><?php endif; ?>
    <script>
    (function() {
        var all = document.getElementById('select_all_posts');
        if (!all) return;
        all.addEventListener('change', function() {
            var boxes = document.querySelectorAll('input[name="post_ids[]"]');
            for (var i = 0; i < boxes.length; i++) boxes[i].checked = all.checked;
        });
    })();
    </script>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php require_once ROOT_PATH . '/app/Views/layouts/footer.php'; ?>

