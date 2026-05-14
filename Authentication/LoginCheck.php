<?php

declare(strict_types=1);

namespace Authentication;

use Exceptions\AuthException;
use Exceptions\RedirectException;
use Logging\Log;

final class LoginCheck
{
    public function __construct(private readonly AuthGateway $auth = new SessionAuthGateway()) {}

    /**
     * Called for ALL protected routes.
     *
     * @throws AuthException
     */
    public function protect(?string $route = null): void
    {
        $route = strtolower((string)$route);

        // Block forbidden internal routes
        if ($route !== '' && in_array($route, ZT_BLOCKED_ROUTES, true)) {
            Log::sysLog("SECURITY → BLOCKED ROUTE ACCESS: {$route}");

            // MUST destroy session + cookie to reduce risk after suspicious access
            $this->auth->logout();

            throw new AuthException('Forbidden route access detected.');
        }

        // User not authenticated
        if (!$this->auth->isLogged()) {
            Log::sysLog("AUTH → NOT LOGGED IN | ROUTE: {$route}");
            throw new AuthException('Authentication required.');
        }
    }

    /**
     * Called for PUBLIC routes (login, logout, autorun).
     *
     * @throws RedirectException
     */
    public function destroy(?string $route = null): void
    {
        $route = strtolower((string)$route);

        // Logged-in user visiting /login => redirect to dashboard
        if ($this->auth->isLogged() && $route === ZT_ROUTE_LOGIN) {
            Log::sysLog("AUTH → LOGGED USER ATTEMPTED LOGIN PAGE");
            throw new RedirectException(ZT_ROUTE_DASHBOARD, 302);
        }

        // If logged-out user hits /logout, allow controller to handle it gracefully
        if (!$this->auth->isLogged() && $route === ZT_ROUTE_LOGOUT) {
            return;
        }
    }
}
