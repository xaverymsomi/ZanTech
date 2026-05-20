<?php

use Http\Request;
use Exceptions\ValidationException;
use PHPUnit\Framework\TestCase;

final class RequestValidationTest extends TestCase
{
    public function testValidateReturnsOnlyValidFields(): void
    {
        $request = Request::fake(
            ['id' => '123'],
            ['name' => 'Alice', 'email' => 'alice@example.com'],
            ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/test'],
            []
        );

        $validated = $request->validate([
            'name' => 'required',
            'email' => 'required|email',
        ]);

        $this->assertSame(['name' => 'Alice', 'email' => 'alice@example.com'], $validated);
    }

    public function testValidateThrowsValidationException(): void
    {
        $this->expectException(ValidationException::class);

        $request = Request::fake([], [], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/test'], []);
        $request->validate(['name' => 'required']);
    }
}
