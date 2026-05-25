<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Provider;

use BC\Core\DTO\MenuItemDTO;
use BC\Core\Trait\WebsiteSettingsTrait;
use BC\Provider\IMenuItemsProvider;

class MenuItemsProvider implements IMenuItemsProvider {
    use WebsiteSettingsTrait;

    public function __construct(
        private readonly IMenuItemsProvider $inner
    ) {
    }

    public function getMenuItems(): array {
        $items = $this->inner->getMenuItems();

        $items[] = new MenuItemDTO(
            title: 'Блог',
            url: $this->getWebsiteSettings()->getWebRoot() . '/blog',
            priority: 0
        );

        return $items;
    }
}
