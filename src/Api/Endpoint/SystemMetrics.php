<?php

declare(strict_types=1);

namespace BC\Api\Endpoint;

use ApiPlatform\Attribute as API;
use ApiPlatform\Attribute\Docs;
use BC\Api\DTO\SystemMetrics\SystemMetricsDTO;
use BC\Api\DTO\SystemMetrics\SystemMetricsPointDTO;
use BC\Core\SystemMetrics\ISystemMetricsHistory;

#[Docs\Group('System metrics')]
class SystemMetrics extends AEndpoint {
    public function __construct(
        private readonly ISystemMetricsHistory $history
    ) {
    }

    #[API\Endpoint(path: 'system_metrics', method: 'GET')]
    public function get(): SystemMetricsDTO {
        $sample = $this->handleWithException(
            fn () => $this->history->sample()
        );

        return new SystemMetricsDTO(
            points: array_map(
                static fn (array $point): SystemMetricsPointDTO => new SystemMetricsPointDTO(
                    time: (int) $point[0],
                    cpu: (float) $point[1],
                    ram: (float) $point[2],
                    ioRead: (float) $point[3],
                    ioWrite: (float) $point[4],
                    netIn: (float) $point[5],
                    netOut: (float) $point[6]
                ),
                $sample->points
            ),
            cpuCores: $sample->counters->cpuCores,
            ramTotalBytes: $sample->counters->ramTotalBytes,
            source: $sample->counters->source
        );
    }
}
