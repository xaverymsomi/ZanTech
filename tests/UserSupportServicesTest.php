<?php



use PHPUnit\Framework\TestCase;
use Services\EmailValidator;
use Services\LogSanitizer;
use Services\MXPhoneNumber;
use Services\NameValidator;
use Services\RBAC;

final class UserSupportServicesTest extends TestCase
{
    public function testPhoneNormalizerAcceptsTanzaniaMobileFormats(): void
    {
        $this->assertSame('255712345678', MXPhoneNumber::normalizeTz('0712 345 678'));
        $this->assertSame('255712345678', MXPhoneNumber::normalizeTz('255712345678'));
        $this->assertNull(MXPhoneNumber::normalizeTz('123'));
    }

    public function testValidatorsAndSanitizers(): void
    {
        $this->assertTrue(EmailValidator::isValid('user@example.com'));
        $this->assertFalse(EmailValidator::isValid('not-an-email'));
        $this->assertTrue(NameValidator::isValid("Ada Lovelace"));
        $this->assertFalse(NameValidator::isValid('<script>'));
        $this->assertSame('ad*@example.com', LogSanitizer::maskEmail('ada@example.com'));
    }

    public function testRbacAllowsManagingSameOrLowerPriorityGroupOnly(): void
    {
        $this->assertTrue(RBAC::canEditGroup(1, 2));
        $this->assertTrue(RBAC::canEditGroup(2, 2));
        $this->assertFalse(RBAC::canEditGroup(3, 2));
        $this->assertFalse(RBAC::canEditGroup(1, null));
    }
}
