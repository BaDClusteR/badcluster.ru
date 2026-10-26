<?php

declare(strict_types=1);

namespace BC\Tests\Fixture\Auth;

use ApiPlatform\Core\Auth\DTO\SessionDTO;
use ApiPlatform\Core\Singleton\IAuth;
use Throwable;

/**
 * The authorization of API platform: the tokens of the active sessions are given, setCredentials() is recorded.
 */
final class ApiAuthStub implements IAuth {
    /** @var list<array{string, string}> */
    public array $credentials = [];

    /** @var list<string> */
    public array $checkedTokens = [];

    /**
     * @param list<string> $activeTokens
     */
    public function __construct(
        public array      $activeTokens = [],
        public ?Throwable $setCredentialsException = null
    ) {}

    public function isEnabled(): bool {
        return true;
    }

    public function hasCredentials(): bool {
        return $this->credentials !== [];
    }

    public function logIn(string $login, string $password, string $ip, string $userAgent): ?SessionDTO {
        return null;
    }

    public function isAuthenticated(string $token): bool {
        $this->checkedTokens[] = $token;

        return in_array($token, $this->activeTokens, true);
    }

    public function logOut(string $token): void {
        $this->activeTokens = array_values(array_diff($this->activeTokens, [$token]));
    }

    public function setCredentials(string $login, string $password): void {
        if ($this->setCredentialsException !== null) {
            throw $this->setCredentialsException;
        }

        $this->credentials[] = [$login, $password];
    }
}
