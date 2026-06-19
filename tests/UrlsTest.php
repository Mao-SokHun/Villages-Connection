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
        $this->assertSame('/post/' . rawurlencode('ខ្មែរ'), post_url('ខ្មែរ'));
        $this->assertSame('/post/hello', app_url('post.php?slug=hello'));
        $this->assertSame('/profile/12', profile_url(12));
    }

    public function testPostPhpRedirectUsesSlugPath()
    {
        putenv('PRETTY_URLS=true');
        $_SERVER['SCRIPT_NAME'] = '/post.php';
        $_SERVER['QUERY_STRING'] = 'slug=my-post';
        $_GET['slug'] = 'my-post';

        $this->assertSame('/post/my-post', exposed_php_redirect_url());

        unset($_GET['slug']);
        unset($_SERVER['QUERY_STRING']);
    }

    public function testAppUrlFallbackWhenPrettyDisabled()
    {
        putenv('PRETTY_URLS=false');
        $this->assertSame('login.php', app_url('login.php'));
    }

    public function testRouteUrlNamedRoutes()
    {
        putenv('PRETTY_URLS=true');
        $this->assertSame('/login', route_url('login'));
        $this->assertSame('/admin/posts?action=add', route_url('admin.posts', array('action' => 'add')));
    }

    public function testAuthUrl()
    {
        $this->assertSame('/auth/google.php', auth_url('google'));
        $this->assertSame('/auth/facebook.php', auth_url('facebook'));
    }
}
