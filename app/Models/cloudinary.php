<?php

function cloudinary_bootstrap_env()
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $url = getenv('CLOUDINARY_URL');
    if ($url == false || trim($url) == '') {
        return;
    }

    if (preg_match('#^cloudinary://([^:]+):([^@]+)@([^/?]+)#', trim($url), $m)) {
        if (!getenv('CLOUDINARY_API_KEY') || getenv('CLOUDINARY_API_KEY') == '') {
            putenv('CLOUDINARY_API_KEY=' . $m[1]);
            $_ENV['CLOUDINARY_API_KEY'] = $m[1];
            $_SERVER['CLOUDINARY_API_KEY'] = $m[1];
        }
        if (!getenv('CLOUDINARY_API_SECRET') || getenv('CLOUDINARY_API_SECRET') == '') {
            putenv('CLOUDINARY_API_SECRET=' . $m[2]);
            $_ENV['CLOUDINARY_API_SECRET'] = $m[2];
            $_SERVER['CLOUDINARY_API_SECRET'] = $m[2];
        }
        if (!getenv('CLOUDINARY_CLOUD_NAME') || getenv('CLOUDINARY_CLOUD_NAME') == '') {
            putenv('CLOUDINARY_CLOUD_NAME=' . $m[3]);
            $_ENV['CLOUDINARY_CLOUD_NAME'] = $m[3];
            $_SERVER['CLOUDINARY_CLOUD_NAME'] = $m[3];
        }
    }
}

cloudinary_bootstrap_env();

function cloudinary_enabled()
{
    $cloud = getenv('CLOUDINARY_CLOUD_NAME');
    $key = getenv('CLOUDINARY_API_KEY');
    $secret = getenv('CLOUDINARY_API_SECRET');

    return $cloud != false && $cloud != '' && $key != false && $key != '' && $secret != false && $secret != '';
}

function cloudinary_folder()
{
    $folder = getenv('CLOUDINARY_FOLDER');
    if ($folder == false || trim($folder) == '') {
        return 'village-connect';
    }

    return trim($folder, '/');
}

function cloudinary_sign($params, $api_secret)
{
    ksort($params);
    $parts = array();
    foreach ($params as $key => $value) {
        if ($key == 'file' || $key == 'resource_type' || $key == 'api_key') {
            continue;
        }
        if (is_array($value)) {
            $value = implode(',', $value);
        }
        $parts[] = $key . '=' . $value;
    }

    return sha1(implode('&', $parts) . $api_secret);
}

function cloudinary_public_id_from_url($url, $resource_type = 'image')
{
    if (!is_cloudinary_url($url)) {
        return '';
    }

    $needle = '/' . $resource_type . '/upload/';
    $pos = strpos($url, $needle);
    if ($pos === false) {
        return '';
    }

    $rest = substr($url, $pos + strlen($needle));
    if (preg_match('/^v\d+\/(.+)$/', $rest, $m)) {
        $rest = $m[1];
    }

    if (strpos($rest, '?') !== false) {
        $rest = strstr($rest, '?', true);
    }

    return rawurldecode($rest);
}

function cloudinary_upload_file($tmp_path, $resource_type, $subdir = 'posts')
{
    if (!cloudinary_enabled()) {
        return array('ok' => false, 'error' => 'Cloudinary is not configured.');
    }

    if (!is_file($tmp_path)) {
        return array('ok' => false, 'error' => 'Upload file not found.');
    }

    $cloud = getenv('CLOUDINARY_CLOUD_NAME');
    $api_key = getenv('CLOUDINARY_API_KEY');
    $api_secret = getenv('CLOUDINARY_API_SECRET');
    $folder = cloudinary_folder();
    if ($subdir != '') {
        $folder = $folder . '/' . trim($subdir, '/');
    }

    $timestamp = time();
    $sign_params = array(
        'folder' => $folder,
        'timestamp' => (string) $timestamp,
    );
    $signature = cloudinary_sign($sign_params, $api_secret);

    $post_fields = array(
        'file' => new CURLFile($tmp_path),
        'api_key' => $api_key,
        'timestamp' => $timestamp,
        'folder' => $folder,
        'signature' => $signature,
    );

    $endpoint = 'https://api.cloudinary.com/v1_1/' . rawurlencode($cloud) . '/' . $resource_type . '/upload';
    $response = cloudinary_http_post($endpoint, $post_fields);
    if ($response['ok'] == false) {
        return $response;
    }

    $data = $response['data'];
    if (!isset($data['secure_url']) || $data['secure_url'] == '') {
        return array('ok' => false, 'error' => 'Cloudinary upload did not return a URL.');
    }

    return array(
        'ok' => true,
        'url' => $data['secure_url'],
        'public_id' => isset($data['public_id']) ? $data['public_id'] : '',
        'filename' => $data['secure_url'],
    );
}

function cloudinary_destroy($url, $resource_type = 'image')
{
    if (!cloudinary_enabled() || !is_cloudinary_url($url)) {
        return array('ok' => false, 'error' => 'Not a Cloudinary asset.');
    }

    $public_id = cloudinary_public_id_from_url($url, $resource_type);
    if ($public_id == '') {
        return array('ok' => false, 'error' => 'Could not resolve Cloudinary public ID.');
    }

    $cloud = getenv('CLOUDINARY_CLOUD_NAME');
    $api_key = getenv('CLOUDINARY_API_KEY');
    $api_secret = getenv('CLOUDINARY_API_SECRET');
    $timestamp = time();
    $sign_params = array(
        'public_id' => $public_id,
        'timestamp' => (string) $timestamp,
    );
    $signature = cloudinary_sign($sign_params, $api_secret);

    $endpoint = 'https://api.cloudinary.com/v1_1/' . rawurlencode($cloud) . '/' . $resource_type . '/destroy';
    $post_fields = array(
        'public_id' => $public_id,
        'api_key' => $api_key,
        'timestamp' => $timestamp,
        'signature' => $signature,
    );

    return cloudinary_http_post($endpoint, $post_fields);
}

function cloudinary_http_post($url, $post_fields)
{
    if (!function_exists('curl_init')) {
        return array('ok' => false, 'error' => 'cURL is required for Cloudinary uploads.');
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 600);

    $body = curl_exec($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($errno != 0) {
        return array('ok' => false, 'error' => 'Cloudinary request failed: ' . $error);
    }

    $data = json_decode($body, true);
    if (!is_array($data)) {
        return array('ok' => false, 'error' => 'Invalid Cloudinary response.');
    }

    if ($status >= 400 || (isset($data['error']) && is_array($data['error']))) {
        $message = 'Cloudinary upload failed.';
        if (isset($data['error']['message'])) {
            $message = $data['error']['message'];
        }
        return array('ok' => false, 'error' => $message);
    }

    return array('ok' => true, 'data' => $data);
}

function cloudinary_delete_media($stored_value, $subdir = '')
{
    if ($stored_value == '' || $stored_value == null) {
        return;
    }

    if (is_cloudinary_url($stored_value)) {
        $resource_type = 'image';
        if ($subdir == 'videos' || strpos($stored_value, '/video/upload/') !== false) {
            $resource_type = 'video';
        }
        cloudinary_destroy($stored_value, $resource_type);
        return;
    }

    delete_upload($stored_value, $subdir);
}
