<?php

declare(strict_types=1);

namespace BC\Modules\Books\Core\Trait;

use BC\Modules\Books\Provider\BookFormat\IBookFormatProvider;
use Runway\Singleton\Container;

trait BookFormatProviderTrait {
    protected function getBookFormatProvider(): IBookFormatProvider {
        return Container::getInstance()->getService(IBookFormatProvider::class);
    }
}
