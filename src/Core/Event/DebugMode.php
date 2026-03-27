<?php

namespace BC\Core\Event;

use BC\Core\Asset\IAssetBuilder;
use Runway\Singleton\Container;
use Runway\Singleton\IKernel;

class DebugMode
{
    public function __construct(
        protected IKernel $kernel,
    ) {
    }

    public function onInit(): void {
        if ($this->kernel->isDebugMode()) {
            $bundler = Container::getInstance()->getService(IAssetBuilder::class);
            $bundler->buildAssets();
        }
    }
}
