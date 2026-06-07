<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once APP_PATH . '/Core/mail.php';

$page_title = 'Contact Us';
$page_description = 'Get in touch with the ' . SITE_NAME . ' team for support, feedback, or partnership questions.';
$page_breadcrumbs = array(
    array('label' => 'Home', 'url' => 'index.php'),
    array('label' => 'Contact Us', 'url' => '')
);

$name = '';
$email = '';
$subject = '';
$message = '';
$errors = array();
$sent = false;

if (isLoggedIn()) {
    $name = $_SESSION['user_name'];
    $email = $_SESSION['user_email'];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_valid_csrf();

    if (isset($_POST['name'])) {
        $name = trim($_POST['name']);
    }
    if (isset($_POST['email'])) {
        $email = trim($_POST['email']);
    }
    if (isset($_POST['subject'])) {
        $subject = trim($_POST['subject']);
    }
    if (isset($_POST['message'])) {
        $message = trim($_POST['message']);
    }

    if ($name == '') {
        $errors[] = 'Your name is required.';
    }
    if ($email == '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($subject == '') {
        $errors[] = 'Subject is required.';
    }
    if (strlen($message) < 10) {
        $errors[] = 'Message must be at least 10 characters.';
    }

    if (count($errors) == 0) {
        save_contact_message($pdo, $name, $email, $subject, $message);
        if (send_contact_email($name, $email, $subject, $message)) {
            $sent = true;
            $name = '';
            $email = isLoggedIn() ? $_SESSION['user_email'] : '';
            $subject = '';
            $message = '';
        } else {
            $errors[] = 'Could not send your message right now. Please try again later.';
        }
    }
}

require_once ROOT_PATH . '/app/Views/layouts/header.php';
render_breadcrumb($page_breadcrumbs, $base_path);
require ROOT_PATH . '/app/Views/pages/contact.php';
require_once ROOT_PATH . '/app/Views/layouts/footer.php';
