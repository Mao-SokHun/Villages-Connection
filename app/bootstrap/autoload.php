<?php

/**
 * PSR-4-style autoload for App\ namespace (Controllers, Core classes).
 */

function bootstrap_register_autoload()
{
    spl_autoload_register(function ($class) {
        $prefix = 'App\\';
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }
        $relative = str_replace('\\', '/', substr($class, $len));
        $file = APP_PATH . '/' . $relative . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    });
}
