<?php

function upload_path($subdir)
{
    $base = PUBLIC_PATH . '/uploads/';

    if ($subdir != '') {
        $base = $base . trim($subdir, '/') . '/';
    }

    if (!is_dir($base)) {
        mkdir($base, 0755, true);
    }

    return $base;
}

function media_url($file, $subdir)
{
    if ($file == '' || $file == null) {
        return '';
    }

    if ($subdir != '') {
        return 'uploads/' . trim($subdir, '/') . '/' . $file;
    }

    return 'uploads/' . $file;
}

function slugify($text)
{
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = strtolower(trim($text, '-'));
    $text = preg_replace('~-+~', '-', $text);

    if ($text == '') {
        $text = 'post-' . time();
    }

    return $text;
}

function delete_upload($filename, $subdir)
{
    if ($filename == '' || $filename == null) {
        return;
    }

    $path = upload_path($subdir) . $filename;

    if (is_file($path)) {
        unlink($path);
    }
}

function handle_image_upload($file, $existing)
{
    $result = array('ok' => false, 'error' => 'Image upload failed');

    if ($file['error'] != UPLOAD_ERR_OK) {
        $result['ok'] = true;
        $result['filename'] = $existing;
        return $result;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = array('jpg', 'jpeg', 'png', 'webp', 'gif');

    if (!in_array($ext, $allowed)) {
        $result['error'] = 'Image must be JPG, PNG, WEBP, or GIF';
        return $result;
    }

    $name = time() . '_' . slugify(pathinfo($file['name'], PATHINFO_FILENAME)) . '.' . $ext;

    if (move_uploaded_file($file['tmp_name'], upload_path('') . $name)) {
        if ($existing != '') {
            delete_upload($existing, '');
        }

        $result['ok'] = true;
        $result['filename'] = $name;
        return $result;
    }

    return $result;
}

function handle_video_upload($file, $existing)
{
    $result = array('ok' => false, 'error' => 'Video upload failed');

    if ($file['error'] != UPLOAD_ERR_OK) {
        $result['ok'] = true;
        $result['filename'] = $existing;
        return $result;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = array('mp4', 'webm', 'mov');

    if (!in_array($ext, $allowed)) {
        $result['error'] = 'Video must be MP4, WEBM, or MOV';
        return $result;
    }

    if ($file['size'] > 50 * 1024 * 1024) {
        $result['error'] = 'Video cannot exceed 50MB';
        return $result;
    }

    $name = time() . '_' . slugify(pathinfo($file['name'], PATHINFO_FILENAME)) . '.' . $ext;

    if (move_uploaded_file($file['tmp_name'], upload_path('videos') . $name)) {
        if ($existing != '') {
            delete_upload($existing, 'videos');
        }

        $result['ok'] = true;
        $result['filename'] = $name;
        return $result;
    }

    return $result;
}

function parse_video_input($video_type, $youtube_url, $file, $existing_url, $existing_type)
{
    if ($video_type == 'none') {
        if ($existing_type == 'upload' && $existing_url != '') {
            delete_upload($existing_url, 'videos');
        }

        return array('ok' => true, 'type' => 'none', 'url' => '');
    }

    if ($video_type == 'youtube') {
        $url = trim($youtube_url);

        if ($url == '' && $existing_type == 'youtube') {
            $url = $existing_url;
        }

        if ($url == '') {
            return array('ok' => false, 'error' => 'Enter a YouTube URL');
        }

        if (strpos($url, 'youtube.com') === false && strpos($url, 'youtu.be') === false) {
            return array('ok' => false, 'error' => 'Invalid YouTube URL');
        }

        if ($existing_type == 'upload' && $existing_url != '') {
            delete_upload($existing_url, 'videos');
        }

        return array('ok' => true, 'type' => 'youtube', 'url' => $url);
    }

    if ($video_type == 'upload') {
        $keep = '';
        if ($existing_type == 'upload') {
            $keep = $existing_url;
        }

        if ($file['error'] == UPLOAD_ERR_NO_FILE && $keep != '') {
            return array('ok' => true, 'type' => 'upload', 'url' => $keep);
        }

        $up = handle_video_upload($file, $keep);

        if ($up['ok'] == false) {
            return $up;
        }

        if (!isset($up['filename']) || $up['filename'] == '') {
            return array('ok' => false, 'error' => 'Select a video file');
        }

        return array('ok' => true, 'type' => 'upload', 'url' => $up['filename']);
    }

    return array('ok' => true, 'type' => 'none', 'url' => '');
}

function youtube_embed_url($url)
{
    $id = '';

    if (preg_match('/youtu\.be\/([^\?&]+)/', $url, $m)) {
        $id = $m[1];
    } elseif (preg_match('/[?&]v=([^&]+)/', $url, $m)) {
        $id = $m[1];
    } elseif (preg_match('/embed\/([^?&]+)/', $url, $m)) {
        $id = $m[1];
    }

    if ($id == '') {
        return '';
    }

    return 'https://www.youtube.com/embed/' . $id;
}

function visitor_key()
{
    if (!isset($_SESSION['visitor_key']) || $_SESSION['visitor_key'] == '') {
        $_SESSION['visitor_key'] = bin2hex(random_bytes(16));
    }

    return $_SESSION['visitor_key'];
}

function format_date($datetime)
{
    return date('j M Y', strtotime($datetime));
}

function khmer_date($datetime)
{
    $months = array(
        1 => 'មករា', 2 => 'កុម្ភៈ', 3 => 'មីនា', 4 => 'មេសា',
        5 => 'ឧសភា', 6 => 'មិថុនា', 7 => 'កក្កដា', 8 => 'សីហា',
        9 => 'កញ្ញា', 10 => 'តុលា', 11 => 'វិច្ឆិកា', 12 => 'ធ្នូ'
    );
    $ts = strtotime($datetime);
    if ($ts == false) {
        return format_date($datetime);
    }
    $day = (int) date('j', $ts);
    $month = (int) date('n', $ts);
    $year = date('Y', $ts);
    if (isset($months[$month])) {
        return $day . ' ' . $months[$month] . ' ' . $year;
    }
    return format_date($datetime);
}

function excerpt($text, $len)
{
    $text = strip_tags($text);

    if (strlen($text) <= $len) {
        return $text;
    }

    return substr($text, 0, $len) . '...';
}

function post_has_video($post)
{
    if (!isset($post['video_url']) || $post['video_url'] == '') {
        return false;
    }

    if (!isset($post['video_type']) || $post['video_type'] == 'none') {
        return false;
    }

    return true;
}

function build_page_url($params)
{
    return build_query_url('index.php', $params);
}

function build_query_url($base, $params)
{
    $parts = array();
    foreach ($params as $key => $val) {
        if ($key == 'page' && ($val == '' || $val == 1 || $val == '1')) {
            continue;
        }
        if ($val != '' && $val != null) {
            $parts[] = $key . '=' . urlencode($val);
        }
    }
    if (count($parts) == 0) {
        return $base;
    }
    return $base . '?' . implode('&', $parts);
}

function user_initials($name)
{
    $name = trim($name);
    if ($name == '') {
        return '?';
    }
    $words = explode(' ', $name);
    $initials = strtoupper(substr($words[0], 0, 1));
    if (count($words) > 1) {
        $initials = $initials . strtoupper(substr($words[1], 0, 1));
    }
    return $initials;
}

function handle_avatar_upload($file, $existing)
{
    $result = array('ok' => false, 'error' => 'Avatar upload failed');

    if ($file['error'] != UPLOAD_ERR_OK) {
        $result['ok'] = true;
        $result['filename'] = $existing;
        return $result;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = array('jpg', 'jpeg', 'png', 'webp', 'gif');

    if (!in_array($ext, $allowed)) {
        $result['error'] = 'Avatar must be JPG, PNG, WEBP, or GIF';
        return $result;
    }

    if ($file['size'] > 2 * 1024 * 1024) {
        $result['error'] = 'Avatar cannot exceed 2MB';
        return $result;
    }

    $name = time() . '_' . slugify(pathinfo($file['name'], PATHINFO_FILENAME)) . '.' . $ext;

    if (move_uploaded_file($file['tmp_name'], upload_path('avatars') . $name)) {
        if ($existing != '') {
            delete_upload($existing, 'avatars');
        }

        $result['ok'] = true;
        $result['filename'] = $name;
        return $result;
    }

    return $result;
}

function is_external_avatar($avatar)
{
    if ($avatar == '' || $avatar == null) {
        return false;
    }
    if (strpos($avatar, 'http://') === 0) {
        return true;
    }
    if (strpos($avatar, 'https://') === 0) {
        return true;
    }
    return false;
}

function user_avatar_exists($avatar)
{
    if ($avatar == '' || $avatar == null) {
        return false;
    }

    if (is_external_avatar($avatar)) {
        return true;
    }

    $path = upload_path('avatars') . $avatar;
    if (is_file($path)) {
        return true;
    }

    return false;
}

function render_user_avatar($name, $avatar, $extra_class)
{
    $class = 'user-avatar';
    if ($extra_class != '') {
        $class = $class . ' ' . $extra_class;
    }

    if (user_avatar_exists($avatar)) {
        $src = media_url($avatar, 'avatars');
        if (is_external_avatar($avatar)) {
            $src = $avatar;
        }
        $html = '<span class="' . $class . ' user-avatar-img-wrap">';
        $html = $html . '<img src="' . htmlspecialchars($src) . '" alt="' . htmlspecialchars($name) . '">';
        $html = $html . '</span>';
        return $html;
    }

    return '<span class="' . $class . '">' . user_initials($name) . '</span>';
}

function get_user_by_id($pdo, $id)
{
    $sql = 'SELECT * FROM users WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array('id' => $id));
    return $stmt->fetch();
}

function refresh_user_session($pdo, $user_id)
{
    $user = get_user_by_id($pdo, $user_id);
    if (!$user) {
        return false;
    }

    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_avatar'] = '';
    if (isset($user['avatar'])) {
        $_SESSION['user_avatar'] = $user['avatar'];
    }

    return true;
}

function user_post_count($pdo, $user_id)
{
    $sql = "SELECT COUNT(*) FROM posts WHERE user_id = :uid AND status = 'Published'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array('uid' => $user_id));
    return (int) $stmt->fetchColumn();
}

function user_role_label($role)
{
    if ($role == 'admin') {
        return 'Administrator';
    }
    if ($role == 'author') {
        return 'Author';
    }
    return 'Member';
}

function generate_otp_code()
{
    return sprintf('%06d', random_int(0, 999999));
}

function create_password_reset_otp($pdo, $user_id, $email)
{
    $otp = generate_otp_code();
    $hash = password_hash($otp, PASSWORD_BCRYPT);

    $sql = 'UPDATE password_reset_otps SET used_at = CURRENT_TIMESTAMP WHERE user_id = :uid AND used_at IS NULL';
    $pdo->prepare($sql)->execute(array('uid' => $user_id));

    $sql = 'INSERT INTO password_reset_otps (user_id, email, otp_hash, expires_at)
            VALUES (:uid, :email, :hash, CURRENT_TIMESTAMP + INTERVAL \'15 minutes\')';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array(
        'uid' => $user_id,
        'email' => $email,
        'hash' => $hash
    ));

    return $otp;
}

function verify_password_reset_otp($pdo, $email, $otp)
{
    $sql = "SELECT * FROM password_reset_otps
            WHERE email = :email AND used_at IS NULL AND expires_at > CURRENT_TIMESTAMP
            ORDER BY created_at DESC
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array('email' => $email));
    $row = $stmt->fetch();

    if (!$row) {
        return false;
    }

    if (!password_verify($otp, $row['otp_hash'])) {
        return false;
    }

    return $row;
}

function mark_otp_used($pdo, $otp_id)
{
    $sql = 'UPDATE password_reset_otps SET used_at = CURRENT_TIMESTAMP WHERE id = :id';
    $pdo->prepare($sql)->execute(array('id' => $otp_id));
}

function admin_count($pdo)
{
    $sql = "SELECT COUNT(*) FROM users WHERE role = 'admin'";
    return (int) $pdo->query($sql)->fetchColumn();
}

function can_delete_own_account($pdo, $user)
{
    if ($user['role'] != 'admin') {
        return true;
    }

    if (admin_count($pdo) <= 1) {
        return false;
    }

    return true;
}

function category_icon_options()
{
    return array(
        'fa-tag' => 'General',
        'fa-newspaper' => 'News',
        'fa-fire' => 'Trending',
        'fa-star' => 'Featured',
        'fa-heart' => 'Love',
        'fa-wheat-awn' => 'Agriculture',
        'fa-seedling' => 'Farming',
        'fa-tractor' => 'Farm Tools',
        'fa-cow' => 'Livestock',
        'fa-masks-theater' => 'Culture',
        'fa-landmark' => 'Heritage',
        'fa-music' => 'Music',
        'fa-palette' => 'Art',
        'fa-calendar-days' => 'Events',
        'fa-champagne-glasses' => 'Celebration',
        'fa-people-group' => 'Community',
        'fa-handshake' => 'Together',
        'fa-map-location-dot' => 'Tourism',
        'fa-plane' => 'Travel',
        'fa-camera' => 'Photography',
        'fa-image' => 'Photos',
        'fa-video' => 'Video',
        'fa-clapperboard' => 'Movies',
        'fa-microphone' => 'Podcast',
        'fa-book' => 'Education',
        'fa-graduation-cap' => 'School',
        'fa-briefcase' => 'Business',
        'fa-store' => 'Shop',
        'fa-cart-shopping' => 'Market',
        'fa-heart-pulse' => 'Health',
        'fa-hospital' => 'Medical',
        'fa-futbol' => 'Sports',
        'fa-basketball' => 'Basketball',
        'fa-dumbbell' => 'Fitness',
        'fa-utensils' => 'Food',
        'fa-mug-hot' => 'Cafe',
        'fa-burger' => 'Restaurant',
        'fa-car' => 'Transport',
        'fa-bus' => 'Public Transit',
        'fa-tree' => 'Nature',
        'fa-leaf' => 'Environment',
        'fa-sun' => 'Weather',
        'fa-cloud-sun' => 'Climate',
        'fa-house-chimney' => 'Village',
        'fa-building' => 'City',
        'fa-mobile-screen' => 'Mobile',
        'fa-wifi' => 'Internet',
        'fa-gamepad' => 'Gaming',
        'fa-gift' => 'Gifts',
        'fa-baby' => 'Family',
        'fa-paw' => 'Animals',
        'fa-fish' => 'Fishing',
        'fa-shirt' => 'Fashion',
        'fa-scissors' => 'Beauty',
        'fa-hammer' => 'Craft',
        'fa-lightbulb' => 'Ideas',
        'fa-bolt' => 'Energy',
        'fa-shield-halved' => 'Safety',
        'fa-hand-holding-heart' => 'Charity',
        'fa-comments' => 'Discussion',
        'fa-bullhorn' => 'Announcement',
    );
}

function category_emoji_presets()
{
    return array(
        '🏠', '🌾', '🌽', '🍚', '🍜', '🥗', '🍔', '☕',
        '📸', '🎥', '🎬', '🎵', '🎤', '🎉', '🎊', '🎈',
        '❤️', '💬', '👍', '⭐', '🔥', '✨', '💡', '📱',
        '⚽', '🏀', '🏐', '🚗', '🛵', '✈️', '🏥', '🏫',
        '🛕', '⛪', '🌳', '🌻', '🐔', '🐄', '🐟', '🌊',
        '👨‍👩‍👧', '👶', '🧑‍🌾', '💼', '🏪', '🛒', '💰', '🎁'
    );
}

function is_category_emoji_icon($icon)
{
    if ($icon == '' || $icon == null) {
        return false;
    }
    return strpos($icon, 'emoji:') === 0;
}

function category_icon_emoji_char($icon)
{
    if (!is_category_emoji_icon($icon)) {
        return '';
    }
    return mb_substr($icon, 6);
}

function category_emoji_is_valid($text)
{
    if ($text == '' || $text == null) {
        return false;
    }
    if (mb_strlen($text) > 16) {
        return false;
    }
    if (strpos($text, 'fa-') === 0) {
        return false;
    }
    return true;
}

function normalize_category_icon($icon)
{
    $icon = trim($icon);
    $icons = category_icon_options();

    if (array_key_exists($icon, $icons)) {
        return $icon;
    }

    if (strpos($icon, 'emoji:') === 0) {
        $emoji = mb_substr($icon, 6);
        if (category_emoji_is_valid($emoji)) {
            return 'emoji:' . $emoji;
        }
    }

    if (category_emoji_is_valid($icon)) {
        return 'emoji:' . $icon;
    }

    return 'fa-tag';
}

function is_valid_category_icon($icon)
{
    $normalized = normalize_category_icon($icon);
    if ($normalized == 'fa-tag' && trim($icon) != '' && trim($icon) != 'fa-tag') {
        if (!array_key_exists(trim($icon), category_icon_options()) && !is_category_emoji_icon(trim($icon)) && strpos(trim($icon), 'emoji:') !== 0) {
            return false;
        }
    }
    return true;
}

function category_icon_label($icon)
{
    $icons = category_icon_options();
    if (array_key_exists($icon, $icons)) {
        return $icons[$icon];
    }
    if (is_category_emoji_icon($icon)) {
        return 'Emoji ' . category_icon_emoji_char($icon);
    }
    return 'Icon';
}

function render_category_icon($icon, $extra_class)
{
    if ($icon == '' || $icon == null) {
        $icon = 'fa-tag';
    }

    if (is_category_emoji_icon($icon)) {
        $emoji = category_icon_emoji_char($icon);
        $class = 'cat-emoji-icon';
        if ($extra_class != '') {
            $class = $class . ' ' . $extra_class;
        }
        return '<span class="' . $class . '" aria-hidden="true">' . htmlspecialchars($emoji) . '</span>';
    }

    $class = 'fa-solid ' . htmlspecialchars($icon);
    if ($extra_class != '') {
        $class = $class . ' ' . htmlspecialchars($extra_class);
    }
    return '<i class="' . $class . '"></i>';
}

function create_user_category($pdo, $name, $description, $icon, $user_id)
{
    $result = array('ok' => false, 'error' => 'Could not create category');

    $name = trim($name);
    $description = trim($description);
    $icon = normalize_category_icon($icon);

    if ($name == '') {
        $result['error'] = 'Category name is required.';
        return $result;
    }
    if (strlen($name) > 100) {
        $result['error'] = 'Category name is too long.';
        return $result;
    }
    if ($description == '') {
        $result['error'] = 'Category description is required.';
        return $result;
    }

    $sql = 'SELECT COUNT(*) FROM categories WHERE LOWER(name) = LOWER(:name)';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array('name' => $name));
    if ($stmt->fetchColumn() > 0) {
        $result['error'] = 'This category name already exists.';
        return $result;
    }

    $slug = slugify($name);
    $sql = 'SELECT COUNT(*) FROM categories WHERE slug = :slug';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array('slug' => $slug));
    if ($stmt->fetchColumn() > 0) {
        $slug = $slug . '-' . time();
    }

    try {
        $sql = 'INSERT INTO categories (name, slug, description, icon, created_by) VALUES (:name, :slug, :description, :icon, :created_by) RETURNING id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array(
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'icon' => $icon,
            'created_by' => $user_id
        ));
        $new_id = $stmt->fetchColumn();

        $result['ok'] = true;
        $result['id'] = (int) $new_id;
        return $result;
    } catch (PDOException $e) {
        $result['error'] = 'Could not create category. Please try again.';
        return $result;
    }
}

function delete_user_account($pdo, $user_id)
{
    $user = get_user_by_id($pdo, $user_id);
    if (!$user) {
        return false;
    }

    if (!can_delete_own_account($pdo, $user)) {
        return false;
    }

    if (isset($user['avatar']) && $user['avatar'] != '') {
        delete_upload($user['avatar'], 'avatars');
    }

    $sql = 'DELETE FROM users WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array('id' => $user_id));

    return true;
}

function site_base_url()
{
    if (defined('APP_URL') && APP_URL != '') {
        return rtrim(APP_URL, '/');
    }

    $scheme = 'http';
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
        $scheme = 'https';
    }

    $host = 'localhost';
    if (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] != '') {
        $host = $_SERVER['HTTP_HOST'];
    }

    return $scheme . '://' . $host;
}

function current_page_url()
{
    $uri = '/';
    if (isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] != '') {
        $uri = $_SERVER['REQUEST_URI'];
    }

    return site_base_url() . $uri;
}

function render_breadcrumb($items, $base_path = '')
{
    if (!is_array($items) || count($items) == 0) {
        return;
    }

    echo '<nav aria-label="breadcrumb" class="mb-4 reveal">';
    echo '<ol class="breadcrumb">';

    $last_index = count($items) - 1;
    for ($i = 0; $i < count($items); $i++) {
        $item = $items[$i];
        $label = isset($item['label']) ? $item['label'] : '';
        $url = isset($item['url']) ? $item['url'] : '';

        if ($i < $last_index && $url != '') {
            echo '<li class="breadcrumb-item"><a href="' . htmlspecialchars($base_path . $url) . '" class="text-secondary text-decoration-none">' . htmlspecialchars($label) . '</a></li>';
        } else {
            echo '<li class="breadcrumb-item active text-white">' . htmlspecialchars($label) . '</li>';
        }
    }

    echo '</ol></nav>';
}

function create_post_url($base_path = '')
{
    if (isLoggedIn()) {
        return $base_path . 'admin/posts.php?action=add';
    }

    return $base_path . 'register.php';
}

function post_url($slug, $base_path = '')
{
    return $base_path . 'post/' . rawurlencode($slug);
}

function post_image_alt($post, $fallback = '')
{
    if (isset($post['image_alt']) && trim($post['image_alt']) != '') {
        return trim($post['image_alt']);
    }
    if ($fallback != '') {
        return $fallback;
    }
    if (isset($post['title'])) {
        return $post['title'];
    }
    return SITE_NAME . ' post image';
}

function record_post_view($pdo, $post_id)
{
    if ($post_id <= 0) {
        return false;
    }
    if (!isset($_SESSION['viewed_posts'])) {
        $_SESSION['viewed_posts'] = array();
    }
    $key = 'post_' . (int) $post_id;
    if (isset($_SESSION['viewed_posts'][$key])) {
        return false;
    }
    $_SESSION['viewed_posts'][$key] = time();
    $pdo->prepare('UPDATE posts SET views = views + 1 WHERE id = :id')->execute(array('id' => (int) $post_id));
    return true;
}

function render_post_content($content)
{
    $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
    $content = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $content);
    $content = preg_replace('/\*(.+?)\*/s', '<em>$1</em>', $content);
    $content = preg_replace('/\[(.+?)\]\((https?:\/\/[^\s)]+)\)/', '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>', $content);
    $content = preg_replace('/^### (.+)$/m', '<h5>$1</h5>', $content);
    $content = preg_replace('/^## (.+)$/m', '<h4>$1</h4>', $content);
    $content = preg_replace('/^# (.+)$/m', '<h3>$1</h3>', $content);
    $content = preg_replace('/^\- (.+)$/m', '<li>$1</li>', $content);
    if (strpos($content, '<li>') !== false) {
        $content = preg_replace('/((?:<li>.+<\/li>\s*)+)/s', '<ul>$1</ul>', $content);
    }
    return nl2br($content);
}

function render_json_ld_article($post, $author_name, $canonical_url)
{
    $image = '';
    if (!empty($post['image_url']) && file_exists(PUBLIC_PATH . '/uploads/' . $post['image_url'])) {
        $image = site_base_url() . '/' . media_url($post['image_url'], '');
    }
    $data = array(
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $post['title'],
        'description' => isset($post['summary']) ? $post['summary'] : '',
        'datePublished' => date('c', strtotime($post['created_at'])),
        'author' => array('@type' => 'Person', 'name' => $author_name),
        'mainEntityOfPage' => $canonical_url
    );
    if ($image != '') {
        $data['image'] = array($image);
    }
    if (isset($post['updated_at']) && $post['updated_at'] != '') {
        $data['dateModified'] = date('c', strtotime($post['updated_at']));
    }
    return '<script type="application/ld+json">' . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
}
