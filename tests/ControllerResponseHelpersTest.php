<?php



use Http\Response;
use Http\Controller;
use PHPUnit\Framework\TestCase;

final class TestableController extends Controller
{
    public function makeJson(array $payload, int $status = 200): Response
    {
        return $this->responseJson($payload, $status);
    }

    public function makeSuccess(): Response
    {
        return $this->responseSuccess(201, 'Created', ['id' => 10]);
    }

    public function makeError(): Response
    {
        return $this->responseError('Nope', 422, ['field' => 'name']);
    }

    public function makeRedirect(string $to): Response
    {
        return $this->responseRedirect($to);
    }
}

final class ControllerResponseHelpersTest extends TestCase
{
    public function testResponseJsonHelper(): void
    {
        $response = (new TestableController())->makeJson(['ok' => true], 202);

        $this->assertSame(202, $response->status());
        $this->assertSame('{"ok":true}', $response->content());
        $this->assertSame('application/json; charset=UTF-8', $response->headers()['Content-Type']);
    }

    public function testResponseSuccessAndErrorHelpers(): void
    {
        $success = (new TestableController())->makeSuccess();
        $error = (new TestableController())->makeError();

        $this->assertSame(201, $success->status());
        $this->assertSame(422, $error->status());

        $successPayload = json_decode($success->content(), true);
        $errorPayload = json_decode($error->content(), true);

        $this->assertTrue($successPayload['ok']);
        $this->assertSame(10, $successPayload['id']);
        $this->assertFalse($errorPayload['ok']);
        $this->assertSame('name', $errorPayload['field']);
    }

    public function testResponseRedirectHelperRejectsExternalTargets(): void
    {
        $safe = (new TestableController())->makeRedirect('dashboard');
        $unsafe = (new TestableController())->makeRedirect('//evil.example');

        $this->assertSame('/dashboard', $safe->headers()['Location']);
        $this->assertSame('/', $unsafe->headers()['Location']);
    }
}
