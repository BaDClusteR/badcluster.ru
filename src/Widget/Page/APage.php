<?php

namespace BC\Widget\Page;

use BC\Core\Asset\DTO\AssetDTO;
use BC\Widget\AWidget;
use BC\Widget\IAssetProvider;

abstract class APage extends AWidget implements IAssetProvider
{
    abstract public function getHeader(): string;

    abstract public function getTitle(): string;

    /**
     * @return string[]
     */
    abstract public function getDescription(): array;

    abstract public function getMainWidget(): AWidget;

    protected function getTemplatePath(): string
    {
        return 'page.phtml';
    }

    public static function getAssets(): array
    {
        return [
            new AssetDTO('critical', 'js/singleton.js', -100),
            new AssetDTO('critical', 'js/event-dispatcher.js', -50),
            new AssetDTO('critical', 'js/theme.js'),

            new AssetDTO('scripts', 'js/theme-switcher.js'),
            new AssetDTO('scripts', 'js/header.js'),
            new AssetDTO('scripts', 'js/scripts.js'),

            new AssetDTO('core', 'css/reset.css', -100),
            new AssetDTO('core', 'css/font.css', -50),
            new AssetDTO('core', 'css/style.css', 0)
        ];
    }
}
