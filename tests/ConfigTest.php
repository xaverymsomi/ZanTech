<?php



use Config\Config;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    protected function tearDown(): void
    {
        Config::clear();
        unset($_ENV['APP_DEBUG'], $_ENV['DATABASE_HOST'], $_ENV['SECURITY_CSRF']);
        putenv('APP_DEBUG');
        putenv('DATABASE_HOST');
        putenv('SECURITY_CSRF');
    }

    public function testGetReadsLoadedDottedKeys(): void
    {
        Config::load([
            'app' => [
                'debug' => true,
            ],
            'database' => [
                'host' => 'localhost',
            ],
        ]);

        $this->assertTrue(Config::get('app.debug'));
        $this->assertSame('localhost', Config::get('database.host'));
        $this->assertSame('fallback', Config::get('database.port', 'fallback'));
    }

    public function testGetFallsBackToEnvironmentStyleKey(): void
    {
        $_ENV['SECURITY_CSRF'] = 'true';
        $_ENV['DATABASE_HOST'] = 'sql.example.test';

        $this->assertTrue(Config::get('security.csrf'));
        $this->assertSame('sql.example.test', Config::get('database.host'));
    }

    public function testGetNormalizesEnvironmentValues(): void
    {
        $_ENV['APP_DEBUG'] = 'false';
        $_ENV['DATABASE_PORT'] = '1433';

        $this->assertFalse(Config::get('app.debug', true));
        $this->assertSame(1433, Config::get('database.port'));
    }

    public function testSetWritesDottedKeys(): void
    {
        Config::set('security.csrf', false);

        $this->assertFalse(Config::get('security.csrf', true));
    }
}
