<?php

namespace App\Controllers;

use App\Core\Controller;

class PageController extends Controller
{
    private function renderPage($view, $page_title, $options = array())
    {
        global $pdo;

        $base_path = '';

        $page_description = SITE_DESC;
        if (isset($options['description'])) {
            $page_description = $options['description'];
        }

        $page_breadcrumbs = array();
        if (isset($options['breadcrumbs'])) {
            $page_breadcrumbs = $options['breadcrumbs'];
        }

        require ROOT_PATH . '/app/Views/layouts/header.php';

        if (count($page_breadcrumbs) > 0) {
            render_breadcrumb($page_breadcrumbs, $base_path);
        }

        $this->view('pages/' . $view);
        require ROOT_PATH . '/app/Views/layouts/footer.php';
    }

    public function about()
    {
        $this->renderPage('about', 'About Us', array(
            'description' => 'Learn about ' . SITE_NAME . ' — a community social platform for sharing photos, videos, and local updates.',
            'breadcrumbs' => array(
                array('label' => 'Home', 'url' => 'index.php'),
                array('label' => 'About Us', 'url' => '')
            )
        ));
    }

    public function faq()
    {
        $this->renderPage('faq', 'FAQ', array(
            'description' => 'Frequently asked questions about accounts, posting, and using ' . SITE_NAME . '.',
            'breadcrumbs' => array(
                array('label' => 'Home', 'url' => 'index.php'),
                array('label' => 'FAQ', 'url' => '')
            )
        ));
    }

    public function helpUs()
    {
        $this->renderPage('help-us', 'Help Us', array(
            'description' => 'Ways to support ' . SITE_NAME . ' and help the community grow.',
            'breadcrumbs' => array(
                array('label' => 'Home', 'url' => 'index.php'),
                array('label' => 'Help Us', 'url' => '')
            )
        ));
    }

    public function terms()
    {
        $this->renderPage('terms', 'Terms of Service', array(
            'description' => 'Terms of Service for using ' . SITE_NAME . '.',
            'breadcrumbs' => array(
                array('label' => 'Home', 'url' => 'index.php'),
                array('label' => 'Terms of Service', 'url' => '')
            )
        ));
    }

    public function privacy()
    {
        $this->renderPage('privacy', 'Privacy Policy', array(
            'description' => 'How ' . SITE_NAME . ' collects, uses, and protects your personal data.',
            'breadcrumbs' => array(
                array('label' => 'Home', 'url' => 'index.php'),
                array('label' => 'Privacy Policy', 'url' => '')
            )
        ));
    }
}
