<?php

declare(strict_types=1);

namespace BC\Controller\System;

use ApiPlatform\Controller\IApiController;
use BC\Core\Auth\IAuth;
use BC\Core\Trait\Controller404Trait;
use Runway\Request\IRequest;
use Runway\Request\Response;

/**
 * Прячет API от неавторизованных: на любой запрос, кроме логина, отдаёт
 * обычную 404-страницу сайта, как будто никакого API здесь нет.
 */
readonly class ApiController implements IApiController {
    use Controller404Trait;

    public function __construct(
        private IApiController $inner,
        private IAuth $auth,
        private IRequest $request,
        private string $pathPrefix = '/api'
    ) {
    }

    public function handle(string ...$pathParameters): Response {
        if (!$this->isLoginRequest() && !$this->auth->isAuthenticated()) {
            return $this->get404Controller()->run();
        }

        return $this->inner->handle(...$pathParameters);
    }

    private function isLoginRequest(): bool {
        return strtoupper($this->request->getMethod()) === 'POST'
               && rtrim($this->request->getPath(), '/') === rtrim($this->pathPrefix, '/') . '/auth/login';
    }
}
