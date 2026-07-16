<?php

declare(strict_types=1);

namespace BC\Core\Storage;

use ApiPlatform\Core\Storage\ITokenStorage;

class CookieTokenStorage implements ITokenStorage {
    public function getToken(): string {
        return (string) ($_COOKIE['token'] ?? '');
    }
}
