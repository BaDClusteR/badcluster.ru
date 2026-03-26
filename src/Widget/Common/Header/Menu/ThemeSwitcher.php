<?php

namespace BC\Widget\Common\Header\Menu;

use BC\Core\Asset\DTO\AssetDTO;
use BC\Widget\Attribute\WidgetList;
use BC\Widget\AWidget;
use BC\Widget\IAssetProvider;

#[WidgetList('header.menu', priority: 200)]
class ThemeSwitcher extends AWidget implements IAssetProvider
{
    protected function getTemplatePath(): string
    {
        return 'common/header/menu/theme-switcher.phtml';
    }

    public static function getAssets(): array
    {
        return [
            new AssetDTO('theme-switcher', 'js/theme-switcher.js')
        ];
    }
}
