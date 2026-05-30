<?php

declare(strict_types=1);

namespace BC\Modules\Games\Provider\Admin;

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
                label: 'Игры',
                icon: file_get_contents(__DIR__ . '/../../../app/assets/icon.svg'),
                position: 200,
                children: [
                    new NavigationDTO(
                        label: 'Список игр',
                        path: '/admin/games',
                        icon: file_get_contents(__DIR__ . '/../../../app/assets/games.svg'),
                        position: 100
                    ),
                    new NavigationDTO(
                        label: 'Добавить игру',
                        path: '/admin/games/new',
                        icon: file_get_contents(__DIR__ . '/../../../app/assets/games.svg'),
                        position: 200
                    ),
                    new NavigationDTO(
                        label: 'Материалы',
                        path: '/admin/games/materials',
                        icon: file_get_contents(__DIR__ . '/../../../app/assets/materials.svg'),
                        position: 300
                    ),
                    new NavigationDTO(
                        label: 'Новый материал',
                        path: '/admin/games/materials/new',
                        icon: file_get_contents(__DIR__ . '/../../../app/assets/materials.svg'),
                        position: 400
                    ),
                ]
            )
        );

        $settings->addModule(
            new ModuleDTO(
                id: 'games',
                path: 'games',
                remoteEntry: $this->pathsProvider->getStaticWebPath() . '/modules/games/remoteEntry.js'
            )
        );

        return $settings;
    }
}
