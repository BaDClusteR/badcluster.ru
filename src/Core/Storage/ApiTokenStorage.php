<?php

declare(strict_types=1);

namespace BC\Core\Storage;

use ApiPlatform\Core\Storage\ITokenStorage;

/**
 * The header takes priority (external apps, console), the cookie is
 * a fallback for browser requests (set as HttpOnly on /auth).
 */
readonly class ApiTokenStorage implements ITokenStorage {
    public function __construct(
        private ITokenStorage      $inner,
        private CookieTokenStorage $cookieTokenStorage
    ) {
    }

    public function getToken(): string {
        return $this->inner->getToken() ?: $this->cookieTokenStorage->getToken();
    }
}
