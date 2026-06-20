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

    public function testFeedUrlUsesCleanPaths()
    {
        putenv('PRETTY_URLS=true');
        $this->assertSame('/', feed_url(array()));
        $this->assertSame('/popular', feed_url(array('sort' => 'popular')));
        $this->assertSame('/following', feed_url(array('sort' => 'following')));
        $this->assertSame('/category/news', feed_url(array('cat' => 'news')));
        $this->assertSame('/popular/category/news', feed_url(array('sort' => 'popular', 'cat' => 'news')));
        $this->assertSame('/popular?page=2', feed_url(array('sort' => 'popular', 'page' => 2)));
        $this->assertSame('/popular', app_url('index.php?sort=popular'));
        $this->assertSame('/category/events', app_url('index.php?cat=events'));
    }

    public function testFeedRouteMatch()
    {
        $this->assertSame(array('sort' => 'popular'), route_registry_feed_match('/popular'));
        $this->assertSame(array('cat' => 'news'), route_registry_feed_match('/category/news'));
        $this->assertSame(
            array('sort' => 'popular', 'cat' => 'news'),
            route_registry_feed_match('/popular/category/news')
        );
        $this->assertNull(route_registry_feed_match('/login'));
    }

    public function testIndexPhpRedirectUsesFeedPaths()
    {
        putenv('PRETTY_URLS=true');
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['QUERY_STRING'] = 'sort=popular&cat=news';
        $_GET['sort'] = 'popular';
        $_GET['cat'] = 'news';

        $this->assertSame('/popular/category/news', exposed_php_redirect_url());

        unset($_GET['sort'], $_GET['cat']);
        unset($_SERVER['QUERY_STRING']);
    }

    public function testAuthUrl()
    {
        $this->assertSame('/auth/google.php', auth_url('google'));
        $this->assertSame('/auth/facebook.php', auth_url('facebook'));
    }
}
