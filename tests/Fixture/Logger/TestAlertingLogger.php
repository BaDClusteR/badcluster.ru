<?php

declare(strict_types=1);

namespace BC\Tests\Fixture\Logger;

use BC\Core\Log\ILogRecorder;
use BC\Logger\AlertingLogger;
use Runway\Logger\ILogger;

/**
 * AlertingLogger with the journal of the test instead of the one of the container.
 */
final class TestAlertingLogger extends AlertingLogger {
    public function __construct(
        ILogger                       $inner,
        private readonly ILogRecorder $recorder
    ) {
        parent::__construct($inner);
    }

    protected function getLogRecorder(): ILogRecorder {
        return $this->recorder;
    }
}
