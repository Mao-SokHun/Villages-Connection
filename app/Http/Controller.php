<?php

namespace App\Http;

use App\Views\View;

class Controller
{
    protected function view($view, $data = array())
    {
        View::render($view, $data);
    }
}
