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
$sent_message_id = 0;

if (isLoggedIn()) {
    $name = $_SESSION['user_name'];
    $email = $_SESSION['user_email'];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_valid_csrf();

    $limit_id = client_rate_limit_id();
    if (!rate_limit_hit('contact_form', $limit_id, 5, 3600)) {
        $errors[] = rate_limit_blocked_response('contact_form', $limit_id, 3600, false);
    }

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
        $contact_user_id = 0;
        if (isLoggedIn()) {
            $contact_user_id = (int) $_SESSION['user_id'];
        }

        $message_id = save_contact_message($pdo, $name, $email, $subject, $message, $contact_user_id);
        send_contact_email($name, $email, $subject, $message);
        if ($contact_user_id > 0) {
            notify_user_contact_submitted($pdo, $message_id, $contact_user_id, $subject);
        }
        $sent = true;
        $sent_message_id = $message_id;
        if ($contact_user_id > 0) {
            setFlashMessage('success', 'Your message was sent. Check the notification bell or Support Messages for our reply.');
        } else {
            setFlashMessage('success', 'Your message was sent. Sign in to get replies in your notification bell, or we will email you.');
        }
        $name = '';
        $email = isLoggedIn() ? $_SESSION['user_email'] : '';
        $subject = '';
        $message = '';
    }
}

require_once ROOT_PATH . '/app/Views/layouts/header.php';
render_breadcrumb($page_breadcrumbs, $base_path);
require ROOT_PATH . '/app/Views/pages/contact.php';
require_once ROOT_PATH . '/app/Views/layouts/footer.php';
