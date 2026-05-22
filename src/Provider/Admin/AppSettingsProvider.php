<?php

namespace BC\Provider\Admin;

use BC\Core\Config\IWebsiteSettings;
use BC\DTO\AppSettings\AppSettingsDTO;
use BC\DTO\AppSettings\ModuleDTO;
use BC\DTO\AppSettings\NavigationDTO;
use BC\Provider\IPathsProvider;

readonly class AppSettingsProvider implements IAppSettingsProvider {
    public function __construct(
        private IWebsiteSettings $websiteSettings,
        private IPathsProvider $pathsProvider,
    ) {
    }

    public function getAppSettings(): AppSettingsDTO {
        return new AppSettingsDTO(
            nav: $this->getNav(),
            modules: $this->getModules(),
            webRoot: $this->websiteSettings->getWebRoot(),
            staticRoot: $this->pathsProvider->getStaticWebPath()
        );
    }

    /**
     * @return NavigationDTO[]
     */
    private function getNav(): array {
        return [
            new NavigationDTO(
                label: 'Дашборд',
                path: '/admin',
                icon: 'dashboard',
            ),
            new NavigationDTO(
                label: 'Комментарии',
                path: '/admin/comments',
                icon: 'message',
                position: 1000
            )
        ];
    }

    /**
     * @return ModuleDTO[]
     */
    private function getModules(): array {
        return [];
    }
}
