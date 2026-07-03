<?php

declare(strict_types=1);

namespace BC\Provider;

use BC\Core\Config\IWebsiteSettings;
use BC\Core\DTO\MenuItemDTO;

readonly class MenuItemsProvider implements IMenuItemsProvider {
    public function __construct(
        private IWebsiteSettings $websiteSettings
    ) {
    }

    /**
     * @param MenuItemDTO[] $menuItems
     *
     * @return MenuItemDTO[]
     */
    public function sortMenuItems(array $menuItems): array {
        usort(
            $menuItems,
            static fn (MenuItemDTO $a, MenuItemDTO $b): int => $a->priority <=> $b->priority
        );

        return $menuItems;
    }

    /**
     * @return MenuItemDTO[]
     */
    public function getMenuItems(): array {
        return [
            new MenuItemDTO(
                title: 'Об авторе',
                url: $this->websiteSettings->getWebRoot() . '/me',
                priority: 10000
            )
        ];
    }
}
