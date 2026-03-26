<?php

namespace BC\Widget\Common\Header\Menu;

use BC\Core\DTO\MenuItemDTO;
use BC\Core\Provider\IMenuItemsProvider;
use BC\Widget\Attribute\WidgetList;
use BC\Widget\AWidget;
use Runway\Singleton\Container;

#[WidgetList('header.menu')]
class Menu extends AWidget
{
    protected function getTemplatePath(): string
    {
        return 'common/header/menu/menu.phtml';
    }

    /**
     * @return MenuItemDTO[]
     */
    public function getMenuItems(): array {
        return $this->getMenuItemsProvider()->getMenuItems();
    }

    protected function getMenuItemsProvider(): IMenuItemsProvider {
        return Container::getInstance()->getService(IMenuItemsProvider::class);
    }
}
