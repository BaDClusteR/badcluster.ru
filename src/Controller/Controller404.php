<?php

namespace BC\Controller;

use ApiPlatform\Controller\System\IApiExceptionResponseController;
use ApiPlatform\Exception\EndpointNotFoundException;
use BC\Core\Response\HtmlResponse;
use BC\Core\Trait\AuthTrait;
use BC\Core\Trait\RequestTrait;
use BC\Widget\Page\Page404;
use Runway\Controller\IController404;
use Runway\Request\Response;
use Runway\Singleton\Container;

class Controller404 implements IController404 {
    use AuthTrait;
    use RequestTrait;

    public function __construct(
        protected string $apiPathPrefix = '/admin/api'
    ) {
    }

    public function run(): Response {
        // Админке на несуществующий путь API — JSON-ошибка, как от самого API.
        // Остальным — страница сайта: API от них спрятан (см. System\ApiController)
        if ($this->isApiPath() && $this->getAuth()->isAuthenticated()) {
            return $this->getApiErrorResponseController()->getResponse(
                new EndpointNotFoundException($this->getRequest()->getPath(), $this->getRequest()->getMethod())
            );
        }

        return new HtmlResponse(
            404,
            new Page404()->render()
        );
    }

    private function isApiPath(): bool {
        $prefix = rtrim($this->apiPathPrefix, '/');
        $path = $this->getRequest()->getPath();

        return $prefix !== '' && ($path === $prefix || str_starts_with($path, "$prefix/"));
    }

    private function getApiErrorResponseController(): IApiExceptionResponseController {
        return Container::getInstance()->getService(IApiExceptionResponseController::class);
    }
}
