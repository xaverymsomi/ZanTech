<?php



use Database\Model;
use PHPUnit\Framework\TestCase;

final class ModelNamespaceTest extends TestCase
{
    public function testBaseModelLivesInDatabaseNamespace(): void
    {
        $this->assertTrue(class_exists(Model::class));
        $this->assertFalse(class_exists('Library\\Model'));
    }

    public function testModelConcernsLiveInDatabaseNamespace(): void
    {
        $this->assertTrue(trait_exists('Database\\Model\\Concerns\\HasAttributes'));
        $this->assertTrue(trait_exists('Database\\Model\\Concerns\\HasDataTable'));
        $this->assertTrue(trait_exists('Database\\Model\\Concerns\\HasRelationships'));
    }
}
