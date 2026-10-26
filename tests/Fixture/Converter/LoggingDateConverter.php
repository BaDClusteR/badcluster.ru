<?php

declare(strict_types=1);

namespace BC\Tests\Fixture\Converter;

use BC\Core\Converter\DateConverter;
use BC\Tests\Support\LoggerSpy;
use Runway\Logger\ILogger;

/**
 * DateConverter with the logger of the test.
 */
final readonly class LoggingDateConverter extends DateConverter {
    public function __construct(
        string           $timezone,
        public LoggerSpy $logger
    ) {
        parent::__construct($timezone);
    }

    protected function getLogger(): ILogger {
        return $this->logger;
    }
}
