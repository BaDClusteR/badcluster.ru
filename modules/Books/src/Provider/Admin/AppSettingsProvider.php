<?php

declare(strict_types=1);

namespace BC\Modules\Books\Provider\Admin;

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
                label: 'Библиотека',
                icon: file_get_contents(__DIR__ . '/../../../app/assets/books.svg'),
                position: 200,
                children: [
                    new NavigationDTO(
                        label: 'Список книг',
                        path: '/admin/books',
                        icon: file_get_contents(__DIR__ . '/../../../app/assets/books.svg'),
                        position: 100
                    ),
                    new NavigationDTO(
                        label: 'Добавить произведение',
                        path: '/admin/books/new',
                        icon: file_get_contents(__DIR__ . '/../../../app/assets/add-book.svg'),
                        position: 100
                    ),
                ]
            )
        );

        $settings->addModule(
            new ModuleDTO(
                id: 'books',
                path: 'books',
                remoteEntry: $this->pathsProvider->getStaticWebPath() . '/modules/books/remoteEntry.js'
            )
        );

        return $settings;
    }
}
