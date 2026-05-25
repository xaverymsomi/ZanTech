<?php



use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/Foundation/AppLoaderFunctions.php';

final class ResolveEntryIntegrationTest extends TestCase
{
    public function testResolveEntryFileWorksWhenFileExists(): void
    {
        $foundationDir = realpath(__DIR__ . '/../src/Foundation') ?: __DIR__;
        $entry = zt_resolve_entry_file($foundationDir, 'web');

        $this->assertStringEndsWith(DIRECTORY_SEPARATOR . 'web.php', $entry);
        $this->assertFileIsReadable($entry);
    }
}
