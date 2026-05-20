<?php



use Exceptions\ValidationException;
use PHPUnit\Framework\TestCase;
use Validation\Validator;

final class ValidatorTest extends TestCase
{
    public function testValidateReturnsOnlyRuledFields(): void
    {
        $validated = Validator::make([
            'name' => 'Ada',
            'email' => 'ada@example.com',
            'ignored' => 'value',
        ])->validate([
            'name' => 'required|min:2',
            'email' => 'required|email',
        ]);

        $this->assertSame([
            'name' => 'Ada',
            'email' => 'ada@example.com',
        ], $validated);
    }

    public function testValidateThrowsWithErrors(): void
    {
        try {
            Validator::make([
                'name' => '',
                'email' => 'bad-email',
            ])->validate([
                'name' => 'required',
                'email' => 'email',
            ]);

            $this->fail('Expected validation exception was not thrown.');
        } catch (ValidationException $e) {
            $errors = $e->getContext()['errors'] ?? [];

            $this->assertArrayHasKey('name', $errors);
            $this->assertArrayHasKey('email', $errors);
        }
    }

    public function testNumericMinAndMaxRules(): void
    {
        $validated = Validator::make([
            'quantity' => 4,
        ])->validate([
            'quantity' => 'numeric|min:2|max:5',
        ]);

        $this->assertSame(['quantity' => 4], $validated);
    }
}
