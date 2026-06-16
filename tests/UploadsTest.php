<?php

use PHPUnit\Framework\TestCase;

class UploadsTest extends TestCase
{
    private $tempFiles = array();

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        $this->tempFiles = array();
    }

    public function testMimeMatchesExtensionForPng()
    {
        $this->assertTrue(upload_mime_matches_extension('png', 'image/png', upload_allowed_image_mimes()));
        $this->assertFalse(upload_mime_matches_extension('png', 'application/x-php', upload_allowed_image_mimes()));
    }

    public function testValidateImageRejectsFakeExtension()
    {
        $path = $this->makeTempFile("<?php echo 'x';", 'evil.jpg');
        $_FILES = array(
            'image' => array(
                'name' => 'evil.jpg',
                'type' => 'image/jpeg',
                'tmp_name' => $path,
                'error' => UPLOAD_ERR_OK,
                'size' => filesize($path),
            ),
        );

        $file = $_FILES['image'];
        $file['tmp_name'] = $path;

        $result = validate_uploaded_image_file($file, 1024 * 1024, 'Image');
        $this->assertFalse($result['ok']);
    }

    public function testValidateImageAcceptsRealPng()
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        $path = $this->makeTempFile($png, 'pixel.png');

        $file = array(
            'name' => 'pixel.png',
            'type' => 'image/png',
            'tmp_name' => $path,
            'error' => UPLOAD_ERR_OK,
            'size' => strlen($png),
        );

        $result = validate_uploaded_image_file($file, 1024 * 1024, 'Image');
        $this->assertTrue($result['ok']);
        $this->assertSame('png', $result['extension']);
    }

    public function testValidateImageRejectsEmptyFile()
    {
        $path = $this->makeTempFile('', 'empty.png');

        $file = array(
            'name' => 'empty.png',
            'type' => 'image/png',
            'tmp_name' => $path,
            'error' => UPLOAD_ERR_OK,
            'size' => 0,
        );

        $result = validate_uploaded_image_file($file, 1024 * 1024, 'Image');
        $this->assertFalse($result['ok']);
    }

    public function testSecureFilenameUsesRandomNameNotOriginal()
    {
        $name = upload_secure_filename('png');
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}_\d+\.png$/', $name);
        $this->assertStringNotContainsString('evil', $name);
    }

    public function testUploadMaxSizeConstants()
    {
        $this->assertSame(5 * 1024 * 1024, upload_max_image_bytes());
        $this->assertSame(2 * 1024 * 1024, upload_max_avatar_bytes());
        $this->assertSame(50 * 1024 * 1024, upload_max_video_bytes());
    }

    public function testUploadBeginHandlerSkipsWhenNoFile()
    {
        $file = array('error' => UPLOAD_ERR_NO_FILE);
        $begin = upload_begin_handler($file, 'old.jpg', 'Upload failed');
        $this->assertTrue($begin['ok']);
        $this->assertSame('old.jpg', $begin['filename']);
    }

    public function testUploadBeginHandlerReportsOversize()
    {
        $file = array('error' => UPLOAD_ERR_INI_SIZE);
        $begin = upload_begin_handler($file, '', 'Upload failed');
        $this->assertFalse($begin['ok']);
        $this->assertStringContainsString('too large', strtolower($begin['error']));
    }

    private function makeTempFile($contents, $name)
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('vc_upload_', true) . '_' . $name;
        file_put_contents($path, $contents);
        $this->tempFiles[] = $path;
        return $path;
    }
}
