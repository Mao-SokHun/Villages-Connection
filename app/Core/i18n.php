<?php

function supported_locales()
{
    return array('en', 'km');
}

function init_locale()
{
    if (isset($_GET['lang'])) {
        $lang = trim($_GET['lang']);
        if (in_array($lang, supported_locales(), true)) {
            $_SESSION['locale'] = $lang;
            $secure = function_exists('request_is_https') ? request_is_https() : false;
            setcookie('vc_locale', $lang, array(
                'expires' => time() + (86400 * 365),
                'path' => '/',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Lax',
            ));
        }
    }

    if (!isset($_SESSION['locale']) || $_SESSION['locale'] == '') {
        if (isset($_COOKIE['vc_locale']) && in_array($_COOKIE['vc_locale'], supported_locales(), true)) {
            $_SESSION['locale'] = $_COOKIE['vc_locale'];
        } else {
            $_SESSION['locale'] = 'en';
        }
    }
}

function current_locale()
{
    if (isset($_SESSION['locale']) && in_array($_SESSION['locale'], supported_locales(), true)) {
        return $_SESSION['locale'];
    }
    return 'en';
}

function locale_label($locale)
{
    if ($locale == 'km') {
        return __('lang.km');
    }
    if ($locale == 'en') {
        return __('lang.en');
    }
    return __('lang.en');
}

function locale_short_label($locale)
{
    if ($locale == 'km') {
        return __('lang.km_short');
    }
    return __('lang.en_short');
}

function language_switch_redirect_path()
{
    $path = request_uri_path();
    $query = '';
    if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] != '') {
        $query = '?' . $_SERVER['QUERY_STRING'];
    }

    foreach (route_registry_public() as $script => $pretty) {
        if ($path === $pretty) {
            return $script . $query;
        }
    }

    foreach (route_registry_admin() as $script => $pretty) {
        if ($path === $pretty) {
            return 'admin/' . $script . $query;
        }
    }

    if (preg_match('#^/post/([^/]+)/?$#', $path, $matches)) {
        $redirect = 'post.php?slug=' . rawurlencode(rawurldecode($matches[1]));
        if ($query != '') {
            $redirect .= '&' . ltrim($query, '?');
        }
        return $redirect;
    }

    if (preg_match('#^/profile/([0-9]+)/?$#', $path, $matches)) {
        $redirect = 'profile.php?id=' . (int) $matches[1];
        if ($query != '') {
            $redirect .= '&' . ltrim($query, '?');
        }
        return $redirect;
    }

    if ($path === '/') {
        return 'index.php' . $query;
    }

    $fallback = ltrim($path, '/') . $query;
    if ($fallback == '' || $fallback == '/') {
        return 'index.php';
    }

    return $fallback;
}

function language_switch_url($locale)
{
    $locale = trim((string) $locale);
    if (!in_array($locale, supported_locales(), true)) {
        $locale = 'en';
    }

    $redirect = language_switch_redirect_path();
    return app_url('set-language.php') . '?lang=' . urlencode($locale) . '&redirect=' . urlencode($redirect);
}

function load_translations($locale)
{
    static $cache = array();

    if (isset($cache[$locale])) {
        return $cache[$locale];
    }

    $file = APP_PATH . '/Lang/' . $locale . '.php';
    if (!file_exists($file)) {
        $cache[$locale] = array();
        return $cache[$locale];
    }

    $strings = include $file;
    if (!is_array($strings)) {
        $strings = array();
    }

    $cache[$locale] = $strings;
    return $strings;
}

function __($key, $replace = array())
{
    $locale = current_locale();
    $strings = load_translations($locale);

    $text = $key;
    if (isset($strings[$key])) {
        $text = $strings[$key];
    } elseif ($locale != 'en') {
        $fallback = load_translations('en');
        if (isset($fallback[$key])) {
            $text = $fallback[$key];
        }
    }

    if (is_array($replace)) {
        foreach ($replace as $name => $value) {
            $text = str_replace(':' . $name, (string) $value, $text);
        }
    }

    return $text;
}

function format_display_date($datetime)
{
    if (current_locale() == 'km') {
        return khmer_date($datetime);
    }
    return format_date($datetime);
}
