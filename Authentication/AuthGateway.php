<?php

declare(strict_types=1);

namespace Authentication;

interface AuthGateway
{
    public function isLogged(): bool;
    public function logout(): void;
}
