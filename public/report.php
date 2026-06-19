<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once APP_PATH . '/Core/mail.php';

$page_title = __('page.report.title');
$page_description = __('page.report.meta', array('site' => __('site.name')));
$page_breadcrumbs = array(
    array('label' => __('common.home'), 'url' => 'index.php'),
    array('label' => __('page.report.title'), 'url' => '')
);

$name = '';
$email = '';
$reason = '';
$post_url = '';
$details = '';
$errors = array();
$sent = false;

if (isLoggedIn()) {
    $name = $_SESSION['user_name'];
    $email = $_SESSION['user_email'];
}

if (isset($_GET['url'])) {
    $post_url = trim($_GET['url']);
}

$reason_options = array(
    'spam' => __('page.report.reason_spam'),
    'harassment' => __('page.report.reason_harassment'),
    'inappropriate' => __('page.report.reason_inappropriate'),
    'copyright' => __('page.report.reason_copyright'),
    'other' => __('page.report.reason_other'),
);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_valid_csrf();

    $limit_id = client_rate_limit_id();
    if (!rate_limit_hit('content_report', $limit_id, 5, 3600)) {
        $errors[] = rate_limit_blocked_response('content_report', $limit_id, 3600, false);
    }

    if (isset($_POST['name'])) {
        $name = trim($_POST['name']);
    }
    if (isset($_POST['email'])) {
        $email = trim($_POST['email']);
    }
    if (isset($_POST['reason'])) {
        $reason = trim($_POST['reason']);
    }
    if (isset($_POST['post_url'])) {
        $post_url = trim($_POST['post_url']);
    }
    if (isset($_POST['details'])) {
        $details = trim($_POST['details']);
    }

    if ($name == '') {
        $errors[] = __('validation.name_required');
    }
    if ($email == '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = __('validation.email_invalid');
    }
    if ($reason == '' || !isset($reason_options[$reason])) {
        $errors[] = __('validation.reason_required');
    }
    if (strlen($details) < 10) {
        $errors[] = __('validation.details_min');
    }

    if (count($errors) == 0) {
        $report_user_id = 0;
        if (isLoggedIn()) {
            $report_user_id = (int) $_SESSION['user_id'];
        }

        $reason_label = $reason_options[$reason];
        save_content_report($pdo, $name, $email, $reason_label, $post_url, $details, $report_user_id);
        send_report_email($name, $email, $reason_label, $post_url, $details);
        $sent = true;
        setFlashMessage('success', __('page.report.sent_flash'));
        $reason = '';
        $post_url = '';
        $details = '';
    }
}

require_once ROOT_PATH . '/app/Views/layouts/header.php';
render_breadcrumb($page_breadcrumbs, $base_path);
require ROOT_PATH . '/app/Views/pages/report.php';
require_once ROOT_PATH . '/app/Views/layouts/footer.php';
