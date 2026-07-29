<?php

declare(strict_types=1);

namespace BC\Core\SystemMetrics;

use BC\Core\SystemMetrics\DTO\SystemCountersDTO;

interface ISystemMetricsProvider {
    public function isSupported(): bool;

    public function readCounters(): SystemCountersDTO;
}
