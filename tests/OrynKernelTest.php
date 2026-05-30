<?php

use Foundation\Oryn;
use PHPUnit\Framework\TestCase;

final class OrynKernelTest extends TestCase
{
    public function testOrynKernelLivesInFoundationNamespace(): void
    {
        $this->assertTrue(class_exists(Oryn::class));
        $this->assertFalse(class_exists('Library\\Oryn'));
    }
}
