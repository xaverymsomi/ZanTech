<?php



use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/Foundation/AppLoaderFunctions.php';

final class AppLoaderTest extends TestCase
{
    public function testNormalizePathStripsQueryString(): void
    {
        $this->assertSame('/api/users', zt_normalize_path('/api/users?page=1'));
    }

    public function testNormalizePathCollapsesSlashes(): void
    {
        $this->assertSame('/api/users', zt_normalize_path('////api///users'));
    }

    public function testNormalizePathRemovesDotSegments(): void
    {
        $this->assertSame('/api/users', zt_normalize_path('/api/./x/../users'));
    }

    public function testDetectNamespaceApi(): void
    {
        $this->assertSame('api', zt_detect_namespace('/api/users'));
    }

    public function testForbiddenBootProbe(): void
    {
        $this->assertTrue(zt_is_forbidden_boot_probe('/api.php'));
        $this->assertFalse(zt_is_forbidden_boot_probe('/api/users'));
    }
}
