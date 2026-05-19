<?php

declare(strict_types=1);

namespace BC\Core\Trait;

use BC\Core\Converter\IDateConverter;
use Runway\Singleton\Container;

trait DateConverterTrait {
    protected function getDateConverter(): IDateConverter {
        return Container::getInstance()->getService(IDateConverter::class);
    }
}
