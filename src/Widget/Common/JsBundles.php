<?php

namespace BC\Widget\Common;

use BC\Core\Asset\IAssetBundler;
use BC\Widget\Attribute\WidgetList;
use BC\Widget\AWidget;
use Runway\Singleton\Container;

#[WidgetList('body', priority: 100000)]
class JsBundles extends AWidget
{

    protected function getTemplatePath(): string
    {
        return 'common/js-bundles.phtml';
    }

    protected function getJsBundles(): array {
        return ['theme-switcher'];
    }

    public function getBundler(): IAssetBundler {
        return Container::getInstance()->getService(IAssetBundler::class);
    }
}
