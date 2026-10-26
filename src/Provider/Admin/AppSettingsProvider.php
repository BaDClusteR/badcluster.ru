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
                icon: $this->getIcon('media.svg'),
                position: 9900,
                children: [
                    new NavigationDTO(
                        label: 'Медиатека',
                        path: '/admin/media',
                        icon: $this->getIcon('media.svg')
                    ),
                    new NavigationDTO(
                        label: 'Скриншоты',
                        path: '/admin/screenshots',
                        icon: $this->getIcon('joystick.svg')
                    ),
                    new NavigationDTO(
                        label: 'Фотки',
                        path: '/admin/photos',
                        icon: $this->getIcon('photo.svg')
                    ),
                    new NavigationDTO(
                        label: 'Тэги фоток',
                        path: '/admin/photo-tags',
                        icon: $this->getIcon('photo-tag.svg')
                    )
                ]
            ),
            new NavigationDTO(
                label: 'Виджеты',
                path: '',
                icon: $this->getIcon('widgets.svg'),
                position: 12000,
                children: [
                    new NavigationDTO(
                        label: 'Фан-факты',
                        path: '/admin/facts',
                        icon: $this->getIcon('fun-fact.svg')
                    ),
                    new NavigationDTO(
                        label: 'Новостные блоки',
                        path: '/admin/pulse',
                        icon: $this->getIcon('news.svg')
                    )
                ]
            ),
            // «Настройки» (/admin/settings — чёрный список комментариев) из меню убраны:
            // со спамом справляется ханипот, а страница и фильтр остались на будущее
            new NavigationDTO(
                label: 'Системные',
                path: '',
                icon: 'settings',
                position: 14000,
                children: [
                    new NavigationDTO(
                        label: 'Редиректы',
                        path: '/admin/redirects',
                        icon: $this->getIcon('redirect.svg')
                    ),
                    new NavigationDTO(
                        label: 'Журнал ошибок',
                        path: '/admin/logs',
                        icon: $this->getIcon('log.svg')
                    )
                ]
            ),
            new NavigationDTO(
                label: 'Страницы',
                path: '/admin/static-pages',
                icon: $this->getIcon('static-page.svg'),
                position: 15000
            ),
            new NavigationDTO(
                label: 'Комментарии',
                path: '/admin/comments',
                icon: 'message',
                position: 16000
            ),
        ];
    }

    /**
     * @return ModuleDTO[]
     */
    private function getModules(): array {
        return [];
    }

    /**
     * @return string|null The SVG of the icon of the admin app, null if it cannot be read.
     */
    private function getIcon(string $filename): ?string {
        $svg = file_get_contents(__DIR__ . "/../../../app/assets/$filename");

        return ($svg !== false)
            ? $svg
            : null;
    }
}
