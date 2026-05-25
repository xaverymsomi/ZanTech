<?php

use Modules\Permission\Service\PermissionValidator;
use PHPUnit\Framework\TestCase;

final class PermissionValidatorTest extends TestCase
{
    public function testValidateDomainAndRowValueRejectsBadDomain(): void
    {
        $errors = PermissionValidator::validateDomainAndRowValue([
            'domain' => 'mx_user; DROP TABLE',
            'id' => 'abc1234567890',
        ]);

        $this->assertArrayHasKey('domain', $errors);
    }

    public function testValidateDomainAndRowValueRejectsNonObjectPayload(): void
    {
        $errors = PermissionValidator::validateDomainAndRowValue(null);

        $this->assertArrayHasKey('payload', $errors);
    }

    public function testValidateDomainAndRowValueRejectsBadId(): void
    {
        $errors = PermissionValidator::validateDomainAndRowValue([
            'domain' => 'mx_user',
            'id' => "bad'id",
        ]);

        $this->assertArrayHasKey('id', $errors);
    }

    public function testValidateCheckedRowsOk(): void
    {
        $errors = PermissionValidator::validateGroupPermissionPayload([
            'id' => 10,
            'new_data' => [
                [1, 5],
                [0, 6],
            ],
        ]);

        $this->assertSame([], $errors);
    }

    public function testValidateCheckedRowsRejectsFkId(): void
    {
        $errors = PermissionValidator::validateGroupPermissionPayload([
            'id' => 10,
            'new_data' => [
                [1, 0],
            ],
        ]);

        $this->assertArrayHasKey('new_data', $errors);
    }

    public function testSanitizePermissionKey(): void
    {
        $this->assertSame('view_permissions', PermissionValidator::sanitizePermissionKey('VIEW Permissions!!'));
    }

    public function testAssertSafeDomainThrowsOnUnsafeTableName(): void
    {
        $this->expectException(InvalidArgumentException::class);

        PermissionValidator::assertSafeDomain('mx_user;DROP');
    }
}
