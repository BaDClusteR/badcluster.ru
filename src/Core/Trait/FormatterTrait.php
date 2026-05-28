<?php

declare(strict_types=1);

namespace BC\Core\Trait;

use BC\Core\Formatter\IFormatter;
use Runway\Singleton\Container;

trait FormatterTrait {
    protected function getFormatter(): IFormatter {
        return Container::getInstance()->getService(IFormatter::class);
    }
}
