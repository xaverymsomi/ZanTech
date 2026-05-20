<?php



use PHPUnit\Framework\TestCase;
use Authentication\LoginCheck;
use Authentication\AuthGateway;
use Exceptions\AuthException;
use Exceptions\RedirectException;

final class FakeAuthGateway implements AuthGateway
{
    public function __construct(private bool $loggedIn) {}
    public bool $logoutCalled = false;

    public function isLogged(): bool { return $this->loggedIn; }
    public function logout(): void { $this->logoutCalled = true; }
}

final class LoginCheckTest extends TestCase
{
    public function testProtectThrowsWhenNotLoggedIn(): void
    {
        $auth = new FakeAuthGateway(false);
        $check = new LoginCheck($auth);

        $this->expectException(AuthException::class);
        $check->protect('dashboard');
    }

    public function testDestroyRedirectsLoggedInUserFromLogin(): void
    {
        $auth = new FakeAuthGateway(true);
        $check = new LoginCheck($auth);

        $this->expectException(RedirectException::class);
        $check->destroy('login');
    }

    public function testProtectBlocksInternalRouteAndLogsOut(): void
    {
        $auth = new FakeAuthGateway(true);
        $check = new LoginCheck($auth);

        try {
            $check->protect('vendor');
            $this->fail('Expected exception not thrown');
        } catch (AuthException) {
            $this->assertTrue($auth->logoutCalled);
        }
    }
}
