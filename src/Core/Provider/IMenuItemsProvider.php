<?php

namespace BC\Core\Provider;

use BC\Core\DTO\MenuItemDTO;

interface IMenuItemsProvider
{
    /**
     * @return MenuItemDTO[]
     */
    public function getMenuItems(): array;
}
