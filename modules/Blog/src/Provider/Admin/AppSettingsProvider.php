<?php

namespace BC\Modules\Blog\Provider\Admin;

use BC\DTO\AppSettings\AppSettingsDTO;
use BC\DTO\AppSettings\ModuleDTO;
use BC\DTO\AppSettings\NavigationDTO;
use BC\Provider\Admin\IAppSettingsProvider;
use BC\Provider\IPathsProvider;

readonly class AppSettingsProvider implements IAppSettingsProvider {
    public function __construct(
        private IAppSettingsProvider $inner,
        private IPathsProvider $pathsProvider
    ) {
    }

    public function getAppSettings(): AppSettingsDTO {
        $settings = $this->inner->getAppSettings();

        $settings->addNavItem(
            new NavigationDTO(
                label: 'Блог',
                icon: file_get_contents(__DIR__ . '/../../../app/assets/icon.svg'),
                position: 100,
                children: [
                    new NavigationDTO(
                        label: 'Посты',
                        path: '/admin/blog',
                        icon: file_get_contents(__DIR__ . '/../../../app/assets/icon.svg'),
                        position: 100
                    ),
                    new NavigationDTO(
                        label: 'Новый пост',
                        path: '/admin/blog/new',
                        icon: file_get_contents(__DIR__ . '/../../../app/assets/icon.svg'),
                        position: 200
                    )
                ]
            )
        );

        $settings->addModule(
            new ModuleDTO(
                id: 'blog',
                path: 'blog',
                remoteEntry: $this->pathsProvider->getStaticWebPath() . '/modules/blog/remoteEntry.js'
            )
        );

        return $settings;
    }
}
