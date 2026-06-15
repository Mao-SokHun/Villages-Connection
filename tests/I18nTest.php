<?php

use PHPUnit\Framework\TestCase;

class I18nTest extends TestCase
{
    public function testKhmerTranslation()
    {
        $_SESSION = array('locale' => 'km');
        $this->assertSame('ចូល', __('nav.sign_in'));
    }

    public function testEnglishTranslation()
    {
        $_SESSION = array('locale' => 'en');
        $this->assertSame('Sign In', __('nav.sign_in'));
    }

    public function testPlaceholderReplacement()
    {
        $_SESSION = array('locale' => 'en');
        $this->assertSame('Hello Ada', __('auth.welcome_named', array('name' => 'Ada')));
    }
}
