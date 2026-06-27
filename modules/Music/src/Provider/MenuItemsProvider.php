<?php

namespace BC\Modules\Music\Provider;

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
            title: 'Музыка',
            url: $this->getWebsiteSettings()->getWebRoot() . '/music',
            priority: 400
        );

        return $items;
    }
}
