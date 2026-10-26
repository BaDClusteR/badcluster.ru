<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Core\SystemMetrics;

use BC\Core\SystemMetrics\ProcSystemMetricsProvider;
use BC\Tests\Support\TempDir;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * The counters of procfs, read from the files of a temporary folder.
 */
#[CoversClass(ProcSystemMetricsProvider::class)]
final class ProcSystemMetricsProviderTest extends TestCase {
    private const string STAT = <<<'TXT'
        cpu  100 20 30 400 50 6 7 8 90 10
        cpu0 50 10 15 200 25 3 3 4 0 0
        cpu1 50 10 15 200 25 3 4 4 0 0
        cpu2 0 0 0 0 0 0 0 0 0 0
        intr 12345 0 0
        ctxt 999
        cpufreq 1 2 3
        TXT;

    private const string MEMINFO = <<<'TXT'
        MemTotal:        8000000 kB
        MemFree:         1000000 kB
        MemAvailable:    6000000 kB
        Buffers:          100000 kB
        SwapTotal:       2000000 kB
        TXT;

    private const string DISKSTATS = <<<'TXT'
           8       0 sda 100 0 0 0 200 0 0 0 0 0 0
           8       1 sda1 50 0 0 0 60 0 0 0 0 0 0
         259       0 nvme0n1 10 0 0 0 20 0 0 0 0 0 0
         259       1 nvme0n1p1 5 0 0 0 5 0 0 0 0 0 0
         253       0 dm-0 1000 0 0 0 1000 0 0 0 0 0 0
         252       0 vda 1 0 0 0 2 0 0 0 0 0 0
         202       0 xvdb 3 0 0 0 4 0 0 0 0 0 0
         179       0 mmcblk0 7 0 0 0 8 0 0 0 0 0 0
         179       1 mmcblk0p1 70 0 0 0 80 0 0 0 0 0 0
           7       0 loop0 500 0 0 0 500 0 0 0 0 0 0
        TXT;

    private const string NET_DEV = <<<'TXT'
        Inter-|   Receive                                                |  Transmit
         face |bytes    packets errs drop fifo frame compressed multicast|bytes    packets errs drop fifo colls carrier compressed
            lo: 9000 10 0 0 0 0 0 0 9000 10 0 0 0 0 0 0
          eth0: 1000 10 0 0 0 0 0 0 2000 20 0 0 0 0 0 0
        wlan0:300 3 0 0 0 0 0 0 400 4 0 0 0 0 0 0
        docker0: 5000 1 0 0 0 0 0 0 5000 1 0 0 0 0 0 0
        veth12ab: 5000 1 0 0 0 0 0 0 5000 1 0 0 0 0 0 0
        br-1234: 5000 1 0 0 0 0 0 0 5000 1 0 0 0 0 0 0
        virbr0: 5000 1 0 0 0 0 0 0 5000 1 0 0 0 0 0 0
          tun0: 5000 1 0 0 0 0 0 0 5000 1 0 0 0 0 0 0
          tap0: 5000 1 0 0 0 0 0 0 5000 1 0 0 0 0 0 0
          wg0: 5000 1 0 0 0 0 0 0 5000 1 0 0 0 0 0 0
          lobby: 10 0 0 0 0 0 0 0 20 0 0 0 0 0 0 0
        TXT;

    private string $proc;

    protected function setUp(): void {
        $this->proc = TempDir::create('bc-proc-');
        mkdir("$this->proc/net");
        file_put_contents("$this->proc/stat", self::STAT . "\n");
        file_put_contents("$this->proc/meminfo", self::MEMINFO . "\n");
        file_put_contents("$this->proc/diskstats", self::DISKSTATS . "\n");
        file_put_contents("$this->proc/net/dev", self::NET_DEV . "\n");
        file_put_contents("$this->proc/uptime", "12345.67 54321.00\n");
    }

    protected function tearDown(): void {
        TempDir::remove($this->proc);
    }

    public function testCounters(): void {
        $counters = new ProcSystemMetricsProvider($this->proc)->readCounters();

        // user nice system idle iowait irq softirq steal: the guest fields are not summed.
        self::assertSame(621.0, $counters->cpuTotalTicks);
        self::assertSame(621.0 - 400 - 50, $counters->cpuBusyTicks);
        self::assertSame(3, $counters->cpuCores);
        self::assertSame(8000000 * 1024, $counters->ramTotalBytes);
        self::assertSame((8000000 - 6000000) * 1024, $counters->ramUsedBytes);
        // sda, nvme0n1, vda, xvdb, mmcblk0: not the partitions, the device mapper or the loop devices.
        self::assertSame(100.0 + 10 + 1 + 3 + 7, $counters->diskReadOps);
        self::assertSame(200.0 + 20 + 2 + 4 + 8, $counters->diskWriteOps);
        // eth0, wlan0, lobby: not the virtual interfaces.
        self::assertSame(1000.0 + 300 + 10, $counters->netRxBytes);
        self::assertSame(2000.0 + 400 + 20, $counters->netTxBytes);
        self::assertSame(12345.67, $counters->uptimeSeconds);
        self::assertSame(ProcSystemMetricsProvider::SOURCE, $counters->source);
        self::assertSame('proc', $counters->source);
        self::assertGreaterThan(0, $counters->diskTotalBytes);
        self::assertGreaterThanOrEqual(0, $counters->diskUsedBytes);
        self::assertLessThanOrEqual($counters->diskTotalBytes, $counters->diskUsedBytes);
    }

    public function testShortCpuLine(): void {
        file_put_contents("$this->proc/stat", "cpu 10 0 5\n");
        file_put_contents("$this->proc/meminfo", "MemTotal: 100 kB\n");

        $counters = new ProcSystemMetricsProvider($this->proc)->readCounters();

        self::assertSame(15.0, $counters->cpuTotalTicks);
        self::assertSame(15.0, $counters->cpuBusyTicks);
        self::assertSame(1, $counters->cpuCores, 'At least one core.');
        self::assertSame(100 * 1024, $counters->ramUsedBytes, 'No MemAvailable: all is used.');
        self::assertSame(100 * 1024, $counters->ramTotalBytes);
    }

    public function testMissingFiles(): void {
        $empty = TempDir::create('bc-proc-empty-');

        try {
            $provider = new ProcSystemMetricsProvider($empty);
            self::assertFalse($provider->isSupported());

            $counters = @$provider->readCounters();

            self::assertSame(0.0, $counters->cpuTotalTicks);
            self::assertSame(0.0, $counters->cpuBusyTicks);
            self::assertSame(1, $counters->cpuCores);
            self::assertSame(0, $counters->ramTotalBytes);
            self::assertSame(0, $counters->ramUsedBytes);
            self::assertSame(0.0, $counters->diskReadOps);
            self::assertSame(0.0, $counters->netRxBytes);
            self::assertSame(0.0, $counters->uptimeSeconds);
        } finally {
            TempDir::remove($empty);
        }
    }

    public function testSupport(): void {
        self::assertTrue(new ProcSystemMetricsProvider($this->proc)->isSupported());

        unlink("$this->proc/meminfo");
        self::assertFalse(new ProcSystemMetricsProvider($this->proc)->isSupported());

        file_put_contents("$this->proc/meminfo", '');
        unlink("$this->proc/stat");
        self::assertFalse(new ProcSystemMetricsProvider($this->proc)->isSupported());
    }

    public function testDefaultRootIsProc(): void {
        self::assertSame(
            is_readable('/proc/stat') && is_readable('/proc/meminfo'),
            new ProcSystemMetricsProvider()->isSupported()
        );
    }
}
