<?php

namespace BC\Modules\Games\Provider;

use BC\Core\DTO\MenuItemDTO;
use BC\Core\Trait\WebsiteSettingsTrait;
use BC\Provider\IMenuItemsProvider;

readonly class MenuItemsProvider implements IMenuItemsProvider {
    use WebsiteSettingsTrait;

    public function __construct(
        private IMenuItemsProvider $inner
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getMenuItems(): array {
        $items = $this->inner->getMenuItems();

        $items[] = new MenuItemDTO(
            title: 'Игры',
            url: $this->getWebsiteSettings()->getWebRoot() . '/games',
            priority: 10
        );

        return $items;
    }
}
