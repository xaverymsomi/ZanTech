<?php



use Http\Request;
use Http\Response;
use PHPUnit\Framework\TestCase;

final class HttpRequestResponseTest extends TestCase
{
    public function testRequestReadsJsonBeforePostAndQuery(): void
    {
        $request = Request::fake(
            query: ['name' => 'query'],
            request: ['name' => 'post'],
            json: ['name' => 'json']
        );

        $this->assertSame('json', $request->input('name'));
        $this->assertSame(['name' => 'json'], $request->all());
    }

    public function testRequestNormalizesMethodPathAndHeaders(): void
    {
        $request = Request::fake(
            query: ['url' => '/dashboard/index?page=2'],
            server: [
                'REQUEST_METHOD' => 'post',
                'HTTP_X_CSRF_TOKEN' => 'abc',
                'REMOTE_ADDR' => '127.0.0.1',
            ]
        );

        $this->assertSame('POST', $request->method());
        $this->assertSame('/dashboard/index', $request->path());
        $this->assertSame('abc', $request->header('X-CSRF-Token'));
        $this->assertSame('127.0.0.1', $request->ip());
    }

    public function testResponseFactories(): void
    {
        $json = Response::json(['ok' => true], 201);
        $redirect = Response::redirect('/login');

        $this->assertSame(201, $json->status());
        $this->assertSame('{"ok":true}', $json->content());
        $this->assertSame('/login', $redirect->headers()['Location']);
    }
}
