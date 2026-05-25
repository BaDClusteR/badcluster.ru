<?php

declare(strict_types=1);

namespace BC\Provider;

use BC\Core\DTO\MenuItemDTO;

interface IMenuItemsProvider {
    /**
     * @return MenuItemDTO[]
     */
    public function getMenuItems(): array;
}
