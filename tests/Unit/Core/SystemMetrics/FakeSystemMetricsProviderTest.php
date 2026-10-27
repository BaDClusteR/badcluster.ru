<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Core\SystemMetrics;

use BC\Core\SystemMetrics\FakeSystemMetricsProvider;
use BC\Core\SystemMetrics\ProcSystemMetricsProvider;
use BC\Core\SystemMetrics\SystemMetricsProviderFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FakeSystemMetricsProvider::class)]
#[CoversClass(SystemMetricsProviderFactory::class)]
final class FakeSystemMetricsProviderTest extends TestCase {
    public function testCountersAreRealistic(): void {
        $provider = new FakeSystemMetricsProvider();
        self::assertTrue($provider->isSupported());

        $before = microtime(true);
        $first = $provider->readCounters();
        usleep(20_000);
        $second = $provider->readCounters();
        $after = microtime(true);

        self::assertSame('fake', $first->source);
        self::assertSame(FakeSystemMetricsProvider::SOURCE, $first->source);
        self::assertSame(8, $first->cpuCores);
        self::assertSame(8 * 1024 ** 3, $first->ramTotalBytes);
        // 0.45 ± 0.12 of the RAM.
        self::assertGreaterThanOrEqual((int)(0.33 * 8 * 1024 ** 3), $first->ramUsedBytes);
        self::assertLessThanOrEqual((int)(0.57 * 8 * 1024 ** 3), $first->ramUsedBytes);
        // The total ticks are 100 a second since the epoch.
        self::assertGreaterThanOrEqual($before * 100, $first->cpuTotalTicks);
        self::assertLessThanOrEqual($after * 100, $second->cpuTotalTicks);

        $dt = ($second->cpuTotalTicks - $first->cpuTotalTicks) / 100;
        self::assertGreaterThan(0, $dt);

        // The load is 0.25 ± 0.24 of the CPU: the busy ticks grow slower than the total ones.
        $cpuShare = ($second->cpuBusyTicks - $first->cpuBusyTicks) / ($second->cpuTotalTicks - $first->cpuTotalTicks);
        self::assertGreaterThan(0.0, $cpuShare);
        self::assertLessThan(0.5, $cpuShare);

        foreach (
            [
                [$first->diskReadOps, $second->diskReadOps, 2, 58],
                [$first->diskWriteOps, $second->diskWriteOps, 10, 80],
                [$first->netRxBytes, $second->netRxBytes, 50_000, 750_000],
                [$first->netTxBytes, $second->netTxBytes, 20_000, 220_000],
            ] as [$from, $to, $min, $max]
        ) {
            $rate = ($to - $from) / $dt;
            self::assertGreaterThanOrEqual($min * 0.9, $rate);
            self::assertLessThanOrEqual($max * 1.1, $rate);
        }

        self::assertGreaterThanOrEqual(12 * 86_400, $first->uptimeSeconds);
        self::assertLessThan(13 * 86_400, $first->uptimeSeconds);
        self::assertGreaterThan(0, $first->diskTotalBytes);
        self::assertLessThanOrEqual($first->diskTotalBytes, $first->diskUsedBytes);
    }

    public function testFactoryChoosesTheProvider(): void {
        $factory = new SystemMetricsProviderFactory();

        self::assertTrue($factory->isSupported());
        self::assertSame(
            new ProcSystemMetricsProvider()->isSupported() ? ProcSystemMetricsProvider::SOURCE : FakeSystemMetricsProvider::SOURCE,
            $factory->readCounters()->source
        );
        self::assertSame($factory->readCounters()->source, $factory->readCounters()->source);
    }
}
