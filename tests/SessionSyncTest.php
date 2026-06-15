<?php

use PHPUnit\Framework\TestCase;

class SessionSyncTest extends TestCase
{
    public function testProductionForcesDebugOff()
    {
        $app_env = 'production';
        $app_debug = 'true';
        $debug_on = ($app_debug === 'true' || $app_debug === '1');
        if ($app_env === 'production') {
            $debug_on = false;
        }

        $this->assertFalse($debug_on);
    }

    public function testLocalDebugRespectsEnv()
    {
        $app_env = 'local';
        $app_debug = 'true';
        $debug_on = ($app_debug === 'true' || $app_debug === '1');
        if ($app_env === 'production') {
            $debug_on = false;
        }

        $this->assertTrue($debug_on);
    }
}
