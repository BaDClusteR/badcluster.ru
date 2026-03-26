<?php

namespace BC\Widget\Head;

use BC\Core\Asset\IAssetBundler;
use BC\Widget\Attribute\WidgetList;
use BC\Widget\AWidget;
use Runway\Singleton\Container;

#[WidgetList('head')]
class Bundles extends AWidget
{
    protected function getTemplatePath(): string
    {
        return 'head/bundles.phtml';
    }

    public function getJsBundles(): array {
        return ['critical'];
    }

    public function getCssBundles(): array {
        return ['core'];
    }

    public function getBundler(): IAssetBundler {
        return Container::getInstance()->getService(IAssetBundler::class);
    }
}