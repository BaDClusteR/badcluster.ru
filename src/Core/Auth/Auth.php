<?php

declare(strict_types=1);

namespace BC\Core\Auth;

use ApiPlatform\Core\Singleton\IAuth as IApiAuth;
use ApiPlatform\Core\Storage\ITokenStorage;

/**
 * Залогинен ли админ: сессия API platform (cookie api_session или заголовок
 * API_TOKEN_HEADER). Логин, пароль и сессии живут в API platform, см. IApiAuth
 */
readonly class Auth implements IAuth {
    public function __construct(
        private IApiAuth $apiAuth,
        private ITokenStorage $tokenStorage
    ) {
    }

    public function isAuthenticated(): bool {
        $token = $this->tokenStorage->getToken();

        return $token !== '' && $this->apiAuth->isAuthenticated($token);
    }
}
