<?php



use Authentication\DualControl;
use PHPUnit\Framework\TestCase;

final class DualControlTest extends TestCase
{
    public function testDualControlLivesInAuthenticationNamespace(): void
    {
        $this->assertTrue(class_exists(DualControl::class));
        $this->assertFalse(class_exists('Library\\DualControl'));
    }
}
