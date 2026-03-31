<?php

declare(strict_types=1);

namespace BC\Core\Trait;

use BC\Core\Asset\IAssetBuilder;
use Runway\Singleton\Container;

trait AssetBundlerTrait
{
    protected function getAssetBuilder(): IAssetBuilder {
        return Container::getInstance()->getService(IAssetBuilder::class);
    }
}
