<?php

declare(strict_types=1);

namespace BC\Core\SystemMetrics;

use BC\Core\SystemMetrics\DTO\SystemCountersDTO;

class SystemMetricsProviderFactory implements ISystemMetricsProvider {
    private ?ISystemMetricsProvider $provider = null;

    public function isSupported(): bool {
        return true;
    }

    public function readCounters(): SystemCountersDTO {
        return $this->getProvider()->readCounters();
    }

    private function getProvider(): ISystemMetricsProvider {
        if ($this->provider === null) {
            $procProvider = new ProcSystemMetricsProvider();

            $this->provider = $procProvider->isSupported()
                ? $procProvider
                : new FakeSystemMetricsProvider();
        }

        return $this->provider;
    }
}
