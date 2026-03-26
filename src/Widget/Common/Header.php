<?php

namespace BC\Widget\Common;

use BC\Core\Asset\DTO\AssetDTO;
use BC\Widget\Attribute\WidgetList;
use BC\Widget\AWidget;
use BC\Widget\IAssetProvider;

#[WidgetList('body', priority: 0)]
class Header extends AWidget implements IAssetProvider
{
    protected function getTemplatePath(): string
    {
        return 'common/header.phtml';
    }

    public static function getAssets(): array
    {
        return [
            new AssetDTO('core', 'css/header.css')
        ];
    }
}
