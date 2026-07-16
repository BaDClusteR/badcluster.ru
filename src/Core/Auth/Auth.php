<?php

declare(strict_types=1);

namespace BC\Core\Auth;

use ApiPlatform\Core\Singleton\IAuth as IApiAuth;
use ApiPlatform\Core\Storage\ITokenStorage;
use Runway\Env\Provider\IEnvVariablesProvider;
use Runway\Singleton;
use Runway\Singleton\Container;

class Auth extends Singleton implements IAuth {
    public function __construct(
        private readonly IEnvVariablesProvider $envVariablesProvider,
        private readonly ITokenStorage $tokenStorage
    ) {
    }

    private function getApiAuth(): IApiAuth {
        return Container::getInstance()->getService(IApiAuth::class);
    }

    public function isAuthenticated(): bool {
        return $this->getApiAuth()->isAuthenticated(
            $this->tokenStorage->getToken()
        );
    }

    public function isCredentialsCorrect(string $login, string $password): bool {
        // hash_equals — сравнение за постоянное время, чтобы по времени ответа
        // нельзя было побайтово подбирать хеш
        return hash_equals(
            $this->getAuthHash(),
            $this->getHash($this->getSaltedCredentials($login, $password))
        );
    }

    private function getSaltedCredentials(string $login, string $password): string {
        return str_replace(
            ['{{LOGIN}}', '{{PASSWORD}}'],
            [$login, $password],
            $this->getAuthSalt()
        );
    }

    private function getHash(string $string): string {
        return sha1($string);
    }

    private function getAuthSalt(): string {
        return $this->envVariablesProvider->getEnvVariable('AUTH_SALT');
    }

    private function getAuthHash(): string {
        return $this->envVariablesProvider->getEnvVariable('AUTH_HASH');
    }
}
