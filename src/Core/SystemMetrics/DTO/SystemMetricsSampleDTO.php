<?php

declare(strict_types=1);

namespace BC\Core\SystemMetrics\DTO;

readonly class SystemMetricsSampleDTO {
    /**
     * @param list<array{int, float, float}> $points [timeMs, cpuPercent, ramPercent]
     */
    public function __construct(
        public array $points,
        public SystemCountersDTO $counters
    ) {
    }
}
