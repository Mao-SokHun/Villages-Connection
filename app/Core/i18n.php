<?php

function supported_locales()
{
    return array('km', 'en');
}

function init_locale()
{
    if (isset($_GET['lang'])) {
        $lang = trim($_GET['lang']);
        if (in_array($lang, supported_locales(), true)) {
            $_SESSION['locale'] = $lang;
            setcookie('vc_locale', $lang, time() + (86400 * 365), '/', '', false, true);
        }
    }

    if (!isset($_SESSION['locale']) || $_SESSION['locale'] == '') {
        if (isset($_COOKIE['vc_locale']) && in_array($_COOKIE['vc_locale'], supported_locales(), true)) {
            $_SESSION['locale'] = $_COOKIE['vc_locale'];
        } else {
            $_SESSION['locale'] = 'km';
        }
    }
}

function current_locale()
{
    if (isset($_SESSION['locale']) && in_array($_SESSION['locale'], supported_locales(), true)) {
        return $_SESSION['locale'];
    }
    return 'km';
}

function locale_label($locale)
{
    if ($locale == 'en') {
        return 'English';
    }
    return 'ខ្មែរ';
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
