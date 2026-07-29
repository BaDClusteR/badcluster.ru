<?php

declare(strict_types=1);

namespace BC\Controller;

use BC\Core\Auth\IAuth;
use BC\Core\Response\HtmlResponse;
use BC\Core\Trait\Controller404Trait;
use BC\Provider\Admin\IAppSettingsProvider;
use Runway\Request\IRequest;
use Runway\Request\Response;
use Runway\Singleton\Container;
use Runway\Singleton\IKernel;

readonly class Admin {
    use Controller404Trait;

    public function __construct(
        private IRequest $request,
        private IAppSettingsProvider $appSettingsProvider,
        private IAuth $auth
    ) {
    }

    public function index(): Response {
        if (!$this->auth->isAuthenticated()) {
            return $this->get404Controller()->run();
        }

        return $this->renderAdminArea();
    }

    public function renderPublicPage(): Response {
        return $this->renderAdminArea();
    }

    private function renderAdminArea(): HtmlResponse {
        return new HtmlResponse(
            200,
            new \BC\Widget\Admin()->render([
                'devMode'     => $this->isInDevMode(),
                'webRoot'     => 'http://localhost:5173/static/app',
                'appSettings' => $this->appSettingsProvider->getAppSettings()
            ])
        );
    }

    private function isInDevMode(): bool {
        return Container::getInstance()->getService(IKernel::class)->isDebugMode()
               && (
                   $this->request->getGetParameter('dev')->asString() === '1'
                   || file_exists(PROJECT_ROOT . '/app/node_modules/.vite')
               );
    }
}
