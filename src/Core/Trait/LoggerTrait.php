<?php

declare(strict_types=1);

namespace BC\Core\Trait;

use Runway\Logger\ILogger;
use Runway\Singleton\Container;

trait LoggerTrait
{
    public function getLogger(): ILogger {
        return Container::getInstance()->getService(ILogger::class);
    }
}