<?php

declare(strict_types=1);

namespace BC\Controller\System;

use ApiPlatform\Controller\ApiPathTrait;
use BC\Core\Auth\IAuth;
use BC\Core\Trait\Controller404Trait;
use Runway\Controller\IMethodNotAllowedController;
use Runway\Request\IRequest;
use Runway\Request\Response;

/**
 * 405 на путь API выдаёт, что эндпоинт существует: неавторизованным вместо
 * него обычная 404-страница сайта (см. ApiController)
 */
readonly class MethodNotAllowedController implements IMethodNotAllowedController {
    use ApiPathTrait;
    use Controller404Trait;

    public function __construct(
        private IMethodNotAllowedController $inner,
        private IAuth $auth,
        private IRequest $request,
        private string $pathPrefix = '/api'
    ) {
    }

    public function run(array $allowedMethods): Response {
        if ($this->isApiPath($this->request->getPath()) && !$this->auth->isAuthenticated()) {
            return $this->get404Controller()->run();
        }

        return $this->inner->run($allowedMethods);
    }
}
