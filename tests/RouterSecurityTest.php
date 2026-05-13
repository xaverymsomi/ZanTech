<?php

declare(strict_types=1);

use Library\RouterSecurity;
use PHPUnit\Framework\TestCase;
use Exceptions\RouterException;

final class RouterSecurityTest extends TestCase
{
    public function testSlugToStudly(): void
    {
        $this->assertSame('MyModuleName', RouterSecurity::slugToStudly('my-module_name'));
        $this->assertSame('Login', RouterSecurity::slugToStudly('login'));
    }

    public function testValidateControllerSlugAcceptsSafe(): void
    {
        RouterSecurity::validateControllerSlug('login');
        RouterSecurity::validateControllerSlug('my_module-1');
        $this->assertTrue(true);
    }

    public function testValidateControllerSlugRejectsUnsafe(): void
    {
        $this->expectException(RouterException::class);
        RouterSecurity::validateControllerSlug('../etc/passwd');
    }

    public function testValidateMethodBlocksMagicMethods(): void
    {
        $this->expectException(RouterException::class);
        RouterSecurity::validateMethodName('__destruct');
    }

    public function testSanitizeParamsRejectsTooLong(): void
    {
        $this->expectException(RouterException::class);
        RouterSecurity::sanitizeParams([str_repeat('a', 1000)]);
    }

    public function testRedactSensitiveRecursive(): void
    {
        $input = [
            'user' => [
                'password' => 'secret',
                'profile' => ['token' => 'abc'],
            ],
            'ok' => 'yes'
        ];

        $out = RouterSecurity::redactSensitive($input);
        $this->assertSame('***', $out['user']['password']);
        $this->assertSame('***', $out['user']['profile']['token']);
        $this->assertSame('yes', $out['ok']);
    }
}
