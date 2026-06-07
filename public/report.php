<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once APP_PATH . '/Core/mail.php';

$page_title = 'Report Content';
$page_description = 'Report inappropriate, spam, or harmful content on ' . SITE_NAME . '.';
$page_breadcrumbs = array(
    array('label' => 'Home', 'url' => 'index.php'),
    array('label' => 'Report Content', 'url' => '')
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
    'Spam or misleading content',
    'Harassment or hate speech',
    'Inappropriate photos or videos',
    'Copyright or ownership issue',
    'Other'
);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_valid_csrf();

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
        $errors[] = 'Your name is required.';
    }
    if ($email == '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($reason == '' || !in_array($reason, $reason_options)) {
        $errors[] = 'Please select a valid reason.';
    }
    if (strlen($details) < 10) {
        $errors[] = 'Please describe the issue in at least 10 characters.';
    }

    if (count($errors) == 0) {
        save_content_report($pdo, $name, $email, $reason, $post_url, $details);
        if (send_report_email($name, $email, $reason, $post_url, $details)) {
            $sent = true;
            $reason = '';
            $post_url = '';
            $details = '';
        } else {
            $errors[] = 'Could not submit your report right now. Please try again later.';
        }
    }
}

require_once ROOT_PATH . '/app/Views/layouts/header.php';
render_breadcrumb($page_breadcrumbs, $base_path);
require ROOT_PATH . '/app/Views/pages/report.php';
require_once ROOT_PATH . '/app/Views/layouts/footer.php';
