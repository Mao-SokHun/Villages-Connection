<?php

namespace App\Views;

class View
{
    public static function render($view, $data = array())
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $$key = $value;
            }
        }

        $view_file = APP_PATH . '/Views/' . $view . '.php';
        if (!file_exists($view_file)) {
            die('View not found: ' . htmlspecialchars($view));
        }

        require $view_file;
    }

    public static function layout($layout, $view, $data = array())
    {
        $data['inner_view'] = $view;
        self::render('layouts/' . $layout, $data);
    }
}
