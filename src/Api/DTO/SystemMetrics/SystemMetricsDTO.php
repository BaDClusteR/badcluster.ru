<?php

declare(strict_types=1);

namespace BC\Api\DTO\SystemMetrics;

readonly class SystemMetricsDTO {
    /**
     * @param SystemMetricsPointDTO[] $points
     */
    public function __construct(
        public array $points,
        public int $cpuCores,
        public int $ramTotalBytes,
        public int $uptimeSeconds,
        public int $diskUsedBytes,
        public int $diskTotalBytes,
        public string $source
    ) {
    }
}
