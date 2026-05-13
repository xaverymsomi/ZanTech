<?php

declare(strict_types=1);

namespace Library;

use Authentication\Session;

final class CaptchaLib
{
    private string $permitted_chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    private $image;
    private string $captcha_string = '';
    private int $time_out = ZT_CAPTCHA_TIMEOUT; // minutes

    private function ensureSession(): void
    {
        // Centralized hardened session init
        Session::init();
    }

    private function generateString(int $strength = 5): string
    {
        $input = $this->permitted_chars;
        $inputLength = strlen($input);

        $randomString = '';
        for ($i = 0; $i < $strength; $i++) {
            $randomString .= $input[random_int(0, $inputLength - 1)];
        }

        return $randomString;
    }

    private function renderBackground(): void
    {
        $image = imagecreatetruecolor(170, 30);
        imageantialias($image, true);

        $colors = [];

        $red = rand(125, 175);
        $green = rand(125, 175);
        $blue = rand(125, 175);

        for ($i = 0; $i < 5; $i++) {
            $colors[] = imagecolorallocate($image, $red - 20 * $i, $green - 20 * $i, $blue - 20 * $i);
        }

        imagefill($image, 0, 0, $colors[0]);

        for ($i = 0; $i < 10; $i++) {
            imagesetthickness($image, rand(2, 10));
            $rectColor = $colors[rand(1, 4)];
            imagerectangle(
                $image,
                rand(-10, 190),
                rand(-10, 10),
                rand(-10, 190),
                rand(40, 60),
                $rectColor
            );
        }

        $this->image = $image;
    }

    /**
     * Outputs the image. Caller must ensure session is initialized and captcha_string is set.
     */
    private function renderString(): void
    {
        $black = imagecolorallocate($this->image, 0, 0, 0);
        $white = imagecolorallocate($this->image, 255, 255, 255);
        $textColors = [$black, $white];

        $stringLength = 6;
        $captchaString = $this->generateString($stringLength);

        $this->captcha_string = $captchaString;

        for ($i = 0; $i < $stringLength; $i++) {
            $letterSpace = (int) round(170 / $stringLength);
            $initial = 15;

            imagestring(
                $this->image,
                5, // built-in GD font 1..5 (63 is not valid for imagestring)
                $initial + $i * $letterSpace,
                rand(5, 10),
                $captchaString[$i],
                $textColors[rand(0, 1)]
            );
        }

        if (!headers_sent()) {
            header('Content-Type: image/png');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
        }

        imagepng($this->image);
        imagedestroy($this->image);
    }

    public function generateCapture(): void
    {
        $this->ensureSession();

        // If session couldn't start (headers already sent etc.), fail safely
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $this->renderBackground();
        $this->renderString();

        Session::set(ZT_SESS_CAPTCHA_TS, time());
        Session::set(ZT_SESS_CAPTCHA_CODE, $this->captcha_string);
    }

    public function testCapture(mixed $input_string): array
    {
        $this->ensureSession();

        $response = ['status' => 404, 'message' => 'unknown reason'];

        if (session_status() !== PHP_SESSION_ACTIVE) {
            return ['status' => 404, 'message' => 'session unavailable'];
        }

        $storedTs   = Session::get(ZT_SESS_CAPTCHA_TS);
        $storedCode = Session::get(ZT_SESS_CAPTCHA_CODE);

        if (!is_int($storedTs) || !is_string($storedCode) || $storedCode === '') {
            return ['status' => 404, 'message' => 'captcha not initialized'];
        }

        $input = strtoupper(trim((string) $input_string));

        $elapsedSeconds = time() - $storedTs;
        $elapsedMinutes = $elapsedSeconds / 60;

        if ($elapsedMinutes > $this->time_out) {
            return ['status' => 404, 'message' => 'time out'];
        }

        if (hash_equals($storedCode, $input)) {
            return ['status' => 200, 'message' => 'captcha passed'];
        }

        return ['status' => 404, 'message' => 'captcha failed'];
    }

    public function refreshCapture(): void
    {
        $this->ensureSession();

        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        Session::set(ZT_SESS_CAPTCHA_TS, null);
        Session::set(ZT_SESS_CAPTCHA_CODE, null);

        $this->generateCapture();
    }
}
