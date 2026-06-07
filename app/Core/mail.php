<?php

function mail_setting($key, $default)
{
    $val = getenv($key);
    if ($val == false || $val == '') {
        return $default;
    }
    return $val;
}

function send_email($to, $subject, $html_body)
{
    $host = mail_setting('MAIL_HOST', 'mailpit');
    $port = (int) mail_setting('MAIL_PORT', '1025');
    $from = mail_setting('MAIL_FROM', 'noreply@villagenews.local');
    $from_name = mail_setting('MAIL_FROM_NAME', SITE_NAME);

    $text_body = strip_tags(str_replace(array('<br>', '<br/>', '<br />'), "\n", $html_body));

    $ok = smtp_send_mail($host, $port, $from, $from_name, $to, $subject, $html_body, $text_body);

    if ($ok) {
        return true;
    }

    $debug = mail_setting('APP_DEBUG', 'false');
    if ($debug == 'true' || $debug == '1') {
        $log_dir = STORAGE_PATH . '/logs';
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        $log_file = $log_dir . '/mail.log';
        $line = date('Y-m-d H:i:s') . ' | TO: ' . $to . ' | SUBJECT: ' . $subject . "\n" . $text_body . "\n---\n";
        file_put_contents($log_file, $line, FILE_APPEND);
        return true;
    }

    return false;
}

function smtp_send_mail($host, $port, $from, $from_name, $to, $subject, $html_body, $text_body)
{
    $socket = @fsockopen($host, $port, $errno, $errstr, 10);
    if (!$socket) {
        return false;
    }

    $read = function () use ($socket) {
        $data = '';
        while ($line = fgets($socket, 515)) {
            $data = $data . $line;
            if (isset($line[3]) && $line[3] == ' ') {
                break;
            }
        }
        return $data;
    };

    $write = function ($cmd) use ($socket) {
        fputs($socket, $cmd . "\r\n");
    };

    $read();

    $write('EHLO localhost');
    $read();

    $write('MAIL FROM:<' . $from . '>');
    $resp = $read();
    if (strpos($resp, '250') === false) {
        fclose($socket);
        return false;
    }

    $write('RCPT TO:<' . $to . '>');
    $resp = $read();
    if (strpos($resp, '250') === false && strpos($resp, '251') === false) {
        fclose($socket);
        return false;
    }

    $write('DATA');
    $resp = $read();
    if (strpos($resp, '354') === false) {
        fclose($socket);
        return false;
    }

    $boundary = 'cms_' . md5(uniqid((string) time(), true));
    $headers = array();
    $headers[] = 'From: ' . $from_name . ' <' . $from . '>';
    $headers[] = 'To: <' . $to . '>';
    $headers[] = 'Subject: ' . $subject;
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';

    $message = implode("\r\n", $headers) . "\r\n\r\n";
    $message = $message . '--' . $boundary . "\r\n";
    $message = $message . "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
    $message = $message . $text_body . "\r\n";
    $message = $message . '--' . $boundary . "\r\n";
    $message = $message . "Content-Type: text/html; charset=UTF-8\r\n\r\n";
    $message = $message . $html_body . "\r\n";
    $message = $message . '--' . $boundary . "--\r\n";
    $message = $message . ".\r\n";

    fputs($socket, $message);
    $resp = $read();
    if (strpos($resp, '250') === false) {
        fclose($socket);
        return false;
    }

    $write('QUIT');
    fclose($socket);
    return true;
}

function send_activity_email($to, $name, $subject, $message, $link_url = '')
{
    $body = '<div style="font-family:Arial,sans-serif;line-height:1.6;color:#0f172a;">';
    $body .= '<h2 style="color:#4f46e5;">' . htmlspecialchars($subject) . '</h2>';
    $body .= '<p>Hello ' . htmlspecialchars($name) . ',</p>';
    $body .= '<p>' . nl2br(htmlspecialchars($message)) . '</p>';
    if ($link_url != '') {
        $href = $link_url;
        if (strpos($href, 'http') !== 0) {
            $href = site_base_url() . '/' . ltrim($href, '/');
        }
        $body .= '<p><a href="' . htmlspecialchars($href) . '" style="color:#4f46e5;">View details</a></p>';
    }
    $body .= '<p style="color:#64748b;font-size:13px;">' . htmlspecialchars(SITE_NAME) . '</p></div>';
    return send_email($to, SITE_NAME . ' — ' . $subject, $body);
}

function send_password_reset_otp_email($to, $name, $otp)
{
    $subject = SITE_NAME . ' — Password Reset Code';
    $body = '<div style="font-family:Arial,sans-serif;line-height:1.6;color:#0f172a;">';
    $body = $body . '<h2 style="color:#4f46e5;">Password Reset</h2>';
    $body = $body . '<p>Hello ' . htmlspecialchars($name) . ',</p>';
    $body = $body . '<p>Your one-time password (OTP) code is:</p>';
    $body = $body . '<p style="font-size:28px;font-weight:bold;letter-spacing:6px;color:#0f172a;">' . htmlspecialchars($otp) . '</p>';
    $body = $body . '<p>This code expires in <strong>15 minutes</strong>. If you did not request this, you can ignore this email.</p>';
    $body = $body . '<p style="color:#64748b;font-size:13px;">' . htmlspecialchars(SITE_NAME) . '</p>';
    $body = $body . '</div>';

    return send_email($to, $subject, $body);
}

function send_contact_email($name, $email, $subject, $message)
{
    $to = SITE_CONTACT_EMAIL;
    $mail_subject = SITE_NAME . ' — Contact: ' . $subject;
    $body = '<div style="font-family:Arial,sans-serif;line-height:1.6;color:#0f172a;">';
    $body = $body . '<h2 style="color:#4f46e5;">New Contact Message</h2>';
    $body = $body . '<p><strong>From:</strong> ' . htmlspecialchars($name) . ' &lt;' . htmlspecialchars($email) . '&gt;</p>';
    $body = $body . '<p><strong>Subject:</strong> ' . htmlspecialchars($subject) . '</p>';
    $body = $body . '<p><strong>Message:</strong></p>';
    $body = $body . '<p>' . nl2br(htmlspecialchars($message)) . '</p>';
    $body = $body . '</div>';

    return send_email($to, $mail_subject, $body);
}

function send_report_email($name, $email, $reason, $post_url, $details)
{
    $to = SITE_CONTACT_EMAIL;
    $mail_subject = SITE_NAME . ' — Content Report';
    $body = '<div style="font-family:Arial,sans-serif;line-height:1.6;color:#0f172a;">';
    $body = $body . '<h2 style="color:#dc2626;">Content Report</h2>';
    $body = $body . '<p><strong>Reporter:</strong> ' . htmlspecialchars($name) . ' &lt;' . htmlspecialchars($email) . '&gt;</p>';
    $body = $body . '<p><strong>Reason:</strong> ' . htmlspecialchars($reason) . '</p>';
    if ($post_url != '') {
        $body = $body . '<p><strong>Post URL:</strong> ' . htmlspecialchars($post_url) . '</p>';
    }
    $body = $body . '<p><strong>Details:</strong></p>';
    $body = $body . '<p>' . nl2br(htmlspecialchars($details)) . '</p>';
    $body = $body . '</div>';

    return send_email($to, $mail_subject, $body);
}
