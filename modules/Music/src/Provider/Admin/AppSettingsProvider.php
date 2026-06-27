<?php

declare(strict_types=1);

namespace BC\Modules\Music\Provider\Admin;

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
                label: 'Музыка',
                icon: file_get_contents(__DIR__ . '/../../../app/assets/icon.svg'),
                position: 700,
                children: [
                    new NavigationDTO(
                        label: 'Альбомы',
                        path: '/admin/music',
                        icon: file_get_contents(__DIR__ . '/../../../app/assets/albums.svg'),
                        position: 100
                    ),
                    new NavigationDTO(
                        label: 'Добавить альбом',
                        path: '/admin/music/new',
                        icon: file_get_contents(__DIR__ . '/../../../app/assets/add-album.svg'),
                        position: 200
                    )
                ]
            )
        );

        $settings->addModule(
            new ModuleDTO(
                id: 'music',
                path: 'music',
                remoteEntry: $this->pathsProvider->getStaticWebPath() . '/modules/music/remoteEntry.js'
            )
        );

        return $settings;
    }
}
