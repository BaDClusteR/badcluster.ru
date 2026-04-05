<?php

declare(strict_types=1);

namespace BC\Core\Trait;

use BC\Core\Helper\IAttributesHelper;
use Runway\Singleton\Container;

trait AttributesHelperTrait
{
    protected function getAttributesHelper(): IAttributesHelper {
        return Container::getInstance()->getService(IAttributesHelper::class);
    }
}
