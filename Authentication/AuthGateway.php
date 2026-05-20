<?php

namespace Authentication;

interface AuthGateway
{
    public function isLogged(): bool;
    public function logout(): void;
}
