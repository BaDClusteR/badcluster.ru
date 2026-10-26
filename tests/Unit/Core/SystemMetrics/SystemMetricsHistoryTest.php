<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Core\SystemMetrics;

use BC\Core\SystemMetrics\DTO\SystemCountersDTO;
use BC\Core\SystemMetrics\DTO\SystemMetricsSampleDTO;
use BC\Core\SystemMetrics\SystemMetricsHistory;
use BC\Tests\Fixture\SystemMetrics\CountersStub;
use BC\Tests\Support\TempDir;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * The ring buffer in a temporary file (never PROJECT_ROOT/log/system-metrics.json).
 */
#[CoversClass(SystemMetricsHistory::class)]
#[CoversClass(SystemMetricsSampleDTO::class)]
#[CoversClass(SystemCountersDTO::class)]
final class SystemMetricsHistoryTest extends TestCase {
    private string $dir;

    private string $file;

    protected function setUp(): void {
        $this->dir = TempDir::create('bc-metrics-');
        $this->file = "$this->dir/system-metrics.json";
    }

    protected function tearDown(): void {
        TempDir::remove($this->dir);
    }

    public function testFirstSampleHasNoPoints(): void {
        $counters = CountersStub::counters(busy: 10, total: 100, diskRead: 1, diskWrite: 2, netRx: 3, netTx: 4);
        $before = self::nowMs();

        $sample = new SystemMetricsHistory(new CountersStub($counters), $this->file)->sample();

        self::assertSame([], $sample->points);
        self::assertSame($counters, $sample->counters);

        $state = $this->readState();
        self::assertSame(2, $state['v']);
        self::assertSame([], $state['points']);
        self::assertGreaterThanOrEqual($before, $state['last']['time']);
        self::assertLessThanOrEqual(self::nowMs(), $state['last']['time']);
        unset($state['last']['time']);
        self::assertEquals(
            ['busy' => 10, 'total' => 100, 'diskR' => 1, 'diskW' => 2, 'netRx' => 3, 'netTx' => 4, 'source' => 'test'],
            $state['last']
        );
    }

    public function testPointIsTheDeltaOfTwoReadings(): void {
        $lastTime = self::nowMs() - 2000;
        $this->writeState([
            'v'      => 2,
            'last'   => ['time' => $lastTime, 'busy' => 100, 'total' => 1000, 'diskR' => 10, 'diskW' => 20, 'netRx' => 1000, 'netTx' => 500, 'source' => 'test'],
            'points' => [[1, 2.5, 3.5, 4.5, 5.5, 6, 7]],
        ]);
        $counters = CountersStub::counters(
            busy: 130,
            total: 1100,
            ramUsed: 3,
            ramTotal: 8,
            diskRead: 30,
            diskWrite: 61,
            netRx: 5000,
            netTx: 501,
        );

        $sample = new SystemMetricsHistory(new CountersStub($counters), $this->file)->sample();

        self::assertCount(2, $sample->points);
        self::assertSame([1, 2.5, 3.5, 4.5, 5.5, 6, 7], $sample->points[0]);

        $point = $sample->points[1];
        $dt = ($point[0] - $lastTime) / 1000;
        self::assertGreaterThanOrEqual(2.0, $dt);
        self::assertSame(
            [$point[0], 30.0, 37.5, round(20 / $dt, 1), round(41 / $dt, 1), round(4000 / $dt), round(1 / $dt)],
            $point
        );
        self::assertSame($point[0], $this->readState()['last']['time']);
        self::assertEquals($sample->points, $this->readState()['points']);
    }

    public function testTooFrequentSampleIsSkipped(): void {
        $state = [
            'v'      => 2,
            // A parallel tab has just taken a sample.
            'last'   => ['time' => self::nowMs() - 100, 'busy' => 0, 'total' => 0, 'diskR' => 0, 'diskW' => 0, 'netRx' => 0, 'netTx' => 0, 'source' => 'test'],
            'points' => [],
        ];
        $this->writeState($state);

        $sample = new SystemMetricsHistory(new CountersStub(CountersStub::counters(busy: 5, total: 10)), $this->file)->sample();

        self::assertSame([], $sample->points);
        self::assertEquals($state, $this->readState(), 'The previous reading stays the base of the next point.');
    }

    public function testSampleRightAfterTheIntervalIsTaken(): void {
        $this->writeState([
            'v'      => 2,
            'last'   => ['time' => self::nowMs() - SystemMetricsHistory::MIN_SAMPLE_INTERVAL_MS, 'busy' => 0, 'total' => 0, 'diskR' => 0, 'diskW' => 0, 'netRx' => 0, 'netTx' => 0, 'source' => 'test'],
            'points' => [],
        ]);

        $sample = new SystemMetricsHistory(new CountersStub(CountersStub::counters(busy: 5, total: 10)), $this->file)->sample();

        self::assertCount(1, $sample->points);
        self::assertSame(50.0, $sample->points[0][1]);
    }

    public function testOtherSourceStartsAgain(): void {
        $this->writeState([
            'v'      => 2,
            'last'   => ['time' => self::nowMs() - 100, 'busy' => 0, 'total' => 0, 'diskR' => 0, 'diskW' => 0, 'netRx' => 0, 'netTx' => 0, 'source' => 'fake'],
            'points' => [[1, 2.5, 3.5, 4.5, 5.5, 6, 7]],
        ]);

        $sample = new SystemMetricsHistory(new CountersStub(CountersStub::counters(busy: 5, total: 10, source: 'proc')), $this->file)->sample();

        self::assertSame([[1, 2.5, 3.5, 4.5, 5.5, 6, 7]], $sample->points);
        self::assertSame('proc', $this->readState()['last']['source']);
        self::assertEquals(5, $this->readState()['last']['busy']);
    }

    /**
     * @return iterable<string, array{SystemCountersDTO}>
     */
    public static function provideResetCounters(): iterable {
        yield 'no CPU ticks' => [CountersStub::counters(busy: 100, total: 1000, diskRead: 10, diskWrite: 20, netRx: 30, netTx: 40)];
        yield 'CPU total reset' => [CountersStub::counters(busy: 100, total: 10, diskRead: 10, diskWrite: 20, netRx: 30, netTx: 40)];
        yield 'CPU busy reset' => [CountersStub::counters(busy: 99, total: 2000, diskRead: 10, diskWrite: 20, netRx: 30, netTx: 40)];
        yield 'disk reads reset' => [CountersStub::counters(busy: 100, total: 2000, diskRead: 9, diskWrite: 20, netRx: 30, netTx: 40)];
        yield 'disk writes reset' => [CountersStub::counters(busy: 100, total: 2000, diskRead: 10, diskWrite: 19, netRx: 30, netTx: 40)];
        yield 'received bytes reset' => [CountersStub::counters(busy: 100, total: 2000, diskRead: 10, diskWrite: 20, netRx: 29, netTx: 40)];
        yield 'sent bytes reset' => [CountersStub::counters(busy: 100, total: 2000, diskRead: 10, diskWrite: 20, netRx: 30, netTx: 39)];
    }

    /**
     * A counter that went down: the server was rebooted.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('provideResetCounters')]
    public function testResetCountersGiveNoPoint(SystemCountersDTO $counters): void {
        $this->writeState([
            'v'      => 2,
            'last'   => ['time' => self::nowMs() - 2000, 'busy' => 100, 'total' => 1000, 'diskR' => 10, 'diskW' => 20, 'netRx' => 30, 'netTx' => 40, 'source' => 'test'],
            'points' => [],
        ]);

        $sample = new SystemMetricsHistory(new CountersStub($counters), $this->file)->sample();

        self::assertSame([], $sample->points);
        self::assertEquals($counters->cpuTotalTicks, $this->readState()['last']['total'], 'The new reading is the base.');
    }

    public function testUnchangedCountersExceptCpuGiveAPoint(): void {
        $this->writeState([
            'v'      => 2,
            'last'   => ['time' => self::nowMs() - 2000, 'busy' => 100, 'total' => 1000, 'diskR' => 10, 'diskW' => 20, 'netRx' => 30, 'netTx' => 40, 'source' => 'test'],
            'points' => [],
        ]);

        $sample = new SystemMetricsHistory(
            new CountersStub(CountersStub::counters(busy: 100, total: 1001, ramUsed: 5, ramTotal: 0, diskRead: 10, diskWrite: 20, netRx: 30, netTx: 40)),
            $this->file
        )->sample();

        self::assertCount(1, $sample->points);
        self::assertSame([0.0, 0.0, 0.0, 0.0, 0.0, 0.0], array_slice($sample->points[0], 1));
    }

    public function testPercentsAreLimited(): void {
        $this->writeState([
            'v'      => 2,
            'last'   => ['time' => self::nowMs() - 2000, 'busy' => 0, 'total' => 0, 'diskR' => 0, 'diskW' => 0, 'netRx' => 0, 'netTx' => 0, 'source' => 'test'],
            'points' => [],
        ]);

        $sample = new SystemMetricsHistory(
            new CountersStub(CountersStub::counters(busy: 300, total: 100, ramUsed: 20, ramTotal: 10)),
            $this->file
        )->sample();

        self::assertSame(100.0, $sample->points[0][1]);
        self::assertSame(100.0, $sample->points[0][2]);
    }

    public function testBufferIsLimited(): void {
        $points = [];
        for ($i = 0; $i < SystemMetricsHistory::MAX_POINTS; $i++) {
            $points[] = [$i, 1.0, 1.0, 1.0, 1.0, 1, 1];
        }
        $this->writeState([
            'v'      => 2,
            'last'   => ['time' => self::nowMs() - 2000, 'busy' => 0, 'total' => 0, 'diskR' => 0, 'diskW' => 0, 'netRx' => 0, 'netTx' => 0, 'source' => 'test'],
            'points' => $points,
        ]);

        $sample = new SystemMetricsHistory(new CountersStub(CountersStub::counters(busy: 1, total: 2)), $this->file)->sample();

        self::assertCount(300, $sample->points);
        self::assertSame(1, $sample->points[0][0], 'The oldest point is dropped.');
        self::assertSame(50.0, $sample->points[299][1]);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideInvalidStates(): iterable {
        yield 'not JSON' => ['garbage'];
        yield 'not an object' => ['"text"'];
        yield 'old version' => ['{"v":1,"last":null,"points":[[1,2,3]]}'];
        yield 'no version' => ['{"last":null,"points":[[1,2,3]]}'];
        yield 'points are not a list' => ['{"v":2,"last":null,"points":"x"}'];
        yield 'no points' => ['{"v":2,"last":null}'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideInvalidStates')]
    public function testInvalidStateIsReset(string $content): void {
        file_put_contents($this->file, $content);

        $sample = new SystemMetricsHistory(new CountersStub(CountersStub::counters(busy: 1, total: 2)), $this->file)->sample();

        self::assertSame([], $sample->points);
        $state = $this->readState();
        self::assertSame(2, $state['v']);
        self::assertSame([], $state['points']);
        self::assertSame('test', $state['last']['source']);
    }

    public function testLongerContentIsTruncated(): void {
        file_put_contents($this->file, str_repeat(' ', 10_000) . 'garbage');

        new SystemMetricsHistory(new CountersStub(CountersStub::counters()), $this->file)->sample();

        self::assertIsArray(json_decode((string)file_get_contents($this->file), true));
    }

    /**
     * @return array{v: int, last: array<string, mixed>, points: list<list<int|float>>}
     */
    private function readState(): array {
        $state = json_decode((string)file_get_contents($this->file), true);
        self::assertIsArray($state);

        /** @var array{v: int, last: array<string, mixed>, points: list<list<int|float>>} $state */
        return $state;
    }

    /**
     * @param array<string, mixed> $state
     */
    private function writeState(array $state): void {
        file_put_contents($this->file, (string)json_encode($state));
    }

    private static function nowMs(): int {
        return (int)round(microtime(true) * 1000);
    }
}
