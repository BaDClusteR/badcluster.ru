<?php

namespace BC\Widget\Page;

use BC\Core\Asset\DTO\AssetDTO;
use BC\Widget\AWidget;
use BC\Widget\DTO\BackLinkDTO;
use BC\Widget\IAssetProvider;

abstract class APage extends AWidget implements IAssetProvider
{
    abstract public function getHeader(): string;

    public function getTitle(): string {
        return "BaD ClusteR";
    }

    /**
     * @return string[]
     */
    abstract public function getDescription(): array;

    abstract public function getMainWidget(): AWidget;

    protected function getTemplatePath(): string
    {
        return 'page.phtml';
    }

    public function getBackLink(): ?BackLinkDTO {
        return null;
    }

    public static function getAssets(): array
    {
        return [
            new AssetDTO('critical', 'js/critical/singleton.js', -100),
            new AssetDTO('critical', 'js/critical/event-dispatcher.js', -50),
            new AssetDTO('critical', 'js/critical/theme.js'),

            new AssetDTO('scripts', 'js/common/theme-switcher.js'),
            new AssetDTO('scripts', 'js/common/header.js'),
            new AssetDTO('scripts', 'js/common/scripts.js'),
            new AssetDTO('scripts', 'js/common/tabs.js'),

            new AssetDTO('core', 'css/core/reset.css', -100),
            new AssetDTO('core', 'css/core/font.css', -50),
            new AssetDTO('core', 'css/core/style.css', 0),

            new AssetDTO('footer', 'css/footer.css')
        ];
    }

    public function getCssBundles(): array {
        return ['core'];
    }

    public function getJsBundles(): array {
        return ['critical'];
    }
}
