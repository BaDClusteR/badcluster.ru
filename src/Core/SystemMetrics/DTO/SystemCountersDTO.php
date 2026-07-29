<?php

declare(strict_types=1);

namespace BC\Core\SystemMetrics\DTO;

/**
 * Кумулятивные счётчики (с момента загрузки): CPU-тики, дисковые операции,
 * сетевые байты — плюс текущее состояние RAM. Загрузка считается как дельта
 * между двумя чтениями — это даёт точное среднее за интервал, а не
 * мгновенный снимок.
 */
readonly class SystemCountersDTO {
    public function __construct(
        public float $cpuBusyTicks,
        public float $cpuTotalTicks,
        public int $ramUsedBytes,
        public int $ramTotalBytes,
        public int $cpuCores,
        public float $diskReadOps,
        public float $diskWriteOps,
        public float $netRxBytes,
        public float $netTxBytes,
        public float $uptimeSeconds,
        public int $diskUsedBytes,
        public int $diskTotalBytes,
        public string $source
    ) {
    }
}
