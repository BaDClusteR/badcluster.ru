<?php

declare(strict_types=1);

namespace BC\Core\SystemMetrics;

use BC\Core\SystemMetrics\DTO\SystemMetricsSampleDTO;

interface ISystemMetricsHistory {
    public function sample(): SystemMetricsSampleDTO;
}
