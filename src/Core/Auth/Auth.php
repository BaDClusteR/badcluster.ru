<?php

namespace BC\Core\Auth;

use Runway\Singleton;

class Auth extends Singleton implements IAuth
{
    public function isAuthenticated(): bool
    {
        return true;
    }

    public function checkCredentials(string $login, string $password): bool
    {
        return true;
    }
}
