<?php

declare(strict_types=1);

namespace BC\Core\Trait;

use Runway\Controller\IController404;
use Runway\Singleton\Container;

trait Controller404Trait
{
    private function get404Controller(): IController404 {
        return Container::getInstance()->getService(IController404::class);
    }
}
