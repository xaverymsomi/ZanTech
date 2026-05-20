<?php



use Authentication\CaptchaLib;
use PHPUnit\Framework\TestCase;

if (!defined('ZT_CAPTCHA_TIMEOUT')) {
    define('ZT_CAPTCHA_TIMEOUT', 30);
}
if (!defined('ZT_SESS_CAPTCHA_TS')) {
    define('ZT_SESS_CAPTCHA_TS', 'capture_activity_ts');
}
if (!defined('ZT_SESS_CAPTCHA_CODE')) {
    define('ZT_SESS_CAPTCHA_CODE', 'captcha_string');
}

final class CaptchaLibTest extends TestCase
{
    public function testCaptchaLibLivesInAuthenticationNamespace(): void
    {
        $this->assertTrue(class_exists(CaptchaLib::class));
        $this->assertFalse(class_exists('Library\\CaptchaLib'));
    }

    public function testTestCaptureFailsSafelyWhenSessionIsUnavailableInCli(): void
    {
        $response = (new CaptchaLib())->testCapture('ABC123');

        $this->assertSame(404, $response['status']);
        $this->assertSame('session unavailable', $response['message']);
    }
}
