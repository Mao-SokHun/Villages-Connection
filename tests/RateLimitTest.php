<?php

use PHPUnit\Framework\TestCase;

class RateLimitTest extends TestCase
{
    public function testClientRateLimitIdIncludesIpForGuests()
    {
        $_SESSION = array();
        $_SERVER['REMOTE_ADDR'] = '203.0.113.10';

        $this->assertSame('ip:203.0.113.10', client_rate_limit_id());
    }

    public function testClientRateLimitIdIncludesUserAndIpWhenLoggedIn()
    {
        $_SESSION = array('user_id' => 42);
        $_SERVER['REMOTE_ADDR'] = '203.0.113.10';

        $this->assertSame('user:42|ip:203.0.113.10', client_rate_limit_id());
    }

    public function testSessionRateLimitBlocksAfterMaxAttempts()
    {
        putenv('RATE_LIMIT_DRIVER=session');
        $_SESSION = array();

        $this->assertTrue(rate_limit_hit_session('test_action', 'ip:1.2.3.4', 3, 60));
        $this->assertTrue(rate_limit_hit_session('test_action', 'ip:1.2.3.4', 3, 60));
        $this->assertTrue(rate_limit_hit_session('test_action', 'ip:1.2.3.4', 3, 60));
        $this->assertFalse(rate_limit_hit_session('test_action', 'ip:1.2.3.4', 3, 60));
    }
}
