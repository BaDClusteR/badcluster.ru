<?php

namespace BC\Widget\Page\Home;

use BC\Core\Asset\DTO\AssetDTO;
use BC\Core\Trait\AssetBundlerTrait;
use BC\DTO\PulseItemDTO;
use BC\Provider\IPulseItemsProvider;
use BC\Widget\AWidget;
use BC\Widget\IAssetProvider;
use Runway\Singleton\Container;

class Pulse extends AWidget implements IAssetProvider
{
    use AssetBundlerTrait;

    protected function getTemplatePath(): string
    {
        return 'home/pulse.phtml';
    }

    public static function getAssets(): array
    {
        return [
            new AssetDTO('grid', 'css/grid.css'),
        ];
    }

    /**
     * @return PulseItemDTO[]
     */
    protected function getItems(): array {
        return $this->getPulseItemsProvider()->getPulseItems();
    }

    private function getPulseItemsProvider(): IPulseItemsProvider {
        return Container::getInstance()->getService(IPulseItemsProvider::class);
    }
}
