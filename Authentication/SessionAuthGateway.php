<?php

namespace Authentication;

final class SessionAuthGateway implements AuthGateway
{
    public function isLogged(): bool
    {
        return Auth::isLogged();
    }

    public function logout(): void
    {
        Auth::logout();
    }
}
