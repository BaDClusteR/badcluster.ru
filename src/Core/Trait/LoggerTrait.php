<?php

declare(strict_types=1);

namespace BC\Core\Trait;

use Runway\Logger\ILogger;
use Runway\Singleton\Container;

trait LoggerTrait
{
    protected function getLogger(): ILogger {
        return static::getLoggerStatic();
    }

    protected static function getLoggerStatic(): ILogger {
        return Container::getInstance()->getService(ILogger::class);
    }
}
