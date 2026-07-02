<?php
require_once APP_PATH . '/Models/mail.php';

$page_title = __('page.contact.title');
$page_description = __('page.contact.meta', array('site' => __('site.name')));
$page_breadcrumbs = array(
    array('label' => __('common.home'), 'url' => 'index.php'),
    array('label' => __('page.contact.title'), 'url' => '')
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
        $errors[] = __('validation.name_required');
    }
    if ($email == '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = __('validation.email_invalid');
    }
    if ($subject == '') {
        $errors[] = __('validation.subject_required');
    }
    if (strlen($message) < 10) {
        $errors[] = __('validation.message_min');
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
            setFlashMessage('success', __('page.contact.sent_logged_in'));
        } else {
            setFlashMessage('success', __('page.contact.sent_guest'));
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
