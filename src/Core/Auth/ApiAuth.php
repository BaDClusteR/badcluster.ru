<?php

declare(strict_types=1);

namespace BC\Core\Auth;

use ApiPlatform\Core\Singleton\IAuth;
use ApiPlatform\Model\Token;

readonly class ApiAuth implements IAuth {
    public function __construct(
        private \BC\Core\Auth\IAuth $auth,
        private IAuth $inner
    ) {
    }

    public function isTokenValid(string $token): bool {
        if (!$token) {
            return false;
        }

        return $this->inner->isTokenValid($token);
    }

    public function isAuthenticated(string $token): bool {
        return $token && $this->isTokenValid($token);
    }

    public function isCredentialsCorrect(string $login, string $password): bool {
        return $this->auth->isCredentialsCorrect($login, $password);
    }

    public function generateToken(): Token {
        return $this->inner->generateToken();
    }

    public function updateTokenLastActiveDate(string $token): void {
        $this->inner->updateTokenLastActiveDate($token);
    }
}
