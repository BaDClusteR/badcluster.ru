<?php

declare(strict_types=1);

namespace BC\Core\Trait;

use Runway\Request\IRequest;
use Runway\Singleton\Container;

trait RequestTrait {
    protected function getRequest(): IRequest {
        return Container::getInstance()->getService(IRequest::class);
    }
}
