<?php

use PHPUnit\Framework\TestCase;

class AnalyticsTest extends TestCase
{
    public function testAnalyticsDaysAllowed()
    {
        $this->assertSame(30, analytics_days_allowed(30));
        $this->assertSame(7, analytics_days_allowed(7));
        $this->assertSame(30, analytics_days_allowed(99));
        $this->assertSame(30, analytics_days_allowed(0));
    }
}
