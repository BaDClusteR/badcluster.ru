<?php

declare(strict_types=1);

namespace BC\Core\Trait;

use BC\Core\Helper\IBlockHelper;
use Runway\Singleton\Container;

trait BlockHelperTrait
{
    private function getBlockHelper(): IBlockHelper {
        return Container::getInstance()->getService(IBlockHelper::class);
    }
}
