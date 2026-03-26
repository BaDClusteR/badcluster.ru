<?php

namespace BC\Core\Provider;

use BC\Core\DTO\MenuItemDTO;
use BC\Core\Trait\WebsiteSettingsTrait;

class MenuItemsProvider implements IMenuItemsProvider
{
    use WebsiteSettingsTrait;

    /**
     * @return MenuItemDTO[]
     */
    public function getMenuItems(): array
    {
        return $this->sortMeuItems(
            $this->collectMenuItems()
        );
    }

    /**
     * @param MenuItemDTO[] $items
     *
     * @return MenuItemDTO[]
     */
    protected function sortMeuItems(array $items): array {
        usort(
            $items,
            static fn(MenuItemDTO $a, MenuItemDTO $b): int => $a->priority <=> $b->priority
        );

        return $items;
    }

    /**
     * @return MenuItemDTO[]
     */
    protected function collectMenuItems(): array {
        return [
            new MenuItemDTO(
                title: 'Блог',
                url: $this->getWebsiteSettings()->getWebRoot() . '/blog'
            )
        ];
    }
}
