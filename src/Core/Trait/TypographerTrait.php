<?php

declare(strict_types=1);

namespace BC\Core\Trait;

use BC\Core\Typography\ITypographer;
use Runway\Singleton\Container;

trait TypographerTrait {
    protected function getTypographer(): ITypographer {
        return Container::getInstance()->getService(ITypographer::class);
    }
}
