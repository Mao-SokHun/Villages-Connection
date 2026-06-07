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
}
