<?php



use Foundation\Zantech;
use PHPUnit\Framework\TestCase;

final class ZantechKernelTest extends TestCase
{
    public function testZantechKernelLivesInFoundationNamespace(): void
    {
        $this->assertTrue(class_exists(Zantech::class));
        $this->assertFalse(class_exists('Library\\Zantech'));
    }
}
