<?php

namespace BC\Provider;

use BC\Core\DTO\MenuItemDTO;

class MenuItemsProvider implements IMenuItemsProvider
{
    /**
     * @return MenuItemDTO[]
     */
    public function getMenuItems(): array
    {
        return $this->sortMenuItems(
            $this->collectMenuItems()
        );
    }

    /**
     * @param MenuItemDTO[] $items
     *
     * @return MenuItemDTO[]
     */
    protected function sortMenuItems(array $items): array {
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
        return [];
    }
}
