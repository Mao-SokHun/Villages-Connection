<?php

namespace App\Controllers;

use App\Core\Controller;

class PageController extends Controller
{
    private function renderPage($view, $page_title, $options = array())
    {
        global $pdo;

        $base_path = '';

        $page_description = __('site.desc');
        if (isset($options['description'])) {
            $page_description = $options['description'];
        } elseif (function_exists('site_default_meta_description')) {
            $page_description = site_default_meta_description();
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
        $description = trim(get_setting('seo_about_description', ''));
        if ($description == '') {
            $description = __('page.about.meta', array('site' => __('site.name')));
        }

        $this->renderPage('about', __('page.about.breadcrumb'), array(
            'description' => $description,
            'breadcrumbs' => array(
                array('label' => __('common.home'), 'url' => 'index.php'),
                array('label' => __('page.about.breadcrumb'), 'url' => '')
            )
        ));
    }

    public function faq()
    {
        $this->renderPage('faq', __('page.faq.title'), array(
            'description' => __('page.faq.meta', array('site' => __('site.name'))),
            'breadcrumbs' => array(
                array('label' => __('common.home'), 'url' => 'index.php'),
                array('label' => __('page.faq.title'), 'url' => '')
            )
        ));
    }

    public function helpUs()
    {
        $this->renderPage('help-us', __('page.help.title'), array(
            'description' => __('page.help.meta', array('site' => __('site.name'))),
            'breadcrumbs' => array(
                array('label' => __('common.home'), 'url' => 'index.php'),
                array('label' => __('page.help.breadcrumb'), 'url' => '')
            )
        ));
    }

    public function terms()
    {
        $this->renderPage('terms', __('page.terms.title'), array(
            'description' => __('page.terms.meta', array('site' => __('site.name'))),
            'breadcrumbs' => array(
                array('label' => __('common.home'), 'url' => 'index.php'),
                array('label' => __('page.terms.title'), 'url' => '')
            )
        ));
    }

    public function privacy()
    {
        $this->renderPage('privacy', __('page.privacy.title'), array(
            'description' => __('page.privacy.meta', array('site' => __('site.name'))),
            'breadcrumbs' => array(
                array('label' => __('common.home'), 'url' => 'index.php'),
                array('label' => __('page.privacy.title'), 'url' => '')
            )
        ));
    }
}
