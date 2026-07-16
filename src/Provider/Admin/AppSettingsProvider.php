<?php

declare(strict_types=1);

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
            staticRoot: $this->pathsProvider->getStaticWebPath(),
            extra: []
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
                label: 'Медиа',
                path: '',
                icon: file_get_contents(__DIR__ . '/../../../app/assets/media.svg'),
                position: 9900,
                children: [
                    new NavigationDTO(
                        label: 'Медиатека',
                        path: '/admin/media',
                        icon: file_get_contents(__DIR__ . '/../../../app/assets/media.svg')
                    ),
                    new NavigationDTO(
                        label: 'Скриншоты',
                        path: '/admin/screenshots',
                        icon: file_get_contents(__DIR__ . '/../../../app/assets/joystick.svg')
                    ),
                    new NavigationDTO(
                        label: 'Фотки',
                        path: '/admin/photos',
                        icon: file_get_contents(__DIR__ . '/../../../app/assets/photo.svg')
                    ),
                    new NavigationDTO(
                        label: 'Тэги фоток',
                        path: '/admin/photo-tags',
                        icon: file_get_contents(__DIR__ . '/../../../app/assets/photo-tag.svg')
                    )
                ]
            ),
            new NavigationDTO(
                label: 'Комментарии',
                path: '/admin/comments',
                icon: 'message',
                position: 10000
            ),
            new NavigationDTO(
                label: 'Редиректы',
                path: '/admin/redirects',
                icon: file_get_contents(__DIR__ . '/../../../app/assets/redirect.svg'),
                position: 11000
            ),
            new NavigationDTO(
                label: 'Фан-факты',
                path: '/admin/facts',
                icon: file_get_contents(__DIR__ . '/../../../app/assets/fun-fact.svg'),
                position: 12000
            ),
            new NavigationDTO(
                label: 'Пульс',
                path: '/admin/pulse',
                icon: file_get_contents(__DIR__ . '/../../../app/assets/pulse.svg'),
                position: 13000
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
