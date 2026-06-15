<?php

use PHPUnit\Framework\TestCase;

class SecurityTest extends TestCase
{
    public function testCsrfTokenGeneration()
    {
        $_SESSION = array();
        $token = csrf_token();
        $this->assertNotSame('', $token);
        $this->assertSame(64, strlen($token));
    }

    public function testCsrfVerification()
    {
        $_SESSION = array();
        $token = csrf_token();
        $_POST['csrf_token'] = $token;
        $this->assertTrue(verify_csrf_token());
    }

    public function testCsrfVerificationFailsOnMismatch()
    {
        $_SESSION = array('csrf_token' => 'abc');
        $_POST['csrf_token'] = 'xyz';
        $this->assertFalse(verify_csrf_token());
    }

    public function testHstsHeaderValueWhenHttps()
    {
        $_SERVER['HTTPS'] = 'on';
        putenv('HSTS_ENABLED=true');
        putenv('HSTS_MAX_AGE=31536000');
        putenv('HSTS_INCLUDE_SUBDOMAINS=true');

        $value = hsts_header_value();
        $this->assertStringContainsString('max-age=31536000', $value);
        $this->assertStringContainsString('includeSubDomains', $value);

        unset($_SERVER['HTTPS']);
    }

    public function testHstsHeaderValueEmptyOnHttp()
    {
        unset($_SERVER['HTTPS']);
        unset($_SERVER['HTTP_X_FORWARDED_PROTO']);
        putenv('HSTS_ENABLED=true');

        $this->assertSame('', hsts_header_value());
    }

    public function testSafeRedirectPathBlocksExternalUrls()
    {
        $this->assertFalse(is_safe_redirect_path('//evil.com'));
        $this->assertFalse(is_safe_redirect_path('https://evil.com'));
        $this->assertFalse(is_safe_redirect_path('javascript:alert(1)'));
        $this->assertTrue(is_safe_redirect_path('notifications.php'));
        $this->assertTrue(is_safe_redirect_path('post.php?slug=hello'));
    }

    public function testSanitizePlainTextFieldStripsTags()
    {
        $this->assertSame('alert(1)', sanitize_plain_text_field('<script>alert(1)</script>', 80));
    }
}
