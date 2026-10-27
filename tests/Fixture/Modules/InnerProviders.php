<?php

declare(strict_types=1);

namespace BC\Tests\Fixture\Modules;

use BC\Core\Auth\IAuth;
use BC\Core\DTO\MenuItemDTO;
use BC\DTO\AppSettings\AppSettingsDTO;
use BC\DTO\PageDTO;
use BC\Provider\Admin\IAppSettingsProvider;
use BC\Provider\Admin\IPageProvider;
use BC\Provider\ICommentsProvider;
use BC\Provider\IMenuItemsProvider;
use BC\Provider\IPreloadFontProvider;
use BC\Widget\Page\APage;

/**
 * The decorated (inner) providers of the module providers and the authorization of the tests.
 */
final class InnerProviders implements IMenuItemsProvider, ICommentsProvider, IPageProvider, IAppSettingsProvider, IPreloadFontProvider, IAuth {
    /** @var list<array{string, int}> The pages asked of the inner providers. */
    public array $calls = [];

    /**
     * @param list<MenuItemDTO> $menuItems
     */
    public function __construct(
        public bool $isAuthenticated = false,
        public array $menuItems = [],
        public bool $pageExists = true
    ) {}

    public function getMenuItems(): array {
        return $this->menuItems;
    }

    public function sortMenuItems(array $menuItems): array {
        return array_reverse($menuItems);
    }

    public function getSuccessMessages(): array {
        return ['inner message'];
    }

    public function isPageExist(string $pageType, int $pageId): bool {
        $this->calls[] = [$pageType, $pageId];

        return $this->pageExists;
    }

    public function getPage(string $pageType, int $pageId): PageDTO {
        $this->calls[] = [$pageType, $pageId];

        return new PageDTO('Inner', '/inner', 'https://inner');
    }

    public function getAppSettings(): AppSettingsDTO {
        return new AppSettingsDTO([], [], 'http://bc.test', 'http://bc.test/static', []);
    }

    public function getPreloadFontPaths(APage $page): array {
        return ['inner.woff2'];
    }

    public function isAuthenticated(): bool {
        return $this->isAuthenticated;
    }
}
