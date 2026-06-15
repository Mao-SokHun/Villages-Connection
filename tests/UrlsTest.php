<?php

use PHPUnit\Framework\TestCase;

class UrlsTest extends TestCase
{
    public function testPrettyRouteLookupForLogin()
    {
        putenv('PRETTY_URLS=true');
        $this->assertSame('/login', pretty_route_lookup('login.php'));
        $this->assertSame('/admin', pretty_route_lookup('admin/dashboard.php'));
        $this->assertSame('/admin/posts?action=add', pretty_route_lookup('admin/posts.php?action=add'));
    }

    public function testAppUrlUsesPrettyPaths()
    {
        putenv('PRETTY_URLS=true');
        $this->assertSame('/', app_url('index.php'));
        $this->assertSame('/search', app_url('search.php'));
        $this->assertSame('/post/my-slug', post_url('my-slug'));
        $this->assertSame('/profile/12', profile_url(12));
    }

    public function testAppUrlFallbackWhenPrettyDisabled()
    {
        putenv('PRETTY_URLS=false');
        $this->assertSame('login.php', app_url('login.php'));
    }
}
