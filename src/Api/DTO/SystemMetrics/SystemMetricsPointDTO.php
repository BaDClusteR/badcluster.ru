<?php

declare(strict_types=1);

namespace BC\Api\DTO\SystemMetrics;

readonly class SystemMetricsPointDTO {
    public function __construct(
        public int $time,
        public float $cpu,
        public float $ram,
        public float $ioRead,
        public float $ioWrite,
        public float $netIn,
        public float $netOut
    ) {
    }
}
