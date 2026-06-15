<?php

use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    public function testSlugify()
    {
        $this->assertSame('hello-world', slugify('Hello World!'));
    }

    public function testExcerpt()
    {
        $this->assertSame('Hello...', excerpt('Hello World', 5));
    }

    public function testPostUrl()
    {
        putenv('PRETTY_URLS=true');
        $this->assertSame('/post/my-post', post_url('my-post'));
        putenv('PRETTY_URLS=false');
        $this->assertSame('post/my-post', post_url('my-post'));
    }

    public function testRenderPostContentBold()
    {
        $html = render_post_content('**Bold** text');
        $this->assertStringContainsString('<strong>Bold</strong>', $html);
    }

    public function testKhmerDateUsesKhmerMonth()
    {
        $formatted = khmer_date('2026-01-15 10:00:00');
        $this->assertStringContainsString('មករា', $formatted);
    }
}
