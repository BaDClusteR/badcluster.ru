<?php

declare(strict_types=1);

namespace BC\Core\SystemMetrics\DTO;

readonly class SystemMetricsSampleDTO {
    /**
     * @param list<array{int, float|int, float|int, float|int, float|int, float|int, float|int}> $points
     *        [timeMs, cpuPercent, ramPercent, diskReadOps/s, diskWriteOps/s, netRxBytes/s, netTxBytes/s]
     */
    public function __construct(
        public array $points,
        public SystemCountersDTO $counters
    ) {
    }
}
