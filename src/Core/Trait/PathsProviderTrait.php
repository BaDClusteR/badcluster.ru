<?php

declare(strict_types=1);

namespace BC\Core\Trait;

use BC\Provider\IPathsProvider;
use Runway\Singleton\Container;

trait PathsProviderTrait {
    protected function getPathsProvider(): IPathsProvider {
        return static::getPathsProviderStatic();
    }

    protected static function getPathsProviderStatic(): IPathsProvider {
        return Container::getInstance()->getService(IPathsProvider::class);
    }
}
