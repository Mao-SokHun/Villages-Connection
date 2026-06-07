<?php

namespace App\Core;

class Controller
{
    protected function view($view, $data = array())
    {
        View::render($view, $data);
    }
}
