<?php

declare(strict_types=1);

namespace BC\Core\Trait;

use BC\Core\Auth\IAuth;
use Runway\Singleton\Container;

trait AuthTrait
{
    private function getAuth(): IAuth {
        return Container::getInstance()->getService(IAuth::class);
    }
}
