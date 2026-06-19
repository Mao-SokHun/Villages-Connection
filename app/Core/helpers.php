<?php

function app_cache_dir()
{
    if (getenv('VERCEL') === '1' || getenv('VERCEL_ENV') !== false) {
        $dir = sys_get_temp_dir() . '/vc_cache';
    } else {
        $dir = STORAGE_PATH . '/cache';
    }
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir;
}

function app_cache_get($key, $ttl_seconds)
{
    $path = app_cache_dir() . '/' . preg_replace('/[^a-z0-9_-]/i', '_', $key) . '.json';
    if (!is_file($path)) {
        return null;
    }
    if ((time() - filemtime($path)) > $ttl_seconds) {
        return null;
    }
    $data = json_decode(file_get_contents($path), true);
    if (!is_array($data)) {
        return null;
    }
    return $data;
}

function app_cache_put($key, $data)
{
    $path = app_cache_dir() . '/' . preg_replace('/[^a-z0-9_-]/i', '_', $key) . '.json';
    file_put_contents($path, json_encode($data));
}

function app_cache_forget($key)
{
    $path = app_cache_dir() . '/' . preg_replace('/[^a-z0-9_-]/i', '_', $key) . '.json';
    if (is_file($path)) {
        @unlink($path);
    }
}

function user_alert_heading($type)
{
    if ($type == 'success') {
        return 'Success';
    }
    if ($type == 'danger') {
        return 'Unable to continue';
    }
    if ($type == 'warning') {
        return 'Please check';
    }
    return 'Notice';
}

function render_user_alerts($messages, $type = 'danger')
{
    if (!is_array($messages)) {
        $messages = array($messages);
    }

    $items = array();
    foreach ($messages as $message) {
        $message = trim((string) $message);
        if ($message != '') {
            $items[] = $message;
        }
    }

    if (count($items) == 0) {
        return;
    }

    if ($type != 'success' && $type != 'danger' && $type != 'warning' && $type != 'info') {
        $type = 'danger';
    }

    $icon = function_exists('alert_icon_class') ? alert_icon_class($type) : 'fa-circle-info';
    $heading = user_alert_heading($type);

    echo '<div class="alert alert-' . htmlspecialchars($type) . ' user-alert" role="alert">';
    echo '<span class="alert-icon" aria-hidden="true"><i class="fa-solid ' . htmlspecialchars($icon) . '"></i></span>';
    echo '<div class="alert-body">';
    echo '<div class="user-alert-title">' . htmlspecialchars($heading) . '</div>';

    if (count($items) == 1) {
        echo '<p class="user-alert-text">' . htmlspecialchars($items[0]) . '</p>';
    } else {
        echo '<ul class="user-alert-list">';
        foreach ($items as $item) {
            echo '<li>' . htmlspecialchars($item) . '</li>';
        }
        echo '</ul>';
    }

    echo '</div></div>';
}

function is_remote_media_url($value)
{
    if ($value == '' || $value == null) {
        return false;
    }

    return strpos($value, 'http://') === 0 || strpos($value, 'https://') === 0;
}

function is_cloudinary_url($url)
{
    return is_remote_media_url($url) && strpos($url, 'res.cloudinary.com') !== false;
}

function ensure_upload_directories()
{
    upload_path('');
    upload_path('avatars');
    upload_path('videos');
}

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

    if (is_remote_media_url($file)) {
        return $file;
    }

    if ($subdir != '') {
        return 'uploads/' . trim($subdir, '/') . '/' . $file;
    }

    return 'uploads/' . $file;
}

function public_asset_url($path)
{
    if ($path == '' || $path == null) {
        return '';
    }

    if (is_remote_media_url($path)) {
        return $path;
    }

    return '/' . ltrim($path, '/');
}

function site_logo_url()
{
    return public_asset_url('icons/logo.png');
}

function site_logo_light_url()
{
    return public_asset_url('icons/logo-light.png');
}

function site_logo_full_url()
{
    return public_asset_url('icons/logo-full.png');
}

function site_logo_alt()
{
    return SITE_NAME . ' — ' . SITE_TAGLINE;
}

function render_code_logo($variant = 'nav', $extra_class = '')
{
    $allowed = array('nav', 'footer', 'auth');
    if (!in_array($variant, $allowed, true)) {
        $variant = 'nav';
    }

    $classes = 'brand-mark brand-mark--' . $variant;
    if ($extra_class != '') {
        $classes = $classes . ' ' . trim($extra_class);
    }

    $label = htmlspecialchars(site_logo_alt(), ENT_QUOTES, 'UTF-8');
    $cx = 178;
    $cy = 38;
    $outer_points = array();
    $inner_points = array();

    for ($i = 0; $i < 8; $i++) {
        $angle = deg2rad(-90 + ($i * 45));
        $outer_points[] = array(
            round($cx + (30 * cos($angle)), 1),
            round($cy + (30 * sin($angle)), 1),
        );
    }

    for ($i = 0; $i < 6; $i++) {
        $angle = deg2rad(-90 + ($i * 60));
        $inner_points[] = array(
            round($cx + (16 * cos($angle)), 1),
            round($cy + (16 * sin($angle)), 1),
        );
    }

    $html = '<span class="' . htmlspecialchars($classes, ENT_QUOTES, 'UTF-8') . '" role="img" aria-label="' . $label . '">';
    $html .= '<svg class="brand-mark-svg vc-logo" viewBox="0 0 300 72" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">';

    $html .= '<g class="vc-logo-network" aria-hidden="true">';
    $html .= '<circle class="vc-logo-ring" cx="' . $cx . '" cy="' . $cy . '" r="32"></circle>';
    $html .= '<circle class="vc-logo-ring vc-logo-ring--inner" cx="' . $cx . '" cy="' . $cy . '" r="20"></circle>';

    foreach ($outer_points as $point) {
        $html .= '<line class="vc-logo-link" x1="' . $cx . '" y1="' . $cy . '" x2="' . $point[0] . '" y2="' . $point[1] . '"></line>';
    }

    for ($i = 0; $i < 8; $i++) {
        $next = ($i + 1) % 8;
        $html .= '<line class="vc-logo-link vc-logo-link--outer" x1="' . $outer_points[$i][0] . '" y1="' . $outer_points[$i][1] . '" x2="' . $outer_points[$next][0] . '" y2="' . $outer_points[$next][1] . '"></line>';
    }

    foreach ($inner_points as $point) {
        $html .= '<line class="vc-logo-link vc-logo-link--inner" x1="' . $cx . '" y1="' . $cy . '" x2="' . $point[0] . '" y2="' . $point[1] . '"></line>';
    }

    for ($i = 0; $i < 6; $i++) {
        $next = ($i + 1) % 6;
        $html .= '<line class="vc-logo-link vc-logo-link--inner" x1="' . $inner_points[$i][0] . '" y1="' . $inner_points[$i][1] . '" x2="' . $inner_points[$next][0] . '" y2="' . $inner_points[$next][1] . '"></line>';
    }

    foreach ($inner_points as $point) {
        $html .= '<circle class="vc-logo-node vc-logo-node--mini" cx="' . $point[0] . '" cy="' . $point[1] . '" r="2.4"></circle>';
    }

    foreach ($outer_points as $point) {
        $html .= '<circle class="vc-logo-node vc-logo-node--outer" cx="' . $point[0] . '" cy="' . $point[1] . '" r="4.2"></circle>';
    }

    $html .= '<circle class="vc-logo-node vc-logo-node--core" cx="' . $cx . '" cy="' . $cy . '" r="7.5"></circle>';
    $html .= '</g>';

    $html .= '<text class="vc-logo-text" fill="currentColor" x="6" y="54">villages</text>';

    if ($variant !== 'nav') {
        $html .= '<text class="vc-logo-tagline" fill="currentColor" x="292" y="66" text-anchor="end">connection</text>';
    }

    $html .= '</svg></span>';

    return $html;
}

function resolve_media_src($file, $subdir = '')
{
    if ($file == '' || $file == null) {
        return '';
    }

    if (is_remote_media_url($file)) {
        return $file;
    }

    return public_asset_url(media_url($file, $subdir));
}

function post_media_available($stored_value, $subdir = '')
{
    if ($stored_value == '' || $stored_value == null) {
        return false;
    }

    if (is_remote_media_url($stored_value)) {
        return true;
    }

    if ($subdir == 'videos') {
        return is_file(upload_path('videos') . $stored_value);
    }

    return is_file(upload_path('') . $stored_value);
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

function ensure_cloudinary_loaded()
{
    static $loaded = false;
    if (!$loaded) {
        require_once APP_PATH . '/Core/cloudinary.php';
        $loaded = true;
    }
}

function delete_upload($filename, $subdir)
{
    if ($filename == '' || $filename == null) {
        return;
    }

    if (strpos($filename, 'res.cloudinary.com') !== false) {
        ensure_cloudinary_loaded();
        $resource_type = 'image';
        if ($subdir == 'videos' || strpos($filename, '/video/upload/') !== false) {
            $resource_type = 'video';
        }
        cloudinary_destroy($filename, $resource_type);
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

    $begin = upload_begin_handler($file, $existing, 'Image upload failed');
    if ($begin !== null) {
        return $begin;
    }

    $check = validate_uploaded_image_file($file, upload_max_image_bytes(), 'Image');
    if ($check['ok'] == false) {
        if (isset($check['skipped']) && $check['skipped'] === true) {
            $result['ok'] = true;
            $result['filename'] = $existing;
            return $result;
        }
        $result['error'] = $check['error'];
        return $result;
    }

    $ext = $check['extension'];
    $name = upload_secure_filename($ext);

    ensure_cloudinary_loaded();
    if (cloudinary_enabled()) {
        $uploaded = cloudinary_upload_file($file['tmp_name'], 'image', 'posts');
        if ($uploaded['ok'] == true) {
            if ($existing != '') {
                delete_upload($existing, '');
            }
            $result['ok'] = true;
            $result['filename'] = $uploaded['filename'];
            return $result;
        }
        if (function_exists('app_log_error')) {
            app_log_error('Cloudinary image upload failed, using local disk: ' . $uploaded['error']);
        }
    }

    if (move_uploaded_file($file['tmp_name'], upload_path('') . $name)) {
        if ($existing != '') {
            delete_upload($existing, '');
        }

        $result['ok'] = true;
        $result['filename'] = $name;
        return $result;
    }

    $result['error'] = 'Could not save uploaded image. Check server upload folder permissions.';
    return $result;
}

function handle_video_upload($file, $existing)
{
    $result = array('ok' => false, 'error' => 'Video upload failed');

    $begin = upload_begin_handler($file, $existing, 'Video upload failed');
    if ($begin !== null) {
        return $begin;
    }

    $check = validate_uploaded_video_file($file, upload_max_video_bytes(), 'Video');
    if ($check['ok'] == false) {
        if (isset($check['skipped']) && $check['skipped'] === true) {
            $result['ok'] = true;
            $result['filename'] = $existing;
            return $result;
        }
        $result['error'] = $check['error'];
        return $result;
    }

    $ext = $check['extension'];
    $name = upload_secure_filename($ext);

    ensure_cloudinary_loaded();
    if (cloudinary_enabled()) {
        $uploaded = cloudinary_upload_file($file['tmp_name'], 'video', 'videos');
        if ($uploaded['ok'] == true) {
            if ($existing != '') {
                delete_upload($existing, 'videos');
            }
            $result['ok'] = true;
            $result['filename'] = $uploaded['filename'];
            return $result;
        }
        if (function_exists('app_log_error')) {
            app_log_error('Cloudinary video upload failed, using local disk: ' . $uploaded['error']);
        }
    }

    if (move_uploaded_file($file['tmp_name'], upload_path('videos') . $name)) {
        if ($existing != '') {
            delete_upload($existing, 'videos');
        }

        $result['ok'] = true;
        $result['filename'] = $name;
        return $result;
    }

    $result['error'] = 'Could not save uploaded video. Check server upload folder permissions.';
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

function youtube_video_id($url)
{
    if ($url == '') {
        return '';
    }

    if (preg_match('/youtu\.be\/([^\?&]+)/', $url, $m)) {
        return $m[1];
    }
    if (preg_match('/[?&]v=([^&]+)/', $url, $m)) {
        return $m[1];
    }
    if (preg_match('/embed\/([^?&]+)/', $url, $m)) {
        return $m[1];
    }

    return '';
}

function youtube_embed_url($url)
{
    $id = youtube_video_id($url);

    if ($id == '') {
        return '';
    }

    return 'https://www.youtube.com/embed/' . $id;
}

function youtube_thumbnail_url($url)
{
    $id = youtube_video_id($url);

    if ($id == '') {
        return '';
    }

    return 'https://img.youtube.com/vi/' . $id . '/hqdefault.jpg';
}

function post_uploaded_image_exists($image_url)
{
    return post_media_available($image_url, '');
}

function post_card_media($post)
{
    $result = array('url' => '', 'external' => false, 'alt' => '');

    if (!empty($post['image_url']) && post_media_available($post['image_url'], '')) {
        $result['url'] = resolve_media_src($post['image_url'], '');
        $result['alt'] = post_image_alt($post, isset($post['title']) ? $post['title'] : '');
        return $result;
    }

    if (isset($post['video_type']) && $post['video_type'] == 'youtube' && !empty($post['video_url'])) {
        $thumb = youtube_thumbnail_url($post['video_url']);
        if ($thumb != '') {
            $result['url'] = $thumb;
            $result['external'] = true;
            $result['alt'] = isset($post['title']) ? $post['title'] : 'Video thumbnail';
            return $result;
        }
    }

    return $result;
}

function render_sidebar_post_thumb($post)
{
    $media = post_card_media($post);
    if ($media['url'] != '') {
        return '<img src="' . htmlspecialchars($media['url']) . '" alt="' . htmlspecialchars($media['alt']) . '" class="sidebar-post-thumb" loading="lazy">';
    }

    $icon = 'fa-image';
    if (isset($post['category_icon']) && $post['category_icon'] != '') {
        $icon = $post['category_icon'];
    }

    return '<div class="sidebar-post-thumb sidebar-post-thumb-placeholder">' . render_category_icon($icon, '') . '</div>';
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

    $begin = upload_begin_handler($file, $existing, 'Avatar upload failed');
    if ($begin !== null) {
        return $begin;
    }

    $check = validate_uploaded_image_file($file, upload_max_avatar_bytes(), 'Avatar');
    if ($check['ok'] == false) {
        if (isset($check['skipped']) && $check['skipped'] === true) {
            $result['ok'] = true;
            $result['filename'] = $existing;
            return $result;
        }
        $result['error'] = $check['error'];
        return $result;
    }

    $ext = $check['extension'];
    $name = upload_secure_filename($ext);

    if (move_uploaded_file($file['tmp_name'], upload_path('avatars') . $name)) {
        if ($existing != '') {
            delete_upload($existing, 'avatars');
        }

        $result['ok'] = true;
        $result['filename'] = $name;
        return $result;
    }

    $result['error'] = 'Could not save uploaded avatar. Check server upload folder permissions.';
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
    $avatar = normalize_avatar_filename($avatar);
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

function normalize_email($email)
{
    return strtolower(trim((string) $email));
}

function gravatar_url($email, $size)
{
    $email = normalize_email($email);
    if ($email == '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return '';
    }

    if (strpos($email, '@oauth.local') !== false) {
        return '';
    }

    $hash = md5($email);
    $size = (int) $size;
    if ($size < 16) {
        $size = 16;
    }
    if ($size > 512) {
        $size = 512;
    }

    return 'https://www.gravatar.com/avatar/' . $hash . '?s=' . $size . '&d=identicon';
}

function resolve_user_avatar_src($avatar, $email, $size)
{
    $avatar = normalize_avatar_filename($avatar);
    if (user_avatar_exists($avatar)) {
        if (is_external_avatar($avatar)) {
            return $avatar;
        }

        return public_asset_url(media_url($avatar, 'avatars'));
    }

    return gravatar_url($email, $size);
}

function is_oauth_user($user)
{
    if (!is_array($user)) {
        return false;
    }
    if (!isset($user['oauth_provider'])) {
        return false;
    }

    $provider = trim($user['oauth_provider']);
    return $provider != '' && $provider != 'local';
}

function is_placeholder_oauth_email($email)
{
    $email = strtolower(trim((string) $email));
    if ($email == '') {
        return true;
    }

    if (strpos($email, '@oauth.local') !== false) {
        return true;
    }

    if (preg_match('/^(facebook|google)_[0-9]+$/', $email)) {
        return true;
    }

    return false;
}

function infer_oauth_provider_from_email($email)
{
    $email = strtolower(trim((string) $email));
    if ($email == '') {
        return '';
    }

    if (strpos($email, 'facebook_') === 0) {
        return 'facebook';
    }

    if (strpos($email, 'google_') === 0) {
        return 'google';
    }

    return '';
}

function resolve_oauth_provider($user)
{
    if (!is_array($user)) {
        return '';
    }

    if (isset($user['oauth_provider'])) {
        $provider = strtolower(trim((string) $user['oauth_provider']));
        if ($provider != '' && $provider != 'local') {
            return $provider;
        }
    }

    if (isset($user['email'])) {
        return infer_oauth_provider_from_email($user['email']);
    }

    return '';
}

function user_has_managed_email($user)
{
    if (!is_array($user) || !isset($user['email'])) {
        return false;
    }

    return is_placeholder_oauth_email($user['email']);
}

function oauth_provider_label($provider)
{
    $provider = strtolower(trim((string) $provider));
    if ($provider == 'facebook') {
        return 'Facebook';
    }
    if ($provider == 'google') {
        return 'Google';
    }
    if ($provider == '') {
        return 'Social account';
    }

    return ucfirst($provider);
}

function user_public_email($user)
{
    if (!is_array($user)) {
        return '';
    }

    $email = '';
    if (isset($user['email'])) {
        $email = trim($user['email']);
    }

    if ($email == '' || is_placeholder_oauth_email($email)) {
        return '';
    }

    return $email;
}

function user_account_subtitle($user)
{
    if (!is_array($user)) {
        return '';
    }

    $public_email = user_public_email($user);
    if ($public_email != '') {
        return $public_email;
    }

    if (is_oauth_user($user) || user_has_managed_email($user)) {
        $provider = resolve_oauth_provider($user);
        if ($provider == '') {
            $provider = 'social';
        }

        return 'Signed in with ' . oauth_provider_label($provider);
    }

    return '';
}

function normalize_avatar_filename($avatar)
{
    $avatar = trim((string) $avatar);
    if ($avatar == '') {
        return '';
    }

    if (is_external_avatar($avatar)) {
        return $avatar;
    }

    if (strpos($avatar, 'uploads/avatars/') !== false) {
        return basename($avatar);
    }

    if (strpos($avatar, 'uploads/') === 0) {
        return basename($avatar);
    }

    return $avatar;
}

function asset_version($relative_path)
{
    $file = PUBLIC_PATH . '/' . ltrim($relative_path, '/');
    if (file_exists($file)) {
        return (string) filemtime($file);
    }

    return '1';
}

function nav_category_list($pdo)
{
    static $cache = null;
    static $cached_at = 0;

    if (is_array($cache) && (time() - $cached_at) < 300) {
        return $cache;
    }

    $file_cached = app_cache_get('nav_categories', 300);
    if (is_array($file_cached) && isset($file_cached['rows']) && is_array($file_cached['rows'])) {
        $cache = $file_cached['rows'];
        $cached_at = time();
        return $cache;
    }

    try {
        $rows = $pdo->query('SELECT name, slug, icon FROM categories ORDER BY name ASC')->fetchAll();
    } catch (Exception $e) {
        // During first boot/migration race, categories table may not exist yet.
        $rows = array();
    }
    $cache = $rows;
    $cached_at = time();
    app_cache_put('nav_categories', array('rows' => $rows));

    return $rows;
}

function clear_nav_category_cache()
{
    app_cache_forget('nav_categories');
}

function site_contact_email()
{
    if (function_exists('get_setting')) {
        $email = trim(get_setting('site_contact_email', SITE_CONTACT_EMAIL));
        if ($email != '') {
            return $email;
        }
    }

    $mail_from = getenv('MAIL_FROM');
    if ($mail_from != false && trim($mail_from) != '') {
        return trim($mail_from);
    }

    return SITE_CONTACT_EMAIL;
}

function site_default_meta_description()
{
    if (function_exists('get_setting')) {
        $desc = trim(get_setting('default_meta_description', SITE_DESC));
        if ($desc != '') {
            return $desc;
        }
    }

    return SITE_DESC;
}

function email_template($key, $defaults, $replacements = array())
{
    $subject = $defaults['subject'];
    $body = $defaults['body'];

    if (function_exists('get_setting')) {
        $saved_subject = trim(get_setting($key . '_subject', ''));
        $saved_body = trim(get_setting($key . '_body', ''));
        if ($saved_subject != '') {
            $subject = $saved_subject;
        }
        if ($saved_body != '') {
            $body = $saved_body;
        }
    }

    foreach ($replacements as $token => $value) {
        $subject = str_replace('{' . $token . '}', $value, $subject);
        $body = str_replace('{' . $token . '}', $value, $body);
    }

    return array('subject' => $subject, 'body' => $body);
}

function render_user_avatar($name, $avatar, $extra_class, $email = '')
{
    $class = 'user-avatar';
    if ($extra_class != '') {
        $class = $class . ' ' . $extra_class;
    }

    $src = resolve_user_avatar_src($avatar, $email, 96);
    if ($src != '') {
        $initials = user_initials($name);
        $html = '<span class="' . $class . ' user-avatar-img-wrap">';
        $html = $html . '<img src="' . htmlspecialchars($src) . '" alt="' . htmlspecialchars($name) . '" loading="lazy" onerror="this.remove();this.parentElement.classList.remove(\'user-avatar-img-wrap\');this.parentElement.textContent=' . json_encode($initials) . ';">';
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
    $_SESSION['oauth_provider'] = 'local';
    if (isset($user['oauth_provider']) && $user['oauth_provider'] != '') {
        $_SESSION['oauth_provider'] = $user['oauth_provider'];
    }
    $_SESSION['account_status'] = user_account_status($user);
    $_SESSION['is_banned'] = user_is_banned($user);
    $_SESSION['ui_theme'] = 'system';
    if (isset($user['ui_theme']) && ($user['ui_theme'] == 'light' || $user['ui_theme'] == 'dark' || $user['ui_theme'] == 'system')) {
        $_SESSION['ui_theme'] = $user['ui_theme'];
    }
    $_SESSION['ui_density'] = 'comfortable';
    if (isset($user['ui_density']) && ($user['ui_density'] == 'comfortable' || $user['ui_density'] == 'compact')) {
        $_SESSION['ui_density'] = $user['ui_density'];
    }

    return true;
}

function archive_expired_posts($pdo)
{
    static $ran = false;
    if ($ran) {
        return;
    }
    $ran = true;

    if (app_cache_get('archive_expired_posts', 300) !== null) {
        return;
    }

    try {
        $sql = "UPDATE posts
                SET status = 'Archived', updated_at = CURRENT_TIMESTAMP
                WHERE status = 'Published'
                  AND archive_on_expiry = TRUE
                  AND expires_at IS NOT NULL
                  AND expires_at <= CURRENT_TIMESTAMP";
        $pdo->exec($sql);
        app_cache_put('archive_expired_posts', array('ran' => time()));
    } catch (Exception $e) {
        // Table/column may not exist before migration.
    }
}

function user_reputation_summary($pdo, $user_id)
{
    $user_id = (int) $user_id;
    if ($user_id <= 0) {
        return array('points' => 0, 'badge' => 'Newcomer', 'trust_rank' => 'Bronze');
    }
    $sql = "SELECT
                COUNT(*) AS post_count,
                COALESCE(SUM(likes), 0) AS total_likes,
                COALESCE(SUM(views), 0) AS total_views
            FROM posts
            WHERE user_id = :uid
              AND status = 'Published'
              AND (expires_at IS NULL OR expires_at > CURRENT_TIMESTAMP)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array('uid' => $user_id));
    $row = $stmt->fetch();
    $posts = $row ? (int) $row['post_count'] : 0;
    $likes = $row ? (int) $row['total_likes'] : 0;
    $views = $row ? (int) $row['total_views'] : 0;
    $followers = follower_count($pdo, $user_id);

    $points = ($posts * 12) + ($likes * 3) + (int) floor($views / 25) + ($followers * 4);
    $badge = 'Newcomer';
    $trust_rank = 'Bronze';
    if ($points >= 1200) {
        $badge = 'Village Champion';
        $trust_rank = 'Platinum';
    } elseif ($points >= 700) {
        $badge = 'Trusted Voice';
        $trust_rank = 'Gold';
    } elseif ($points >= 300) {
        $badge = 'Helpful Contributor';
        $trust_rank = 'Silver';
    }

    return array(
        'points' => $points,
        'badge' => $badge,
        'trust_rank' => $trust_rank
    );
}

function user_post_count($pdo, $user_id)
{
    $sql = "SELECT COUNT(*) FROM posts
            WHERE user_id = :uid
              AND status = 'Published'
              AND (expires_at IS NULL OR expires_at > CURRENT_TIMESTAMP)";
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
    $sql = "SELECT COUNT(*) FROM users WHERE role = 'admin' AND COALESCE(account_status, 'active') != 'deleted'";
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

    if (user_is_deleted($user)) {
        return false;
    }

    $sql = "UPDATE users SET account_status = 'deleted', deleted_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array('id' => (int) $user_id));

    if ($stmt->rowCount() > 0) {
        log_activity($pdo, 'user.deleted', 'User #' . (int) $user_id);
        return true;
    }

    return false;
}

function request_scheme()
{
    $scheme = 'http';
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
        $scheme = 'https';
    }
    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
        $scheme = 'https';
    }
    return $scheme;
}

function site_base_url()
{
    if (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] != '') {
        return request_scheme() . '://' . $_SERVER['HTTP_HOST'];
    }

    if (defined('APP_URL') && APP_URL != '') {
        return rtrim(APP_URL, '/');
    }

    return 'http://localhost:8080';
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

    $now = time();
    $key = 'post_' . (int) $post_id;

    foreach ($_SESSION['viewed_posts'] as $view_key => $viewed_at) {
        if ($now - (int) $viewed_at > 86400) {
            unset($_SESSION['viewed_posts'][$view_key]);
        }
    }

    if (isset($_SESSION['viewed_posts'][$key])) {
        return false;
    }

    $stmt = $pdo->prepare('UPDATE posts SET views = COALESCE(views, 0) + 1 WHERE id = :id');
    $stmt->execute(array('id' => (int) $post_id));
    if ((int) $stmt->rowCount() < 1) {
        return false;
    }

    $_SESSION['viewed_posts'][$key] = $now;
    app_cache_forget('home_feed_meta');

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
    if (!empty($post['image_url']) && post_media_available($post['image_url'], '')) {
        $image = resolve_media_src($post['image_url'], '');
        if (!is_remote_media_url($image)) {
            $image = site_base_url() . $image;
        }
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
