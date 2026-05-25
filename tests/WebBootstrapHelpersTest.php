<?php



use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/Foundation/WebBootstrapHelpers.php';

final class WebBootstrapHelpersTest extends TestCase
{
    public function testEnvBoolParsesTrueVariants(): void
    {
        $_ENV['APP_DEBUG'] = 'true';
        $this->assertTrue(zt_env_bool('APP_DEBUG'));

        $_ENV['APP_DEBUG'] = '1';
        $this->assertTrue(zt_env_bool('APP_DEBUG'));

        $_ENV['APP_DEBUG'] = 'yes';
        $this->assertTrue(zt_env_bool('APP_DEBUG'));

        $_ENV['APP_DEBUG'] = 'on';
        $this->assertTrue(zt_env_bool('APP_DEBUG'));
    }

    public function testEnvBoolParsesFalseVariants(): void
    {
        $_ENV['APP_DEBUG'] = 'false';
        $this->assertFalse(zt_env_bool('APP_DEBUG', true));

        $_ENV['APP_DEBUG'] = '0';
        $this->assertFalse(zt_env_bool('APP_DEBUG', true));
    }
}
