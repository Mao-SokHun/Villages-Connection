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
    $username = mail_setting('MAIL_USERNAME', '');
    $password = mail_setting('MAIL_PASSWORD', '');
    $encryption = strtolower(mail_setting('MAIL_ENCRYPTION', 'none'));

    $text_body = strip_tags(str_replace(array('<br>', '<br/>', '<br />'), "\n", $html_body));

    $ok = smtp_send_mail($host, $port, $from, $from_name, $to, $subject, $html_body, $text_body, $username, $password, $encryption);

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

function smtp_read_response($socket)
{
    $data = '';
    while ($line = fgets($socket, 515)) {
        $data = $data . $line;
        if (isset($line[3]) && $line[3] == ' ') {
            break;
        }
    }
    return $data;
}

function smtp_write($socket, $cmd)
{
    fputs($socket, $cmd . "\r\n");
}

function smtp_expect($socket, $codes)
{
    $resp = smtp_read_response($socket);
    if (!is_array($codes)) {
        $codes = array($codes);
    }
    foreach ($codes as $code) {
        if (strpos($resp, (string) $code) === 0) {
            return true;
        }
    }
    return false;
}

function smtp_connect($host, $port, $encryption)
{
    $target = $host . ':' . $port;
    if ($encryption == 'ssl') {
        $target = 'ssl://' . $target;
    }

    $socket = @stream_socket_client($target, $errno, $errstr, 20, STREAM_CLIENT_CONNECT);
    if (!$socket) {
        return false;
    }

    stream_set_timeout($socket, 20);
    return $socket;
}

function smtp_starttls($socket)
{
    smtp_write($socket, 'STARTTLS');
    if (!smtp_expect($socket, '220')) {
        return false;
    }

    $crypto = STREAM_CRYPTO_METHOD_TLS_CLIENT;
    if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
        $crypto = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
    }

    if (!stream_socket_enable_crypto($socket, true, $crypto)) {
        return false;
    }

    return true;
}

function smtp_auth_login($socket, $username, $password)
{
    if ($username == '' || $password == '') {
        return true;
    }

    smtp_write($socket, 'AUTH PLAIN ' . base64_encode("\0" . $username . "\0" . $password));
    if (smtp_expect($socket, '235')) {
        return true;
    }

    smtp_write($socket, 'AUTH LOGIN');
    if (!smtp_expect($socket, '334')) {
        return false;
    }

    smtp_write($socket, base64_encode($username));
    if (!smtp_expect($socket, '334')) {
        return false;
    }

    smtp_write($socket, base64_encode($password));
    if (!smtp_expect($socket, '235')) {
        return false;
    }

    return true;
}

function smtp_ehlo($socket, $host_label)
{
    smtp_write($socket, 'EHLO ' . $host_label);
    return smtp_expect($socket, '250');
}

function smtp_send_mail($host, $port, $from, $from_name, $to, $subject, $html_body, $text_body, $username = '', $password = '', $encryption = 'none')
{
    $socket = smtp_connect($host, $port, $encryption);
    if (!$socket) {
        return false;
    }

    if (!smtp_expect($socket, '220')) {
        fclose($socket);
        return false;
    }

    if (!smtp_ehlo($socket, 'localhost')) {
        fclose($socket);
        return false;
    }

    if ($encryption == 'tls') {
        if (!smtp_starttls($socket)) {
            fclose($socket);
            return false;
        }
        if (!smtp_ehlo($socket, 'localhost')) {
            fclose($socket);
            return false;
        }
    }

    if (!smtp_auth_login($socket, $username, $password)) {
        fclose($socket);
        return false;
    }

    smtp_write($socket, 'MAIL FROM:<' . $from . '>');
    if (!smtp_expect($socket, '250')) {
        fclose($socket);
        return false;
    }

    smtp_write($socket, 'RCPT TO:<' . $to . '>');
    if (!smtp_expect($socket, array('250', '251'))) {
        fclose($socket);
        return false;
    }

    smtp_write($socket, 'DATA');
    if (!smtp_expect($socket, '354')) {
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
    if (!smtp_expect($socket, '250')) {
        fclose($socket);
        return false;
    }

    smtp_write($socket, 'QUIT');
    fclose($socket);
    return true;
}

function mail_layout_wrap($title, $inner_html, $preheader = '')
{
    $site = htmlspecialchars(SITE_NAME);
    $tagline = htmlspecialchars(SITE_TAGLINE);
    $year = date('Y');
    $preheader_text = htmlspecialchars($preheader);
    $logo_src = '';
    $logo_file = ROOT_PATH . '/public/icons/logo-light.png';
    if (is_file($logo_file)) {
        $logo_bytes = @file_get_contents($logo_file);
        if ($logo_bytes !== false && $logo_bytes !== '') {
            // Embed image directly so email clients do not need localhost/public URL access.
            $logo_src = 'data:image/png;base64,' . base64_encode($logo_bytes);
        }
    }
    if ($logo_src == '') {
        $asset_path = public_asset_url('icons/logo-light.png');
        if (is_remote_media_url($asset_path)) {
            $logo_src = $asset_path;
        } else {
            $base_url = '';
            if (defined('APP_URL') && APP_URL != '') {
                $base_url = rtrim(APP_URL, '/');
            } else {
                $base_url = rtrim(site_base_url(), '/');
            }
            $logo_src = $base_url . $asset_path;
        }
    }
    $logo_url = htmlspecialchars($logo_src);

    $html = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">';
    $html .= '<title>' . htmlspecialchars($title) . '</title></head>';
    $html .= '<body style="margin:0;padding:0;background:#eef2f7;font-family:Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#0f172a;">';
    if ($preheader_text != '') {
        $html .= '<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">' . $preheader_text . '</div>';
    }
    $html .= '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#eef2f7;padding:32px 16px;">';
    $html .= '<tr><td align="center">';
    $html .= '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:560px;background:#ffffff;border-radius:18px;overflow:hidden;border:1px solid #e2e8f0;box-shadow:0 12px 30px rgba(15,23,42,0.08);">';
    $html .= '<tr><td style="padding:28px 32px 22px;background:linear-gradient(135deg,#6366f1 0%,#0ea5e9 52%,#14b8a6 100%);color:#ffffff;text-align:center;">';
    $html .= '<div style="font-size:13px;letter-spacing:0.08em;text-transform:uppercase;opacity:0.92;margin-bottom:8px;">Security</div>';
    $html .= '<div style="margin:0 0 10px;"><img src="' . $logo_url . '" alt="' . $site . ' logo" width="180" style="display:block;margin:0 auto;max-width:180px;width:100%;height:auto;border:0;outline:none;text-decoration:none;"></div>';
    $html .= '<div style="font-size:28px;font-weight:700;line-height:1.2;">' . $site . '</div>';
    $html .= '<div style="font-size:14px;opacity:0.92;margin-top:6px;">' . $tagline . '</div>';
    $html .= '</td></tr>';
    $html .= '<tr><td style="padding:32px 32px 28px;">';
    $html .= $inner_html;
    $html .= '</td></tr>';
    $html .= '<tr><td style="padding:18px 32px 24px;background:#f8fafc;border-top:1px solid #e2e8f0;text-align:center;">';
    $html .= '<p style="margin:0 0 6px;font-size:13px;color:#64748b;">&copy; ' . $year . ' ' . $site . '. All rights reserved.</p>';
    $html .= '<p style="margin:0;font-size:12px;color:#94a3b8;">This is an automated message. Please do not reply directly to this email.</p>';
    $html .= '</td></tr></table></td></tr></table></body></html>';

    return $html;
}

function mail_otp_digit_boxes($otp)
{
    $digits = str_split(preg_replace('/\D/', '', (string) $otp));
    if (count($digits) == 0) {
        return '';
    }

    $html = '<table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin:8px auto 20px;">';
    $html .= '<tr>';
    foreach ($digits as $digit) {
        $html .= '<td style="padding:0 4px;">';
        $html .= '<div style="width:42px;height:50px;line-height:50px;text-align:center;font-size:24px;font-weight:700;font-family:Consolas,Monaco,monospace;color:#0f172a;background:#f8fafc;border:1px solid #dbe3ee;border-radius:12px;">';
        $html .= htmlspecialchars($digit);
        $html .= '</div></td>';
    }
    $html .= '</tr></table>';

    return $html;
}

function mail_button($label, $url)
{
    return '<table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin:8px auto 20px;">'
        . '<tr><td style="border-radius:999px;background:linear-gradient(135deg,#6366f1 0%,#0ea5e9 50%,#14b8a6 100%);">'
        . '<a href="' . htmlspecialchars($url) . '" style="display:inline-block;padding:14px 28px;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:999px;">'
        . htmlspecialchars($label)
        . '</a></td></tr></table>';
}

function send_password_reset_otp_email($to, $name, $otp)
{
    $template = email_template('email_template_reset', array(
        'subject' => SITE_NAME . ' — Password Reset Code',
        'body' => "Hello {name},\n\nYour one-time password (OTP) code is: {otp}\n\nThis code expires in 15 minutes.",
    ), array(
        'name' => $name,
        'otp' => $otp,
        'site_name' => SITE_NAME,
    ));

    $reset_url = site_base_url() . '/reset-password.php';
    $safe_name = htmlspecialchars($name);
    $safe_otp = htmlspecialchars($otp);

    $inner = '';
    $inner .= '<h1 style="margin:0 0 10px;font-size:24px;line-height:1.3;color:#0f172a;">Password reset code</h1>';
    $inner .= '<p style="margin:0 0 18px;font-size:15px;line-height:1.7;color:#475569;">Hello ' . $safe_name . ', we received a request to reset your password. Use the verification code below to continue.</p>';
    $inner .= mail_otp_digit_boxes($otp);
    $inner .= '<p style="margin:0 0 6px;text-align:center;font-size:13px;color:#64748b;">Or copy this code manually:</p>';
    $inner .= '<p style="margin:0 0 22px;text-align:center;font-size:18px;font-weight:700;letter-spacing:0.35em;font-family:Consolas,Monaco,monospace;color:#0f172a;">' . $safe_otp . '</p>';
    $inner .= mail_button('Reset Password', $reset_url);
    $inner .= '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 18px;background:#fffbeb;border:1px solid #fde68a;border-radius:12px;">';
    $inner .= '<tr><td style="padding:14px 16px;font-size:14px;line-height:1.6;color:#92400e;">';
    $inner .= '<strong>Expires in 15 minutes.</strong> For your security, this code can only be used once.';
    $inner .= '</td></tr></table>';
    $inner .= '<p style="margin:0;font-size:13px;line-height:1.7;color:#64748b;">If you did not request a password reset, you can safely ignore this email. Your account will remain secure.</p>';

    $body = mail_layout_wrap(
        $template['subject'],
        $inner,
        'Your ' . SITE_NAME . ' password reset code is ' . $otp . '. It expires in 15 minutes.'
    );

    return send_email($to, $template['subject'], $body);
}

function send_email_verification_email($to, $name, $verify_url)
{
    $safe_name = htmlspecialchars($name);
    $subject = SITE_NAME . ' — Verify your email';

    $inner = '';
    $inner .= '<h1 style="margin:0 0 10px;font-size:24px;line-height:1.3;color:#0f172a;">Verify your email</h1>';
    $inner .= '<p style="margin:0 0 18px;font-size:15px;line-height:1.7;color:#475569;">Hello ' . $safe_name . ', please confirm your email address to activate your Village Connect account.</p>';
    $inner .= mail_button('Verify Email', $verify_url);
    $inner .= '<p style="margin:0;font-size:13px;line-height:1.7;color:#64748b;">This link expires in 24 hours. If you did not create an account, you can ignore this email.</p>';

    $body = mail_layout_wrap(
        $subject,
        $inner,
        'Verify your email for ' . SITE_NAME . ': ' . $verify_url
    );

    return send_email($to, $subject, $body);
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

function send_contact_reply_email($to, $name, $original_subject, $reply_body, $original_message, $original_date)
{
    $subject = $original_subject;
    if (stripos($subject, 're:') !== 0) {
        $subject = 'Re: ' . $subject;
    }

    $body = '<div style="font-family:Arial,sans-serif;line-height:1.6;color:#0f172a;">';
    $body .= '<p>Hello ' . htmlspecialchars($name) . ',</p>';
    $body .= '<p>' . nl2br(htmlspecialchars($reply_body)) . '</p>';
    $body .= '<hr style="border:none;border-top:1px solid #e2e8f0;margin:1.25rem 0;">';
    $body .= '<p style="color:#64748b;font-size:13px;margin:0 0 0.5rem;">Your message';
    if ($original_date != '') {
        $body .= ' (' . htmlspecialchars($original_date) . ')';
    }
    $body .= ':</p>';
    $body .= '<blockquote style="margin:0;padding-left:1rem;border-left:3px solid #cbd5e1;color:#475569;">';
    $body .= nl2br(htmlspecialchars($original_message));
    $body .= '</blockquote>';
    $body .= '<p style="color:#64748b;font-size:13px;margin-top:1.25rem;">' . htmlspecialchars(SITE_NAME) . '</p></div>';

    return send_email($to, $subject, $body);
}

function send_welcome_email($to, $name)
{
    $template = email_template('email_template_welcome', array(
        'subject' => 'Welcome to ' . SITE_NAME,
        'body' => "Hello {name},\n\nWelcome to {site_name}! You can now create posts, follow members, and join the conversation.",
    ), array(
        'name' => $name,
        'site_name' => SITE_NAME,
    ));

    $safe_name = htmlspecialchars($name);
    $feed_url = site_base_url() . '/index.php';
    $create_url = site_base_url() . '/admin/posts.php?action=add';

    $inner = '';
    $inner .= '<h1 style="margin:0 0 10px;font-size:24px;line-height:1.3;color:#0f172a;">Welcome to ' . htmlspecialchars(SITE_NAME) . '!</h1>';
    $inner .= '<p style="margin:0 0 16px;font-size:15px;line-height:1.75;color:#475569;">Hello ' . $safe_name . ', your account is ready. Start sharing community updates and connect with members today.</p>';
    $inner .= mail_button('Explore Feed', $feed_url);
    $inner .= '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 16px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;">';
    $inner .= '<tr><td style="padding:14px 16px;font-size:14px;line-height:1.65;color:#334155;">';
    $inner .= '<strong style="color:#0f172a;">Quick start:</strong>';
    $inner .= '<ul style="margin:10px 0 0 18px;padding:0;color:#475569;">';
    $inner .= '<li style="margin:0 0 6px;">Complete your profile and avatar</li>';
    $inner .= '<li style="margin:0 0 6px;">Create your first post with photo or video</li>';
    $inner .= '<li style="margin:0;">Follow members and engage with likes/comments</li>';
    $inner .= '</ul>';
    $inner .= '</td></tr></table>';
    $inner .= '<p style="margin:0;font-size:13px;line-height:1.7;color:#64748b;">Want to publish immediately? <a href="' . htmlspecialchars($create_url) . '" style="color:#4f46e5;text-decoration:none;font-weight:600;">Create a post now</a>.</p>';

    $body = mail_layout_wrap(
        $template['subject'],
        $inner,
        'Welcome to ' . SITE_NAME . '. Your account is ready.'
    );

    return send_email($to, $template['subject'], $body);
}

function send_contact_email($name, $email, $subject, $message)
{
    $to = site_contact_email();
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
    $to = site_contact_email();
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
