<?php

declare(strict_types=1);

namespace BC\Tests\Fixture\SystemMetrics;

use BC\Core\SystemMetrics\DTO\SystemCountersDTO;
use BC\Core\SystemMetrics\ISystemMetricsProvider;

/**
 * The counters of a test.
 */
final class CountersStub implements ISystemMetricsProvider {
    public int $reads = 0;

    public function __construct(
        public SystemCountersDTO $counters
    ) {}

    public static function counters(
        float  $busy = 0,
        float  $total = 0,
        int    $ramUsed = 0,
        int    $ramTotal = 0,
        float  $diskRead = 0,
        float  $diskWrite = 0,
        float  $netRx = 0,
        float  $netTx = 0,
        string $source = 'test'
    ): SystemCountersDTO {
        return new SystemCountersDTO(
            cpuBusyTicks: $busy,
            cpuTotalTicks: $total,
            ramUsedBytes: $ramUsed,
            ramTotalBytes: $ramTotal,
            cpuCores: 4,
            diskReadOps: $diskRead,
            diskWriteOps: $diskWrite,
            netRxBytes: $netRx,
            netTxBytes: $netTx,
            uptimeSeconds: 100.0,
            diskUsedBytes: 10,
            diskTotalBytes: 20,
            source: $source
        );
    }

    public function isSupported(): bool {
        return true;
    }

    public function readCounters(): SystemCountersDTO {
        $this->reads++;

        return $this->counters;
    }
}
