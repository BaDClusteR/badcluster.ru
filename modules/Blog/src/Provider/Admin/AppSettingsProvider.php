<?php

declare(strict_types=1);

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
                        icon: file_get_contents(__DIR__ . '/../../../app/assets/posts.svg'),
                        position: 100
                    ),
                    new NavigationDTO(
                        label: 'Новый пост',
                        path: '/admin/blog/new',
                        icon: file_get_contents(__DIR__ . '/../../../app/assets/post.svg'),
                        position: 200
                    ),
                    new NavigationDTO(
                        label: 'Тэги',
                        path: '/admin/blog/tags',
                        icon: file_get_contents(__DIR__ . '/../../../app/assets/tags.svg'),
                        position: 300
                    ),
                    new NavigationDTO(
                        label: 'Заметки',
                        path: '/admin/blog/notes',
                        icon: file_get_contents(__DIR__ . '/../../../app/assets/notes.svg'),
                        position: 400
                    ),
                    new NavigationDTO(
                        label: 'Добавить заметку',
                        path: '/admin/blog/notes/new',
                        icon: file_get_contents(__DIR__ . '/../../../app/assets/note.svg'),
                        position: 500
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
