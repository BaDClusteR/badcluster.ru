<?php

declare(strict_types=1);

namespace BC\Core\Trait;

use BC\Core\Converter\IConverter;
use Runway\Singleton\Container;

trait ConverterTrait
{
    protected function getConverter(): IConverter {
        return Container::getInstance()->getService(IConverter::class);
    }
}
