<?php

function upload_max_image_bytes()
{
    return 5 * 1024 * 1024;
}

function upload_max_avatar_bytes()
{
    return 2 * 1024 * 1024;
}

function upload_max_video_bytes()
{
    return 50 * 1024 * 1024;
}

function upload_secure_filename($ext)
{
    $ext = strtolower(preg_replace('/[^a-z0-9]/', '', (string) $ext));
    if ($ext == '') {
        $ext = 'bin';
    }

    return bin2hex(random_bytes(16)) . '_' . time() . '.' . $ext;
}

function upload_php_error_message($code)
{
    switch ((int) $code) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return 'File is too large. Reduce the file size or contact the administrator.';
        case UPLOAD_ERR_PARTIAL:
            return 'Upload was interrupted. Please try again.';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Server upload folder is missing. Contact the administrator.';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Server could not save the uploaded file.';
        case UPLOAD_ERR_EXTENSION:
            return 'Upload blocked by a server extension.';
        default:
            return 'Upload failed. Please try again.';
    }
}

function upload_file_selected($file)
{
    if (!isset($file['error'])) {
        return false;
    }

    return (int) $file['error'] !== UPLOAD_ERR_NO_FILE;
}

function upload_begin_handler($file, $existing, $default_error = 'Upload failed')
{
    if (!isset($file['error'])) {
        return array('ok' => false, 'error' => $default_error);
    }

    if ((int) $file['error'] === UPLOAD_ERR_NO_FILE) {
        return array('ok' => true, 'filename' => $existing, 'skipped' => true);
    }

    if ((int) $file['error'] !== UPLOAD_ERR_OK) {
        return array('ok' => false, 'error' => upload_php_error_message($file['error']));
    }

    return null;
}

function upload_allowed_image_extensions()
{
    return array('jpg', 'jpeg', 'png', 'webp', 'gif');
}

function upload_allowed_image_mimes()
{
    return array(
        'jpg' => array('image/jpeg'),
        'jpeg' => array('image/jpeg'),
        'png' => array('image/png'),
        'webp' => array('image/webp'),
        'gif' => array('image/gif'),
    );
}

function upload_allowed_video_extensions()
{
    return array('mp4', 'webm', 'mov');
}

function upload_allowed_video_mimes()
{
    return array(
        'mp4' => array('video/mp4'),
        'webm' => array('video/webm'),
        'mov' => array('video/quicktime', 'video/mp4'),
    );
}

function upload_detect_mime($path)
{
    if (!is_file($path) || !is_readable($path)) {
        return '';
    }

    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mime = finfo_file($finfo, $path);
            finfo_close($finfo);
            if (is_string($mime) && $mime != '') {
                return strtolower($mime);
            }
        }
    }

    if (function_exists('mime_content_type')) {
        $mime = mime_content_type($path);
        if (is_string($mime) && $mime != '') {
            return strtolower($mime);
        }
    }

    return '';
}

function upload_mime_matches_extension($ext, $mime, $mime_map)
{
    $ext = strtolower($ext);
    if (!isset($mime_map[$ext])) {
        return false;
    }

    foreach ($mime_map[$ext] as $allowed) {
        if ($mime === strtolower($allowed)) {
            return true;
        }
    }

    return false;
}

function upload_is_real_image($path)
{
    if (!is_file($path)) {
        return false;
    }

    $info = @getimagesize($path);
    if ($info === false || !isset($info[0]) || !isset($info[1])) {
        return false;
    }

    if ((int) $info[0] < 1 || (int) $info[1] < 1) {
        return false;
    }

    $allowed_types = array(IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF);
    if (!in_array((int) $info[2], $allowed_types, true)) {
        return false;
    }

    return true;
}

function validate_uploaded_image_file($file, $max_bytes, $label = 'Image')
{
    $result = array('ok' => false, 'error' => $label . ' upload failed');

    if (!isset($file['error']) || $file['error'] == UPLOAD_ERR_NO_FILE) {
        $result['ok'] = true;
        $result['skipped'] = true;
        return $result;
    }

    if ($file['error'] != UPLOAD_ERR_OK) {
        $result['error'] = upload_php_error_message($file['error']);
        return $result;
    }

    if (!isset($file['tmp_name']) || !is_file($file['tmp_name'])) {
        $result['error'] = 'Invalid upload payload';
        return $result;
    }

    if (PHP_SAPI !== 'cli' && !is_uploaded_file($file['tmp_name'])) {
        $result['error'] = 'Invalid upload payload';
        return $result;
    }

    if ((int) $file['size'] < 1) {
        $result['error'] = $label . ' file is empty';
        return $result;
    }

    if ((int) $file['size'] > (int) $max_bytes) {
        $result['error'] = $label . ' cannot exceed ' . (int) floor($max_bytes / (1024 * 1024)) . 'MB';
        return $result;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, upload_allowed_image_extensions(), true)) {
        $result['error'] = $label . ' must be JPG, PNG, WEBP, or GIF';
        return $result;
    }

    $mime = upload_detect_mime($file['tmp_name']);
    if ($mime == '' || !upload_mime_matches_extension($ext, $mime, upload_allowed_image_mimes())) {
        $result['error'] = $label . ' file type is not allowed';
        return $result;
    }

    if (!upload_is_real_image($file['tmp_name'])) {
        $result['error'] = $label . ' must be a valid image file';
        return $result;
    }

    $result['ok'] = true;
    $result['extension'] = $ext;
    return $result;
}

function validate_uploaded_video_file($file, $max_bytes, $label = 'Video')
{
    $result = array('ok' => false, 'error' => $label . ' upload failed');

    if (!isset($file['error']) || $file['error'] == UPLOAD_ERR_NO_FILE) {
        $result['ok'] = true;
        $result['skipped'] = true;
        return $result;
    }

    if ($file['error'] != UPLOAD_ERR_OK) {
        $result['error'] = upload_php_error_message($file['error']);
        return $result;
    }

    if (!isset($file['tmp_name']) || !is_file($file['tmp_name'])) {
        $result['error'] = 'Invalid upload payload';
        return $result;
    }

    if (PHP_SAPI !== 'cli' && !is_uploaded_file($file['tmp_name'])) {
        $result['error'] = 'Invalid upload payload';
        return $result;
    }

    if ((int) $file['size'] < 1) {
        $result['error'] = $label . ' file is empty';
        return $result;
    }

    if ((int) $file['size'] > (int) $max_bytes) {
        $result['error'] = $label . ' cannot exceed ' . (int) floor($max_bytes / (1024 * 1024)) . 'MB';
        return $result;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, upload_allowed_video_extensions(), true)) {
        $result['error'] = $label . ' must be MP4, WEBM, or MOV';
        return $result;
    }

    $mime = upload_detect_mime($file['tmp_name']);
    if ($mime == '' || !upload_mime_matches_extension($ext, $mime, upload_allowed_video_mimes())) {
        $result['error'] = $label . ' file type is not allowed';
        return $result;
    }

    $result['ok'] = true;
    $result['extension'] = $ext;
    return $result;
}
