<?php

namespace BC\Widget\Common\Header;

use BC\Core\Asset\DTO\AssetDTO;
use BC\Widget\Attribute\WidgetList;
use BC\Widget\AWidget;
use BC\Widget\IAssetProvider;

#[WidgetList('header', priority: 1000)]
class Menu extends AWidget implements IAssetProvider
{
    protected function getTemplatePath(): string
    {
        return 'common/header/menu.phtml';
    }

    public static function getAssets(): array
    {
        return [
            new AssetDTO('theme-switcher', 'js/theme-switcher.js')
        ];
    }
}
