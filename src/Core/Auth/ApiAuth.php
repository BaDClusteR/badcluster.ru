<?php

namespace BC\Core\Auth;

use ApiPlatform\Core\Singleton\IAuth;
use ApiPlatform\Model\Token;

readonly class ApiAuth implements IAuth
{
    public function __construct(
        private \BC\Core\Auth\IAuth $auth
    ) {
    }

    public function isTokenValid(string $token): bool
    {
        return $this->auth->isAuthenticated();
    }

    public function isAuthenticated(string $token): bool
    {
        return $this->auth->isAuthenticated();
    }

    public function isCredentialsCorrect(string $login, string $password): bool {
        return true;
    }

    public function generateToken(): Token {
        return new Token();
    }

    public function updateTokenLastActiveDate(string $token): void {
    }
}
