<?php

declare(strict_types=1);

namespace BC\Controller\System;

use ApiPlatform\Controller\IApiRouter;
use ApiPlatform\Core\Singleton\IAuth;
use ApiPlatform\Core\Storage\ITokenStorage;
use BC\Core\Trait\Controller404Trait;
use Runway\Request\IRequest;
use Runway\Request\Response;

/**
 * Прячет API от неавторизованных: на любой запрос, кроме логина, отдаёт
 * обычную 404-страницу сайта, как будто никакого API здесь нет.
 */
readonly class ApiRouter implements IApiRouter {
    use Controller404Trait;

    public function __construct(
        private IApiRouter $inner,
        private IAuth $auth,
        private ITokenStorage $tokenStorage,
        private IRequest $request
    ) {
    }

    public function route(
        string  $apiVersion = "v1",
        ?string $section = null,
        ?string $subSection = null,
        ?string $identifier = null
    ): Response {
        if (!$this->isLoginRequest($section, $subSection, $identifier) && !$this->isAuthenticated()) {
            return $this->get404Controller()->run();
        }

        return $this->inner->route($apiVersion, $section, $subSection, $identifier);
    }

    private function isLoginRequest(?string $section, ?string $subSection, ?string $identifier): bool {
        return $this->request->getMethod() === 'POST'
               && $section === 'auth'
               && $subSection === null
               && $identifier === null;
    }

    private function isAuthenticated(): bool {
        return $this->auth->isAuthenticated(
            $this->tokenStorage->getToken()
        );
    }
}
